<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use ADV\Core\Session;
  use ADV\Core\JS;
  use ADV\Core\Event;
  use DB_Company;
  use ADV\Core\DB\DB;
  use ADV\Core\Config;
  use ADV\Core\Traits\StaticAccess;

  /**
   * @method static theme
   * @method User ii()
   * @method static logout()
   * @method static date_format()
   * @method static date_sep()
   * @method static int qty_dec()
   * @method static price_dec()
   * @method static numeric($input)
   * @method static print_profile()
   * @method static fallback()
   * @method static show_gl()
   * @method static rep_popup()
   * @method static percent_dec()
   * @method static graphic_links()
   * @method static register_login($object, $function = null, $arguments = [])
   */
  class User {
    use \ADV\Core\Traits\Hook;
    use StaticAccess {
    StaticAccess::i as ii;
    }

    /***
     * @static
     * @return User
     */
    public $user;
    /**
     * @var string
     */
    public $loginname;
    /** @var */
    public $username;
    /** @var */
    public $name;
    /**
     * @var string
     */
    public $company;
    /** @var */
    public $pos;
    /**
     * @var bool
     */
    public $salesmanid = false;
    /** @var */
    public $access;
    /** @var */
    public $timeout;
    /** @var */
    public $last_act;
    /**
     * @var array
     */
    protected $role_set = [];
    /** @var */
    public $old_db;
    /**
     * @var bool
     */
    public $logged = false;
    /**
     * @var int
     */
    public $ui_mode = 0;
    /***
     * @var UserPrefs
     */
    public $prefs;
    /**
     * @var bool
     */
    public $change_password = false;
    public $selectedApp;
    /** @var */
    protected $access_sections;
    protected $_hash;
    /** @var */
    public $last_record;
    /** @var Session */
    protected $Session;
    /** @var \ADV\App\Security */
    public $Security;
    /** @var \ADV\Core\DB\DB */
    protected $DB;
    /**
     * @static
     *
     * @param Session $session
     * @param Config  $config
     *
     * @return User
     */
    public static function getCurrentUser(Session $session, Config $config) {
      return static::i($session, $config);
    }
    /**
     * @static
     *
     * @param Session $session
     * @param Config  $config
     *
     * @return User
     */
    public static function  i(Session $session = null, Config $config = null) {
      $user = null;
      if (isset($_SESSION['User'])) {
        $user = $_SESSION['User'];
      }

      return static::ii($user, $session, $config);
    }
    /**
     * @param \ADV\Core\Session $session
     * @param \ADV\Core\Config  $config
     */
    public function __construct(Session $session = null, Config $config = null) {
      $this->DB             = DB::i();
      $this->Session        = $session ? : Session::i();
      $this->Config         = $config ? : Config::i();
      $this->company        = $this->Config->get('default.company') ? : 'default';
      $this->date_ui_format = $this->Config->get('date.ui_format');
      $this->logged         = false;
      $this->prefs          = new UserPrefs((array) $this);
    }
    /**
     * @return array
     */
    public function __sleep() {
      $this->Session = null;

      return array_keys((array) $this);
    }
    /**
     * @param null $salesmanid
     */
    public function set_salesman($salesmanid = null) {
      if ($salesmanid == null) {
        $salesman_name = $this->name;
        $sql           = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::_escape($salesman_name);
        $query         = DB::_query($sql, 'Couldn\'t find current salesman');
        $result        = DB::_fetchAssoc($query);
        if (!empty($result['salesman_code'])) {
          $this->salesmanid = $result['salesman_code'];
        }
      }
      if ($salesmanid != null) {
        $this->salesmanid = $salesmanid;
      }
    }
    /**
     * @return bool
     */
    public function logged_in() {
      $this->timeout();
      if ($this->logged && date('i', time() - $this->last_record) > 4) {
        $this->last_record = time();
        Event::registerShutdown([$this, '_addLog']);
      }

      return $this->logged;
    }
    /**
     * @param $company
     * @param $loginname
     *
     * @internal param $password
     * @return bool
     */
    public function login($company, $loginname) {
      $this->Session = $this->Session ? : Session::i();
      $this->company = $company;
      $this->logged  = false;
      $myrow         = Users::get_for_login($loginname, $_POST['password']);
      if ($myrow) {
        if ($myrow["inactive"]) {
          return false;
        }
        $this->role_set = [];
        $this->access   = $myrow['role_id'];
        $this->_hash    = $myrow["hash"];
        $this->Security = new Security($this->Config);
        // store area codes available for current user role
        $role = $this->Security->get_role($myrow['role_id']);
        if (!$role) {
          return false;
        }
        $this->access_sections = $role['sections'];
        foreach ($role['areas'] as $code) // filter only area codes for enabled security sections
        {
          if (in_array($code & ~0xff, $role['sections'])) {
            $this->role_set[] = $code;
          }
        }
        $this->change_password = $myrow['change_password'];
        $this->logged          = true;
        $this->name            = $myrow['real_name'];
        $this->pos             = $myrow['pos'];
        $this->username        = $this->loginname = $loginname;
        $this->prefs           = new UserPrefs($myrow);
        $this->user            = $myrow['id'];
        $this->last_act        = time();
        $this->timeout         = DB_Company::get_pref('login_tout');
        $this->salesmanid      = $this->get_salesmanid();
        $this->fireHooks('login');
        $this->Session->checkUserAgent();
        $this->Session['User'] = $this;
        Event::registerShutdown(['Users', 'update_visitdate'], [$this->username]);
        Event::registerShutdown([$this, '_addLog']);
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
    public function _register_login($function = null, $arguments = []) {
      $this->registerHook('login', $function, $arguments);
    }
    /**
     * @static
     *
     * @param       $function
     * @param array $arguments
     *
     * @internal param $object
     */
    public function _register_logout($function, $arguments = []) {
      $this->registerHook('logout', $function, $arguments);
    }
    public function timeout() {
      // skip timeout on logout page
      if ($this->logged) {
        $tout = $this->timeout;
        if ($tout && (time() > $this->last_act + $tout)) {
          $this->logged = false;
        }
        $this->last_act = time();
      }
    }
    public function _addLog() {
      DB::_insert('user_login_log')->values(
        array(
             'user'    => $this->username,
             'IP'      => Users::get_ip(),
             'success' => 2
        )
      )->exec();
    }
    /**
     * @param $page_level
     *
     * @return bool
     */
    public function hasAccess($page_level) {
      if ($page_level == SA_OPEN) {
        return true;
      }

      return $this->Security->hasAccess($this, $page_level);
    }
    /**
     * @param $section
     *
     * @return bool
     */
    public function hasSectionAccess($section) {
      return isset($this->access_sections) and in_array($section, $this->access_sections);
    }
    /**
     * @param $role
     *
     * @return bool
     */
    public function hasRole($role) {
      return in_array($role, $this->role_set);
    }
    /**
     * @param        $price_dec
     * @param        $qty_dec
     * @param        $exrate_dec
     * @param        $percent_dec
     * @param static $show_gl
     * @param        $show_codes
     * @param        $date_format
     * @param        $date_sep
     * @param        $tho_sep
     * @param        $dec_sep
     * @param        $theme
     * @param        $page_size
     * @param        $show_hints
     * @param        $profile
     * @param        $rep_popup
     * @param        $query_size
     * @param        $graphic_links
     * @param        $language
     * @param        $stickydate
     * @param        $startup_tab
     */
    public function update_prefs(
      $price_dec,
      $qty_dec,
      $exrate_dec,
      $percent_dec,
      $show_gl,
      $show_codes,
      $date_format,
      $date_sep,
      $tho_sep,
      $dec_sep,
      $theme,
      $page_size,
      $show_hints,
      $profile,
      $rep_popup,
      $query_size,
      $graphic_links,
      $language,
      $stickydate,
      $startup_tab
    ) {

      if (!$this->Config->get('demo_mode')) {
        Users::update_display_prefs(
          $this->user,
          $price_dec,
          $qty_dec,
          $exrate_dec,
          $percent_dec,
          $show_gl,
          $show_codes,
          $date_format,
          $date_sep,
          $tho_sep,
          $dec_sep,
          $theme,
          $page_size,
          $show_hints,
          $profile,
          $rep_popup,
          $query_size,
          $graphic_links,
          $language,
          $stickydate,
          $startup_tab
        );
      }
      $this->prefs = new UserPrefs(Users::get($this->user));
    }
    /**
     * @static
     * @return UserPrefs
     */
    public function _prefs() {
      return $this->prefs;
    }
    /**
     * @static

     */
    public function _add_js_data() {
      $js = "var user = {theme: '/themes/" . $this->prefs->theme . "/'" //
        . ",loadtxt: '" . _('Requesting data...') //
        . "',date: '" . Dates::_today() //
        . "',datefmt: " . $this->prefs->date_format //
        . ",datesep: '" . $this->date_ui_format //
        . "',ts: '" . $this->prefs->tho_sep //
        . "',ds: '" . $this->prefs->dec_sep //
        . "',pdec: " . $this->prefs->price_dec //
        . "};";
      JS::_beforeload($js);
    }
    /**
     * @static
     * @return bool
     */
    public function _fallback() {
      return $this->ui_mode == 0;
    }
    /**
     * @static
     *
     * @param $input
     *
     * @return bool|float|int|mixed|string
     */
    public function _numeric($input) {
      $num = trim($input);
      $sep = $this->_tho_sep();
      if ($sep != '') {
        $num = str_replace($sep, '', $num);
      }
      $sep = $this->_dec_sep();
      if ($sep != '.') {
        $num = str_replace($sep, '.', $num);
      }
      if (!is_numeric($num)) {
        return false;
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
    public function _pos() {
      return $this->pos;
    }
    /**
     * @static
     * @return mixed
     */
    public function _language() {
      return $this->prefs->language;
    }
    /**
     * @static
     * @return mixed
     */
    public function _qty_dec() {
      return $this->prefs->qty_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function _price_dec() {
      return $this->prefs->price_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function _exrate_dec() {
      return $this->prefs->exrate_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function _percent_dec() {
      return $this->prefs->percent_dec;
    }
    /**
     * @static
     * @return mixed
     */
    public function _show_gl() {
      return $this->prefs->show_gl;
    }
    /**
     * @static
     * @return mixed
     */
    public function _show_codes() {
      return $this->prefs->show_codes;
    }
    /**
     * @static
     * @return mixed
     */
    public function _date_format() {
      return $this->prefs->date_format;
    }
    /**
     * @static
     * @return mixed
     */
    public function _date_display() {
      return $this->prefs->date_display();
    }
    /**
     * @static
     * @return int
     */
    public function _date_sep() {
      return (isset($_SESSION["current_user"])) ? $this->prefs->date_sep : $this->Config->get('date.ui_separator');
    }
    /**
     * @static
     * @return mixed
     */
    public function _tho_sep() {
      return $this->prefs->tho_sep();
    }
    /**
     * @static
     * @return mixed
     */
    public function _dec_sep() {
      return $this->prefs->dec_sep();
    }
    /**
     * @static
     * @return mixed
     */
    public function _theme() {
      return $this->prefs->theme;
    }
    /**
     * @static
     * @return mixed
     */
    public function _page_size() {
      return $this->prefs->page_size;
    }
    /**
     * @static
     * @return mixed
     */
    public function _hints() {
      return $this->prefs->show_hints;
    }
    /**
     * @static
     * @return mixed
     */
    public function _print_profile() {
      return $this->prefs->print_profile;
    }
    /**
     * @static
     * @return mixed
     */
    public function _rep_popup() {
      return $this->prefs->rep_popup;
    }
    /**
     * @static
     * @return mixed
     */
    public function _query_size() {
      return $this->prefs->query_size;
    }
    /**
     * @static
     * @return mixed
     */
    public function _graphic_links() {
      return $this->prefs->graphic_links;
    }
    /**
     * @static
     * @return mixed
     */
    public function _sticky_doc_date() {
      return $this->prefs->sticky_doc_date;
    }
    /**
     * @static
     * @return mixed
     */
    public function _startup_tab() {
      return $this->prefs->startup_tab;
    }
    /**
     * @return mixed
     */
    private function get_salesmanid() {
      return DB::_select('salesman_code')->from('salesman')->where('user_id=', $this->user)->fetch()->one('salesman_code');
    }
    public function _logout() {
      Session::_kill();
      $this->logged = false;
    }
    public function getHash() {
      return $this->_hash;
    }
  }


