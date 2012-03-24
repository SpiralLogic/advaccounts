<?php
	/**
	 * PHP version 5.4
	 * @category  PHP
	 * @package   ADVAccounts
	 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
	 * @copyright 2010 - 2012
	 * @link      http://www.advancedgroup.com.au
	 **/

	class EventException extends \Exception {

	}

	class Event {

		/**
		 * @var array all objects with methods to be run on shutdown
		 */
		protected static $shutdown_objects = array();
		/**
		 * @var bool Whether the request from the browser has finsihed
		 */
		protected static $request_finsihed = false;
		/**
		 * @var array Events which occur after browser dissconnect which will be shown on next request
		 */
		protected static $shutdown_events = array();
		/**
		 * @var string id for cache handler to store shutdown events
		 */
		protected static $shutdown_events_id;
		/** @var Hooks */
		protected static $hooks = null;
		/**
		 * @static

		 */
		static public function i() {
			static::$shutdown_events_id = 'shutdown.events.' . User::i()->username;
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
		static public function error($message) {
			static::handle($message, reset(debug_backtrace()), E_USER_ERROR);
		}
		/**
		 * @static
		 *
		 * @param string $message
		 */
		static public function notice($message) {
			static::handle($message, @reset(debug_backtrace()), E_USER_NOTICE);
		}
		/**
		 * @static
		 *
		 * @param string $message
		 */
		static public function success($message) {
			static::handle($message, reset(debug_backtrace()), E_SUCCESS);
		}
		/**
		 * @static
		 *
		 * @param $message
		 */
		static public function warning($message) {
			static::handle($message, reset(debug_backtrace()), E_USER_WARNING);
		}
		/**
		 * @static
		 *
		 * @param $message
		 * @param $source
		 * @param $type
		 */
		static protected function handle($message, $source, $type) {
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
		static public function register_shutdown($object, $function = '_shutdown', $arguments = array()) {
			if (static::$hooks === null) {
				static::$hooks = new Hooks();
			}

			$callback = $object . '::' . $function;
			if (!is_callable($callback)) {
				throw new EventException("Class $object doesn't have a callable function $function");
			}
			static::$hooks->add('shutdown', $callback, $arguments);
		}
		/*** @static Shutdown handler */
		static public function shutdown() {
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
			try {
				static::$hooks->fire('shutdown');
			}
			catch (Exception $e) {
				static::error('Error during post processing: ' . $e->getMessage());
			}
			Cache::set(static::$shutdown_events_id, static::$shutdown_events);
			if (extension_loaded('xhprof')) {
				$profiler_namespace = $_SERVER["SERVER_NAME"]; // namespace for your application
				$xhprof_data = xhprof_disable();
				$xhprof_runs = new XHProfRuns_Default();
				$xhprof_runs->save_run($xhprof_data, $profiler_namespace);
			}
		}
	}

