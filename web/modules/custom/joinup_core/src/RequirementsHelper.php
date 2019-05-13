<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Database\Connection;

/**
 * Implements helper methods related to the requirements.
 */
class RequirementsHelper {

  /**
   * The connection class for the primary database storage.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * RequirementsHelper constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection class for the primary database storage.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Returns a query to find the forward revisions.
   *
   * @return array
   *   An associative array of vids indexed by their id. The vid is the latest
   *   published revision in the database.
   */
  public function getNodesWithProblematicRevisions(): array {
    $query = <<<QUERY
SELECT n.nid AS nid,
  (
    SELECT max(vid)
    FROM node_revision
    LEFT JOIN node_revision__field_state ON node_revision.vid = node_revision__field_state.revision_id
    WHERE nid = n.nid AND field_state_value = 'validated'
    AND revision_default = 1
  ) as latest_vid
FROM node as n
LEFT JOIN node_revision AS nr
ON n.nid = nr.nid
# Published revision is behind latest default revision
AND n.vid < (
  SELECT max(vid)
  FROM node_revision
  LEFT JOIN node_revision__field_state ON node_revision.vid = node_revision__field_state.revision_id
  WHERE nid = n.nid AND field_state_value = 'validated'
  AND revision_default = 1
)
LEFT JOIN node_field_data AS nfd
ON n.nid = nfd.nid
WHERE nr.vid > n.vid
GROUP BY nid, latest_vid
QUERY;
    return $this->connection->query($query)->fetchAllAssoc('nid');
  }

}
