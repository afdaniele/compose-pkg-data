<?php

use \system\classes\Core;
use \system\classes\Configuration;
use \system\packages\data\Data;

require_once $GLOBALS['__SYSTEM__DIR__'].'templates/tableviewers/TableViewer.php';
use \system\templates\tableviewers\TableViewer;


$db = null;
$db_name = null;
if (!is_null(Configuration::$ACTION)) {
  $db_name = Configuration::$ACTION;
  if (!Data::exists($db_name)) {
    Core::throwError(sprintf('The database "%s" does not exist', $db_name));
  }
}
$db = Data::getDB($db_name);
?>

<p style="margin-top:-30px; margin-bottom:30px">
  <?php
  $lst_args = isset($_GET['lst'])? base64_decode($_GET['lst']) : '';
  ?>
  <a href="<?php
    echo sprintf(
      '%s%s%s%s',
      Configuration::$BASE,
      Configuration::$PAGE,
      strlen($lst_args)>0? '?':'',
      $lst_args
    )
    ?>">
    &larr; Back to the list
  </a>
</p>


<?php

// Define Constants
$features = array(
  'page' => array(
    'type' => 'integer',
    'default' => 1,
    'values' => null,
    'minvalue' => 1,
    'maxvalue' => PHP_INT_MAX
  ),
  'results' => array(
    'type' => 'integer',
    'default' => 10,
    'values' => null,
    'minvalue' => 1,
    'maxvalue' => PHP_INT_MAX
  ),
  'keywords' => array(
    'type' => 'text',
    'default' => null,
    'placeholder' => 'e.g., MyKey'
  )
);

$table = array(
  'style' => 'table-striped table-hover',
  'layout' => array(
    'database' => array(
      'type' => 'text',
      'show' => false,
      'editable' => false
    ),
    'key' => array(
      'type' => 'text',
      'show' => true,
      'width' => 'md-7',
      'align' => 'left',
      'translation' => 'Key',
      'editable' => false
    ),
    'size' => array(
      'type' => 'text',
      'show' => true,
      'width' => 'md-2',
      'align' => 'center',
      'translation' => 'Size',
      'editable' => false
    )
  ),
  'actions' => array(
    '_width' => 'md-3',
    'open' => array(
      'type' => 'default',
      'glyphicon' => 'folder-open',
      'tooltip' => 'Open entry',
      'text' => null,
      'function' => array(
        'type' => '_toggle_modal',
        'class' => 'open-entry-modal',
        'arguments' => [
          'database',
          'key'
        ]
      )
    ),
    'delete' => array(
      'type' => 'danger',
      'glyphicon' => 'trash',
      'tooltip' => 'Delete entry',
      'text' => null,
      'function' => array(
        'type' => '_toggle_modal',
        'class' => 'yes-no-modal',
        'API_resource' => 'data',
        'API_action' => 'delete',
        'arguments' => [
          'database',
          'key'
        ],
        'static_data' => [
          'question' => 'Are you sure you want to delete this entry?<br/><strong>This cannot be undone.</strong>'
        ]
      )
    )
  ),
  'features' => array(
    '_counter_column',
    '_actions_column'
  )
);

// parse the arguments
TableViewer::parseFeatures($features, $_GET);

// get data
$res = Data::list($db_name);
if (!$res['success']) {
  Core::throwError($res['data']);
}
$keys = $res['data'];

// filter based on keywords (if needed)
if ($features['keywords']['value'] != null) {
  $tmp = array();
  foreach($keys as &$key){
    if (strpos(strtolower($key), strtolower($features['keywords']['value'])) !== false) {
      array_push($tmp, $key);
    }
  }
  $keys = $tmp;
}

// compute total number of entries for pagination purposes
$total_entries = count($keys);

// take the slice corresponding to the selected page
$keys = array_slice(
  $keys,
  ($features['page']['value']-1)*$features['results']['value'],
  $features['results']['value']
);

// read keys
$entries = [];
foreach($keys as &$key){
  $key_size = $db->key_size($key);
  // build record for the table
  $key_record = [
    'database' => $db_name,
    'key' => $key,
    'size' => human_filesize($key_size, $decimals=1)
  ];
  array_push($entries, $key_record);
}

// prepare data for the table viewer
$res = array(
  'size' => count($entries),
  'total' => $total_entries,
  'data' => $entries
);

$baseuri = sprintf("%s/%s", Configuration::$PAGE, $db_name);
TableViewer::generateTableViewer($baseuri, $res, $features, $table);

include_once __DIR__.'/components/open_entry_modal.php';
?>
