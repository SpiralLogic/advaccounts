<?php
  namespace ADV\App;
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use Display;
  use ADV\Core\Errors;
  use ADV\Core\Files;
  use ADV\Core\Dates;
  use Messages;
  use Sidemenu;
  use ADV\Core\Input;
  use ADV\Core\View;
  use ADV\Core\JS;
  use Security;
  use ADVAccounting;
  use ADV\Core\Ajax;
  use ADV\Core\Config;
  use User;

  class Page
  {
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
    protected $App;
    /** @var User */
    protected $User;
    protected $Config;
    protected $sel_app;
    /**
     * @var bool
     */
    protected $frame = false;
    /**
     * @var bool
     */
    protected $menu = true;
    /**
     * @var bool
     */
    protected $is_index = false;
    /**
     * @var array
     */
    protected $css = array();
    /**
     * @var bool
     */
    protected $header = true;
    /**
     * @var string
     */
    protected $theme = 'default';
    /**
     * @var string
     */
    protected $title = '';
    /** @var Page */
    protected static $i = null;
    /**
     * @var Security
     */
    protected $Security = null;
    protected static $pagesecurity;
    /**
     * @param $hide_back_link
     */
    public function end_page($hide_back_link)
    {
      if ($this->frame) {
        $hide_back_link = true;
        $this->header   = false;
      }
      if ((!$this->is_index && !$hide_back_link) && method_exists('Display', 'link_back')) {
        Display::link_back(true, !$this->menu);
      }
      echo "<!-- end page body div -->";
      Display::div_end(); // end of _page_body section
      $this->footer();
    }
    /**
     * @param $application
     */
    public function display_application($application)
    {
      if ($application->direct) {
        Display::meta_forward($application->direct);
      }
      foreach ($application->modules as $module) {
        $app            = new View('application');
        $app['colspan'] = (count($module->rappfunctions) > 0) ? 2 : 1;
        $app['name']    = $module->name;
        foreach ([$module->lappfunctions, $module->rappfunctions] as $modules) {
          $mods = [];
          foreach ($modules as $func) {
            $mod['access'] = $this->User->can_access_page($func->access);
            $mod['label']  = $func->label;
            if ($mod['access']) {
              $mod['link'] = Display::menu_link($func->link, $func->label);
            } else {
              $mod['anchor'] = Display::access_string($func->label, true);
            }
            $mods[] = $mod;
          }
          $app->set((!$app['lmods']) ? 'lmods' : 'rmods', $mods);
        }
        $app->render();
      }
    }
    /**
     * @param      $title
     * @param bool $index
     */
    protected function __construct($title, $index = false)
    {
      $this->User     = User::i();
      $this->Config   = Config::i();
      $this->Ajax     = Ajax::i();
      $this->is_index = $index;
      $this->title    = $title;
      $this->frame    = isset($_GET['frame']);
    }
    /**
     * @param $menu
     */
    protected function init($menu)
    {
      $this->Security = new Security($this->User, $this);
      $this->App      = ADVAccounting::i();
      $this->sel_app  = $this->App->selected;
      $this->ajaxpage = (AJAX_REFERRER || Ajax::inAjax());
      $this->menu     = ($this->frame) ? false : $menu;
      $this->theme    = $this->User->theme();
      $this->encoding = $_SESSION['language']->encoding;
      $this->lang_dir = $_SESSION['language']->dir;
      if (!$this->ajaxpage) {
        $this->header();
        JS::openWindow(900, 500);
        if ($this->menu) {
          $this->menu_header();
        }
      }
      if (!IS_JSON_REQUEST) {
        Errors::errorBox();
      }
      if (!$this->ajaxpage) {
        echo "<div id='wrapper'>";
      }
      if ($this->Security->check_page(static::$pagesecurity)){
        echo "<div class='center'><br><br><br><span class='bold'>";
        echo _("The security settings on your account do not permit you to access this function");
        echo "</span>";
        echo "<br><br><br><br></div>";
        $this->end_page(false);
        exit;
      }

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
      $this->header = true;
      JS::openWindow(900, 500);
      if (!headers_sent()) {
        header("Content-type: text/html; charset={$this->encoding}");
      }
      $header                = new View('header');
      $header['class']       = (is_object($this->sel_app) ? strtolower($this->sel_app->id) : '');
      $header['lang_dir']    = $this->lang_dir;
      $header['title']       = $this->title;
      $header['body_class']  = !$this->menu ? 'lite' : '';
      $header['encoding']    = $_SESSION['language']->encoding;
      $header['stylesheets'] = $this->renderCSS();
      $header['scripts']     = [];
      if (class_exists('JS', false)) {
        $header['scripts'] = JS::renderHeader();
      }
      $header->render();
    }
    /**

     */
    protected function menu_header()
    {
      $menu                = new View('menu_header');
      $menu['theme']       = $this->User->theme();
      $menu['company']     = $this->Config->_get('db.' . $this->User->company)['company'];
      $menu['server_name'] = $_SERVER['SERVER_NAME'];
      $menu['username']    = $this->User->username;
      $menu['name']        = $this->User->name;
      $menu['help_url']    = '';
      if ($this->Config->_get('help_baseurl') != null) {
        $menu['help_url'] = $this->help_url();
      }
      /** @var ADVAccounting $application */
      $menuitems = [];
      foreach ($this->App->applications as $app) {
        $item          = [];
        $acc           = Display::access_string($app->name);
        $item['acc0']  = $acc[0];
        $item['acc1']  = $acc[1];
        $item['class'] = ($this->sel_app && $this->sel_app->id == $app->id) ? "active" : "";
        $item['href']  = (!$app->direct) ? '/index.php?application=' . $app->id : '/' . ltrim($app->direct, '/');
        $menuitems[]   = $item;
      }
      $menu->set('menu', $menuitems);
      $menu->render();
    }
    /**
     * @param null $context
     *
     * @return string
     */
    protected function help_url($context = null)
    {
      global $help_context;
      $country = $_SESSION['language']->code;
      if ($context != null) {
        $help_page_url = $context;
      } elseif (isset($help_context)) {
        $help_page_url = $help_context;
      } else // main menu
      {
        $help_page_url = $this->App->applications[$this->App->selected->id]->help_context;
        $help_page_url = Display::access_string($help_page_url, true);
      }
      return $this->Config->_get('help_baseurl') . urlencode(strtr(ucwords($help_page_url), array(
                                                                                                 ' ' => '',
                                                                                                 '/' => '',
                                                                                                 '&' => 'And'
                                                                                            ))) . '&ctxhelp=1&lang=' . $country;
    }
    /**
     * @return mixed
     */
    protected function footer()
    {
      $validate = array();
      $footer   = $this->menu_footer();
      $footer->set('beforescripts', "_focus = '" . Input::post('_focus') . "';_validate = " . $this->Ajax->php2js($validate) . ";");
      $this->User->_add_js_data();
      if ($this->header && $this->menu) {
        $footer->set('sidemenu', Sidemenu::render());
      } else {
        $footer->set('sidemenu', '');
      }
      $footer->set('js', JS::render(true));
      if (!AJAX_REFERRER) {
        $footer->set('messages', Messages::show());
      } else {
        $footer->set('messages', '');
      }
      $footer->render();
    }
    /**

     */
    protected function menu_footer()
    {
      $footer              = new View('footer');
      $footer['today']     = Dates::today();
      $footer['now']       = Dates::now();
      $footer['mem']       = Files::convertSize(memory_get_usage(true)) . '/' . Files::convertSize(memory_get_peak_usage(true));
      $footer['load_time'] = Dates::getReadableTime(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
      $footer['user']      = $this->User->username;
      $footer['footer']    = $this->menu && !AJAX_REFERRER;
      return $footer;
    }
    /**

     */
    protected function renderCSS()
    {
      $this->css += class_exists('Config', false) ? $this->Config->_get('assets.css') : array('default.css');
      $path = DS . "themes" . DS . $this->theme . DS;
      $css  = implode(',', $this->css);
      return [$path . $css];
    }
    /**
     * @static
     *
     * @param bool $hide_back_link
     */
    public static function end($hide_back_link = false)
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
    public static function error_exit($text, $exit = true)
    {
      ob_get_clean();
      $page = new static('Fatal Error.', false);
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
    public static function start($title, $security = SA_OPEN, $no_menu = false, $is_index = false)
    {
      if (static::$i === null) {
        static::$i = new static($title, $is_index);
      }
      static::$i->set_security($security);
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
    public static function simple_mode($numeric_id = true)
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
     * @param $security
     */
    public static function set_security($security)
    {
      static::$pagesecurity = $security;
    }
    /**
     * @static
     * @return null
     */
    public static function get_security()
    {
      return static::$pagesecurity;
    }
    public static function footer_exit()
    {
      Display::br(2);
      static::$i->end_page(true);
      exit;
    }
  }

