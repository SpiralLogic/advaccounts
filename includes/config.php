<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 5/08/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */

	include_once(APP_PATH . "config/defines.php");
	include_once(APP_PATH . "config/access_levels.php");
	include_once(APP_PATH . "config/types.php");
	ini_set("ignore_repeated_errors", "On");
	ini_set("log_errors", "On");
	class Config {

		static $_vars = null;
		protected static $_intitalised = false;

		public static function init() {

			if (static::$_intitalised === true) return;
			if (static::$_vars === null) {
				static::$_vars = Input::session('config');
			}
			if (static::$_vars === false) {
				static::$_vars = array();
				static::load();
			}
			static::$_intitalised = true;
			static::js();
		}

		protected static function load() {
			$group = func_get_args();
			if (count($group) == 0) $group = array('config');
			foreach ($group as $file) {
				$filepath = APP_PATH . "config/{$file}.php";
				if (!file_exists($filepath)) throw new Adv_Exception("There is no file for this config");
				if (array_key_exists($file, static::$_vars)) continue;
				static::$_vars[$file] = include($filepath);
			}
		}

		public static function set($var, $value, $group = 'config') {
			static::init();
			static::$_vars[$group][$var] = $value;
			return $value;
		}

		public static function get($var, $array_key = null, $group = 'config') {
			static::init();
			if (!isset(static::$_vars[$group])) static::load($group);
			if ($var === null && $array_key === null) return static::get_all($group);

			if (!isset(static::$_vars[$group][$var])) return false;
			return ($array_key !== null && is_array(static::$_vars[$group][$var])) ?
			 static::$_vars[$group][$var][$array_key] : static::$_vars[$group][$var];
		}

		public static function remove($var, $group = 'config') {
			if (array_key_exists($var, static::$_vars[$group])) unset(static::$_vars[$group][$var]);
		}

		public static function store() {
			$_SESSION['config'] = static::$_vars;
		}

		public static function get_all($group = 'config') {
			static::init();
			if (!isset(static::$_vars[$group])) static::load($group);
			return static::$_vars[$group];
		}

		protected static function js() {
			$files = static::get_all('js');
			JS::headerFile($files['header']);
			JS::footerFile($files['footer']);
		}
	}

?>