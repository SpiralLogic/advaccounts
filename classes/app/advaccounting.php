<?php

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

    public $settings;
    public $applications = array();
    /** var Application*/
    public $selected;
    /** @var \Menu */
    public $menu;
    public $buildversion;
    /** @var User $user */
    protected $User = null;
    /** @var Session */
    protected $Session;
    /** @var Cache */
    protected $Cache;
    /** @var Config */
    protected $Config;
    /** */
    public function __construct(Config $config = null, Session $session = null, \ADV\Core\Cache $cache = null)
    {
      $this->Config  = $config ? : Config::i();
      $this->Session = $session ? : Session::i();
      $this->Cache   = $cache ? : Cache::i();
      $this->User    = User::getCurrentUser();
      $this->getApplications();
      $this->getSelected();
      $this->runModules();
      $this->setVersion();
      // logout.php is the only page we should have always
      // accessable regardless of access level and current login status.
      if (!strstr($_SERVER['DOCUMENT_URI'], 'logout.php')) {
        $this->checkLogin();
      }
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
        }
      }
      if ($show404) {
        header('HTTP/1.0 404 Not Found');
        Event::error('Error 404 Not Found:' . $_SERVER['DOCUMENT_URI']);
      }
      if (($index || $show404)) {
        $this->display();
      }
    }
    protected function runModules()
    {
      array_walk($_POST, function(&$v)
      {
        $v = is_string($v) ? trim($v) : $v;
      });
      $modules = $this->Config->_get_all('modules', array());
      foreach ($modules as $module => $module_config) {
        $module = '\\Modules\\' . $module;
        new $module($module_config);
      }
    }
    protected function setVersion()
    {
      if (!$this->buildversion) {
        is_readable(DOCROOT . 'version') and define('BUILD_VERSION', file_get_contents(DOCROOT . 'version', null, null, null, 6));
        defined('BUILD_VERSION') or define('BUILD_VERSION', 000);
        $this->buildversion = BUILD_VERSION;
      } else {
        define('BUILD_VERSION', $this->buildversion);
      }
      define('VERSION', '3.' . BUILD_VERSION . '-SYEDESIGN');
    }
    /**
     * @return bool
     */
    protected function getApplications()
    {
      if ($this->Cache) {
        $this->applications = $this->Cache->get('application', []);
      }
      if ($this->applications) {
        return true;
      }
      $extensions = $this->Config->_get('extensions.installed');
      $this->menu = new Menu(_("Main Menu"));
      $this->menu->add_item(_("Main Menu"), "index.php");
      $this->menu->add_item(_("Logout"), "/account/access/logout.php");
      $apps = $this->Config->_get('apps.active');
      foreach ($apps as $app) {
        $app = 'Apps_' . $app;
        $this->addApplication(new $app());
      }
      if (count($extensions) > 0) {
        foreach ($extensions as $ext) {
          $ext = 'Apps_' . $ext['name'];
          $this->addApplication(new $ext());
        }
        $this->Session->get_text->add_domain(Language::i()->code, LANG_PATH);
      }
      $this->addApplication(new Apps_System());
      if ($this->Cache) {
        $this->Cache->set('application', $this->applications);
      }
      return true;
    }
    /**
     * @param $app
     */
    public function addApplication($app)
    {
      if ($app->enabled) // skip inactive modules
      {
        $this->applications[strtolower($app->id)] = $app;
      }
    }
    /**
     * @param $id
     *
     * @return null
     */
    public function getApplication($id)
    {
      $id = strtolower($id);
      return isset($this->applications[$id]) ? $this->applications[$id] : null;
    }
    /**
     * @return null
     */
    public function getSelected()
    {
      if ($this->selected !== null && is_object($this->selected)) {
        return $this->selected;
      }
      $path           = explode('/', $_SERVER['DOCUMENT_URI']);
      $app_id         = $path[1];
      $this->selected = $this->getApplication($app_id);
      if (!$this->selected && $this->User) {
        $app_id         = $this->User->startup_tab();
        $this->selected = $this->getApplication($app_id);
      }
      if (!$this->selected || !is_object($this->selected)) {
        $this->selected = $this->getApplication(Config::get('apps.default'));
      }
      return $this->selected;
    }
    public function display()
    {
      Extensions::add_access();
      Input::get('application')  and $this->setSelected($_GET['application']);
      $page = \ADV\App\Page::start(_($help_context = "Main Menu"), SA_OPEN, false, true, $this);
      $page->display_application($this->getSelected());
      \ADV\App\Page::end();
    }
    /**

     */
    public function loginFail()
    {
      header("HTTP/1.1 401 Authorization Required");
      (new View('failed_login'))->render();
      $this->Session->kill();
      die();
    }
    protected function checkLogin()
    {

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
      }
      $this->selected = $this->User->selectedApp;
      if ($this->User->change_password && strstr($_SERVER['DOCUMENT_URI'], 'change_current_user_password.php') == false) {
        Display::meta_forward('/system/change_current_user_password.php', 'selected_id=' . $this->User->username);
      }
    }
    protected function login()
    {
      $company = Input::post('login_company', null, 'default');
      if ($company) {
        try {
          if (!$this->User->login($company, $_POST["user_name"], $_POST["password"])) {
            // Incorrect password
            $this->loginFail();
          }
        }
        catch (\ADV\Core\DB\DBException $e) {
          Page::error_exit('Could not connect to database!');
        }
        $this->User->ui_mode = $_POST['ui_mode'];
        $this->Session->regenerate();
        $this->Session['Language']->set_language($_SESSION['Language']->code);
      }
    }
    /**
     * @static
     *
     * @param $config
     * @param $session
     * @param $cache
     */
    public static function refresh()
    {
      static::i()->getApplications();
    }
    /**
     * @param $app_id
     *
     * @return bool
     */
    public function setSelected($app_id)
    {
      $this->User->selectedApp = $this->getApplication($app_id);
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
    public static function write_extensions($extensions = null, $company = -1)
    {
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
    /**

     */
    protected function showLogin()
    {
      // strip ajax marker from uri, to force synchronous page reload
      $_SESSION['timeout'] = array(
        'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI'])
      );
      require(DOCROOT . "controllers/access/login.php");
      if (Ajax::inAjax()) {
        Ajax::redirect($_SERVER['DOCUMENT_URI']);
      } elseif (AJAX_REFERRER) {
        JS::redirect('/');
      }
      exit();
    }
  }

