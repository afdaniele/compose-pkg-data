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
        <h2>Data Viewer - <?php echo $subtitle ?></h2>
      </td>
    </tr>
  </table>

  <?php
  include_once __DIR__.sprintf('/parts/%s.php', $part);
  ?>

</div>
