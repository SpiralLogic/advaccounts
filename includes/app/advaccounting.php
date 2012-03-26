<?php

  /* * *******************************************************************
              Copyright (C) Advanced Group PTY LTD
              Released under the terms of the GNU General Public License, GPL,
              as published by the Free Software Foundation, either version 3
              of the License, or (at your option) any later version.
              This program is distributed in the hope that it will be useful,
              but WITHOUT ANY WARRANTY; without even the implied warranty of
              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
              See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
             * ********************************************************************* */
  /** @define "null" "VALUE" */
  /**

   */
  class ADVAccounting {

    /**
     * @var
     */
    public $user;
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
    /**

     */
    public function __construct() {
      $extensions = Config::get('extensions.installed');
      $this->menu = new Menu(_("Main Menu"));
      $this->menu->add_item(_("Main Menu"), "index.php");
      $this->menu->add_item(_("Logout"), "/account/access/logout.php");
      $apps = Config::get('apps.active');
      foreach ($apps as $app) {
        $app = 'Apps_' . $app;
        $this->add_application(new $app());
      }
      if (count($extensions) > 0) {
        foreach ($extensions as $ext) {
          $ext = 'Apps_' . $ext['name'];
          $this->add_application(new $ext());
        }
        Session::$get_text->add_domain(Language::i()->code, PATH_TO_ROOT . "/lang");
      }
      $this->add_application(new Apps_System());
      $this->get_selected();
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
     * @param $id
     *
     * @return null
     */
    public function get_application($id) {
      $id = strtolower($id);
      return isset($this->applications[$id]) ? $this->applications[$id] : NULL;
    }
    /**
     * @return null
     */
    public function get_selected() {
      if ($this->selected !== NULL && is_object($this->selected)) {
        return $this->selected;
      }
      $path = explode('/', $_SERVER['SCRIPT_NAME']);
      $app_id = $path[0];
      $this->selected = $this->get_application($app_id);
      if (!$this->selected) {
        $app_id = User::i()->startup_tab();
        $this->selected = $this->get_application($app_id);
      }
      if (!$this->selected || !is_object($this->selected)) {
        $this->selected = $this->get_application(Config::get('apps.default'));
      }
      return $this->selected;
    }
    /**

     */
    public function display() {
      Extensions::add_access();
      Input::get('application')  and $this->set_selected($_GET['application']);
      $page = Page::start(_($help_context = "Main Menu"), SA_OPEN, FALSE, TRUE);
      $page->renderer->display_application($this);
      Page::end();
    }
    /**
     * @param $app_id
     *
     * @return bool
     */
    public function set_selected($app_id) { return $this->selected = $this->get_application($app_id); }
    /**
     * @static

     */

    static public function i() {

      $buildversion = Cache::get('build.version', FALSE);
      if (!$buildversion) {
        is_readable(DOCROOT . 'version') and define('BUILD_VERSION', file_get_contents(DOCROOT . 'version', NULL, NULL, NULL, 6));
        defined('BUILD_VERSION') or define('BUILD_VERSION', 000);
        Cache::set('build.version', BUILD_VERSION);
      }
      else {
        define('BUILD_VERSION', $buildversion);
      }
      define('VERSION', '3.' . BUILD_VERSION . '-SYEDESIGN');
      array_walk($_POST, function(&$v) {
        $v = is_string($v) ? trim($v) : $v;
      });
      require APPPATH . "main.php";
      // logout.php is the only page we should have always
      // accessable regardless of access level and current login status.
      if (strstr($_SERVER['PHP_SELF'], 'logout.php') == FALSE) {
        static::checkLogin();
      }
      Event::init();
      isset($_SESSION["App"]) or $_SESSION["App"] = new static();
      return $_SESSION["App"];
    }
    static protected function checkLogin() {
      if (!Session::checkUserAgent()) {
        static::showLogin();
      }
      $currentUser = User::i();
      if (Input::post("user_name")) {
        $company = isset($_POST["login_company"]) ? $_POST["login_company"] : 'default';
        if ($company) {
          if (!$currentUser->login($company, $_POST["user_name"], $_POST["password"])) {
            // Incorrect password
            static::loginFail();
          }
          $currentUser->ui_mode = $_POST['ui_mode'];
          Session::regenerate();
          Language::i()->set_language($_SESSION['Language']->code);
        }
      }
      elseif (!$currentUser->logged_in()) {
        static::showLogin();
      }
      if ($currentUser->change_password && strstr($_SERVER['PHP_SELF'], 'change_current_user_password.php') == FALSE) {
        Display::meta_forward('/system/change_current_user_password.php', 'selected_id=' . $currentUser->username);
      }
    }
    /**

     */
    static protected function showLogin() {
      // strip ajax marker from uri, to force synchronous page reload
      $_SESSION['timeout'] = array(
        'uri' => preg_replace('/JsHttpRequest=(?:(\d+)-)?([^&]+)/s', '', $_SERVER['REQUEST_URI']), 'post' => $_POST
      );
      require(DOCROOT . "access/login.php");
      if (Ajax::in_ajax()) {
        Ajax::i()->redirect($_SERVER['PHP_SELF']);
      }
      elseif (AJAX_REFERRER) {
        JS::redirect('/');
      }
      exit();
    }
    /**

     */
    static public function loginFail() {
      header("HTTP/1.1 401 Authorization Required");
      echo "<div class='font5 red bold center'><br><br>" . _("Incorrect Password") . "<br><br>";
      echo _("The user and password combination is not valid for the system.") . "<br><br>";
      echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
      echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
      echo "</div>";
      Session::kill();
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
    static public function write_extensions($extensions = NULL, $company = -1) {
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
      }
      else {
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
      $filename = PATH_TO_ROOT . ($company == -1 ? '' : '/company/' . $company) . '/installed_extensions.php';
      // Check if the file is writable first.
      if (!$zp = fopen($filename, 'w')) {
        Event::error(sprintf(_("Cannot open the extension setup file '%s' for writing."), $filename));
        return FALSE;
      }
      else {
        if (!fwrite($zp, $msg)) {
          Event::error(sprintf(_("Cannot write to the extensions setup file '%s'."), $filename));
          fclose($zp);
          return FALSE;
        }
        // Close file
        fclose($zp);
      }
      return TRUE;
    }
  }

