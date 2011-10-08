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
			spl_autoload_extensions('.php,.inc');
			spl_autoload_register('adv_autoload_handler');

			self::add_path(
				array(
					realpath('.') . '/includes/classes',
					APP_PATH . 'includes/ui2',
					APP_PATH . 'includes/ui',
					APP_PATH . 'includes',
					APP_PATH . 'includes/classes',
					APP_PATH . 'includes/classes/db',
					APP_PATH . 'contacts/includes/classes',
					APP_PATH . 'items/includes/classes',
					APP_PATH . 'sales/includes',
					APP_PATH . 'purchasing/includes',
					APP_PATH . 'reporting/includes'
				));
		}

		static function add_path($path = array()) {
			$path = (array)$path;
			$path[] .= get_include_path();
			set_include_path(implode(PATH_SEPARATOR, $path));
		}
	}
