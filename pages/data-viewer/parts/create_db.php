<?php

require_once $GLOBALS['__PACKAGES__DIR__'].'/core/modules/modals/record_editor_modal.php';

// create modal
$db_create_form = [
	'database' => [
		'name' => 'New database name',
		'type' => 'text',
		'editable' => true
	]
];

generateRecordEditorModal(
  $db_create_form,
  $formID='db-create-form',
  $method='GET',
  $action=null,
  $values=[],
  $size='md'
);

?>
