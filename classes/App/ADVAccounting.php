<?php
  namespace ADV\App;

  use ADV\Core\JS;
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
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**
   * @method static \ADV\App\ADVAccounting i()
   */
  class ADVAccounting {
    use \ADV\Core\Traits\Singleton;

    public $applications = [];
    /** var Application*/
    public $selected;
    /** @var \Menu */
    public $menu;
    public $buildversion;
    /** @var User $user */
    protected $User = null;
    /** @var Config $Config */
    protected $Config = null;
    /** @var Session */
    protected $Session = null;
    /** @var Ajax */
    protected $Ajax = null;
    protected $controller = null;
    protected $get_text = null;
    public $extensions;
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
      $dic  = \ADV\Core\DIC::getInstance();
      $self = $this;
      $dic->set(
        'ADVAccounting',
        function () use ($self) {
          return $self;
        }
      );
      $this->Cache  = $dic->set(
        'Cache',
        function () {
          $driver = new \ADV\Core\Cache\APC();
          $cache  = new \ADV\Core\Cache($driver);
          return $cache;
        }
      )->get();
      $this->Config = $dic->set(
        'Config',
        function ($c) {
          return new \ADV\Core\Config($c->get('Cache'));
        }
      )->get();
      $loader->registerCache($this->Cache);
      $this->Cache->defineConstants(
        $_SERVER['SERVER_NAME'] . '.defines',
        function () {
          return include(ROOT_DOC . 'config' . DS . 'defines.php');
        }
      );
      $this->Ajax = $dic->set(
        'Ajax',
        function () {
          return new \ADV\Core\Ajax();
        }
      )->get();
      ob_start([$this, 'flush_handler'], 0);
      $this->Session = $dic->set(
        'Session',
        function ($c) {
          $session              = new \ADV\Core\Session();
          $config               = $c->get('Config');
          $l                    = \ADV\Core\Arr::searchValue($config->get('default.language'), $config->get('languages.installed'), 'code');
          $name                 = $l['name'];
          $code                 = $l['code'];
          $encoding             = $l['encoding'];
          $dir                  = isset($l['rtl']) ? 'rtl' : 'ltr';
          $_SESSION['language'] = new \ADV\Core\Language($name, $code, $encoding, $dir);

          return $session;
        }
      )->get();
      $this->JS      = $dic->set(
        'JS',
        function ($c) {
          $js             = new \ADV\Core\JS();
          $config         = $c->get('Config');
          $js->apikey     = $config->get('assets.maps_api_key');
          $js->openWindow = $config->get('ui_windows_popups');
          return $js;
        }
      )->get();

      $this->User = $dic->set(
        'User',
        function () {
          if (isset($_SESSION['User'])) {
            return $_SESSION['User'];
          }
          $_SESSION['User'] = new \ADV\App\User();
          return $_SESSION['User'];
        }
      )->get();
      $dic->set(
        'Num',
        function () {
          $num              = new \ADV\Core\Num();
          $num->price_dec   = $this->User->_price_dec();
          $num->qty_dec     = $this->User->_qty_dec();
          $num->tho_sep     = $this->User->prefs->tho_sep;
          $num->dec_sep     = $this->User->prefs->dec_sep;
          $num->exrate_dec  = $this->User->_exrate_dec();
          $num->percent_dec = $this->User->_percent_dec();
          return $num;
        }
      );
      $dic->set(
        'DB_Company',
        function () {
          return new DB_Company();
        }
      );
      $dic->set(
        'Dates',
        function ($c) {
          $config  = $c->get('Config');
          $user    = $c->get('User');
          $session = $c->get('Session');
          $company = $c->get('DB_Company');
          $dates   = new \ADV\App\Dates($session, $company);
          $sep     = is_int($user->prefs->date_sep) ? $user->prefs->date_sep : $config->get('date.ui_separator');
          $dates->setSep($sep);
          $dates->format = $user->prefs->date_format;
          $dates->use_fiscal_year = $config->get('use_fiscalyear');
          $dates->sticky_doc_date = $user->prefs->sticky_doc_date;
          return $dates;
        }
      )->get();

      $this->JS->footerFile($this->Config->get('assets.footer'));
      $this->menu = new Menu(_("Main Menu"));
      $this->menu->addItem(_("Main Menu"), "index.php");
      $this->menu->addItem(_("Logout"), "/account/access/logout.php");
      array_walk(
        $_POST,
        function (&$v) {
          $v = is_string($v) ? trim($v) : $v;
        }
      );
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

      $this->get_selected();
      $this->route();
    }
    protected function route() {
      $this->setupPage();
      $controller = isset($_SERVER['DOCUMENT_URI']) ? $_SERVER['DOCUMENT_URI'] : false;
      $index      = $controller == $_SERVER['SCRIPT_NAME'];
      $show404    = false;
      if (!$index && $controller) {
        $controller2 = 'ADV\\Controllers' . array_reduce(
          explode('/', ltrim($controller, '/')),
          function ($result, $val) {
            return $result . '\\' . ucfirst($val);
          },
          ''
        );
        if (class_exists($controller2)) {
          $this->runController($controller2);
        } else {
          // substr_compare returns 0 if true
          $controller = (substr_compare($controller, '.php', -4, 4, true) === 0) ? $controller : $controller . '.php';
          $controller = ROOT_DOC . 'controllers' . DS . $controller;
          if (file_exists($controller)) {
            $this->controller = $controller;
          } else {
            $show404 = true;
            header('HTTP/1.0 404 Not Found');
            Event::error('Error 404 Not Found:' . $_SERVER['DOCUMENT_URI']);
          }
        }
      }
      if ($index || $show404) {
        $this->display();
      }
    }
    /**
     * @param $controller2
     */
    protected function runController($controller2) {

      new $controller2;
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
    public function get_application($id) {
      $app_class = '\\ADV\\App\\Apps\\' . $id;
      return isset($this->applications[$id]) ? new $app_class : null;
    }
    /**
     * @return null
     */
    public function get_selected() {
      if ($this->selected !== null && is_object($this->selected)) {
        return $this->selected;
      }
      $path           = explode('/', $_SERVER['DOCUMENT_URI']);
      $app_id         = $path[1];
      $this->selected = $this->get_application($app_id);
      if (!$this->selected) {
        $app_id         = $this->User->_startup_tab();
        $this->selected = $this->get_application($app_id);
      }
      if (!$this->selected || !is_object($this->selected)) {
        $this->selected = $this->get_application($this->Config->get('apps.default'));
      }
      return $this->selected;
    }
    /**
     * @return null|string
     */
    public function getController() {
      return $this->controller;
    }
    public function display() {
      Extensions::add_access($this->User);
      Input::_get('application')  and $this->set_selected($_GET['application']);
      $page = Page::start(_($help_context = "Main Menu"), SA_OPEN, false, true);
      $page->display_application($this->get_selected());
      Page::end();
    }
    public function loginFail() {
      header("HTTP/1.1 401 Authorization Required");
      (new View('failed_login'))->render();
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
      /** @var ADVAccounting $instance  */
      $instance               = static::i();
      $instance->applications = [];
      $instance->setupApplications();
    }
    /**
     * @param $app_id
     *
     * @return bool
     */
    public function set_selected($app_id) {
      $this->User->selectedApp = $this->get_application($app_id);
      $this->selected          = $this->User->selectedApp;
      return $this->selected;
    }
    protected function checkLogin() {
      if (!$this->Session instanceof \ADV\Core\Session || !$this->Session->checkUserAgent()) {
        $this->showLogin();
      }
      if (Input::_post("user_name")) {
        $this->login();
      } elseif (!$this->User->logged_in()) {
        $this->showLogin();
      }
      if ($this->User->username != 'admin' && strpos($_SERVER['SERVER_NAME'], 'dev') !== false) {
        Display::meta_forward('http://dev.advanced.advancedgroup.com.au:8090');
      } else {
        ini_set('html_errors', 'On');
      }
      $this->selected = $this->User->selectedApp;
      if ($this->User->change_password && strstr($_SERVER['DOCUMENT_URI'], 'change_current_user_password.php') == false) {
        Display::meta_forward('/system/change_current_user_password.php', 'selected_id=' . $this->User->username);
      }
    }
    protected function login() {
      $company = Input::_post('login_company', null, 'default');
      if ($company) {
        $modules = $this->Config->get('modules.login', []);
        foreach ($modules as $module=> $module_config) {
          $this->User->register_login(
            function () use ($module, $module_config) {
              $module = '\\Modules\\' . $module . '\\' . $module;
              new $module($module_config);
            }
          );
        }
        try {
          if (!$this->User->login($company, $_POST["user_name"], $_POST["password"])) {
            // Incorrect password
            $this->loginFail();
          }
        } catch (\ADV\Core\DB\DBException $e) {
          throw new \ADV\Core\DB\DBException('Could not connect to database!');
        }
        $this->Session->checkUserAgent();
        $this->Session['User'] = $this->User;
        $this->Session->regenerate();
        $this->Session->language->setLanguage($this->Session['language']->code);
      }
    }
    protected function showLogin() {
      // strip ajax marker from uri, to force synchronous page reload
      $_SESSION['timeout'] = array(
        'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI'])
      );
      require(ROOT_DOC . "controllers/access/login.php");
      if ($this->Ajax->inAjax()) {
        $this->Ajax->redirect($_SERVER['DOCUMENT_URI']);
      } elseif (REQUEST_AJAX) {
        JS::_redirect('/');
      }
      exit();
    }
    protected function loadModules() {
      $types = $this->Config->get('modules.default', []);
      foreach ($types as $type => $modules) {
        foreach ($modules as $module=> $module_config) {
          switch ($type) {
            default:
              $module = '\\Modules\\' . $module . '\\' . $module;
              new $module($module_config);
          }
        }
      }
    }
    protected function setupApplications() {
      $this->extensions   = $this->Config->get('extensions.installed');
      $this->applications = $this->Config->get('apps.active');
    }
    /**
     * @static
     *
     * @param null $extensions
     * @param      $company
     *
     * @return bool
     */
    public static function write_extensions($extensions = null, $company = -1) {
      global $installed_extensions, $next_extension_id;
      if (!isset($extensions)) {
        $extensions = $installed_extensions;
      }
      if (!isset($next_extension_id)) {
        $next_extension_id = 1;
      }
      //	$exts = Arr::natsort($extensions, 'name', 'name');
      //	$extensions = $exts;
      $msg = "<?php\n\n";
      if ($company == -1) {
        $msg
          .= "/* List of installed additional modules and plugins. If adding extensions manually
                   to the list make sure they have unique, so far not used extension_ids as a keys,
                   and \$next_extension_id is also updated.
                   'name' - name for identification purposes;
                   'type' - type of extension: 'module' or 'plugin'
                   'path' - ADV root based installation path
                   'filename' - name of module menu file, or plugin filename; related to path.
                   'tab' - index of the module tab (new for module, or one of standard module names for plugin);
                   'title' - is the menu text (for plugin) or new tab name
                   'active' - current status of extension
                   'acc_file' - (optional) file name with \$security_areas/\$security_sections extensions;
                       related to 'path'
                   'access' - security area code in string form
               */
               \n\$next_extension_id = $next_extension_id; // unique id for next installed extension\n\n";
      } else {
        $msg
          .= "/*
                   Do not edit this file manually. This copy of global file is overwritten
                   by extensions editor.
               */\n\n";
      }
      $msg .= "\$installed_extensions = array (\n";
      foreach ($extensions as $i => $ext) {
        $msg .= "\t$i => ";
        $msg .= "array ( ";
        $t = '';
        foreach ($ext as $key => $val) {
          $msg .= $t . "'$key' => '$val',\n";
          $t = "\t\t\t";
        }
        $msg .= "\t\t),\n";
      }
      $msg .= "\t);\n?>";
      $filename = ROOT_DOC . ($company == -1 ? '' : 'company' . DS . $company) . DS . 'installed_extensions.php';
      // Check if the file is writable first.
      if (!$zp = fopen($filename, 'w')) {
        Event::error(sprintf(_("Cannot open the extension setup file '%s' for writing."), $filename));
        return false;
      } else {
        if (!fwrite($zp, $msg)) {
          Event::error(sprintf(_("Cannot write to the extensions setup file '%s'."), $filename));
          fclose($zp);
          return false;
        }
        // Close file
        fclose($zp);
      }
      return true;
    }
    private function setupPage() {
      $dic = \ADV\Core\DIC::getInstance();
      $dic->set(
        'Page',
        function ($c) {

          new static($c->get('User'), $c->get('Config'), $c->get('User'), $c->get('JS'), $c->get('Dates'));
        }
      );
    }
  }

