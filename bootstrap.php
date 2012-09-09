<?php
  /**
   * PHP version 5.4
  \   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (strpos($_SERVER['HTTP_HOST'], 'dev.advaccounts') === 0) {
    header('Location: http://dev.advanced.advancedgroup.com.au' . $_SERVER['REQUEST_URI']);
  } elseif (strpos($_SERVER['HTTP_HOST'], 'advaccounts') !== false) {
    header('Location: http://advanced.advancedgroup.com.au' . $_SERVER['REQUEST_URI']);
  }
  if ($_SERVER['DOCUMENT_URI'] !== '/assets.php' && (!isset($_SERVER['QUERY_STRING']) || (strlen($_SERVER['QUERY_STRING']) && substr_compare(
    $_SERVER['QUERY_STRING'],
    '/profile/',
    0,
    9,
    true
  )) !== 0) && extension_loaded('xhprof')
  ) {
    $XHPROF_ROOT = realpath(dirname(__FILE__) . '/xhprof');
    include $XHPROF_ROOT . "/xhprof_lib/config.php";
    include $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
    include $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
    $ignore = array('call_user_func', 'call_user_func_array');
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, array('ignored_functions' => $ignore));
    register_shutdown_function(
      function () {
        register_shutdown_function(
          function () {
            $profiler_namespace = $_SERVER["SERVER_NAME"]; // namespace for your application
            $xhprof_data        = xhprof_disable();
            $xhprof_runs        = new \XHProfRuns_Default();
            $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
          }
        );
      }
    );
  }
  error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
  ini_set('display_errors', 'On');
  ini_set("ignore_repeated_errors", "On");
  ini_set("log_errors", "On");
  define('E_SUCCESS', E_ALL << 1);
  define('DS', DIRECTORY_SEPARATOR);
  define('DOCROOT', __DIR__ . DS);
  define('WEBROOT', DOCROOT . 'public' . DS);
  define('APPPATH', DOCROOT . 'classes' . DS . 'App' . DS);
  define('COREPATH', DOCROOT . 'classes' . DS . 'Core' . DS);
  define('VENDORPATH', DOCROOT . 'classes' . DS . 'Vendor' . DS);
  define('VIEWPATH', DOCROOT . 'views' . DS);
  define('COMPANY_PATH', WEBROOT . 'company' . DS);
  define('LANG_PATH', DOCROOT . 'lang' . DS);
  define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
  define('IS_JSON_REQUEST', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));
  define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
  define('CRLF', chr(13) . chr(10));
  $loader = require COREPATH . 'Loader.php';
  if ($_SERVER['DOCUMENT_URI'] === '/assets.php') {
    new \ADV\Core\Assets();
    exit;
  }
  if (!function_exists('e')) {
    /**
     * @param $string
     *
     * @return array|string
     */
    function e($string) {
      return \ADV\Core\Security::htmlentities($string);
    }
  }
  call_user_func(
    function () use ($loader) {
      $app        = new \ADV\App\ADVAccounting($loader);
      $controller = $app->getController();
      if ($controller) {
        include($controller);
      }
    }
  );

