<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  use \User;

  /**

   */
  class Event {
  use \ADV\Core\Traits\Hook;

    /**
     * @var array all objects with methods to be run on shutdown
     */
    protected static $shutdown_objects = array();
    /**
     * @var bool Whether the request from the browser has finsihed
     */
    protected static $request_finsihed = FALSE;
    /**
     * @var array Events which occur after browser dissconnect which will be shown on next request
     */
    protected static $shutdown_events = array();
    /**
     * @var string id for cache handler to store shutdown events
     */
    protected static $shutdown_events_id;
    /**
     * @static

     */
    static public function init() {
      static::$shutdown_events_id = 'shutdown.events.' . User::i()->username;
      static::$shutdown_events = Cache::get(static::$shutdown_events_id);
      if (static::$shutdown_events) {
        while ($msg = array_pop(static::$shutdown_events)) {
          static::handle($msg[0], $msg[1], $msg[2]);
        }
      }
    }
    /**
     * @static
     *
     * @param string $message Error message
     *
     * @return bool
     */
    static public function error($message) {
      return static::handle($message, reset(debug_backtrace()), E_USER_ERROR);
    }
    /**
     * @static
     *
     * @param string $message
     *
     * @return bool
     */
    static public function notice($message) {
      return static::handle($message, @reset(debug_backtrace()), E_USER_NOTICE);
    }
    /**
     * @static
     *
     * @param string $message
     *
     * @return bool
     */
    static public function success($message) {
      return static::handle($message, reset(debug_backtrace()), E_SUCCESS);
    }
    /**
     * @static
     *
     * @param $message
     *
     * @return bool
     */
    static public function warning($message) {
      return static::handle($message, reset(debug_backtrace()), E_USER_WARNING);
    }
    /**
     * @static
     *
     * @param $message
     * @param $source
     * @param $type
     *
     * @return bool
     */
    static protected function handle($message, $source, $type) {
      if (static::$request_finsihed) {
        static::$shutdown_events[] = array($message, $source, $type);
      }
      else {
        $message = $message . '||' . $source['file'] . '||' . $source['line'];
        ($type == E_SUCCESS) ? Errors::handler($type, $message) : trigger_error($message, $type);
      }
      return ($type === E_SUCCESS || $type === E_USER_NOTICE);
    }
    /**
     * @static
     *
     * @param        $object
     * @param string $function
     * @param array  $arguments
     */
    static public function register_shutdown($object, $function = '_shutdown', $arguments = array()) {
      Event::registerHook('shutdown', $object, $function, $arguments);
    }
    /**
     * @static
     *
     * @param        $object
     * @param string $function
     * @param array  $arguments
     */
    static public function register_pre_shutdown($object, $function = '_shutdown', $arguments = array()) {
      Event::registerHook('pre_shutdown', $object, $function, $arguments);
    }
    /*** @static Shutdown handler */
    static public function shutdown() {
      Errors::process();
      // flush all output buffers (works also with exit inside any div levels)
      while (ob_get_level()) {
        ob_end_flush();
      }
      session_write_close();
      /** @noinspection PhpUndefinedFunctionInspection */
      fastcgi_finish_request();
      static::$request_finsihed = TRUE;
      try {
        static::fireHooks('shutdown');
      }
      catch (\Exception $e) {
        static::error('Error during post processing: ' . $e->getMessage());
      }
      if (count(static::$shutdown_events)) {
        Cache::set(static::$shutdown_events_id, static::$shutdown_events);
      }
    }
  }