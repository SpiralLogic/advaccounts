<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 5/08/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */

	include_once(APP_PATH . "config/defines.php");
	class Config {

		static $_vars = null;
		protected static $_intitalised = false;

		public static function init() {
			if (static::$_intitalised === true) return;
			if (static::$_vars === null) {
				static::$_vars = Input::session('Config');
			}
			if (static::$_vars === false) {
				static::$_vars = array();
				static::load();
				if (count(static::$_vars['config']['config.onload']) > 0) call_user_func_array('Config::load', static::$_vars['config']['config.onload']);
			}
			static::js();
			static::$_intitalised = true;
		}

		public static function load() {
			$group = func_get_args();
			if (count($group) == 0) $group = array('config');
			foreach ($group as $file) {
				if (array_key_exists($file, static::$_vars)) continue;
				static::$_vars[$file] = include(APP_PATH . "config/{$file}.php");
			}
		}

		public static function set($var, $value, $group = 'config') {
			if (static::$_vars === null) static::init();
			static::$_vars[$group][$var] = $value;
			return $value;
		}

		public static function get($var, $array_key = null, $group = 'config') {
			if (static::$_vars === null) static::init();
			if ($var === null && $array_key === null) return static::_getGroup($group);
			if (!isset(static::$_vars[$group][$var])) return false;
			return ($array_key !== null && is_array(static::$_vars[$group][$var])) ?
			 static::$_vars[$group][$var][$array_key] : static::$_vars[$group][$var];
		}

		public static function remove($var, $group = 'config') {
			if (array_key_exists($var, static::$_vars[$group])) unset(static::$_vars[$group][$var]);
		}

		public static function store() {
			$_SESSION['Config'] = static::$_vars;
		}

		protected static function _getGroup($group = 'config') {
			static::load($group);
			return static::$_vars[$group];
		}

		protected static function js() {
			$files = static::$_vars['js'];
			JS::headerFile($files['header']);
			JS::footerFile($files['footer']);
		}
	}

?>