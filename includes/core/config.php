<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 5/08/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include(DOCROOT . "config/defines.php");
	ini_set("ignore_repeated_errors", "On");
	ini_set("log_errors", "On");
/***
 *
 */
	class Config_Exception extends Exception { }
/***
 *
 */
	class Config
	{
		/***
		 * @var array|null
		 */
		static $_vars = null;
		protected static $_initialised = false;

		public static function init()
		{
			if (static::$_initialised === true) {
				return;
			}
			if (static::$_vars === null) {
				static::$_vars = Input::session('config');
			}
			if (static::$_vars === false || Input::get('reload_config')) {
				static::$_vars = array();
				static::load();
			}
			static::$_initialised = true;
			static::js();
		}

		protected static function load($group = 'config')
		{
			$file = DOCROOT . "config" . DS . $group . '.php';
			$group_name = $group;
			if (is_array($group)) {
				$group_name = implode('.', $group);
				$group_file = array_pop($group) . '.php';
				$group_path = implode(DS, $group);
				$file = DOCROOT . "config" . $group_path . DS . $group_file;
			}
			if (static::$_vars && array_key_exists($group_name, static::$_vars)) {
				return;
			}
			if (!file_exists($file)) {
				throw new Config_Exception("There is no file for config: " . $file);
			}
			/** @noinspection PhpIncludeInspection */
			static::$_vars[$group_name] = include($file);
		}

		public static function set($var, $value, $group = 'config')
		{
			static::$_vars[$group][$var] = $value;
			return $value;
		}
/***
 * @static
 * @param $var
 * @param null $array_key
 * @param null $group
 * @return mixed
 */
		public static function get($var, $array_key = null, $group = null)
		{
			static::init();
			if (!strstr($var, '.')) {
				$group = 'config';
			}
			if ($group != null) {
				$var = $group . '.' . $var;
			}
			$group_array = explode('.', $var);
			$var = array_pop($group_array);
			$group = implode('.', $group_array);
			if (!isset(static::$_vars[$group])) {
				static::load($group_array);
			}
			if ($var === null && $array_key === null) {
				return static::get_all($group);
			}
			if (!isset(static::$_vars[$group][$var])) {
				return false;
			}
			return ($array_key !== null && is_array(static::$_vars[$group][$var])) ? static::$_vars[$group][$var][$array_key] : static::$_vars[$group][$var];
		}

		public static function remove($var, $group = 'config')
		{
			if (array_key_exists($var, static::$_vars[$group])) {
				unset(static::$_vars[$group][$var]);
			}
		}

		public static function store()
		{
			$_SESSION['config'] = static::$_vars;
		}

		public static function get_all($group = 'config')
		{
			static::init();
			if (!isset(static::$_vars[$group])) {
				static::load($group);
			}
			return static::$_vars[$group];
		}

		protected static function js()
		{
			JS::headerFile(static::get('assets.header'));
			JS::footerFile(static::get('assets.footer'));
		}
	}

?>