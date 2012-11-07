<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\JS;
  use ADV\App\Pager\Pager;
  use DB_Company;
  use ADV\Core\Cache\APC;
  use ADV\Core\Event;
  use ADV\Core\View;
  use ADV\Core\Errors;
  use ADV\Core\Input\Input;
  use ADV\Core\Config;
  use ADV\Core\Ajax;
  use ADV\Core\Session;
  use ADV\Core\Language;
  use ADV\Core\Menu;
  use ADV\Core\DB\DB;

  /**
   * @method static \ADV\App\ADVAccounting i()
   */
  class ADVAccounting {
    use \ADV\Core\Traits\Singleton;

    public $applications = [];
    public $buildversion;
    /** var Application*/
    public $selected;
    /** @var Menu */
    public $menu;
    /** @var Ajax */
    protected $Ajax = null;
    /** @var Config $Config */
    protected $Config = null;
    /** @var Input $user */
    protected $Input = null;
    /** @var \ADV\Core\Session $Session */
    protected $Session = null;
    /** @var User $user */
    protected $User = null;
    protected $controller = null;
    /** */
    public function __construct(\ADV\Core\Loader $loader) {
      set_error_handler(
        function ($severity, $message, $filepath, $line) {
          if ($filepath == PATH_CORE . 'Errors.php') {
            while (ob_get_level()) {
              ob_end_clean();
            }
            die($message);
          }
          class_exists('ADV\\Core\\Errors', false) or include_once PATH_CORE . 'Errors.php';
          return \ADV\Core\Errors::handler($severity, $message, $filepath, $line);
        },
        E_ALL & ~E_STRICT & ~E_NOTICE
      );
      set_exception_handler(
        function (\Exception $e) {
          class_exists('ADV\\Core\\Errors', false) or include_once PATH_CORE . 'Errors.php';
          \ADV\Core\Errors::exceptionHandler($e);
        }
      );
      register_shutdown_function(
        function () {
          \ADV\Core\Event::shutdown();
        }
      );
      $dic  = \ADV\Core\DIC::i();
      $self = $this;
      $dic->offsetSet(
        'ADVAccounting',
        function () use ($self) {
          return $self;
        }
      );
      $this->Cache  = $dic->offsetSet(
        'Cache',
        function () {
          $driver = new \ADV\Core\Cache\APC();
          $cache  = new \ADV\Core\Cache($driver);
          if (isset($_GET['cache_reloaded'])) {
            Event::notice('Cache Reloaded');
          }
          return $cache;
        }
      )->offsetGet(null);
      $this->Config = $dic->offsetSet(
        'Config',
        function (\ADV\Core\DIC $c) {
          return new \ADV\Core\Config($c->offsetGet('Cache'));
        }
      )->offsetGet(null);
      $loader->registerCache($this->Cache);
      $this->Cache->defineConstants(
        $_SERVER['SERVER_NAME'] . '.defines',
        function () {
          return include(ROOT_DOC . 'config' . DS . 'defines.php');
        }
      );
      $this->Ajax = $dic->offsetSet(
        'Ajax',
        function () {
          return new \ADV\Core\Ajax();
        }
      )->offsetGet(null);
      $dic->offsetSet(
        'Input',
        function () {
          array_walk(
            $_POST,
            function (&$v) {
              $v = is_string($v) ? trim($v) : $v;
            }
          );
          return new \ADV\Core\Input\Input();
        }
      );
      $dic->offsetSet(
        'Num',
        function () {
          $num              = new \ADV\Core\Num();
          $num->price_dec   = $this->User->price_dec();
          $num->qty_dec     = $this->User->qty_dec();
          $num->tho_sep     = $this->User->prefs->tho_sep;
          $num->dec_sep     = $this->User->prefs->dec_sep;
          $num->exrate_dec  = $this->User->exrate_dec();
          $num->percent_dec = $this->User->percent_dec();
          return $num;
        }
      );
      $dic->offsetSet(
        'DB_Company',
        function () {
          return new \DB_Company();
        }
      );
      $dic->offsetSet(
        'Dates',
        function (\ADV\Core\DIC $c) {
          $config  = $c->offsetGet('Config');
          $user    = $c->offsetGet('User');
          $company = $c->offsetGet('DB_Company');
          $dates   = new \ADV\App\Dates($company);
          $sep     = is_int($user->prefs->date_sep) ? $user->prefs->date_sep : $config->get('date.ui_separator');
          $dates->setSep($sep);
          $dates->format          = $user->prefs->date_format;
          $dates->use_fiscal_year = $config->get('use_fiscalyear');
          $dates->sticky_doc_date = $user->prefs->sticky_doc_date;
          return $dates;
        }
      );
      $dic->offsetSet(
        'DB',
        function (\ADV\Core\DIC $c, $name = 'default') {
          $config   = $c->offsetGet('Config');
          $dbconfig = $config->get('db.' . $name);
          $cache    = $c->offsetGet('Cache');
          $db       = new \ADV\Core\DB\DB($dbconfig, $cache);
          return $db;
        }
      );
      $dic->offsetSet(
        'Pager',
        function (\ADV\Core\DIC $c, $name, $sql = null, $coldef) {
        }
      );
      ob_start([$this, 'flush_handler'], 0);
      $this->JS = $dic->offsetSet(
        'JS',
        function (\ADV\Core\DIC $c) {
          $js             = new \ADV\Core\JS();
          $config         = $c->offsetGet('Config');
          $js->apikey     = $config->get('assets.maps_api_key');
          $js->openWindow = $config->get('ui_windows_popups');
          return $js;
        }
      )->offsetGet(null);
      $dic->offsetSet(
        'User',
        function () {
          if (isset($_SESSION['User'])) {
            return $_SESSION['User'];
          }
          $_SESSION['User'] = new \ADV\App\User();
          return $_SESSION['User'];
        }
      );
      $this->Session = $dic->offsetSet(
        'Session',
        function (\ADV\Core\DIC $c) {
          $handler           = new \ADV\Core\Session\Memcached();
          $session           = new \ADV\Core\Session($handler);
          $config            = $c->offsetGet('Config');
          $l                 = \ADV\Core\Arr::searchValue($config->get('default.language'), $config->get('languages.installed'), 'code');
          $name              = $l['name'];
          $code              = $l['code'];
          $encoding          = $l['encoding'];
          $dir               = isset($l['rtl']) ? 'rtl' : 'ltr';
          $session->language = new \ADV\Core\Language($name, $code, $encoding, $dir);
          return $session;
        }
      )->offsetGet(null);
      $this->User    = $dic['User'];
      $this->Input   = $dic['Input'];
      $this->JS->footerFile($this->Config->get('assets.footer'));
      $this->menu = new Menu(_("Main Menu"));
      $this->menu->addItem(_("Main Menu"), "index.php");
      $this->menu->addItem(_("Logout"), "/account/access/logout.php");
      $this->loadModules();
      $this->setupApplications();
      define('BUILD_VERSION', is_readable(ROOT_DOC . 'version') ? file_get_contents(ROOT_DOC . 'version', null, null, null, 6) : 000);
      define('VERSION', '3.' . BUILD_VERSION . '-SYEDESIGN');
      // logout.php is the only page we should have always
      // accessable regardless of access level and current login status.
      if (!strstr($_SERVER['DOCUMENT_URI'], 'logout.php')) {
        $this->checkLogin();
      }
      \ADV\Core\Event::init($this->Cache, $this->User->username);
      $this->route();
    }
    /**
     * @return bool|string
     */
    protected function route() {
      $this->setupPage();
      $request = isset  ($_SERVER['DOCUMENT_URI']) ? parse_url($_SERVER['DOCUMENT_URI'])['path'] : false;
      if ($request == '/index.php') {
        return $this->defaultController();
      }
      // first check for autoloadable controller.
      if ($request) {
        $app = ucfirst(trim($request, '/'));
        if (isset($this->applications[$app])) {
          $request = (isset($this->applications[$app]['route']) ? $this->applications[$app]['route'] : $app);
        }
        $controller = 'ADV\\Controllers' . array_reduce(
          explode('/', ltrim($request, '/')),
          function ($result, $val) {
            return $result . '\\' . ucfirst($val);
          },
          ''
        );
        if (class_exists($controller)) {
          $this->runController($controller);
        } else {
          //then check to see if a file exists for address and if it does store it
          // substr_compare returns 0 if true
          $request    = (substr_compare($request, '.php', -4, 4, true) === 0) ? $request : $request . '.php';
          $controller = ROOT_DOC . 'controllers' . DS . $request;
          if (file_exists($controller)) {
            $this->controller = $controller;
          } else {
            //no controller so 404 then find next best default
            header('HTTP/1.0 404 Not Found');
            Event::error('Error 404 Not Found:' . parse_url($_SERVER['DOCUMENT_URI'])['path']);
            return $this->defaultController();
          }
        }
      }
      return null;
    }
    /**
     * @param $controller
     *
     * @internal param $request
     * @internal param $controller2
     */
    protected function runController($controller) {
      $dic = \ADV\Core\DIC::i();
      /** @var \ADV\App\Controller\Base $controller */
      $controller = new $controller($this->Session, $this->User, $this->Ajax, $this->JS, $dic['Input'], $dic->offsetGet('DB', 'default'));
      $controller->setPage($dic->offsetGet('Page'));
      $controller->run();
    }
    /**
     * @return bool|string
     */
    protected function defaultController() {
      $controller = false;
      $path       = explode('/', $_SERVER['DOCUMENT_URI']);
      if (count($path)) {
        $controller = 'ADV\\Controllers\\' . ucFirst($path[1]);
      }
      if (!class_exists($controller)) {
        $controller = 'ADV\\Controllers\\' . ($this->User->prefs->startup_tab ? : $this->Config->get('apps.default'));
      }
      if (class_exists($controller)) {
        $this->runController($controller);
      }
      return $controller;
    }
    /**
     * @param $app
     */
    /**
     * @param $text
     *
     * @return string
     * @noinspection PhpUnusedFunctionInspection
     */
    public function flush_handler($text) {
      return ($this->Ajax->inAjax()) ? Errors::format() : Page::$before_box . Errors::format() . $text;
    }
    /**
     * @param $id
     *
     * @return null
     */
    /**
     * @return null|string
     */
    public function getController() {
      return $this->controller;
    }
    public function loginFail() {
      header("HTTP/1.1 401 Authorization Required");
      (new View('failed_login'))->render();
      $this->Config->removeAll();
      $this->Session->kill();
      die();
    }
    /**
     * @static
     * @internal param $config
     * @internal param $session
     * @internal param $cache
     */
    public static function refresh() {
      /** @var ADVAccounting $instance */
      $instance               = static::i();
      $instance->applications = [];
      $instance->setupApplications();
    }
    protected function checkLogin() {
      if (!$this->Session instanceof \ADV\Core\Session || !$this->Session->checkUserAgent()) {
        $this->showLogin();
      }
      if ($this->Input->post("user_name")) {
        $this->login();
      } elseif (!$this->User->logged_in()) {
        $this->showLogin();
      }
      if ($this->User->username != 'admin' && strpos($_SERVER['SERVER_NAME'], 'dev') !== false) {
        header('Location: http://dev.advanced.advancedgroup.com.au:8090');
      } else {
        ini_set('html_errors', 'On');
      }
      $this->selected = $this->User->selectedApp;
      if ($this->User->change_password && strstr($_SERVER['DOCUMENT_URI'], 'change_current_user_password.php') == false) {
        header('Location: /system/change_current_user_password?selected_id=' . $this->User->username);
      }
    }
    protected function login() {
      $company = $this->Input->post('login_company', null, 'default');
      if ($company) {
        $modules = $this->Config->get('modules.login', []);
        foreach ($modules as $module => $module_config) {
          $this->User->register_login(
            function () use ($module, $module_config) {
              $module = '\\Modules\\' . $module . '\\' . $module;
              new $module($module_config);
            }
          );
        }
        try {
          $password = \AesCtr::decrypt(base64_decode($_POST['password']), $this->Session->getFlash('password_iv'), 256);
          if (!$this->User->login($company, $_POST["user_name"], $password)) {
            // Incorrect password
            $this->Session->keepFlash('uri');
            $this->loginFail();
          }
        } catch (\ADV\Core\DB\DBException $e) {
          throw new \ADV\Core\DB\DBException('Could not connect to database!');
        }
        $this->Session->checkUserAgent();
        $this->Session['User'] = $this->User;
        $this->Session->regenerate();
        $this->Session->language->setLanguage($this->Session['language']->code);
        header('HTTP/1.1 303 See Other');
        header('Location: ' . $this->Session->getFlash('uri'));
      }
    }
    protected function showLogin() {
      // strip ajax marker from uri, to force synchronous page reload
      $_SESSION['timeout'] = array(
        'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI'])
      );
      $dic                 = \ADV\Core\DIC::i();
      (new \ADV\Controllers\Access\Login($this->Session, $this->User, $this->Ajax, $this->JS, $dic['Input'], $dic->offsetGet('DB', 'default')))->run();
      if ($this->Ajax->inAjax()) {
        $this->Ajax->redirect($_SERVER['DOCUMENT_URI']);
      } elseif (REQUEST_AJAX) {
        $this->JS->redirect('/');
      }
      exit();
    }
    protected function loadModules() {
      $modules = $this->Config->get('modules.default', []);
      foreach ($modules as $module => $module_config) {
        $module = '\\Modules\\' . $module . '\\' . $module;
        new $module($module_config);
      }
    }
    protected function setupApplications() {
      $this->applications = $this->Config->get('apps.active');
    }
    private function setupPage() {
      $dic = \ADV\Core\DIC::i();
      $dic->offsetSet(
        'Page',
        function (\ADV\Core\DIC $c) {
          return new Page($c['Session'], $c['User'], $c['Config'], $c['Ajax'], $c['JS'], $c['Dates']);
        }
      );
    }
  }

