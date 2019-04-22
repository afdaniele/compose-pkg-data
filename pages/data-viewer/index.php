<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\classes\Core;
use \system\classes\Configuration;
use \system\packages\data\Data;


$subtitle = 'Databases';
$part = 'list';

if (!is_null(Configuration::$ACTION)) {
  $db_name = Configuration::$ACTION;
  if (!Data::exists($db_name)) {
    Core::throwError(sprintf('The database "%s" does not exist', $db_name));
  }
  $part = 'open_db';
  $subtitle = sprintf('Database <span class="mono">"%s"</span>', $db_name);
}
?>

<div style="width:100%; margin:auto">

  <table style="width:100%; border-bottom:1px solid #ddd; margin-bottom:32px">
    <tr>
      <td style="width:100%">
        <h2>
          Data Viewer - <?php echo $subtitle ?>
          <?php
          if ($part == 'list') {
            $url = Core::getAPIurl('data', 'new');
            ?>
            <button
            class="btn btn-warning"
            type="button"
            data-toggle="tooltip dialog"
            data-placement="bottom"
            data-original-title="Create new database"
            data-modal-mode="insert"
            data-target="#record-editor-modal-db-create-form"
            data-url="<?php echo $url ?>"
            style="float: right">
              &nbsp;
              <i class="fa fa-asterisk" aria-hidden="true"></i>
              &nbsp;
              New Database
            </button>
            <?php
          }
          ?>
        </h2>
      </td>
    </tr>
  </table>

  <?php
  include_once __DIR__.sprintf('/parts/%s.php', $part);

  if ($part == 'list') {
    include_once __DIR__.'/parts/create_db.php';
  }
  ?>

</div>
