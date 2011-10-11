<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 5/08/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */

	include_once(APP_PATH . "config/types.php");
	include_once(APP_PATH . "config/defines.php");
	include_once(APP_PATH . "config/access_levels.php");

	class Config {

		static $_vars = array();
		protected static $_loaded_files = array();

		public static function init() {

			static::load();
			if (count(static::$_vars['config']['config.onload']) > 0) call_user_func_array('Config::load', static::$_vars['config']['config.onload']);
			static::js();
			if (Config::get('logs.error.file') != '') {
				ini_set("error_log", Config::get('logs.error.file'));
				ini_set("ignore_repeated_errors", "On");
				ini_set("log_errors", "On");
			}
		}

		public static function load() {
			$group = func_get_args();

			if (count($group) == 0) $group = array('config');
			foreach ($group as $file) {
				if (array_key_exists($file, static::$_loaded_files)) continue;
				static::$_vars[$file] = include(APP_PATH . "config/{$file}.php");
				static::$_loaded_files[$file] = true;
			}
		}

		public static function set($var, $value, $group = 'config') {
			static::$_vars[$group][$var] = $value;
			return $value;
		}

		public static function get($var, $array_key = null, $group = 'config') {
			if ($var === null && $array_key === null) return static::$_vars[$group];
			if (!isset(static::$_vars[$group][$var])) return false;
			return ($array_key !== null && is_array(static::$_vars[$group][$var])) ?
			 static::$_vars[$group][$var][$array_key] : static::$_vars[$group][$var];
		}

		public static function remove($var, $group = 'config') {
			if (array_key_exists($var, static::$_vars[$group])) unset(static::$_vars[$group][$var]);
		}

		protected static function js() {
			$files = include(APP_PATH . "config/js.php");
			JS::headerFile($files['header']);
			JS::footerFile($files['footer']);
		}
	}

?>