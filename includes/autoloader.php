<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 7:27 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Autoloader {
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
			$path = explode('_', strtolower($class));
			$class = array_pop($path);
			$path = realpath(APP_PATH . 'includes' . DS . implode(DS, $path) . DS . $class . '.php');
			try {
				include $path;
			} catch (Adv_Exception $e 	 ) {
				throw new Adv_Exception('Could not load class ' . $className);
			}

		}
	}
