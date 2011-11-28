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
		protected static $classes = array();

		static function init()
			{
				ini_set('unserialize_callback_func', 'adv_autoload_handler'); // set your callback_function
				spl_autoload_register(array(__CLASS__, 'includeClass'));

			}

		static function add_path($path = array())
			{
				$path = (array)$path;
				$path[] .= get_include_path();
				set_include_path(implode(PATH_SEPARATOR, $path));
			}

		static function add_core_classes(array $classes)
			{
				static::add_classes((array)$classes, COREPATH);
			}

		protected static function add_classes(array $classes, $type)
			{
				foreach ($classes as $class) {
					static::$classes[strtolower($class)] = $type . static::classpath($class);
				}
			}

		static function add_vendor_classes(array $classes)
			{
				static::add_classes((array)$classes, VENDORPATH);
			}

		protected static function classpath($class)
			{
				$path = explode('_', $class);
				$classfile = DS . array_pop($path) . '.php';
				return implode(DS, $path) . $classfile;
			}

		protected static function tryPath($path)
			{
				$filepath = realpath(strtolower($path));
				if (empty($filepath)) {
					$filepath = realpath($path);
				}
				return $filepath;
			}

		public static function includeClass($class)
			{
				if (isset(static::$classes[strtolower($class)])) {
					$path = static::$classes[strtolower($class)];
				} else {
					$path = APPPATH . static::classpath($class);
				}
				$filepath = static::tryPath($path);
				if (!$filepath) {
					$path = COREPATH . static::classpath($class);
					$filepath = static::tryPath($path);
				}
				if (empty($filepath)) {
					throw new Autoload_Exception('File for class ' . $class . ' does not exist here: ' . $path);
				}
				/** @noinspection PhpIncludeInspection */
				if (!include($filepath)) {
					throw new Autoload_Exception('Could not load class ' . $class);
				}

				static::$loaded[$class] = array($class,$filepath, memory_get_usage(true), microtime(true));
			}

		public static function getLoaded()
			{
				array_walk(static::$loaded, function(&$v)
					{
						$v[1] = Files::convert_size($v[2]);
						$v[2] = Dates::getReadableTime($v[3] - ADV_START_TIME);
					});
				return static::$loaded;
			}
		public static function setLoaded(array $loaded) {
			foreach ($loaded as $class) {
				static::$loaded[$class[0]]=$class;
			}
		}
	}

	Autoloader::init();

