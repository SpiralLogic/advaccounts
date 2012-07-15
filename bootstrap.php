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
  if ($_SERVER['DOCUMENT_URI'] !== '/assets.php' && (isset($_SERVER['QUERY_STRING'])&& strlen($_SERVER['QUERY_STRING']) && substr_compare($_SERVER['QUERY_STRING'], '/profile/', 0, 9, true) !== 0) && extension_loaded('xhprof')) {
    $XHPROF_ROOT = realpath(dirname(__FILE__) . '/xhprof');
    include $XHPROF_ROOT . "/xhprof_lib/config.php";
    include $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
    include $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    register_shutdown_function(function()
    {
      register_shutdown_function(function()
      {
        $profiler_namespace = $_SERVER["SERVER_NAME"]; // namespace for your application
        $xhprof_data        = xhprof_disable();
        $xhprof_runs        = new \XHProfRuns_Default();
        $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
      });
    });
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
  define('VIEWPATH', DOCROOT . 'views' . DS);
  define('COMPANY_PATH', WEBROOT . 'company' . DS);
  define('LANG_PATH', DOCROOT . 'lang' . DS);
  define("AJAX_REFERRER", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
  define('IS_JSON_REQUEST', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));
  define('BASE_URL', str_ireplace(realpath(__DIR__), '', DOCROOT));
  define('CRLF', chr(13) . chr(10));
  set_error_handler(function ($severity, $message, $filepath, $line)
  {
    class_exists('ADV\\Core\\Errors', false) or include_once COREPATH . 'errors.php';
    return ADV\Core\Errors::handler($severity, $message, $filepath, $line);
  });
  set_exception_handler(function (\Exception $e)
  {
    class_exists('ADV\\Core\\Errors', false) or include_once COREPATH . 'errors.php';
    ADV\Core\Errors::exceptionHandler($e);
  });
  $loader = require COREPATH . 'autoloader.php';
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
    function e($string)
    {
      return Security::htmlentities($string);
    }
  }
  register_shutdown_function(function ()
  {
    ADV\Core\Event::shutdown();
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
      return (Ajax::inAjax()) ? Errors::format() : Errors::$before_box . Errors::format() . $text;
    }
  }
  $dic = new \ADV\Core\DIC();
  $loader->registerCache(\ADV\Core\Cache::i()->_defineConstants($_SERVER['SERVER_NAME'] . '.defines', function()
  {
    return include(DOCROOT . 'config' . DS . 'defines.php');
  }));
  include(DOCROOT . 'config' . DS . 'types.php');
  Session::i();
  Ajax::i();
  Config::i();
  ob_start('adv_ob_flush_handler', 0);
  $app        = ADVAccounting::i();
  $controller = isset($_SERVER['DOCUMENT_URI']) ? $_SERVER['DOCUMENT_URI'] : false;
  $index      = $controller == $_SERVER['SCRIPT_NAME'];
  $show404    = false;
  if (!$index && $controller) {
    $controller = ltrim($controller, '/');
    // substr_compare returns 0 if true
    $controller = (substr_compare($controller, '.php', -4, 4, true) === 0) ? $controller : $controller . '.php';
    $controller = DOCROOT . 'controllers' . DS . $controller;
    if (file_exists($controller)) {
      include($controller);
    } else {
      $show404 = true;
      header('HTTP/1.0 404 Not Found');
      Event::error('Error 404 Not Found:' . $_SERVER['DOCUMENT_URI']);
    }
  }
  if ($index || $show404) {
    $app->display();
  }



