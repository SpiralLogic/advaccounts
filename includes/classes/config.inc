<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 5/08/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */

	include_once(APP_PATH . "config/types.inc");
	include_once(APP_PATH . "config/access_levels.inc");
	include_once(APP_PATH . "config/installed_languages.inc");

	class Config {

		protected static $_vars = array();

		public static function load() {
			static::$_vars = include(APP_PATH . 'config/config.php');
		}

		public static function set($var, $value) {
			static::$_vars[$var] = $value;
			return $value;
		}

		public static function get($var, $array_key = null) {
			if (!isset(static::$_vars[$var])) return false;
			return ($array_key !== null && is_array(static::$_vars[$var])) ?
			 static::$_vars[$var][$array_key] :
			 static::$_vars[$var];
		}
	}

?>