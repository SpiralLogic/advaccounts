<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class User {

    use \ADV\Core\Traits\Hook;
    use ADV\Core\Traits\StaticAccess;

    /***
     * @static
     * @return User
     */
    public $user;
    /**
     * @var string
     */
    public $loginname;
    /**
     * @var
     */
    public $username;
    /**
     * @var
     */
    public $name;
    /**
     * @var string
     */
    public $company;
    /**
     * @var
     */
    public $pos;
    /**
     * @var bool
     */
    public $salesmanid = FALSE;
    /**
     * @var
     */
    public $access;
    /**
     * @var
     */
    public $timeout;
    /**
     * @var
     */
    public $last_act;
    /**
     * @var array
     */
    public $role_set = array();
    /**
     * @var
     */
    public $old_db;
    /**
     * @var bool
     */
    public $logged = FALSE;
    /**
     * @var int
     */
    public $ui_mode = 0;
    /***
     * @var \userPrefs
     */
    public $prefs;
    /**
     * @var bool
     */
    public $change_password = FALSE;
    public $selectedApp;
    /**
     * @var
     */
    protected $access_sections;
    protected $_hash;
    /**
     * @var null
     */
    protected static $_instance = NULL;
    /**
     * @var
     */
    public $last_record;
    protected $session;
    /**
     * @static
     * @return mixed
     */
    static function  getCurrentUser()
    {
      global $dic;
      if (isset($_SESSION["current_user"])) {
        $dic['User'] = $dic->share(function() { return $_SESSION["current_user"]; });
      }
      $_SESSION["current_user"] = static::i();

      return $dic['User'];
    }

    /**

     */
    public function __construct(Session $session = null)
    {

      $this->session   = $session ? : Session::i();
      $this->loginname = $this->username = $this->name = "";
      $this->company   = Config::get('default.company') ? : 'default';
      $this->logged    = FALSE;
      $this->prefs     = new userPrefs();
    }
    /**
     * @param null $salesmanid
     */
    public function set_salesman($salesmanid = NULL)
    {
      if ($salesmanid == NULL) {
        $salesman_name = $this->name;
        $sql           = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::escape($salesman_name);
        $query         = DB::query($sql, 'Couldn\'t find current salesman');
        $result        = DB::fetch_assoc($query);
        if (!empty($result['salesman_code'])) {
          $this->salesmanid = $result['salesman_code'];
        }
      }
      if ($salesmanid != NULL) {
        $this->salesmanid = $salesmanid;
      }
    }
    /**
     * @return bool
     */
    public function logged_in()
    {
      $this->timeout();
      if ($this->logged && date('i', time() - $this->last_record) > 4) {
        $this->last_record = time();
        Event::register_shutdown(__CLASS__, 'addLog');
      }
      return $this->logged;
    }
    /**
     * @param $company
     */
    public function set_company($company)
    {
      $this->company = $company;
    }
    /**
     * @param $company
     * @param $loginname
     *
     * @internal param $password
     * @return bool
     */
    public function login($company, $loginname)
    {
      $this->set_company($company);
      $this->logged = FALSE;
      $myrow        = Users::get_for_login($loginname, $_POST['password']);
      if ($myrow) {
        if (!$myrow["inactive"]) {
          $this->role_set = array();
          $this->access   = $myrow["role_id"];
          $this->_hash    = $myrow["hash"];
          // store area codes available for current user role
          $role = Security::i()->get_role($this->access);
          if (!$role) {
            return FALSE;
          }
          $this->access_sections = $role['sections'];
          foreach ($role['areas'] as $code) // filter only area codes for enabled security sections
          {
            if (in_array($code & ~0xff, $role['sections'])) {
              $this->role_set[] = $code;
            }
          }
        }
        $this->change_password = $myrow['change_password'];
        $this->logged          = TRUE;
        $this->name            = $myrow["real_name"];
        $this->pos             = $myrow["pos"];
        $this->username        = $this->loginname = $loginname;
        $this->prefs           = new userPrefs($myrow);
        $this->user            = $myrow["id"];
        $this->last_act        = time();
        $this->timeout         = DB_Company::get_pref('login_tout');
        $this->salesmanid      = $this->get_salesmanid();
        $this->fireHooks('login');
        $this->session->checkUserAgent();
        Event::register_shutdown('Users', 'update_visitdate', [$this->username]);
        Event::register_shutdown(__CLASS__, 'addLog');
      }
      return $this->logged;
    }
    /**
     * @static
     *
     * @param       $object
     * @param       $function
     * @param array $arguments
     */
    public function _register_login($object, $function = NULL, $arguments = array())
    {
      $this->registerHook('login', $object, $function, $arguments);
    }
    /**
     * @static
     *
     * @param       $object
     * @param       $function
     * @param array $arguments
     */
    public function _register_logout($object, $function, $arguments = array())
    {
      $this->registerHook('logout', $object, $function, $arguments);
    }
    /**

     */
    public function timeout()
    {
      // skip timeout on logout page
      if ($this->logged) {
        $tout = $this->timeout;
        if ($tout && (time() > $this->last_act + $tout)) {
          $this->logged = FALSE;
        }
        $this->last_act = time();
      }
    }
    public function _addLog()
    {
      DB::insert('user_login_log')->values(array(
        'user'    => $this->username, 'IP'      => Users::get_ip(), 'success' => 2
      ))->exec();
    }
    /**
     * @param $page_level
     *
     * @return bool
     */
    public function can_access($page_level)
    {
      global $security_areas;
      if ($page_level === SA_OPEN) {
        return TRUE;
      }
      if ($page_level === SA_DENIED || $page_level === '') {
        return FALSE;
      }
      $access = FALSE;
      if (isset($security_areas[$page_level])) {
        $code   = $security_areas[$page_level][0];
        $access = $code && in_array($code, $this->role_set);
      } elseif (isset($this->access_sections) && in_array($page_level, $this->access_sections)) {
        $access = in_array($page_level, $this->access_sections);
      }
      // only first registered company has site admin privileges
      return $access && ($this->company == 'default' || (isset($code) && ($code & ~0xff) != SS_SADMIN));
    }
    /**
     * @param $page_level
     *
     * @return bool
     */
    public function can_access_page($page_level)
    {
      return $this->can_access($page_level);
    }
    /**
     * @param $price_dec
     * @param $qty_dec
     * @param $exrate_dec
     * @param $percent_dec
     * @param $show_gl
     * @param $show_codes
     * @param $date_format
     * @param $date_sep
     * @param $tho_sep
     * @param $dec_sep
     * @param $theme
     * @param $page_size
     * @param $show_hints
     * @param $profile
     * @param $rep_popup
     * @param $query_size
     * @param $graphic_links
     * @param $lang
     * @param $stickydate
     * @param $startup_tab
     */
    public function update_prefs($price_dec, $qty_dec, $exrate_dec, $percent_dec, $show_gl, $show_codes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $page_size, $show_hints, $profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab)
    {
      $user = array(
        'price_dec'       => $price_dec,
        'qty_dec'         => $qty_dec,
        'exrate_dec'      => $exrate_dec,
        'percent_dec'     => $percent_dec,
        'show_gl'         => $show_gl,
        'show_codes'      => $show_codes,
        'date_format'     => $date_format,
        'date_sep'        => $date_sep,
        'tho_sep'         => $tho_sep,
        'dec_sep'         => $dec_sep,
        'theme'           => $theme,
        'page_size'       => $page_size,
        'show_hints'      => $show_hints,
        'print_profile'   => $profile,
        'rep_popup'       => $rep_popup,
        'query_size'      => $query_size,
        'graphic_links'   => $graphic_links,
        'language'        => $lang,
        'sticky_doc_date' => $stickydate,
        'startup_tab'     => $startup_tab
      );
      if (!Config::get('demo_mode')) {
        Users::update_display_prefs($this->user, $price_dec, $qty_dec, $exrate_dec, $percent_dec, $show_gl, $show_codes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $page_size, $show_hints, $profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab);
      }
      $this->prefs = new userPrefs(Users::get($this->user));
    }
    /**
     * @static
     * @return userPrefs
     */
    public function _prefs()
    {
      return $this->prefs;
    }
    /**
     * @static

     */
    public function _add_js_data()
    {
      $js
        = "var user = {theme: '/themes/" . $this->theme() . "/',loadtxt: '" . _('Requesting data...') . "',date: '" . Dates::today() . "',datefmt: " . $this->date_format() . ",datesep: '" . Config::get('date.ui_format') . "',
        ts: '" . $this->tho_sep() . "',ds: '" . $this->dec_sep() . "',pdec: " . $this->price_dec() . "};";
      JS::beforeload($js);
    }
    /**
     * @static
     * @return bool
     */
    public function _fallback()
    {
      return $this->ui_mode == 0;
    }
    /**
     * @static
     *
     * @param $input
     *
     * @return bool|float|int|mixed|string
     */
    public function _numeric($input)
    {
      $num = trim($input);
      $sep = $this->tho_sep();
      if ($sep != '') {
        $num = str_replace($sep, '', $num);
      }
      $sep = $this->dec_sep();
      if ($sep != '.') {
        $num = str_replace($sep, '.', $num);
      }
      if (!is_numeric($num)) {
        return FALSE;
      }
      $num = (float) $num;
      if ($num == (int) $num) {
        return (int) $num;
      } else {
        return $num;
      }
    }
    /**
     * @static
     * @return mixed
     */
    public function _pos()
    {
      return $this->pos;
    }
    /**
     * @static
     * @return mixed
     */
    public function _language()
    {
      return $this->prefs()->language();
    }
    /**
     * @static
     * @return mixed
     */
    public function _qty_dec()
    {
      return $this->prefs()->qty_dec();
    }
    /**
     * @static
     * @return mixed
     */
    public function _price_dec()
    {
      return $this->prefs()->price_dec();
    }
    /**
     * @static
     * @return mixed
     */
    public function _exrate_dec()
    {
      return $this->prefs()->exrate_dec();
    }
    /**
     * @static
     * @return mixed
     */
    public function _percent_dec()
    {
      return $this->prefs()->percent_dec();
    }
    /**
     * @static
     * @return mixed
     */
    public function _show_gl()
    {
      return $this->prefs()->show_gl();
    }
    /**
     * @static
     * @return mixed
     */
    public function _show_codes()
    {
      return $this->prefs()->show_codes();
    }
    /**
     * @static
     * @return mixed
     */
    public function _date_format()
    {
      return $this->prefs()->date_format();
    }
    /**
     * @static
     * @return mixed
     */
    public function _date_display()
    {
      return $this->prefs()->date_display();
    }
    /**
     * @static
     * @return int
     */
    public function _date_sep()
    {
      return (isset($_SESSION["current_user"])) ? $this->prefs()->date_sep() : Config::get('date.ui_separator');
    }
    /**
     * @static
     * @return mixed
     */
    public function _tho_sep()
    {
      return $this->prefs()->tho_sep();
    }
    /**
     * @static
     * @return mixed
     */
    public function _dec_sep()
    {
      return $this->prefs()->dec_sep();
    }
    /**
     * @static
     * @return mixed
     */
    public function _theme()
    {
      return $this->prefs()->get_theme();
    }
    /**
     * @static
     * @return mixed
     */
    public function _page_size()
    {
      return $this->prefs()->get_pagesize();
    }
    /**
     * @static
     * @return mixed
     */
    public function _hints()
    {
      return $this->prefs()->show_hints();
    }
    /**
     * @static
     * @return mixed
     */
    public function _print_profile()
    {
      return $this->prefs()->print_profile();
    }
    /**
     * @static
     * @return mixed
     */
    public function _rep_popup()
    {
      return $this->prefs()->rep_popup();
    }
    /**
     * @static
     * @return mixed
     */
    public function _query_size()
    {
      return $this->prefs()->query_size();
    }
    /**
     * @static
     * @return mixed
     */
    public function _graphic_links()
    {
      return $this->prefs()->graphic_links();
    }
    /**
     * @static
     * @return mixed
     */
    public function _sticky_doc_date()
    {
      return $this->prefs()->sticky_doc_date();
    }
    /**
     * @static
     * @return mixed
     */
    public function _startup_tab()
    {
      return $this->prefs()->start_up_tab();
    }
    /**
     * @return mixed
     */
    private function get_salesmanid()
    {
      return DB::select('salesman_code')->from('salesman')->where('user_id=', $this->user)->fetch()->one('salesman_code');
    }
    public function _logout()
    {
      $this->session->kill();
      $this->logged = FALSE;
    }
    public function getHash()
    {
      return $this->_hash;
    }
  }


