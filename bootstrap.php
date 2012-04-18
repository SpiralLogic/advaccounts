<?php
  /**
   * PHP version 5.4
  \   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (extension_loaded('xhprof')) {
    $XHPROF_ROOT = realpath(dirname(__FILE__) . '/xhprof');
    include_once $XHPROF_ROOT . "/xhprof_lib/config.php";
    include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
    include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
    /** @noinspection PhpUndefinedConstantInspection */
    /** @noinspection PhpUndefinedFunctionInspection */
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_NO_BUILTINS + XHPROF_FLAGS_MEMORY);
  }

  error_reporting(-1);
  ini_set('display_errors', 1);
  ini_set("ignore_repeated_errors", "On");
  ini_set("log_errors", "On");
  define('E_SUCCESS', E_ALL << 1);
  define('DS', DIRECTORY_SEPARATOR);
  define('DOCROOT', __DIR__ . DS);
  define('WEBROOT', DOCROOT . 'public' . DS);
  define('APPPATH', DOCROOT . 'classes' . DS . 'app' . DS);
  define('COREPATH', DOCROOT . 'classes' . DS . 'core' . DS);
  define('VENDORPATH', DOCROOT . 'classes' . DS . 'vendor' . DS);
  define('COMPANY_PATH', WEBROOT . 'company' . DS);
  define('LANG_PATH', DOCROOT . 'lang' . DS);
  define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
  define('IS_JSON_REQUEST', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== FALSE));
  define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
  define('CRLF', chr(13) . chr(10));
  set_error_handler(function ($severity, $message, $filepath, $line) {
    class_exists('ADV\\Core\\Errors', FALSE) or include_once COREPATH . 'errors.php';
    return ADV\Core\Errors::handler($severity, $message, $filepath, $line);
  });
  set_exception_handler(function (\Exception $e) {
    class_exists('ADV\\Core\\Errors', FALSE) or include_once COREPATH . 'errors.php';
    ADV\Core\Errors::exception_handler($e);
  });
  require COREPATH . 'autoloader.php';


  if ($_SERVER['DOCUMENT_URI'] !== '/assets.php') {
    if (!function_exists('e')) {
      /**
       * @param $string
       *
       * @return array|string
       */
      function e($string) { return Security::htmlentities($string); }
    }   register_shutdown_function(function () {
       ADV\Core\Event::shutdown();
     });
    if (!function_exists('adv_ob_flush_handler')) {
      /**
       * @param $text
       *
       * @return string
       * @noinspection PhpUnusedFunctionInspection
       */
      function adv_ob_flush_handler($text) {
        return (Ajax::i()->in_ajax()) ? Errors::format() : Errors::$before_box . Errors::format() . $text;
      }
    }
    Cache::define_constants('defines', function() {
      return include(DOCROOT . 'config' . DS . 'defines.php');
    });
    include(DOCROOT . 'config' . DS . 'types.php');
    include(DOCROOT . 'config' . DS . 'access_levels.php');
    Session::i();
    Ajax::i();
    ob_start('adv_ob_flush_handler', 0);
    ADVAccounting::i();
  }
  register_shutdown_function(function() {
       if (extension_loaded('xhprof')) {
             $profiler_namespace = $_SERVER["SERVER_NAME"]; // namespace for your application
             /** @noinspection PhpUndefinedFunctionInspection */
             $xhprof_data = xhprof_disable();
             /** @noinspection PhpUndefinedClassInspection */
             $xhprof_runs = new \XHProfRuns_Default();
             /** @noinspection PhpUndefinedMethodInspection */
             $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
           }
     });
