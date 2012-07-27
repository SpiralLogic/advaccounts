<?php
  use ADV\App\Page;
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

   */
  class ADVAccounting
  {
    use \ADV\Core\Traits\Singleton;

    public $applications = array();
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
    protected $get_text = null;
    /** */
    public function __construct(\ADV\Core\Loader $loader) {
   /*   set_error_handler(function ($severity, $message, $filepath, $line) {
        class_exists('ADV\\Core\\Errors', false) or include_once COREPATH . 'Errors.php';
        return \ADV\Core\Errors::handler($severity, $message, $filepath, $line);
      });
      set_exception_handler(function (\Exception $e) {
        class_exists('ADV\\Core\\Errors', false) or include_once COREPATH . 'Errors.php';
        \ADV\Core\Errors::exceptionHandler($e);
      });
  */    register_shutdown_function(function () {
        \ADV\Core\Event::shutdown();
      });
      static::i($this);
      $this->Cache = \ADV\Core\Cache::i();
      $loader->registerCache($this->Cache);
      $this->Cache->_defineConstants($_SERVER['SERVER_NAME'] . '.defines', function() {
        return include(DOCROOT . 'config' . DS . 'defines.php');
      });
      include(DOCROOT . 'config' . DS . 'types.php');
      $this->Config = Config::i();
      // Ajax communication object
      $this->Ajax = Ajax::i();
      //ob_start([$this, 'flush_handler'], 0);
      $this->Session = Session::i();
      $this->setTextSupport();
      $this->Session['language'] = new Language();
      $this->User                = User::i($this->Session);
      $this->menu                = new Menu(_("Main Menu"));
      $this->menu->addItem(_("Main Menu"), "index.php");
      $this->menu->addItem(_("Logout"), "/account/access/logout.php");
      array_walk($_POST, function(&$v) {
        $v = is_string($v) ? trim($v) : $v;
      });
      $this->loadModules();
      $this->applications = $this->Cache->_get('applications');
      if (!$this->applications) {
        $this->setupApplications();
      }
      is_readable(DOCROOT . 'version') and define('BUILD_VERSION', file_get_contents(DOCROOT . 'version', null, null, null, 6));
      defined('BUILD_VERSION') or define('BUILD_VERSION', 000);
      define('VERSION', '3.' . BUILD_VERSION . '-SYEDESIGN');
      // logout.php is the only page we should have always
      // accessable regardless of access level and current login status.
      if (!strstr($_SERVER['DOCUMENT_URI'], 'logout.php')) {
        $this->checkLogin();
      }
      \Event::init();
      $this->get_selected();
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
          \Event::error('Error 404 Not Found:' . $_SERVER['DOCUMENT_URI']);
        }
      }
      if ($index || $show404) {
        $this->display();
      }
    }
    /**
     * @param $app
     */
    public function add_application($app) {
      if ($app->enabled) // skip inactive modules
      {
        $this->applications[strtolower($app->id)] = $app;
      }
    }
    /**
     * @param $text
     *
     * @return string
     * @noinspection PhpUnusedFunctionInspection
     */
    function flush_handler($text) {
      return ($this->Ajax->_inAjax()) ? Errors::format() : Page::$before_box . Errors::format() . $text;
    }
    /**
     * @param $id
     *
     * @return null
     */
    public function get_application($id) {
      $id = strtolower($id);
      return isset($this->applications[$id]) ? $this->applications[$id] : null;
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
        $this->selected = $this->get_application($this->Config->_get('apps.default'));
      }
      return $this->selected;
    }
    public function display() {
      Extensions::add_access($this->User);
      Input::get('application')  and $this->set_selected($_GET['application']);
      $page = Page::start(_($help_context = "Main Menu"), SA_OPEN, false, true);
      $page->display_application($this->get_selected());
      Page::end();
    }
    public function loginFail() {
      header("HTTP/1.1 401 Authorization Required");
      (new View('failed_login'))->render();
      $this->Session->_kill();
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
      $filename = DOCROOT . ($company == -1 ? '' : 'company' . DS . $company) . DS . 'installed_extensions.php';
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
    protected function checkLogin() {
      if (!$this->Session->_checkUserAgent()) {
        $this->showLogin();
      }
      if (Input::post("user_name")) {
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
      $company = Input::post('login_company', null, 'default');
      if ($company) {
        try {
          if (!$this->User->login($company, $_POST["user_name"], $_POST["password"])) {
            // Incorrect password
            $this->loginFail();
          }
        }
        catch (\ADV\Core\DB\DBException $e) {
          throw new \ADV\Core\DB\DBException('Could not connect to database!');
        }
        $this->User->ui_mode = $_POST['ui_mode'];
        $this->Session->regenerate();
        $this->Session['language']->setLanguage($this->Session['language']->code);
      }
    }
    protected function showLogin() {
      // strip ajax marker from uri, to force synchronous page reload
      $_SESSION['timeout'] = array(
        'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI'])
      );
      require(DOCROOT . "controllers/access/login.php");
      if ($this->Ajax->_inAjax()) {
        $this->Ajax->_redirect($_SERVER['DOCUMENT_URI']);
      } elseif (AJAX_REFERRER) {
        JS::redirect('/');
      }
      exit();
    }
    protected function loadModules() {
      $modules = $this->Config->_getAll('modules', array());
      foreach ($modules as $module => $module_config) {
        $module = '\\Modules\\' . $module . '\\' . $module;
        new $module($module_config);
      }
    }
    protected function setupApplications() {
      $this->applications = [];
      $extensions         = $this->Config->_get('extensions.installed');
      $apps               = $this->Config->_get('apps.active');
      foreach ($apps as $app) {
        $app = '\\ADV\\App\\Apps\\' . $app;
        $this->add_application(new $app());
      }
      if (count($extensions) > 0) {
        foreach ($extensions as $ext) {
          $ext = '\\ADV\\App\\Apps\\' . $ext['name'];
          $this->add_application(new $ext());
        }
        $this->Session['get_text']->add_domain($this->Session['langauge']->code, LANG_PATH);
      }
      $this->add_application(new \ADV\App\Apps\System());
      $this->Cache->_set('applications', $this->applications);
    }
    /**
     * @return mixed
     */
    protected function setTextSupport() {
      if (!isset($this->Session['get_text'])) {
        $this->Session['get_text'] = \gettextNativeSupport::i();
      }
    }
  }

