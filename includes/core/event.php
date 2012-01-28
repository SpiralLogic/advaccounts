<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 26/12/11
	 * Time: 6:39 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Event
	{
		protected static $shutdown_objects = array();
		/**
		 * @static
		 *
		 * @param $message Error message
		 */
		static function error($message) {
			//Get the caller of the calling function and details about it
			$source = next(debug_backtrace());
			//Trigger appropriate error
			trigger_error($message . '||' . $source['file'] . '||' . $source['line'] . '||', E_USER_ERROR);
		}
		/**
		 * @static
		 *
		 * @param $msg Notice message
		 */
		static function notice($message) {
			$source = next(debug_backtrace());
			//Trigger appropriate error
			trigger_error($message . '||' . $source['file'] . '||' . $source['line'] . '||', E_USER_NOTICE);
		}
		/**
		 * @static
		 *
		 * @param $msg Warning message
		 */
		static function warning($message) {
			$source = next(debug_backtrace());
			//Trigger appropriate error
			trigger_error($message . '||' . $source['file'] . '||' . $source['line'] . '||', E_USER_WARNING);
		}
		static function register_shutdown($object) {
			static::$shutdown_objects[] = $object;
		}
		/**
		 * @static Shutdown handler
		 *
		 */
		static function shutdown() {
			ob_end_flush();
			exit("running");
			$Ajax = Ajax::i();
			Errors::process();
			// flush all output buffers (works also with exit inside any div levels)
			while (ob_get_level()) {
				ob_end_flush();
			}
			fastcgi_finish_request();
			foreach (static::$shutdown_objects as $object) {
				if (method_exists($object, '_shutdown')) {
					call_user_func($object . '::_shutdown');
				}
			}
		}
	}
