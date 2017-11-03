<?php

/**
 * @file
 * Post update functions for Joinup Migrate module.
 */

use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_run\MigrateExecutable;
use Drupal\node\Entity\Node;
use Drupal\rdf_entity\UriEncoder;
use Drupal\redirect\Entity\Redirect;

/**
 * Add the missed 'simatosc' user (uid 73932) [ISAICP-4004].
 */
function joinup_migrate_post_update_add_user_73932() {
  $uid = 73932;

  // Make this user eligible for migration.
  Database::getConnection('default', 'migrate')
    ->update('userpoints')
    ->fields(['points' => 10])
    ->condition('uid', $uid)
    ->execute();

  /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager */
  $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
  $message = new MigrateMessage();

  $migrations = $migration_plugin_manager->createInstances([
    'user',
    'file:user_photo',
    'user_profile',
  ]);
  // Run user migrations.
  foreach ($migrations as $migration_id => $migration) {
    $migration->set('requirements', []);
    (new MigrateExecutable($migration, $message, ['idlist' => $uid]))
      ->import();
  }
  // Run solution OG membership migration.
  $migration = $migration_plugin_manager->createInstance('og_user_role_solution')
    ->set('requirements', []);
  (new MigrateExecutable($migration, $message, ['idlist' => "157750:$uid"]))
    ->import();

  // Fix the authorship for two 'news' nodes.
  foreach (Node::loadMultiple([165260, 164381]) as $node) {
    /** @var \Drupal\node\NodeInterface $node */
    $node->setOwnerId($uid)->save();
  }
  // Fix the authorship for several files.
  $fids = [33857, 33858, 33859, 33860, 33861, 33862, 33863];
  foreach (File::loadMultiple($fids) as $file) {
    /** @var \Drupal\file\FileInterface $file */
    $file->setOwnerId($uid)->save();
  }
}

/**
 * Disable the Update module [ISAICP-4033].
 */
function joinup_migrate_post_update_disable_update() {
  \Drupal::service('module_installer')->uninstall(['update']);
}

/**
 * Add more specific redirects [ISAICP-4012].
 */
function joinup_migrate_post_update_more_redirects() {
  $redirects = [];
  $db = Database::getConnection();
  $legacy_db = Database::getConnection('default', 'migrate');
  $legacy_db_name = $legacy_db->getConnectionOptions()['database'];
  /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $redirect_storage */
  $redirect_storage = \Drupal::service('entity_type.manager')->getStorage('redirect');

  // Update the 'd8_solution' MySQL view.
  $legacy_db->query(file_get_contents(__DIR__ . '/fixture/0.solution.sql'))->execute();

  // Redirects due to source 'project_project'.
  /** @var \Drupal\Core\Database\Query\SelectInterface $query */
  $query = $db->select("$legacy_db_name.d8_solution", 's')
    ->fields('s', ['short_name'])
    ->fields('ms', ['destid1'])
    ->fields('t', ['field_project_common_type_value'])
    ->isNotNull('s.short_name')
    ->isNotNull('ms.destid1');
  $query->join('migrate_map_solution', 'ms', 's.nid = ms.sourceid1');
  $query->join("$legacy_db_name.content_field_project_common_type", 't', 's.vid = t.vid');
  foreach ($query->execute()->fetchAll() as $row) {
    $short_name = $row->short_name;
    $solution_uri = 'internal:/rdf_entity/' . UriEncoder::encodeUrl($row->destid1);
    $redirect = ['uri' => $solution_uri];
    $redirect_download_releases = ['uri' => "$solution_uri/releases"];
    foreach (['asset', 'software'] as $prefix) {
      $redirects["$prefix/$short_name/asset_release/all"] = $redirect_download_releases;
      $redirects["$prefix/$short_name/communications/all"] = $redirect;
      $redirects["$prefix/$short_name/issue/all"] = $redirect + [
        'options' => [
          'query' => ['f[0]' => 'solution_content_bundle:discussion'],
        ],
      ];
    }
    // Project forum pages are not duplicating the URL.
    $prefix = $row->field_project_common_type_value == 1 ? 'software' : 'asset';
    $redirects["$prefix/$short_name/forum/all"] = $redirect;
  }

  // Redirects due to source 'community'.
  $query = $db->select("$legacy_db_name.d8_mapping", 'm')
    ->fields('c', ['field_community_short_name_value'])
    ->fields('mc', ['destid1'])
    ->isNotNull('c.field_community_short_name_value')
    ->isNotNull('mc.destid1');
  $query->join("$legacy_db_name.node", 'n', 'm.nid = n.nid');
  $query->join("$legacy_db_name.content_type_community", 'c', 'n.vid = c.vid');
  $query->join('migrate_map_collection', 'mc', 'm.collection = mc.sourceid1');
  foreach ($query->execute()->fetchAllKeyed() as $short_name => $id) {
    $redirect = ['uri' => 'internal:/rdf_entity/' . UriEncoder::encodeUrl($id)];
    $redirects["community/$short_name/forum/all"] = $redirect;
    $redirects["community/$short_name/communications/all"] = $redirect;
  }

  // Parent custom page redirects.
  $query = $db->select('migrate_map_custom_page_parent', 'm')->fields('m', ['sourceid1', 'destid1']);
  // @see https://api.drupal.org/api/drupal/includes%21path.inc/function/drupal_lookup_path/6.x
  $sql = "SELECT dst FROM {url_alias} WHERE language IN ('', 'en') AND src = :src ORDER BY pid DESC";
  $deleted_redirects = [];
  foreach ($query->execute()->fetchAllKeyed() as $source_nid => $destination_nid) {
    $deleted_redirects[] = "node/$source_nid";
    $redirect = ['uri' => "internal:/node/$destination_nid"];
    $redirects["node/$source_nid"] = $redirect;
    if ($alias = $legacy_db->queryRange($sql, 0, 1, [':src' => "node/$source_nid"])->fetchField()) {
      $deleted_redirects[] = $alias;
      $redirects[$alias] = $redirect;
    }
  }
  if ($rids = $redirect_storage->getQuery()
    ->condition('redirect_source.path', $deleted_redirects, 'IN')
    ->execute()) {
    $redirect_storage->delete($redirect_storage->loadMultiple($rids));
  }

  // Create the redirects.
  foreach ($redirects as $source_path => $redirect) {
    if (!$redirect_storage->loadByProperties(['redirect_source__path' => $source_path])) {
      $redirect += ['title' => '', 'options' => []];
      Redirect::create([
        'uid' => 1,
        'redirect_source' => ['path' => $source_path, 'query' => NULL],
        'redirect_redirect' => $redirect,
        'status_code' => 301,
      ])->save();
    }
  }
}

/**
 * Fix distribution files with the same name [ISAICP-3968].
 */
function joinup_migrate_post_update_same_name_distribution_files() {
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = \Drupal::service('file_system');
  /** @var \Drupal\Component\Uuid\UuidInterface $uuid_generator */
  $uuid_generator = Drupal::service('uuid');
  /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $redirect_storage */
  $redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');

  $legacy_db = Database::getConnection('default', 'migrate');
  $legacy_db_name = $legacy_db->getConnectionOptions()['database'];

  /** @var \Drupal\Core\Database\Query\SelectInterface $query */
  $query = Database::getConnection()->select('file_managed', 'f')
    ->fields('f', ['fid', 'uri'])
    ->fields('d6f', ['filename', 'filepath'])
    ->orderBy('f.uri')
    ->condition('f.uri', 'public://distribution/%', 'LIKE');
  $query->join("$legacy_db_name.files", 'd6f', 'f.fid = d6f.fid');
  $file_groups = [];
  foreach ($query->execute()->fetchAll() as $row) {
    $dir = substr($file_system->dirname($row->uri), 22);
    // We accept only date formatted dirs.
    if (preg_match('@^\d{4}\-\d{2}$@', $dir)) {
      $file_groups[$dir][$row->filename][$row->fid] = $row->filepath;
    }
  }

  // Keep only duplicates.
  array_walk($file_groups, function (array &$file_group) {
    $file_group = array_filter($file_group, function (array $files) {
      return count($files) > 1;
    });
  });
  $file_groups = array_filter($file_groups, function (array $file_group) {
    return (bool) $file_group;
  });

  $prefix = "public://distribution/access_url";
  $redirects = [];
  foreach ($file_groups as $month => $file_group) {
    foreach ($file_group as $file_name => $files) {
      foreach ($files as $fid => $source_path) {
        $destination = "$prefix/$month/{$uuid_generator->generate()}";
        // Create the destination directory.
        if (!is_dir($destination)) {
          $file_system->mkdir($destination, NULL, TRUE);
        }
        $file = File::load($fid);

        // Ensure the filename.
        $file->setFilename($file_name);
        if (!$file = file_move($file, "$destination/$file_name")) {
          throw new \Exception("Can't move file $file_name (fid $fid).");
        }
        $redirects[$source_path] = "base:/sites/default/files/" . str_replace('public://', '', $file->getFileUri());
      }
    }
  }

  // Delete existing redirects.
  if ($rids = $redirect_storage->getQuery()
    ->condition('redirect_source.path', array_keys($redirects), 'IN')
    ->execute()) {
    $redirect_storage->delete($redirect_storage->loadMultiple($rids));
  }
  // Create the new redirects.
  foreach ($redirects as $source_path => $redirect_uri) {
    Redirect::create([
      'uid' => 1,
      'redirect_source' => ['path' => $source_path, 'query' => NULL],
      'redirect_redirect' => [
        'uri' => $redirect_uri,
        'title' => '',
        'options' => [],
      ],
      'status_code' => 301,
    ])->save();
  }
}