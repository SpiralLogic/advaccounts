<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Page
  {
    /** @var \Renderer */
    public $renderer = NULL;
    /** @var \Twig_Environment  */

    public $Twig = NULL;
    /**
     * @var
     */
    public $encoding;
    /**
     * @var
     */
    public $ajaxpage;
    public $lang_dir = '';
    /** @var ADVAccounting */
    protected $app;
    protected $User;
    protected $Config;
    protected $sel_app;
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
    /**
     * @var string
     */
    protected $title = '';
    /** @var Page */
    protected static $i = NULL;
    /**
     * @var null
     */
    protected static $security = NULL;
    /**
     * @param      $title
     * @param bool $index
     */
    protected function __construct($title, $index = FALSE)
    {
      $this->User     = User::i();
      $this->Config   = Config::i();
      $this->Ajax     = Ajax::i();
      $this->is_index = $index;
      $this->title    = $title;
      $this->frame    = isset($_GET['frame']);
      require_once 'Twig/Autoloader.php';
      \Twig_Autoloader::register();
      $loader     = new \Twig_Loader_Filesystem(DOCROOT . 'views');
      $this->Twig = new \Twig_Environment($loader);
    }
    /**
     * @param $menu
     */
    protected function init($menu)
    {
      $this->app      = ADVAccounting::i();
      $this->sel_app  = $this->app->selected;
      $this->ajaxpage = (AJAX_REFERRER || Ajax::in_ajax());
      $this->menu     = ($this->frame) ? FALSE : $menu;
      $this->renderer = new Renderer();
      $this->theme    = $this->User->theme();
      $this->encoding = $_SESSION['Language']->encoding;
      $this->lang_dir = $_SESSION['Language']->dir;

      if (!$this->ajaxpage) {
        $this->header();
        JS::open_window(900, 500);
        if ($this->menu) {
          $this->menu_header();
        }
      }
      if (!IS_JSON_REQUEST) {
        Errors::error_box();
      }
      if (!$this->ajaxpage) {
        echo "<div id='wrapper'>";
      }
      Security::i()->check_page(static::$security);
      if ($this->title && !$this->is_index && !$this->frame && !IS_JSON_REQUEST) {
        echo "<div class='titletext'>$this->title" . ($this->User->hints() ? "<span id='hints' class='floatright'
										style='display:none'></span>" : '') . "</div>";
      }
      Display::div_start('_page_body');
    }
    /**

     */
    protected function header()
    {
      $this->header = TRUE;
      JS::open_window(900, 500);
      if (!headers_sent()) {
        header("Content-type: text/html; charset={$this->encoding}");
      }
      $viewdata['class']       = (is_object($this->sel_app) ? strtolower($this->sel_app->id) : '');
      $viewdata['lang_dir']    = $this->lang_dir;
      $viewdata['title']       = $this->title;
      $viewdata['body_class']  = !$this->menu ? 'lite' : '';
      $viewdata['encoding']    = $_SESSION['Language']->encoding;
      $viewdata['stylesheets'] = $this->renderCSS();
      if (class_exists('JS', FALSE)) {
        $viewdata['scripts'] = JS::renderHeader();
      }
      echo $this->Twig->render('header.twig', $viewdata);
    }
    /**

     */
    protected function menu_header()
    {
      $viewdata['BASE_URL']    = BASE_URL;
      $viewdata['APP_TITLE']   = APP_TITLE;
      $viewdata['VERSION']     = VERSION;
      $viewdata['theme']       = $this->User->theme();
      $viewdata['company']     = Config::get('db.' . $this->User->company)['company'];
      $viewdata['server_name'] = $_SERVER['SERVER_NAME'];
      $viewdata['username']    = $this->User->name;
      $viewdata['help_url']    = '';
      if (Config::get('help_baseurl') != NULL) {
        $viewdata['help_url'] = $this->help_url();
      }
      echo $this->Twig->render('menu_header.twig', $viewdata);
      $this->renderer->menu();
      echo "</div>";
    }
    /**
     * @param null $context
     *
     * @return string
     */
    protected function help_url($context = NULL)
    {
      global $help_context;
      $country = $_SESSION['Language']->code;
      if ($context != NULL) {
        $help_page_url = $context;
      } elseif (isset($help_context)) {
        $help_page_url = $help_context;
      } else // main menu
      {
        $help_page_url = ADVAccounting::i()->applications[ADVAccounting::i()->selected->id]->help_context;
        $help_page_url = Display::access_string($help_page_url, TRUE);
      }
      return Config::get('help_baseurl') . urlencode(strtr(ucwords($help_page_url), array(
                                                                                         ' ' => '', '/' => '', '&' => 'And'
                                                                                    ))) . '&ctxhelp=1&lang=' . $country;
    }
    /**
     * @param $hide_back_link
     */
    protected function end_page($hide_back_link)
    {
      if ($this->frame) {
        $hide_back_link = TRUE;
        $this->header   = FALSE;
      }
      if ((!$this->is_index && !$hide_back_link) && method_exists('Display', 'link_back')) {
        Display::link_back(TRUE, !$this->menu);
      }
      echo "<!-- end page body div -->";
      Display::div_end(); // end of _page_body section
      $this->footer();
    }
    /**
     * @return mixed
     */
    protected function footer()
    {
      $validate = array();
      $this->menu_footer();
      JS::beforeload("_focus = '" . Input::post('_focus') . "';_validate = " . Ajax::i()->php2js($validate) . ";");
      $this->User->add_js_data();
      echo "<!-- end content div-->";
      echo "</div>";
      if ($this->header && $this->menu) {
        Sidemenu::render();
      }
      if (AJAX_REFERRER) {
        JS::render();
        return;
      } else {
        Messages::show();
      }
      JS::render();
      echo   "</body></html>\n";
    }
    /**

     */
    protected function menu_footer()
    {
      echo "<!-- end wrapper div-->";
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
      echo "<!-- end footer div-->";
      echo "</div>\n"; //end footer div
    }
    /**

     */
    protected function display_loaded()
    {
      $loaded = Autoloader::getPerf();
      $row    = "<table id='autoloaded'>";
      while ($v1 = array_shift($loaded)) {
        $v2 = array_shift($loaded);
        $row .= "<tr><td>{$v1[0]}</td><td>{$v1[1]}</td><td>{$v1[2]}</td><td>{$v1[3]}</td><td>{$v2[0]}</td><td>{$v2[1]}</td><td>{$v2[2]}</td><td>{$v2[3]}</td></tr>";
      }
      echo $row . "</table>";
    }
    /**

     */
    protected function renderCSS()
    {
      $this->css += class_exists('Config', FALSE) ? \Config::get('assets.css') : array('default.css');
      $path = DS . "themes" . DS . $this->theme . DS;
      $css  = implode(',', $this->css);
      return [$path . $css];
    }
    /**
     * @static
     *
     * @param bool $hide_back_link
     */
    public static function end($hide_back_link = FALSE)
    {
      if (static::$i) {
        static::$i->end_page($hide_back_link);
      }
    }
    /**
     * @static
     *
     * @param      $text
     * @param bool $exit
     */
    public static function error_exit($text, $exit = TRUE)
    {
      ob_get_clean();
      $page = new static('Fatal Error.', FALSE);
      $page->header();
      echo "<div id='msgbox'>$text</div></div></body></html>";
      ($exit)  and exit();
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
    public static function start($title, $security = SA_OPEN, $no_menu = FALSE, $is_index = FALSE)
    {
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
    public static function simple_mode($numeric_id = TRUE)
    {
      $default     = $numeric_id ? -1 : '';
      $selected_id = Input::post('selected_id', null, $default);
      foreach (array(ADD_ITEM, UPDATE_ITEM, MODE_RESET, MODE_CLONE) as $m) {
        if (isset($_POST[$m])) {
          Ajax::activate('_page_body');
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
            Ajax::activate('_page_body');
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
    public static function add_css($file = FALSE)
    {
      static::$i->css[] = $file;
    }
    /**
     * @static
     *
     * @param $security
     */
    public static function set_security($security)
    {
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
    public static function footer_exit()
    {
      Display::br(2);
      static::$i->end_page(TRUE);
      exit;
    }
  }

