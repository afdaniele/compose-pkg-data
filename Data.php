<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele



namespace system\packages\data;

use \system\classes\Core;
use \system\classes\Configuration;
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

  public static function exists($database_name){
    return Database::database_exists(self::$package_id, $database_name);
  }//exists

  public static function list($database_name){
    $res = self::_authenticate($database_name);
    if (!$res['success']){
      return $res;
    }
    // ---
    $db = new Database(self::$package_id, $database_name);
    $lst = array_values(array_diff($db->list_keys(), [self::$metadata_key]));
    return ['success' => true, 'data' => $lst];
  }//list

  public static function has($database_name, $key){
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return false;
    }
    // make sure the user has access to this db
    $res = self::_authenticate($database_name);
    if (!$res['success']){
      return false;
    }
    // ---
    $db = new Database(self::$package_id, $database_name);
    return $db->key_exists($key);
  }//has

  public static function get($database_name, $key){
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return ['success' => false, 'data' => sprintf('The key "%s" is reserved and cannot be used.', $key)];
    }
    // make sure the user has access to this db
    $res = self::_authenticate($database_name);
    if (!$res['success']){
      return $res;
    }
    // ---
    $db = new Database(self::$package_id, $database_name);
    return $db->read($key);
  }//get

  public static function set($database_name, $key, $data){
    // make sure the key is not reserved
    if (self::_is_key_reserved($key)) {
      return ['success' => false, 'data' => sprintf('The key "%s" is reserved and cannot be used.', $key)];
    }
    // make sure the user has access to this db
    $res = self::_authenticate($database_name);
    if (!$res['success']){
      return $res;
    }
    // ---
    if (!self::exists($database_name)){
      // write metadata
      $user_id = Core::getUserLogged('username');
      $mdata = ['type' => 'private', 'owner' => $user_id];
      foreach ($mdata as $k => $value) {
        $res = self::_update_metadata($database_name, $k, $value);
        if (!$res['success']){
          return $res;
        }
      }
    }
    // write data to DB
    $db = new Database(self::$package_id, $database_name);
    return $db->write($key, $data);
  }//set

  public static function set_public_access($database_name){
    $res = self::_authenticate($database_name);
    if (!$res['success']){
      return $res;
    }
    // ---
    return self::_update_metadata($database_name, 'type', 'public');
  }//set_public_access

  public static function set_private_access($database_name, $grant_list){
    $res = self::_authenticate($database_name, true);
    if (!$res['success']){
      return $res;
    }
    // ---
    $res = self::_update_metadata($database_name, 'grant', $grant_list);
    if (!$res['success']){
      return $res;
    }
    return self::_update_metadata($database_name, 'type', 'private');
  }//set_public_access

  public static function set_ownership($database_name, $user_id){
    $res = self::_authenticate($database_name);
    if (!$res['success']){
      return $res;
    }
    // ---
    if (!Core::userExists($user_id)) {
      return ['success' => false, 'data' => sprintf('The user "%s" does not exist.', $user_id)];
    }
    // ---
    return self::_update_metadata($database_name, 'owner', $user_id);
  }//set_ownership



  // =======================================================================================================
  // Private functions

  private static function _is_key_reserved($key) {
    $k = Utils::string_to_valid_filename($key);
    if (in_array($k, [self::$metadata_key])) {
      return true;
    }
    return false;
  }//_is_key_reserved

  private static function _authenticate($database_name, $force_ownership=false){
    // guests do not have access to DBs
    if (!Core::isUserLoggedIn()) {
      return ['success' => false, 'data' => 'You need to login to access the databases.'];
    }
    // a database that does not exist is always accessible
    if (!self::exists($database_name)){
      return ['success' => true, 'data' => null];
    }
    // get user id
    $user_id = Core::getUserLogged('username');
    // get metadata
    $res = self::_get_metadata($database_name);
    if (!$res['success']){
      return $res;
    }
    $metadata = $res['data'];
    if (is_null($metadata) || !array_key_exists('auth', $metadata)) {
      return ['success' => false, 'data' => sprintf('No authentication data available for the database "%s"', $database_name)];
    }
    $no_access_msg = sprintf('You don\'t have access to the database "%s".', $database_name);
    $auth_data = $metadata['auth'];
    if (is_null($auth_data)) {
      return ['success' => false, 'data' => $no_access_msg];
    }
    // get auth type
    if (!array_key_exists('type', $auth_data) || !in_array($auth_data['type'], ['public', 'private'])) {
      return ['success' => false, 'data' => $no_access_msg];
    }
    $auth_type = $auth_data['type'];
    if ($auth_type == 'public' && !$force_ownership) {
      return ['success' => true, 'data' => null];
    }
    // get owner and grant list
    $auth_owner = array_key_exists('owner', $auth_data)? $auth_data['owner'] : null;
    $auth_grant = array_key_exists('grant', $auth_data)? $auth_data['grant'] : null;
    if (is_null($auth_owner) && (is_null($auth_grant) || !is_array($auth_grant))) {
      return ['success' => false, 'data' => $no_access_msg];
    }
    // check owner
    if ($auth_owner == $user_id || (is_array($auth_grant) && in_array($user_id, $auth_grant))){
      return ['success' => true, 'data' => null];
    }
    // by default, deny access
    return ['success' => false, 'data' => $no_access_msg];
  }//_authenticate

  private static function _get_metadata($database_name){
    // get db metadata
    $db = new Database(self::$package_id, $database_name);
    return $db->read(self::$metadata_key);
  }//_get_metadata

  private static function _update_metadata($database_name, $key, $value){
    if (!in_array($key, ['type', 'owner', 'grant'])) {
      return ['success' => false, 'data' => "Key must be one of ['type', 'owner', 'grant']."];
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

}//Data

?>
