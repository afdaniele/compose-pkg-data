<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele



namespace system\packages\data;

use \system\classes\Core;
use \system\classes\Utils;
use \system\classes\Database;


/**
*   Module that provides a simple interface to a key-based database.
*
*   Authentication happens at the DB level, not at the key level. This means
*   that a user that has access to a DB, has access to all its keys.
*   A database managed by the Data library has a special metadata key
*   with a special key (stored in $metadata_key). This keys contains the
*   following data:
*
*      {
*         "auth" : {
*            "type" : one-of{ "public", "private" },
*            "owner" : "<user_id>",
*            "grant" : [
*               "<user_id>",
*               ...
*            ]
*         }
*      }
*
*/
class Data{

  private static $package_id = 'data';
  private static $initialized = false;
  private static $metadata_key = '__metadata__';


  // disable the constructor
  private function __construct() {}

  /** Initializes the module.
  *
  *	@retval array
  *		a status array of the form
  *	<pre><code class="php">[
  *		"success" => boolean, 	// whether the function succeded
  *		"data" => mixed 		// error message or NULL
  *	]</code></pre>
  *		where, the `success` field indicates whether the function succeded.
  *		The `data` field contains errors when `success` is `FALSE`.
  */
  public static function init(){
    if( !self::$initialized ){
      self::$initialized = true;
      //
      return array( 'success' => true, 'data' => null );
    }else{
      return array( 'success' => true, 'data' => "Module already initialized!" );
    }
  }//init


  /** Safely terminates the module.
  *
  *	@retval array
  *		a status array of the form
  *	<pre><code class="php">[
  *		"success" => boolean, 	// whether the function succeded
  *		"data" => mixed 		// error message or NULL
  *	]</code></pre>
  *		where, the `success` field indicates whether the function succeded.
  *		The `data` field contains errors when `success` is `FALSE`.
  */
  public static function close(){
    // do stuff
    return array( 'success' => true, 'data' => null );
  }//close



  // =======================================================================================================
  // Public functions

  public static function listDBs(){
    return Database::list_dbs(self::$package_id);
  }//listDBs

  public static function getDB($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // ---
    return new Database(self::$package_id, $database_name);
  }//getDB

  public static function exists($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    //---
    return Database::database_exists(self::$package_id, $database_name);
  }//exists

  public static function list($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // ---
    $db = new Database(self::$package_id, $database_name);
    $lst = array_values(array_diff($db->list_keys(), [self::$metadata_key]));
    return ['success' => true, 'data' => $lst];
  }//list

  public static function new($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database does not exist
    if (self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" already exists.', $database_name)];
    }
    // write metadata
    $user_id = Core::getUserLogged('username');
    $mdata = ['type' => 'private', 'owner' => $user_id, 'guest' => []];
    foreach ($mdata as $k => $value) {
      $res = self::_update_metadata($database_name, $k, $value);
      if (!$res['success']){
        return $res;
      }
    }
    return ['success' => true, 'data' => null];
  }//new

  public static function drop($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // ---
    return Database::delete_db(self::$package_id, $database_name);
  }//drop

  public static function info($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // ---
    $res = self::_get_metadata($database_name);
    if (!$res['success']){
      return $res;
    }
    $metadata = $res['data'];
    // get list of keys
    $res = self::list($database_name);
    if (!$res['success']){
      return $res;
    }
    $keys = $res['data'];
    $count_keys = count($res['data']);
    // compile output
    $info = [
      'name' => $database_name,
      'keys' => $keys,
      'size' => $count_keys,
      'metadata' => $metadata
    ];
    // ---
    return ['success' => true, 'data' => $info];
  }//info

  public static function has($database_name, $key){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return false;
    }
    // ---
    $db = new Database(self::$package_id, $database_name);
    return $db->key_exists($key);
  }//has

  public static function get($database_name, $key){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return ['success' => false, 'data' => sprintf('The key "%s" is reserved and cannot be used.', $key)];
    }
    // ---
    $db = new Database(self::$package_id, $database_name);
    return $db->read($key);
  }//get

  public static function set($database_name, $key, $data){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the database exists
    if (!self::exists($database_name)){
      return ['success' => false, 'data' => sprintf('The database "%s" does not exist.', $database_name)];
    }
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return ['success' => false, 'data' => sprintf('The key "%s" is reserved and cannot be used.', $key)];
    }
    // ---
    // parse data if it is JSON
    if (is_string($data) && is_JSON($data)) {
      $data = json_decode($data, true);
    }
    // write data to DB
    $db = new Database(self::$package_id, $database_name);
    return $db->write($key, $data);
  }//set

  public static function del($database_name, $key){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return ['success' => false, 'data' => sprintf('The key "%s" is reserved and cannot be used.', $key)];
    }
    // ---
    // delete key from DB
    $db = new Database(self::$package_id, $database_name);
    return $db->delete($key);
  }//del

  public static function set_public_access($database_name){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    //---
    return self::_update_metadata($database_name, 'type', 'public');
  }//set_public_access

  public static function set_private_access($database_name, $grant_list){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    //---
    $res = self::_update_metadata($database_name, 'grant', $grant_list);
    if (!$res['success']){
      return $res;
    }
    return self::_update_metadata($database_name, 'type', 'private');
  }//set_public_access

  public static function set_ownership($database_name, $user_id){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    //---
    if (!Core::userExists($user_id)) {
      return ['success' => false, 'data' => sprintf('The user "%s" does not exist.', $user_id)];
    }
    // ---
    return self::_update_metadata($database_name, 'owner', $user_id);
  }//set_ownership

  public static function set_guest_access($database_name, $can_read, $can_write){
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    // define access
    $guest = [];
    if ($can_read){
      array_push($guest, "r");
    }
    if ($can_write){
      array_push($guest, "w");
    }
    //---
    return self::_update_metadata($database_name, 'guest', $guest);
  }//set_guest_access

  public static function canAccess($database_name, $force_ownership=false, $mode="rw") {
    // make sure that the given arguments are valid
    if (!self::_is_database_name_valid($database_name)) {
      return ['success' => false, 'data' => sprintf('The database name "%s" is not valid.', $database_name)];
    }
    //---
    return self::_authenticate($database_name, $force_ownership, $mode);
  }//canAccess



  // =======================================================================================================
  // Private functions

  private static function _is_key_reserved($key) {
    $k = Utils::string_to_valid_filename($key);
    if (in_array($k, [self::$metadata_key])) {
      return true;
    }
    return false;
  }//_is_key_reserved

  private static function _authenticate($database_name, $force_ownership=false, $mode="rw"){
    // a database that does not exist is always accessible
    if (!self::exists($database_name)) {
      return ['success' => true, 'data' => null];
    }
    // an administrator has access to everything
    if (Core::getUserRole() == 'administrator') {
      return ['success' => true, 'data' => null];
    }
    // we know the database exists, and we are not an admin, we might be a user or a guest
    $res = self::_get_metadata($database_name);
    if (!$res['success']){
      return $res;
    }
    // get metadata
    $metadata = $res['data'];
    if (is_null($metadata) || !array_key_exists('auth', $metadata)) {
      return [
        'success' => false,
        'data' => sprintf('No authentication data available for the database "%s"', $database_name)
      ];
    }
    $auth_data = $metadata['auth'];
    if (is_null($auth_data)) {
      return ['success' => false, 'data' => "Internal error. Invalid database metadata."];
    }
    $auth_type = $auth_data['type'];
    // check for guest access
    if (!Core::isUserLoggedIn()) {
      // we are a guest, can only access public databases with explicitly defined guest access control rules
      if ($auth_type != "public") {
        return ['success' => false, 'data' => sprintf("Guests can only access public databases [%s]", $auth_type)];
      }
      $guest_access = [];
      if (array_key_exists("guest", $auth_data)) {
        $guest_access = $auth_data["guest"];
      }
      // check rules
      $ops = str_split($mode);
      foreach($ops as $op){
        if (!in_array($op, $guest_access)) {
          return [
              'success' => false,
              'data' => sprintf('You don\'t have "%s" access to the database "%s".', $op, $database_name)
          ];
        }
      }
      return ['success' => true, 'data' => null];
    }
    // get user id
    $user_id = Core::getUserLogged('username');
    if ($auth_type == 'public' && !$force_ownership) {
      return ['success' => true, 'data' => null];
    }
    // get owner and grant list
    $auth_owner = array_key_exists('owner', $auth_data)? $auth_data['owner'] : null;
    $auth_grant = array_key_exists('grant', $auth_data)? $auth_data['grant'] : null;
    if (is_null($auth_owner) && (is_null($auth_grant) || !is_array($auth_grant))) {
      return [
          'success' => false,
          'data' => sprintf('You don\'t have access to the database "%s".', $database_name)
      ];
    }
    // check owner
    if ($auth_owner == $user_id || (is_array($auth_grant) && in_array($user_id, $auth_grant))){
      return ['success' => true, 'data' => null];
    }
    // by default, deny access
    return [
        'success' => false,
        'data' => sprintf('You don\'t have access to the database "%s".', $database_name)
    ];
  }//_authenticate

  private static function _get_metadata($database_name){
    // get db metadata
    $db = new Database(self::$package_id, $database_name);
    return $db->read(self::$metadata_key);
  }//_get_metadata

  private static function _update_metadata($database_name, $key, $value){
    if (!in_array($key, ['type', 'owner', 'guest', 'grant'])) {
      return ['success' => false, 'data' => "Key must be one of ['type', 'owner', 'guest', 'grant']."];
    }
    $db = new Database(self::$package_id, $database_name);
    $metadata = [
      'auth' => []
    ];
    if ($db->key_exists(self::$metadata_key)){
      $res = $db->read(self::$metadata_key);
      if (!$res['success']){
        return $res;
      }
      $metadata['auth'] = $res['data']['auth'];
    }
    $metadata['auth'][$key] = $value;
    return $db->write(self::$metadata_key, $metadata);
  }//_update_metadata

  private static function _is_database_name_valid($database_name){
    return (!is_null($database_name) && strlen(trim($database_name)) > 0);
  }//_is_database_name_valid

}//Data

?>
