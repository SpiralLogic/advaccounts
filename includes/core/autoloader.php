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

	/**
	 *
	 */
	class Autoloader {
		/**
		 * @var array
		 */
		static protected $loaded = array();
		/**
		 * @var array
		 */
		static protected $loadperf = array();
		/**
		 * @var int
		 */
		static protected $time = 0;
		/**
		 * @var array
		 */
		static protected $classes = array();

		/**
		 * @static
		 *
		 */
		static function i() {
			ini_set('unserialize_callback_func', 'Autoloader::load'); // set your callback_function
			spl_autoload_register('Autoloader::load', true, true);
			static::$classes = Cache::get('autoload.classes');
			static::$loaded = Cache::get('autoload.paths');
			if (!static::$classes) {
				$core = include(DOCROOT . 'config' . DS . 'core.php');
				$vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
				static::add_classes((array)$core, COREPATH);
				static::add_classes((array)$vendor, VENDORPATH);
				Event::register_shutdown(__CLASS__);
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
		 * @param			 $type
		 */
		static protected function add_classes(array $classes, $type) {
			$classes = array_flip(array_diff_key(array_flip($classes), (array)static::$loaded));
			foreach ($classes as $dir => $class) {
				if (!is_string($dir)) {
					$dir = '';
				}
				static::$classes[$class] = $type . $dir . str_replace('_', DS, $class) . '.php';
			}
		}

		/**
		 * @static
		 * @param $path
		 * @return string
		 */
		static protected function tryPath($paths, $classname) {
			$paths = (array)$paths;
			while ($path = array_shift($paths)) {
				$filepath = realpath(str_replace(strtolower(DOCROOT), DOCROOT, strtolower($path)));
				if (empty($filepath)) {
					$filepath = realpath($path);
				}
				if ($filepath) {
					return static::includeFile($filepath, $classname);
				}
			}
			return false;
		}

		static public function load($classname) {
			try {
				if (isset(static::$loaded[$classname])) {
					return static::tryPath(static::$loaded[$classname], $classname);
				}
			}
			catch (Autoload_Exception $e) {
				Event::register_shutdown(__CLASS__);
			}
			try {
				static::findFile($classname);
			}
			catch (Autoload_Exception $e) {
				return Errors::exception_handler($e);
			}
		}

		/**
		 * @static
		 * @param $classname
		 * @internal param $class
		 * @return bool|void
		 * @throws Autoload_Exception
		 */
		static protected function findFile($classname) {
	//		static::$time = microtime(true);

			if (strpos($classname, 'Modules') !== false) {
				return static::loadModules($classname);
			}
			$class = $classname;
			if (isset(static::$classes[$class])) {
				$paths[] = static::$classes[$class];
			}
			$class = str_replace('_', DS, $class);
			if (substr($class, 0, 1) == 'I') {
				$paths[] = APPPATH . 'interfaces' . DS . substr($class, 1) . '.php';
			}
			$paths[] = APPPATH . $class . '.php';
			$paths[] = APPPATH . $class . DS . $class . '.php';
			$paths[] = COREPATH . $class . '.php';
			return static::tryPath($paths, $classname);
		}

		/**
		 * @static
		 * @param $filepath
		 * @param $class
		 *
		 * @throws Autoload_Exception
		 */
		static protected function includeFile($filepath, $class) {
			if (empty($filepath)) {
				throw new Autoload_Exception('File for class ' . $class . ' cannot be found!');
			}
			/** @noinspection PhpIncludeInspection */
			if (!include($filepath)) {
				throw new Autoload_Exception('File for class ' . $class . ' cannot be	loaded from : ' . $filepath);
			}
			if (!isset(static::$loaded[$class])) {
				static::$loaded[$class] = $filepath;
				if ($class != 'Cache' && $class != 'Event') Event::register_shutdown(__CLASS__);
			}
		//	static::$loadperf[$class] = array($class, memory_get_usage(true), microtime(true) - static::$time, microtime(true) - ADV_START_TIME);
			return true;
		}

		static protected function loadModules($classname) {
			$class = explode("\\", $classname);
			$mainclass = array_pop($class);
			$class[] = (count($class) > 1) ? 'classes' : $mainclass;
			$class[] = $mainclass;
			$class = implode(DS, $class);
			$filepath = static::trypath(array(DOCROOT . $class . '.php'), $classname);
			if (!$filepath) {
				throw new Autoload_Exception('Could not find module:' . $classname . ' here: ' . $class . '.php');
			}
		}

		/**
		 * @static
		 * @return array
		 */
		static public function getPerf() {
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
		static public function getLoaded() {
			return static::$loaded;
		}

		static public function _shutdown() {
			Cache::set('autoload.classes', static::$classes);
			Cache::set('autoload.paths', static::$loaded);
		}
	}

	Autoloader::i();
