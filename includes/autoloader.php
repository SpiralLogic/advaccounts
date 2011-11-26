<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 7:27 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Autoload_Exception extends Exception {
	}

	;
	class Autoloader {
		protected static $loaded = array();

		static function init() {
			ini_set('unserialize_callback_func', 'adv_autoload_handler'); // set your callback_function
			spl_autoload_register(array(__CLASS__, 'includeClass'));
		}

		static function add_path($path = array()) {
			$path = (array)$path;
			$path[] .= get_include_path();
			set_include_path(implode(PATH_SEPARATOR, $path));
		}

		public static function includeClass($class) {

			$className = $class;
			$path = explode('_', $class);
			$class = array_pop($path);
			$filepath = realpath(APP_PATH . 'includes' . DS . implode(DS, $path) . DS . $class . '.php');
			if (empty($filepath)) $filepath = realpath(strtolower(APP_PATH . 'includes' . DS . implode(DS, $path) . DS . $class) . '.php');
			if (empty($filepath)) throw new Autoload_Exception('File for class ' . $className . ' does not exist here: ' . $path);
			/** @noinspection PhpIncludeInspection */
			if (!include($filepath)) throw new Autoload_Exception('Could not load class ' . $className);
			static::$loaded[$className] = array($className, memory_get_usage(true), microtime(true));
		}

		public static function getLoaded() {
			array_walk(static::$loaded, function(&$v) {

				$v[1] = Files::convert_size($v[1]);
				$v[2] = Dates::getReadableTime($v[2] - ADV_START_TIME);
			});
			return static::$loaded;
		}
	}

	Autoloader::init();

