#!/usr/bin/env bash
# This is a copy from https://github.com/tenforce/docker-virtuoso/blob/master/virtuoso.sh.
# This file is meant to only extend the original virtuoso.sh file by adding the possibility to
# restore the database from online backups that exist in the /data/backups directory.
# An upstream issue has been opened to find a way to do this automatically or integrate the current solution
# with a PR. Issue is https://github.com/tenforce/docker-virtuoso/issues/21 and there was already a rejected
# related PR about it in https://github.com/tenforce/docker-virtuoso/pull/19.
# @todo: Fix this when an upstream PR is merged in.
SETTINGS_DIR=/settings
mkdir -p $SETTINGS_DIR

cd /data

mkdir -p dumps

if [ ! -f ./virtuoso.ini ];
then
  mv /virtuoso.ini . 2>/dev/null
fi

chmod +x /clean-logs.sh
mv /clean-logs.sh . 2>/dev/null

original_port=`crudini --get virtuoso.ini HTTPServer ServerPort`
# NOTE: prevents virtuoso to expose on port 8890 before we actually run
#		the server
crudini --set virtuoso.ini HTTPServer ServerPort 27015

if [ ! -f "$SETTINGS_DIR/.config_set" ];
then
  echo "Converting environment variables to ini file"
  printenv | grep -P "^VIRT_" | while read setting
  do
    section=`echo "$setting" | grep -o -P "^VIRT_[^_]+" | sed 's/^.\{5\}//g'`
    key=`echo "$setting" | sed -E 's/^VIRT_[^_]+_(.*)=.*$/\1/g'`
    value=`echo "$setting" | grep -o -P "=.*$" | sed 's/^=//g'`
    echo "Registering $section[$key] to be $value"
    crudini --set virtuoso.ini $section $key "$value"
  done
  echo "`date +%Y-%m%-dT%H:%M:%S%:z`" >  $SETTINGS_DIR/.config_set
  echo "Finished converting environment variables to ini file"
fi

### Custom code
if [ ! -f ".backup_restored" -a -d "backupToRestore" ] ;
then
    echo "Start restoring a backup"
    cd backupToRestore
    virtuoso-t +restore-backup $BACKUP_PREFIX +configfile /data/virtuoso.ini
    echo "`date +%Y-%m-%dT%H:%M:%S%:z`" > .backup_restored
    echo "Data restore procedure completed."
    cd /data
fi
### End custom code.

if [ ! -f ".dba_pwd_set" ];
then
  touch /sql-query.sql
  if [ "$DBA_PASSWORD" ]; then echo "user_set_password('dba', '$DBA_PASSWORD');" >> /sql-query.sql ; fi
  if [ "$SPARQL_UPDATE" = "true" ]; then echo "GRANT SPARQL_UPDATE to \"SPARQL\";" >> /sql-query.sql ; fi
  virtuoso-t +wait && isql-v -U dba -P dba < /dump_nquads_procedure.sql && isql-v -U dba -P dba < /sql-query.sql
  kill "$(ps aux | grep '[v]irtuoso-t' | awk '{print $2}')"
  echo "`date +%Y-%m-%dT%H:%M:%S%:z`" >  .dba_pwd_set
fi

if [ ! -f ".data_loaded" -a -d "toLoad" ] ;
then
    echo "Start data loading from toLoad folder"
    pwd="dba"
    graph="http://localhost:8890/DAV"

    if [ "$DBA_PASSWORD" ]; then pwd="$DBA_PASSWORD" ; fi
    if [ "$DEFAULT_GRAPH" ]; then graph="$DEFAULT_GRAPH" ; fi
    echo "ld_dir('toLoad', '*', '$graph');" >> /load_data.sql
    echo "rdf_loader_run();" >> /load_data.sql
    echo "exec('checkpoint');" >> /load_data.sql
    echo "WAIT_FOR_CHILDREN; " >> /load_data.sql
    echo "$(cat /load_data.sql)"
    virtuoso-t +wait && isql-v -U dba -P "$pwd" < /load_data.sql
    kill $(ps aux | grep '[v]irtuoso-t' | awk '{print $2}')
    echo "`date +%Y-%m-%dT%H:%M:%S%:z`" > .data_loaded
fi

crudini --set virtuoso.ini HTTPServer ServerPort ${VIRT_HTTPServer_ServerPort:-$original_port}

exec virtuoso-t +wait +foreground