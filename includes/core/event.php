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
		 * @param string $message Error message
		 */
		static function error($message) {
			$source = reset(debug_backtrace());
			trigger_error($message . '||' . $source['file'] . '||' . $source['line'], E_USER_ERROR);
		}
		/**
		 * @static
		 * @param string $message
		 */
		static function notice($message) {
			$source = reset(debug_backtrace());
			trigger_error($message . '||' . $source['file'] . '||' . $source['line'], E_USER_NOTICE);
		}
		/**
		 * @static
		 * @param string $message
		 */
		static function success($message) {
			$source = reset(debug_backtrace());
			Errors::handler(E_SUCCESS,$message. '||' . $source['file'] . '||' . $source['line']);
		}
		/**
		 * @static
		 * @param $message
		 */
		static function warning($message) {
			$source = reset(debug_backtrace());
			//Trigger appropriate error
			trigger_error($message . '||' . $source['file'] . '||' . $source['line'], E_USER_WARNING);
		}
		/**
		 * @static
		 * @param $object
		 */
		static function register_shutdown($object) {

			if (!in_array($object,static::$shutdown_objects)) {
				static::$shutdown_objects[] = $object;
			}
		}
		/*** @static Shutdown handler */
		static function shutdown() {

			Ajax::i();
			Errors::process();
			// flush all output buffers (works also with exit inside any div levels)
			while (ob_get_level()) {
				ob_end_flush();
			}

			/** @noinspection PhpUndefinedFunctionInspection */
		fastcgi_finish_request();
				foreach (static::$shutdown_objects as $object) {

					if (method_exists($object, '_shutdown')) {
					call_user_func($object . '::_shutdown');
				}
			}
		}
	}
