<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 5/08/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	/***
	 *
	 */
	class Config_Exception extends Exception
	{
	}

	/***
	 *
	 */
	class Config
	{
		/***
		 * @var array|null
		 */
		static $_vars = null;
		/**
		 * @var bool
		 */
		static protected $i = false;
		/**
		 * @static
		 * @return mixed
		 */
		static public function i() {
			if (static::$i === true) {
				return;
			}
			if (static::$_vars === null) {
				static::$_vars = Cache::get('config');
			}
			if (static::$_vars === false || isset($_GET['reload_config'])) {
				static::$_vars = array();
				static::load();
				Event::register_shutdown(__CLASS__);
			}
			static::$i = true;
			static::js();
		}
		/**
		 * @static
		 *
		 * @param string $group
		 *
		 * @return mixed
		 * @throws Config_Exception
		 */
		static protected function load($group = 'config') {
			if (is_array($group)) {
				$group_name = implode('.', $group);
				$group_file = array_pop($group) . '.php';
				$group_path = implode(DS, $group);
				$file = DOCROOT . "config" . $group_path . DS . $group_file;
			}else {
				$file = DOCROOT . "config" . DS . $group . '.php';
							$group_name = $group;
			}
			if (static::$_vars && array_key_exists($group_name, static::$_vars)) {
				return;
			}
			if (!file_exists($file)) {
				throw new Config_Exception("There is no file for config: " . $file);
			}
			/** @noinspection PhpIncludeInspection */
			static::$_vars[$group_name] = include($file);
			Event::register_shutdown(__CLASS__);
		}
		/**
		 * @static
		 *
		 * @param        $var
		 * @param        $value
		 * @param string $group
		 *
		 * @return mixed
		 */
		static public function set($var, $value, $group = 'config') {
			static::$_vars[$group][$var] = $value;
			return $value;
		}
		/***
		 * @static
		 *
		 * @param      $var
		 * @param null $array_key
		 * @param null $group
		 *
		 * @return mixed
		 */
		static public function get($var, $array_key = null, $group = null) {
			static::i();
			if (!strstr($var, '.')) {
				$group = 'config';
			}
			if ($group != null) {
				$var = $group . '.' . $var;
			}
			$group_array = explode('.', $var);
			$var = array_pop($group_array);
			$group = implode('.', $group_array);
			(isset(static::$_vars[$group])) or static::load($group_array);
			if ($var === null && $array_key === null) {
				return static::get_all($group);
			}
			if (!isset(static::$_vars[$group][$var])) {
				return false;
			}
			return ($array_key !== null && is_array(static::$_vars[$group][$var])) ? static::$_vars[$group][$var][$array_key] :
			 static::$_vars[$group][$var];
		}
		/**
		 * @static
		 *
		 * @param        $var
		 * @param string $group
		 */
		static public function remove($var, $group = 'config') {
			if (array_key_exists($var, static::$_vars[$group])) {
				unset(static::$_vars[$group][$var]);
			}
		}
		/**
		 * @static
		 *
		 */
		static public function _shutdown() {
			Cache::set('config', static::$_vars);
		}
		/**
		 * @staticx
		 *
		 * @param string $group
		 *
		 * @return mixed
		 */
		static public function get_all($group = 'config') {
			static::i();
			(isset(static::$_vars[$group])) or static::load($group);
			return static::$_vars[$group];
		}
		/**
		 * @static
		 *
		 */
		static protected function js() {
			JS::headerFile(static::get('assets.header'));
			JS::footerFile(static::get('assets.footer'));
		}
	}

?>
