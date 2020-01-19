<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele



namespace system\packages\demo_mode;

use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Utils;
use \system\classes\Database;


/**
*   Module that provides tools for using *\compose\* on TVs in demo mode.
*
*/
class DemoMode{

  private static $initialized = false;


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


}//DemoMode

?>
