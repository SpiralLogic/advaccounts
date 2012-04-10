<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Page {

    /** @var \Renderer */
    public $renderer = NULL;
    /**
     * @var bool
     */
    protected $frame = FALSE;
    /**
     * @var bool
     */
    protected $menu = TRUE;
    /**
     * @var bool
     */
    protected $is_index = FALSE;
    /**
     * @var array
     */
    protected $css = array();
    /**
     * @var bool
     */
    protected $header = TRUE;
    /**
     * @var string
     */
    protected $theme = 'default';
    /** @var ADVAccounting */
    protected $app;
    /**
     * @var
     */
    protected $sel_app;
    /**
     * @var string
     */
    protected $title = '';
    /** @var Page */
    static protected $i = NULL;
    /**
     * @var null
     */
    static protected $security = NULL;
    /**
     * @var
     */
    public $encoding;
    /**
     * @var
     */
    public $ajaxpage;
    /**
     * @var string
     */
    public $lang_dir = '';

    /**
     * @param      $title
     * @param bool $index
     */
    protected function __construct($title, $index = FALSE) {
      $this->is_index = $index;
      $this->title = $title;
      $this->frame = isset($_GET['frame']);
    }

    /**
     * @param $menu
     */
    protected function init($menu) {
      $this->app = $_SESSION['App'];
      $this->sel_app = $this->app->selected;
      $this->ajaxpage = (AJAX_REFERRER || Ajax::in_ajax());
      $this->menu = ($this->frame) ? FALSE : $menu;
      $this->renderer = new Renderer();
      $this->theme = User::theme();
      $this->encoding = $_SESSION['Language']->encoding;
      $this->lang_dir = $_SESSION['Language']->dir;
      if (!$this->ajaxpage) {
        $this->header();
        JS::open_window(900, 500);

        if ($this->menu) {
          $this->menu_header();
        }
      }
      Errors::error_box();
      if (!$this->ajaxpage) {
        echo "<div id='wrapper'>";
      }
      if ($this->title && !$this->is_index && !$this->frame) {
        echo "<div class='titletext'>$this->title" . (User::hints() ? "<span id='hints' class='floatright'
										style='display:none'></span>" : '') . "</div>";
      }
      Security::check_page(static::$security);
      Display::div_start('_page_body');
    }

    /**
     * @static
     *
     * @param        $title
     * @param string $security
     * @param bool   $no_menu
     * @param bool   $is_index
     *
     * @return null|Page
     */
    static public function start($title, $security = SA_OPEN, $no_menu = FALSE, $is_index = FALSE) {
      static::set_security($security);
      if (static::$i === NULL) {
        static::$i = new static($title, $is_index);
      }
      static::$i->init(!$no_menu);
      return static::$i;
    }

    /**
     * @static
     *
     * @param bool $numeric_id
     *
     * @return array
     */
    static public function simple_mode($numeric_id = TRUE) {
      $default = $numeric_id ? -1 : '';
      $selected_id = get_post('selected_id', $default);
      foreach (array(ADD_ITEM, UPDATE_ITEM, MODE_RESET, MODE_CLONE) as $m) {
        if (isset($_POST[$m])) {
          Ajax::i()->activate('_page_body');
          if ($m == MODE_RESET || $m == MODE_CLONE) {
            $selected_id = $default;
          }
          unset($_POST['_focus']);
          return array($m, $selected_id);
        }
      }
      foreach (array(MODE_EDIT, MODE_DELETE) as $m) {
        foreach ($_POST as $p => $pvar) {
          if (strpos($p, $m) === 0) {
            unset($_POST['_focus']); // focus on first form entry
            $selected_id = quoted_printable_decode(substr($p, strlen($m)));
            Ajax::i()->activate('_page_body');
            return array($m, $selected_id);
          }
        }
      }
      return array('', $selected_id);
    }

    /**
     * @static
     *
     * @param bool $file
     */
    static public function add_css($file = FALSE) {
      static::$i->css[] = $file;
    }

    /**
     * @static
     *
     * @param $security
     */
    static public function set_security($security) {
      static::$security = $security;
    }

    /**
     * @static
     * @return null
     */
    public static function get_security() { return static::$security; }

    /**
     * @static

     */
    static public function footer_exit() {
      Display::br(2);
      static::$i->end_page(TRUE);
      exit;
    }

    /**

     */
    protected function header() {
      $this->header = TRUE;
      JS::open_window(900, 500);
      if (!headers_sent()) {
        header("Content-type: text/html; charset={$this->encoding}");
      }
      echo "<!DOCTYPE HTML>\n";
      echo "<html " . (is_object($this->sel_app) ? "class='" . strtolower($this->sel_app->id) . "'" :
        '') . " dir='" . $this->lang_dir . "' >\n";
      echo "<head><title>" . $this->title . "</title>";
      echo "<meta charset='{$this->encoding}'>";
      echo "<link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>";
      $this->renderCSS();
      if (class_exists('JS', FALSE)) {
        JS::renderHeader();
      }
      echo "</head><body" . (!$this->menu ? ' class="lite">' : '>');
      echo "<div id='content'>\n";
    }

    /**

     */
    protected function menu_header() {
      echo "<div class='ajaxmark'><img alt='Ajax Loading' id='ajaxmark' src='/themes/" . User::theme() . "/images/ajax-loader.gif'>\n";

      echo "<div id='top'><p>" . Config::get('db.' . User::i()->company)['company'] . " | " . $_SERVER['SERVER_NAME'] . " | " .
        User::i()
          ->name . "</p>\n";
      echo "<ul>\n";
      echo   " <li><a href='" . BASE_URL . "system/display_prefs.php?'>" . _("Preferences") . "</a></li>\n" . " <li><a
		href='" . BASE_URL . "system/change_current_user_password.php?selected_id=" . User::i()->username . "'>" . _("Change password") . "</a></li>\n";
      if (Config::get('help_baseurl') != NULL) {
        echo " <li><a target = '_blank' class='.openWindow' href='" . $this->help_url() . "'>" . _("Help") . "</a></li>";
      }
      echo " <li><a href='" . BASE_URL . "access/logout.php?'>" . _("Logout") . "</a></li></ul></div></div><div
			id='logo'><h1>" . APP_TITLE . "<br><span class='slogan'>" . VERSION . "</span></h1></div><div id='_tabs2'>";
      $this->renderer->menu();
      echo "</div>";
    }

    /**
     * @param null $context
     *
     * @return string
     */
    protected function help_url($context = NULL) {
      global $help_context;
      $country = $_SESSION['Language']->code;
      if ($context != NULL) {
        $help_page_url = $context;
      }
      elseif (isset($help_context)) {
        $help_page_url = $help_context;
      }
      else // main menu
      {
        $help_page_url = Session::i()->App->applications[Session::i()->App->selected->id]->help_context;
        $help_page_url = Display::access_string($help_page_url, TRUE);
      }
      return Config::get('help_baseurl') . urlencode(strtr(ucwords($help_page_url), array(
        ' ' => '', '/' => '', '&' => 'And'
      ))) . '&ctxhelp=1&lang=' . $country;
    }

    /**
     * @static
     *
     * @param bool $hide_back_link
     */
    static public function end($hide_back_link = FALSE) {
      if (static::$i) {
        static::$i->end_page($hide_back_link);
      }
    }

    /**
     * @param $hide_back_link
     */
    protected function end_page($hide_back_link) {
      if ($this->frame) {
        $hide_back_link = TRUE;
        $this->header = FALSE;
      }
      if ((!$this->is_index && !$hide_back_link) && method_exists('Display', 'link_back')) {
        Display::link_back(TRUE, !$this->menu);
      }
      Display::div_end(); // end of _page_body section
      $this->footer();
    }

    /**
     * @return mixed
     */
    protected function footer() {
      $Validate = array();

      $this->menu_footer();
      $edits = "editors = " . Ajax::i()->php2js(Display::set_editor(FALSE, FALSE)) . ";";
      Ajax::i()->addScript('editors', $edits);
      JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . Ajax::i()->php2js($Validate) . ";var $edits");
      User::add_js_data();
      if ($this->header && $this->menu) {
        Sidemenu::render();
      }
      Messages::show();
      JS::render();
      if (AJAX_REFERRER) {
        return;
      }
      echo "</div></body>"; //End content div
      echo   "</html>\n";
    }

    /**

     */
    protected function menu_footer() {
      echo "</div>"; //end wrapper div
      if ($this->menu && !AJAX_REFERRER) {
        echo "<div id='footer'>\n";
        if (User::i()) {
          echo "<span class='power'><a target='_blank' href='" . POWERED_URL . "'>" . POWERED_BY . "</a></span>\n";
          echo "<span class='date'>" . Dates::today() . " | " . Dates::now() . "</span>\n";
          echo "<span> </span>| <span>mem/peak: " . Files::convert_size(memory_get_usage(TRUE)) . '/' . Files::convert_size(memory_get_peak_usage(TRUE)) . ' </span><span>|</span><span> load time: ' . Dates::getReadableTime(microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']) . "</span>";
        }
      }
      if (Config::get('debug.enabled')) {
        $this->display_loaded();
      }
      echo "</div>\n"; //end footer div
    }

    /**

     */
    protected function display_loaded() {
      $loaded = Autoloader::getPerf();
      $row = "<table id='autoloaded'>";
      while ($v1 = array_shift($loaded)) {
        $v2 = array_shift($loaded);
        $row .= "<tr><td>{$v1[0]}</td><td>{$v1[1]}</td><td>{$v1[2]}</td><td>{$v1[3]}</td><td>{$v2[0]}</td><td>{$v2[1]}</td><td>{$v2[2]}</td><td>{$v2[3]}</td></tr>";
      }
      echo $row . "</table>";
    }

    /**

     */
    protected function renderCSS() {
      $this->css += class_exists('Config', FALSE) ? \Config::get('assets.css') : array('default.css');
      $path = DS . "themes" . DS . $this->theme . DS;
      $css = implode(',', $this->css);
      echo "<link href='{$path}{$css}' rel='stylesheet'> \n";
    }

    /**
     * @static
     *
     * @param      $text
     * @param bool $exit
     */
    public static function error_exit($text, $exit = TRUE) {
    //  ob_get_clean();
      $page = new static('Fatal Error.', FALSE);
      $page->header();
      echo "<div id='msgbox'>$text</div></div></body></html>";
      ($exit)  and exit();
    }
  }

