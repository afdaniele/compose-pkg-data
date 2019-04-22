<?php

use \system\classes\Core;
use \system\classes\Configuration;
use \system\packages\data\Data;

require_once $GLOBALS['__PACKAGES__DIR__'].'/core/modules/modals/record_editor_modal.php';

require_once $GLOBALS['__SYSTEM__DIR__'].'templates/tableviewers/TableViewer.php';
use \system\templates\tableviewers\TableViewer;


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
    'placeholder' => 'e.g., MyDatabase'
  )
);

$table = array(
  'style' => 'table-striped table-hover',
  'layout' => array(
    'database' => array(
      'type' => 'text',
      'show' => true,
      'width' => 'md-3',
      'align' => 'left',
      'translation' => 'Database',
      'editable' => false
    ),
    'owner_visible' => array(
      'type' => 'text',
      'show' => true,
      'width' => 'md-3',
      'align' => 'center',
      'translation' => 'Owner',
      'editable' => false
    ),
    'size' => array(
      'type' => 'text',
      'show' => true,
      'width' => 'md-1',
      'align' => 'center',
      'translation' => 'Size',
      'editable' => false
    ),
    'access_visible' => array(
      'type' => 'text',
      'show' => true,
      'width' => 'md-1',
      'align' => 'center',
      'translation' => 'Access',
      'editable' => false
    )
  ),
  'actions' => array(
    '_width' => 'md-4',
    'lookup' => array(
      'type' => 'default',
      'glyphicon' => 'folder-open',
      'tooltip' => 'Open database',
      'text' => null,
      'function' => array(
        'type' => 'custom',
        'custom_html' => 'onclick="_open_db(this)"',
        'arguments' => [],
        'static_data' => [
          'modal-mode' => 'edit'
        ]
      )
    ),
    'separator' => array(
      'type' => 'separator'
    ),
    'edit_owner' => array(
      'type' => 'default',
      'glyphicon' => 'user',
      'tooltip' => 'Edit owner',
      'text' => null,
      'function' => array(
        'type' => '_toggle_modal',
        'class' => 'record-editor-modal-db-owner-form',
        'static_data' => ['modal-mode' => 'edit'],
        'API_resource' => 'data',
        'API_action' => 'chown',
        'arguments' => [
          'database'
        ]
      )
    ),
    'edit_access' => array(
      'type' => 'default',
      'glyphicon' => 'lock',
      'tooltip' => 'Edit access',
      'text' => null,
      'function' => array(
        'type' => '_toggle_modal',
        'class' => 'record-editor-modal-db-access-form',
        'static_data' => ['modal-mode' => 'edit'],
        'API_resource' => 'data',
        'API_action' => 'chmod',
        'arguments' => [
          'database'
        ]
      )
    ),
    'separator2' => array(
      'type' => 'separator'
    ),
    'delete' => array(
      'type' => 'danger',
      'glyphicon' => 'trash',
      'tooltip' => 'Delete database',
      'text' => null,
      'function' => array(
        'type' => '_toggle_modal',
        'class' => 'yes-no-modal',
        'API_resource' => 'data',
        'API_action' => 'delete',
        'arguments' => [
          'database'
        ],
        'static_data' => [
          'question' => 'Are you sure you want to delete this database?<br/><strong>This cannot be undone.</strong>'
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

$dbs = Data::listDBs();

$tmp = [];
$users_cache = [];
$users_name_cache = [];
for( $i = 0; $i < sizeof($dbs); $i++ ){
  $db_id = $dbs[$i];
  $res = Data::info($db_id);
  if(!$res['success']){
    Core::throwError($res['data']);
  }
  $db_info = $res['data'];
  //
  $owner = '(nobody)';
  $owner_name = '';
  $owner_id = $db_info['metadata']['auth']['owner'];
  if (array_key_exists($owner_id, $users_cache)) {
    $owner = $users_cache[$owner_id];
    $owner_name = $users_name_cache[$owner_id];
  } else {
    $res = Core::getUserInfo($owner_id);
    if(!$res['success']){
      $owner = '(error)';
    }else{
      $user_info = $res['data'];
      $owner_name = $user_info['name'];
      $owner = sprintf(
        '<img src="%s" class="formatted-avatar formatted-avatar-small"> %s',
        $user_info['picture'],
        $user_info['name']
      );
      // update cache
      $users_cache[$owner_id] = $owner;
      $users_name_cache[$owner_id] = $owner_name;
    }
  }
  //
  $db_record = [
    'database' => $db_info['name'],
    'owner' => $owner_id,
    'owner_visible' => $owner,
    'owner_name' => $owner_name,
    'size' => $db_info['size'],
    'access' => $db_info['metadata']['auth']['type'],
    'access_visible' => ucfirst($db_info['metadata']['auth']['type'])
  ];
  array_push($tmp, $db_record);
}
$dbs = $tmp;


// filter based on keywords (if needed)
if( $features['keywords']['value'] != null ){
  $tmp = array();
  foreach($dbs as &$db){
    if (strpos(strtolower($db['name']), strtolower($features['keywords']['value'])) !== false ||
        strpos(strtolower($db['owner_name']), strtolower($features['keywords']['value'])) !== false) {
      array_push($tmp, $db);
    }
  }
  $dbs = $tmp;
}

// compute total number of databases for pagination purposes
$total_dbs = sizeof($dbs);

// take the slice corresponding to the selected page
$dbs = array_slice(
  $dbs,
  ($features['page']['value']-1)*$features['results']['value'],
  $features['results']['value']
);

// prepare data for the table viewer
$res = array(
  'size' => sizeof($dbs),
  'total' => $total_dbs,
  'data' => $dbs
);

TableViewer::generateTableViewer(Configuration::$PAGE, $res, $features, $table);


// create modal for changing access type
$db_access_edit_form = [
	'database' => [
		'name' => 'Name',
		'type' => 'text',
		'editable' => false
	],
	'access' => [
		'name' => 'Access',
    'type' => 'enum',
    'placeholder' => ['Public', 'Private'],
		'placeholder_id' => ['public', 'private'],
		'editable' => true
	]
];
generateRecordEditorModal(
  $db_access_edit_form,
  $formID='db-access-form',
  $method='GET',
  $action=null,
  $values=array(),
  $size='sm'
);


// get list of users
$users = Core::getUsersList();
$users_ids = [];
$users_names = [];
for($i = 0; $i < sizeof($users); $i++){
	$user_id = $users[$i];
	$res = Core::getUserInfo($user_id);
	if( !$res['success'] ){
		Core::throwError( $res['data'] );
	}
  array_push($users_ids, $user_id);
	array_push($users_names, $res['data']['name']);
}
// create modal for changing owner
$db_owner_edit_form = [
	'database' => [
		'name' => 'Name',
		'type' => 'text',
		'editable' => false
	],
	'owner' => [
		'name' => 'Owner',
    'type' => 'enum',
    'placeholder' => $users_names,
		'placeholder_id' => $users_ids,
		'editable' => true
	]
];
generateRecordEditorModal(
  $db_owner_edit_form,
  $formID='db-owner-form',
  $method='GET',
  $action=null,
  $values=array(),
  $size='sm'
);
?>

<script type="text/javascript">

	var args = "<?php echo base64_encode(toQueryString(array_keys($features), $_GET)) ?>";

	function _open_db(target){
		var record = $(target).data('record');
		// open page here
		var url = "<?php echo sprintf('%s%s/{0}{1}{2}', Configuration::$BASE, 'data-viewer') ?>".format(record.database, (args.length > 0)? '?lst=' : '', args);
		location.href = url;
	}

</script>
