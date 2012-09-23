<?php
  namespace ADV\App;

  use ADV\App\ADVAccounting;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\Files;
  use ADV\Core\Input\Input;
  use ADV\Core\View;
  use ADV\Core\JS;
  use ADV\Core\Ajax;
  use ADV\Core\Config;

  /**

   */
  class Page {
    /**
     * @var
     */
    public $encoding;
    /**
     * @var
     */
    public $ajaxpage;
    public $lang_dir = '';
    /** @var \ADV\App\ADVAccounting */
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
    public static $before_box = '';
    /**
     * @var bool
     */
    protected $isIndex = false;
    /**
     * @var array
     */
    protected $css = [];
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
    public static $i = null;
    protected $JS = null;
    /** @var Dates */
    protected $Dates = null;
    /** @var \Security */
    protected $security;
    public $hide_back_link;
    public $renderedjs;
    /**
     * @param $hide_back_link
     */
    public function end_page($hide_back_link) {
      $this->hide_back_link = $hide_back_link;
      if ($this->frame) {
        $this->hide_back_link = true;
        $this->header         = false;
      }
      $this->footer();
    }
    /**
     * @param $application
     */
    public function display_application($application) {
      if ($application->direct) {
        Display::meta_forward($application->direct);
      }

      foreach ($application->modules as $module) {
        $app            = new View('application');
        $app['colspan'] = (count($module->rightAppFunctions) > 0) ? 2 : 1;
        $app['name']    = $module->name;
        foreach ([$module->leftAppFunctions, $module->rightAppFunctions] as $modules) {
          $mods = [];
          foreach ($modules as $func) {
            $mod['access'] = $this->User->hasAccess($func->access);
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
     * @param User             $user
     * @param \ADV\Core\Config $config
     * @param \ADV\Core\Ajax   $ajax
     * @param \ADV\Core\JS     $js
     * @param Dates            $dates

     */
    public function __construct(User $user, Config $config, \ADV\Core\Ajax $ajax, \ADV\Core\JS $js, \ADV\App\Dates $dates) {
      $this->User   = $user ? : User::i();
      $this->Config = $config ? : Config::i();
      $this->Ajax   = $ajax ? : Ajax::i();
      $this->JS     = $js ? : JS::i();
      $this->Dates  = $dates ? : Dates::i();
      $this->frame  = isset($_GET['frame']);
    }
    /**
     * @param $menu
     */
    protected function init($menu) {
      $this->App      = ADVAccounting::i();
      $path           = explode('/', $_SERVER['DOCUMENT_URI']);
      $this->sel_app  = $path[1];
      $this->ajaxpage = (REQUEST_AJAX || Ajax::_inAjax());
      $this->menu     = ($this->frame) ? false : $menu;
      $this->theme    = $this->User->theme();
      $this->encoding = $_SESSION['language']->encoding;
      $this->lang_dir = $_SESSION['language']->dir;
      if (!$this->ajaxpage) {
        $this->header();
        $this->JS->openWindow(900, 500);
        if ($this->menu) {
          $this->menu_header();
        }
      }
      if (!REQUEST_JSON) {
        $this->errorBox();
      }
      if (!$this->ajaxpage) {
        echo "<div id='wrapper'>";
      }
      if (!$this->User->hasAccess($this->security)) {
        echo "<div class='center'><br><br><br><span class='bold'>";
        echo _("The security settings on your account do not permit you to access this function");
        echo "</span>";
        echo "<br><br><br><br></div>";
        $this->end_page(false);
        exit;
      }
      if ($this->title && !$this->isIndex && !$this->frame && !REQUEST_JSON) {
        echo "<div class='titletext'>$this->title" . ($this->User->_hints() ? "<span id='hints' class='floatright' style='display:none'></span>" : '') . "</div>";
      }
      if (!REQUEST_JSON) {
        Display::div_start('_page_body');
      }
    }
    protected function header() {
      $this->header = true;
      $this->JS->openWindow(900, 500);
      if (!headers_sent()) {
        header("Content-type: text/html; charset={$this->encoding}");
      }
      $header                = new View('header');
      $header['class']       = strtolower($this->sel_app);
      $header['lang_dir']    = $this->lang_dir;
      $header['title']       = $this->title;
      $header['body_class']  = !$this->menu ? 'lite' : '';
      $header['encoding']    = $_SESSION['language']->encoding;
      $header['stylesheets'] = $this->renderCSS();
      $header['scripts']     = [];
      if (class_exists('JS', false)) {
        $header['scripts'] = $this->JS->renderHeader();
      }
      $header->render();
    }
    protected function menu_header() {
      $menu                = new View('menu_header');
      $menu['theme']       = $this->User->theme();
      $menu['company']     = $this->Config->get('db.' . $this->User->company)['company'];
      $menu['server_name'] = $_SERVER['SERVER_NAME'];
      $menu['username']    = $this->User->username;
      $menu['name']        = $this->User->name;
      $menu['help_url']    = '';
      if ($this->Config->get('help_baseurl') != null) {
        $menu['help_url'] = $this->help_url();
      }
      /** @var ADVAccounting $application */
      $menuitems = [];
      foreach ($this->App->applications as $app=> $config) {
        $item          = [];
        $acc           = Display::access_string($app);
        $item['acc0']  = isset($config['name']) ? $config['name'] : $acc[0];
        $item['acc1']  = $acc[1];
        $item['class'] = ($this->sel_app == strtolower($app)) ? "active" : null;
        $item['href']  = '/' . strtolower($item['acc0']);
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
    protected function help_url($context = null) {
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

      return $this->Config->get('help_baseurl') . urlencode(
        strtr(
          ucwords($help_page_url),
          array(
               ' ' => '',
               '/' => '',
               '&' => 'And'
          )
        )
      ) . '&ctxhelp=1&lang=' . $country;
    }
    /**
     * @return \ADV\Core\View
     */
    protected function menu_footer() {
      $footer             = new View('footer');
      $footer['backlink'] = false;
      if ((!$this->isIndex && !$this->hide_back_link)) {
        $footer['backlink'] = $this->menu ? _("Back") : _("Close");
      }
      $footer['today']     = $this->Dates->today();
      $footer['now']       = $this->Dates->now();
      $footer['mem']       = Files::convertSize(memory_get_usage(true)) . '/' . Files::convertSize(memory_get_peak_usage(true));
      $footer['load_time'] = Dates::_getReadableTime(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
      $footer['user']      = $this->User->username;
      $footer['footer']    = $this->menu && !REQUEST_AJAX;

      return $footer;
    }
    /**
     * @return mixed
     */
    protected function footer() {
      $validate = [];
      $footer   = $this->menu_footer();
      $footer->set('beforescripts', "_focus = '" . Input::_post('_focus') . "';_validate = " . $this->Ajax->php2js($validate) . ";");
      $this->User->_add_js_data();
      $footer->set('sidemenu', ($this->header && $this->menu ? ['bank'=> $this->User->hasAccess(SS_GL)] : false));
      $footer->set('JS', $this->JS);
      $footer->set('messages', (!REQUEST_AJAX ? Messages::show() : ''));
      $footer->set('page_body', Display::div_end(true));
      $footer->render();
    }
    /**
     * @return array
     */
    protected function renderCSS() {
      $this->css += $this->Config->get('assets.css');
      $path = THEME_PATH . $this->theme . DS;
      $css  = implode(',', $this->css);

      return [$path . $css];
    }
    /**
     * @static
     *
     * @param bool $hide_back_link
     */
    public static function end($hide_back_link = false) {
      if (static::$i) {
        static::$i->end_page($hide_back_link);
      }
    }
    /**
     * @static
     *
     * @param        $title
     * @param string $security
     * @param bool   $no_menu
     * @param bool   $isIndex
     *
     * @return null|Page
     */
    public static function start($title, $security = SA_OPEN, $no_menu = false, $isIndex = false) {
      if (static::$i === null) {
        static::$i = new static(User::i(), Config::i(), \ADV\Core\Ajax::i(), \ADV\Core\JS::i(), \ADV\App\Dates::i());
      }
      if (is_array($title)) {
        static::$i->title   = $title[0];
        static::$i->sel_app = $title[1];
      } else {
        static::$i->title = $title;
      }
      static::$i->isIndex  = $isIndex;
      static::$i->security = $security;
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
    public static function simple_mode($numeric_id = true) {
      $default     = $numeric_id ? -1 : '';
      $selected_id = Input::_post('selected_id', null, $default);
      foreach (array(ADD_ITEM, UPDATE_ITEM, MODE_RESET, MODE_CLONE) as $m) {
        if (isset($_POST[$m])) {
          Ajax::_activate('_page_body');
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
            Ajax::_activate('_page_body');

            return array($m, $selected_id);
          }
        }
      }
      return array('', $selected_id);
    }
    public static function footer_exit() {
      static::$i->end_page(true);
      exit;
    }
    /** @static */
    public function errorBox() {
      printf("<div %s='msgbox'>", REQUEST_AJAX ? 'class' : 'id');
      static::$before_box = ob_get_clean(); // save html content before error box
      ob_start([$this->App, 'flush_handler']);
      echo "</div>";
    }
  }

