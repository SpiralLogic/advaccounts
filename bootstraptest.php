<?php
  /**
   * PHP version 5.4
  \   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  define('E_SUCCESS', E_ALL << 1);
  define('DS', DIRECTORY_SEPARATOR);
  define('DOCROOT', __DIR__ . DS);
  define('APPPATH', DOCROOT . 'classes' . DS . 'app' . DS);
  define('COREPATH', DOCROOT . 'classes' . DS . 'core' . DS);
  define('VENDORPATH', DOCROOT . 'classes' . DS . 'vendor' . DS);
  define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
  define('IS_JSON_REQUEST', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));
  define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
  define('CRLF', chr(13) . chr(10));


  require COREPATH . 'autoloader.php';
  if (!function_exists('e')) {
    /**
     * @param $string
     *
     * @return array|string
     */
     function e($string) { return Security::htmlentities($string); }
  }
  register_shutdown_function(function () {
    class_exists('Event', false) or  include(COREPATH . 'event.php');
    Event::shutdown();
  });
  if (!function_exists('adv_ob_flush_handler')) {
    /**
     * @param $text
     *
     * @return string
     * @noinspection PhpUnusedFunctionInspection
     */
     function adv_ob_flush_handler($text)
    {
      return (Ajax::in_ajax()) ? Errors::format() : Errors::$before_box . Errors::format() . $text;
    }
  }
