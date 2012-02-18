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

	 */
	class Autoloader
	{
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

		 */
		static function i() {
			ini_set('unserialize_callback_func', 'Autoloader::load'); // set your callback_function
			spl_autoload_register('Autoloader::loadCore', true);
			static::$classes = Cache::get('autoload.classes');
			static::$loaded = Cache::get('autoload.paths');
			if (!static::$classes) {
				$core = include(DOCROOT . 'config' . DS . 'core.php');
				$vendor = include(DOCROOT . 'config' . DS . 'vendor.php');
				static::add_classes((array)$core, COREPATH);
				static::add_classes((array)$vendor, VENDORPATH);
				Event::register_shutdown(__CLASS__);
			}
			spl_autoload_register('Autoloader::loadVendor', true, true);
			spl_autoload_register('Autoloader::loadApp', true, true);
			spl_autoload_register('Autoloader::loadInterface', true, true);
			spl_autoload_register('Autoloader::loadModule', true, true);
			spl_autoload_register('Autoloader::loadFromCache', true, true);
		}
		static function load($classname) {
			class_exists($classname);
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
		 *
		 * @param $path
		 *
		 * @return string
		 */
		static protected function tryPath($paths, $classname) {
			$paths = (array)$paths;
			while ($path = array_shift($paths)) {
				$filepath = realpath($path);
				if ($filepath) {
					return static::includeFile($filepath, $classname);
				}
			}
			if (isset(static::$loaded[$classname])) {
				unset (static::$loaded[$classname]);
			}
			static::$classes = false;
			Cache::delete('autoload.classes');
			return false;
		}
		static public function loadFromCache($classname) {
			$result = false;
			if (isset(static::$loaded[$classname])) {
				$result = static::tryPath(static::$loaded[$classname], $classname);
			}
			elseif (isset(static::$classes[$classname])) {
				$result = static::tryPath(static::$classes[$classname], $classname);
			}
			if (!$result) {
				Event::register_shutdown(__CLASS__);
			}
			return $result;
		}
		static public function loadModule($classname) {
			if (strpos($classname, 'Modules') === false) {
				return false;
			}
			$class = explode("\\", $classname);
			$mainclass = array_pop($class);
			$class[] = (count($class) > 1) ? 'classes' : $mainclass;
			$class[] = $mainclass;
			$class = implode(DS, $class);
			return static::trypath(DOCROOT . $class . '.php', $classname);
		}
		static public function loadInterface($classname) {
			$class = str_replace('_', DS, $classname);
			if (substr($class, 0, 1) != 'I') {
				return false;
			}
			return static::trypath(APPPATH . 'interfaces' . DS . substr($class, 1) . '.php', $classname);
		}
		static public function loadApp($classname) {
			$class = str_replace('_', DS, $classname);
			$lowerclass = strtolower($class);
			$paths[] = APPPATH . $class . '.php';
			$paths[] = APPPATH . $lowerclass . '.php';
			$paths[] = APPPATH . $class . DS . $class . '.php';
			$paths[] = APPPATH . $lowerclass . DS . $lowerclass . '.php';
			return static::trypath($paths, $classname);
		}
		static public function loadVendor($classname) {
			$class = str_replace('_', DS, $classname);
			$lowerclass = strtolower($class);
			$paths[] = VENDORPATH . $class . '.php';
			$paths[] = VENDORPATH . $lowerclass . '.php';
			$paths[] = VENDORPATH . $class . DS . $class . '.php';
			$paths[] = VENDORPATH . $lowerclass . DS . $lowerclass . '.php';
			return static::trypath($paths, $classname);
		}
		static public function loadCore($classname) {
			$class = str_replace('_', DS, $classname);
			$lowerclass = strtolower($class);
			$paths[] = COREPATH . $class . '.php';
			$paths[] = COREPATH . $lowerclass . '.php';
			return static::tryPath($paths, $classname);
		}
		/**
		 * @static
		 *
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
				if ($class != 'Cache' && $class != 'Event') {
					Event::register_shutdown(__CLASS__);
				}
			}
			//	static::$loadperf[$class] = array($class, memory_get_usage(true), microtime(true) - static::$time, microtime(true) - ADV_START_TIME);
			return true;
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
			if (static::$classes) {
				Cache::set('autoload.classes', static::$classes);
			}
			Cache::set('autoload.paths', static::$loaded);
		}
	}

	Autoloader::i();
