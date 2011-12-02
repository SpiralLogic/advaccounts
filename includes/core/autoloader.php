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

	class Autoloader
	{
		protected static $loaded = array();
		protected static $loadperf = array();
		protected static $time = 0;
		protected static $classes = array();

		/**
		 * @static
		 *
		 */
		static function init()
			{
				ini_set('unserialize_callback_func', 'adv_autoload_handler'); // set your callback_function
				spl_autoload_register(array(__CLASS__, 'includeClass'));
				if (class_exists('Cache')) {
					static::$loaded = Cache::get('autoloads');
				}
			}

		/**
		 * @static
		 *
		 * @param array $path
		 */
		static function add_path($path = array())
			{
				$path = (array)$path;
				$path[] .= get_include_path();
				set_include_path(implode(PATH_SEPARATOR, $path));
			}

		/**
		 * @static
		 *
		 * @param array $classes
		 */
		static function add_core_classes(array $classes)
			{
				static::add_classes((array)$classes, COREPATH);
			}

		/**
		 * @static
		 *
		 * @param array $classes
		 * @param			 $type
		 */
		protected static function add_classes(array $classes, $type)
			{
				$classes = array_flip(array_diff_key(array_flip($classes), (array)static::$loaded));
						foreach ($classes as $class) {
					static::$classes[strtolower($class)] = $type . static::classpath($class);
				}
			}

		/**
		 * @static
		 *
		 * @param array $classes
		 */
		static function add_vendor_classes(array $classes)
			{
				static::add_classes((array)$classes, VENDORPATH);
			}

		/**
		 * @static
		 *
		 * @param $class
		 *
		 * @return string
		 */
		protected static function classpath($class)
			{
				$path = str_replace('_', DS,$class). '.php';

				return $path ;
			}

		/**
		 * @static
		 *
		 * @param $path
		 *
		 * @return string
		 */
		protected static function tryPath($path)
			{
				$filepath = realpath(strtolower($path));
				if (empty($filepath)) {
					$filepath = realpath($path);
				}
				return $filepath;
			}

		/**
		 * @static
		 *
		 * @param $class
		 *
		 * @throws Autoload_Exception
		 */
		public static function includeClass($class)
			{
				static::$time = microtime(true);
				if (isset(static::$loaded[$class])) {
					return static::load($class, static::$loaded[$class]);
				}
				if (isset(static::$classes[strtolower($class)])) {
					$path = static::$classes[strtolower($class)];
				} else {
					$classfile = static::classpath($class);
					$path = APPPATH . $classfile;
				}
				$filepath = static::tryPath($path);
				if (!$filepath) {
					$path = COREPATH . $classfile;
					$filepath = static::tryPath($path);
				}
				if (empty($filepath)) {
					throw new Autoload_Exception('File for class ' . $class . ' does not exist here: ' . $path);
				}
				return static::load($class, $filepath);
			}

		/**
		 * @static
		 *
		 * @param $class
		 * @param $path
		 *
		 * @throws Autoload_Exception
		 */
		protected static function load($class, $path)
			{
				/** @noinspection PhpIncludeInspection */
				if (!include($path)) {
					throw new Autoload_Exception('Could not load class ' . $class);
				}
				static::$loaded[$class] = $path;
		//		static::$loadperf[$class] = array($class, memory_get_usage(true), microtime(true) - static::$time, 		 microtime(true) - ADV_START_TIME);
			}

		/**
		 * @static
		 * @return array
		 */
		public static function getPerf()
			{
				array_walk(
					static::$loadperf, function(&$v)
						{
							$v[1] = Files::convert_size($v[1]);
							$v[2] = Dates::getReadableTime($v[2]);
							$v[3] = Dates::getReadableTime($v[3]);
						}
				);
				return static::$loadperf;
			}

		/**
		 * @static
		 * @return array
		 */
		public static function getLoaded()
			{
				return static::$loaded;
			}
	}

	Autoloader::init();