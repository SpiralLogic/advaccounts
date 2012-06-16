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
    /**
     * @var
     */
    public $settings;
    /**
     * @var array
     */
    public $applications = array();
    /**
     * @var Application
     */
    public $selected;
    /**
     * @var \Menu
     */
    public $menu;
    /** @var User $user */
    public static $user;
    /***
     * @var ADVAccounting
     */
    public static $i = false;
    public $buildversion;
    /** @var Session */
    protected $session = null;
    /**

     */
    public function __construct($config,$session)
    {
      $extensions    = $config->_get('extensions.installed');
      $this->menu    = new Menu(_("Main Menu"));
      $this->menu->add_item(_("Main Menu"), "index.php");
      $this->menu->add_item(_("Logout"), "/account/access/logout.php");
      $apps = $config->_get('apps.active');
      foreach ($apps as $app) {
        $app = 'Apps_' . $app;
        $this->add_application(new $app());
      }
      if (count($extensions) > 0) {
        foreach ($extensions as $ext) {
          $ext = 'Apps_' . $ext['name'];
          $this->add_application(new $ext());
        }
        $session->get_text->add_domain(Language::i()->code, LANG_PATH);
      }
      $this->add_application(new Apps_System());
      $this->get_selected();
    }
    /**
     * @param $app
     */
    public function add_application($app)
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
    public function get_application($id)
    {
      $id = strtolower($id);

      return isset($this->applications[$id]) ? $this->applications[$id] : null;
    }
    /**
     * @return null
     */
    public function get_selected()
    {
      if ($this->selected !== null && is_object($this->selected)) {
        return $this->selected;
      }
      $path           = explode('/', $_SERVER['DOCUMENT_URI']);
      $app_id         = $path[1];
      $this->selected = $this->get_application($app_id);
      if (!$this->selected) {
        $app_id         = User::i()->startup_tab();
        $this->selected = $this->get_application($app_id);
      }
      if (!$this->selected || !is_object($this->selected)) {
        $this->selected = $this->get_application(Config::get('apps.default'));
      }

      return $this->selected;
    }
    public function display()
    {
      Extensions::add_access();
      Input::get('application')  and $this->set_selected($_GET['application']);
      $page = Page::start(_($help_context = "Main Menu"), SA_OPEN, false, true);
      $page->renderer->display_application($this);
      Page::end();
    }
    /**
     * @param $app_id
     *
     * @return bool
     */
    public function set_selected($app_id)
    {
      static::$user->selectedApp = $this->get_application($app_id);
      $this->selected            = static::$user->selectedApp;

      return $this->selected;
    }
    /**
     * @static
     * @return ADVAccounting
     */
    public static function i(Config $config=null,Session $session=null,Cache $cache=null)
    {
      if (static::$i === false) {
        static::init($config,$session,$cache);
      }

      return static::$i;
    }
    /**
     * @static
     * @return \ADVAccounting|bool
     */
    protected static function init($config,$session,$cache)
    {
      array_walk($_POST, function(&$v)
      {
        $v = is_string($v) ? trim($v) : $v;
      });
      $modules = $config->_get_all('modules', array());
      foreach ($modules as $module => $module_config) {
        $module = '\\Modules\\' . $module;
        new $module($module_config);
      }
      static::$i = $cache->_get('App');
      if (static::$i === false) {
        static::refresh($config,$session,$cache);
      }
      if (!static::$i->buildversion) {
        is_readable(DOCROOT . 'version') and define('BUILD_VERSION', file_get_contents(DOCROOT . 'version', null, null, null, 6));
        defined('BUILD_VERSION') or define('BUILD_VERSION', 000);
        static::$i->buildversion = BUILD_VERSION;
      } else {
        define('BUILD_VERSION', static::$i->buildversion);
      }
      define('VERSION', '3.' . BUILD_VERSION . '-SYEDESIGN');
      // logout.php is the only page we should have always
      // accessable regardless of access level and current login status.
      if (!strstr($_SERVER['DOCUMENT_URI'], 'logout.php')) {
        static::$i->checkLogin();
      }
      Event::init();

      return static::$i;
    }
    /**

     */
    public function loginFail()
    {
      header("HTTP/1.1 401 Authorization Required");
      echo "<div class='font5 red bold center'><br><br>" . _("Incorrect Password") . "<br><br>";
      echo _("The user and password combination is not valid for the system.") . "<br><br>";
      echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
      echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
      echo "</div>";
      Session::i()->kill();
      die();
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
    protected function checkLogin()
    {
      if (!Session::i()->checkUserAgent()) {
        $this->showLogin();
      }
      static::$user = User::i();
      if (Input::post("user_name")) {
        $this->login();
      } elseif (!static::$user->logged_in()) {
        $this->showLogin();
      }
      if ($_SESSION['current_user']->username != 'admin' && strpos($_SERVER['SERVER_NAME'], 'dev') !== false) {
        Display::meta_forward('http://dev.advanced.advancedgroup.com.au:8090');
      }
      static::$i->selected = static::$user->selectedApp;
      if (static::$user->change_password && strstr($_SERVER['DOCUMENT_URI'], 'change_current_user_password.php') == false) {
        Display::meta_forward('/system/change_current_user_password.php', 'selected_id=' . static::$user->username);
      }
    }
    protected function login()
    {
      $company = Input::post('login_company', null, 'default');
      if ($company) {
        try {
          if (!static::$user->login($company, $_POST["user_name"], $_POST["password"])) {
            // Incorrect password
            $this->loginFail();
          }
        }
        catch (\ADV\Core\DB\DBException $e) {
          Page::error_exit('Could not connect to database!');
        }
        static::$user->ui_mode = $_POST['ui_mode'];
        Session::i()->regenerate();
        Language::i()->set_language($_SESSION['Language']->code);
      }
    }
    /**

     */
    protected static function showLogin()
    {
      // strip ajax marker from uri, to force synchronous page reload
      $_SESSION['timeout'] = array(
        'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI'])
      );
      require(DOCROOT . "controllers/access/login.php");
      if (Ajax::in_ajax()) {
        Ajax::i()->redirect($_SERVER['DOCUMENT_URI']);
      } elseif (AJAX_REFERRER) {
        JS::redirect('/');
      }
      exit();
    }
    public static function refresh($config,$session,$cache)
    {
      static::$i = $cache->_set('App', new static($config,$session,$cache));
    }
  }

