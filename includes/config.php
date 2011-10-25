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
				static::$_vars = Input::session('Config');
			}
			if (static::$_vars === false) {
				static::$_vars = array();
				static::load();
				if (count(static::$_vars['config']['config.onload']) > 0) call_user_func_array('Config::load', static::$_vars['config']['config.onload']);
			}
			static::js();
		}

		protected static function load($group='config') {

				$file=APP_PATH."config".DS.$group.'.php';
				if (is_array($group)) {
					$groupfile = array_pop($group) . '.php';
					$grouppath = implode(DS, $group);
					$file = APP_PATH . "config".$grouppath.DS.$groupfile;
					}
				if (!is_string($group)) return;
				if (array_key_exists($group, static::$_vars)) return;
				if (!file_exists($file)) throw new Adv_Exception("There is no file for config: ".$file);
				static::$_vars[$group] = include($file);
			}


		public static function set($var, $value, $group = 'config') {
			if (static::$_vars === null) static::init();
			static::$_vars[$group][$var] = $value;
			return $value;
		}

		public static function get($var, $array_key = null, $group = null) {
			static::init();

			if ($group!=null) $var = $group.'.'.$var;
			$grouparray= explode('.', $var);
			$var = array_pop($grouparray);
			$group=implode('.',$grouparray);
			if (!isset(static::$_vars[$group])) static::load($grouparray);
			if ($var === null && $array_key === null) return static::get_all($group);
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