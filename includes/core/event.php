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
		protected static $request_finsihed = false;
		protected static $shutdown_events = array();
		protected static $shutdown_events_id;
		static function i() {
			static::$shutdown_events_id='shutdown.events.'.User::get()->username;
			$shutdown_events = Cache::get(static::$shutdown_events_id);
			if ($shutdown_events) {
				while ($msg = array_pop($shutdown_events)) {
					static::handle($msg[0], $msg[1], $msg[2]);
				}
			}
		}
		/**
		 * @static
		 *
		 * @param string $message Error message
		 */
		static function error($message) {
			static::handle($message, reset(debug_backtrace()), E_USER_ERROR);
		}
		/**
		 * @static
		 *
		 * @param string $message
		 */
		static function notice($message) {
			static::handle($message, reset(debug_backtrace()), E_USER_NOTICE);
		}
		/**
		 * @static
		 *
		 * @param string $message
		 */
		static function success($message) {
			static::handle($message, reset(debug_backtrace()), E_SUCCESS);
		}
		/**
		 * @static
		 *
		 * @param $message
		 */
		static function warning($message) {
			static::handle($message, reset(debug_backtrace()), E_USER_WARNING);
		}
		protected static function handle($message, $source, $type) {
			if (static::$request_finsihed) {
				static::$shutdown_events[] = array($message, $source, $type);
			}
			else {
				$message = $message . '||' . $source['file'] . '||' . $source['line'];
				($type == E_SUCCESS) ? Errors::handler($type, $message) : trigger_error($message, $type);
			}
		}
		/**
		 * @static
		 *
		 * @param $object
		 */
		static function register_shutdown($object) {
			if (!in_array($object, static::$shutdown_objects)) {
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
			session_write_close();
			/** @noinspection PhpUndefinedFunctionInspection */
			fastcgi_finish_request();
			static::$request_finsihed = true;
			foreach (static::$shutdown_objects as $object) {
				try {
					if (method_exists($object, '_shutdown')) {
						call_user_func($object . '::_shutdown');
					}
				}
				catch (Exception $e) {
					static::error('Error during post processing: ' . $e->getMessage());
				}
			}

			Cache::set(static::$shutdown_events_id,static::$shutdown_events);
			if (extension_loaded('xhprof')) {
				$profiler_namespace = $_SERVER["SERVER_NAME"]; // namespace for your application
				$xhprof_data = xhprof_disable();
				$xhprof_runs = new XHProfRuns_Default();
				$xhprof_runs->save_run($xhprof_data, $profiler_namespace);
			}
		}
	}

