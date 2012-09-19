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
  use ADV\Core\Auth;
  use ADV\Core\JS;
  use ADV\Core\Event;
  use DB_Company;
  use ADV\Core\DB\DB;
  use ADV\Core\Traits\StaticAccess;
  use ADV\App\Validation;

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
   * @method static show_gl()
   * @method static rep_popup()
   * @method static percent_dec()
   * @method static graphic_links()
   * @method static register_login($object, $function = null, $arguments = [])
   */
  class User extends \ADV\App\DB\Base {
    use \ADV\Core\Traits\Hook;
    use StaticAccess;

    protected $_table = 'users';
    protected $_classname = 'Users';
    protected $_id_column = 'id';
    public $user;
    public $id;
    public $user_id;
    /**
     * @var string
     */
    public $loginname;
    /** @var */
    public $username;
    /** @var */
    public $real_name;
    public $name;
    /**
     * @var string
     */
    public $company = 'default';
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
    public $last_action;
    /**
     * @var array
     */
    protected $role_set = [];
    /**
     * @var bool
     */
    public $logged = false;
    /**@var UserPrefs */
    public $prefs;
    public $phone;
    public $language;
    public $email;
    /**
     * @var bool
     */
    public $change_password = false;
    public $selectedApp;
    /** @var */
    protected $access_sections;
    public $hash;
    /** @var */
    public $last_record;
    /** @var \ADV\App\Security */
    public $Security;
    static function i() {
      return $_SESSION['User'];
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (strlen($this->user_id) < 4) {
        Event::error(_("The user login entered must be at least 4 characters long."));
        JS::_setFocus('user_id');
        return false;
      }
      return true;
    }
    /**
     * @param bool $inactive
     *
     * @return array
     */
    public static function getAll($inactive = false) {
      $q = DB::_select('users.id', 'user_id', 'real_name', 'phone', 'email', 'last_visit_date', 'role', 'users.inactive')->from('users,security_roles')->where(
        'security_roles.id=users.role_id'
      );
      if (!$inactive) {
        $q->andWhere('users.inactive=', 0);
      }
      return $q->fetch()->all();
    }
    /**
     */
    public function __construct($id = 0) {
      parent::__construct($id = 0);
      $this->logged = false;
      $this->prefs  = new UserPrefs();
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
      $this->company = $company;
      $this->logged  = false;
      $myrow         = $this->get_for_login($loginname, $_POST['password']);
      if ($myrow) {
        if ($myrow["inactive"]) {
          return false;
        }
        $this->role_set = [];
        $this->access   = $myrow['role_id'];
        $this->hash     = $myrow["hash"];
        $this->Security = new Security();
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
        $this->last_action     = time();
        $this->timeout         = DB_Company::get_pref('login_tout');
        $this->salesmanid      = $this->get_salesmanid();
        $this->fireHooks('login');
        Event::registerShutdown(['Users', 'update_visitdate'], [$this->username]);
        Event::registerShutdown([$this, '_addLog']);
      }
      return $this->logged;
    }
    /**
     * @static
     *
     * @param $user_id
     *
     * @internal param $password
     * @return bool|mixed
     */
    public function  get_for_login($user_id) {
      $auth = new Auth($user_id);
      if ($auth->isBruteForce()) {
        return false;
      }
      return $auth->checkUserPassword($user_id);
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
     * @param       $object
     * @param       $function
     * @param array $arguments
     */
    public function _register_logout($function, $arguments = []) {
      $this->registerHook('logout', $function, $arguments);
    }
    public function timeout() {
      // skip timeout on logout page
      if ($this->logged) {
        $tout = $this->timeout;
        if ($tout && (time() > $this->last_action + $tout)) {
          $this->logged = false;
        }
        $this->last_action = time();
      }
    }
    public function _addLog() {
      DB::_insert('user_login_log')->values(
        array(
             'user'    => $this->username,
             'IP'      => Auth::get_ip(),
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
     */
    public function update_prefs($prefs) {
      $this->prefs = new UserPrefs($this->get());
      $this->prefs->update($this->user, $prefs);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result
     */
    protected function  get() {
      $sql    = "SELECT * FROM users WHERE id=" . DB::_escape($this->user);
      $result = DB::_query($sql, "could not get user " . $this->user);
      return DB::_fetch($result);
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
        . ",datesep: '" . $this->prefs->date_sep //
        . "',ts: '" . $this->prefs->tho_sep //
        . "',ds: '" . $this->prefs->dec_sep //
        . "',pdec: " . $this->prefs->price_dec //
        . "};";
      JS::_beforeload($js);
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
      $sep = $this->prefs->tho_sep;
      if ($sep != '') {
        $num = str_replace($sep, '', $num);
      }
      $sep = $this->prefs->dec_sep;
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
      return (isset($_SESSION["User"])) ? $this->prefs->date_sep : 0;
    }
    /**
     * @return int
     */
    public function _tho_sep() {
      return $this->prefs->tho_sep;
    }
    /**
     * @static
     * @return mixed
     */
    public function _dec_sep() {
      return $this->prefs->dec_sep;
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
      return $this->hash;
    }
  }


