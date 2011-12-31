<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 7:27 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Autoload_Exception extends Exception
	{
	}

	/**
	 *
	 */
	class Autoloader
	{
		/**
		 * @var array
		 */
		protected static $loaded = array();
		/**
		 * @var array
		 */
		protected static $loadperf = array();
		/**
		 * @var int
		 */
		protected static $time = 0;
		/**
		 * @var array
		 */
		protected static $classes = array();

		/**
		 * @static
		 *
		 */
		static function i() {
			ini_set('unserialize_callback_func', 'Autoloader::load'); // set your callback_function
			spl_autoload_register('Autoloader::load',true,true);
			static::$classes = Cache::get('autoload.classes');
			if (!static::$classes) {
				$core = include(DOCROOT . 'config' . DS . 'core.php');
				$vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
				static::add_core_classes($core);
				static::add_vendor_classes($vendor);
				Cache::set('autoload.classes', static::$classes);
			}
			static::$loaded = Cache::get('autoload.paths');
			if (!static::$loaded) {
				static::$loaded = array();
			}
			static::$loaded = Cache::get('autoload.paths');
			if (!static::$loaded) {
				static::$loaded = array();
			}
		}

		/**
		 * @static
		 *
		 * @param array $path
		 */
		static function add_path($path = array()) {
			$path = (array)$path;
			$path[] .= get_include_path();
			set_include_path(implode(PATH_SEPARATOR, $path));
		}

		/**
		 * @static
		 *
		 * @param array $classes
		 */
		static function add_core_classes(array $classes) {
			static::add_classes((array)$classes, COREPATH);
		}

		/**
		 * @static
		 *
		 * @param array $classes
		 * @param			 $type
		 */
		protected static function add_classes(array $classes, $type) {
			$classes = array_flip(array_diff_key(array_flip($classes), (array)static::$loaded));
			foreach ($classes as $dir => $class) {
				if (!is_string($dir)) $dir='';
				static::$classes[$class] = $type . $dir.DS.str_replace('_', DS, $class) . '.php';
			}
		}

		/**
		 * @static
		 *
		 * @param array $classes
		 */
		static function add_vendor_classes(array $classes) {
			static::add_classes((array)$classes, VENDORPATH);
		}

		/**
		 * @static
		 *
		 * @param $path
		 *
		 * @return string
		 */
		protected static function tryPath($path) {
			$filepath = realpath(str_replace(strtolower(DOCROOT), DOCROOT, strtolower($path)));
			if (empty($filepath)) {
				$filepath = realpath($path);
			}
			return $filepath;
		}

		/**
		 * @static
		 *
		 * @param $classname
		 *
		 * @internal param $class
		 *
		 * @return bool|void
		 * @throws Autoload_Exception
		 */
		public static function load($classname) {
			static::$time = microtime(true);
			$class = $classname;
			if (isset(static::$loaded[$class])) {
				$path = static::$loaded[$class];
				$filepath = static::tryPath($path);
				if ($filepath) {
					return static::includeFile($filepath, $classname);
				}
			}
			if (isset(static::$classes[$class])) {
				$path = static::$classes[$class];
				$filepath = static::tryPath($path);
				if ($filepath) {
					return static::includeFile($filepath, $classname);
				}
			}
			$class = str_replace('_', DS, $class);
			if (substr($class, 0, 1) == 'I') {
				$path = APPPATH . 'interfaces' . DS . substr($class, 1) . '.php';
				$filepath = static::tryPath($path);
				if ($filepath) {
					return static::includeFile($filepath, $classname);
				}
			}
			$path = APPPATH . $class . '.php';
			$filepath = static::tryPath($path);
			if ($filepath) {
				return static::includeFile($filepath, $classname);
			}
			$path = APPPATH . $class . DS . $class . '.php';
			$filepath = static::tryPath($path, $class);
			if ($filepath) {
				return static::includeFile($filepath, $classname);
			}
			$path = COREPATH . $class . '.php';
			$filepath = static::tryPath($path);
			if ($filepath) {
				return static::includeFile($filepath, $classname);
			}					throw new Autoload_Exception('File for class ' . $class . ' does not exist here: ' . static::$classes[$class]);

			return false;
		}

		/**
		 * @static
		 *
		 * @param $filepath
		 * @param $class
		 *
		 * @throws Autoload_Exception
		 */
		protected static function includeFile($filepath, $class) {
			try {
				if (empty($filepath)) {
					throw new Autoload_Exception('File for class ' . $class . ' does not exist here: ' . $filepath);
				}
				/** @noinspection PhpIncludeInspection */
				if (!include($filepath)) {
					throw new Autoload_Exception('File for class ' . $class . ' cannot be	loaded from : ' . $filepath);
				}
				static::$loaded[$class] = $filepath;
				static::$loadperf[$class] = array(
					$class, memory_get_usage(true), microtime(true) - static::$time, microtime(true) - ADV_START_TIME);
			}
			catch (Autoload_Exception $e) {
				Errors::exception_handler($e);
			}
		}

		/**
		 * @static
		 * @return array
		 */
		public static function getPerf() {
			array_walk(static::$loadperf, function(&$v) {
				$v[1] = Files::convert_size($v[1]);
				$v[2] = Dates::getReadableTime($v[2]);
				$v[3] = Dates::getReadableTime($v[3]);
			});
			return static::$loadperf;
		}

		/**
		 * @static
		 * @return array
		 */
		public static function getLoaded() {
			return static::$loaded;
		}
	}

	Autoloader::i();