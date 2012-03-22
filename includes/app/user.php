<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
  class User {

		/***
		 * @static
		 * @return User
		 */
		public $user;
		public $loginname;
		public $username;
		public $name;
		public $company;
		public $pos;
		public $salesmanid = false;
		public $access;
		public $timeout;
		public $last_act;
    public $role_set = array();
		public $old_db;
		public $logged = false;
		public $ui_mode = 0;
		/***
		 * @var \userPrefs
		 */
		public $prefs;
		public $change_password = false;
    protected $access_sections;
    static protected $_instance = null;

    /**

     */
    public function __construct() {
			$this->loginname = $this->username = $this->name = "";
			$this->company = Config::get('default.company') ? : 'default';
			$this->logged = false;
			$this->prefs = new userPrefs();
		}
    /**
     * @param null $salesmanid
     */
		public function set_salesman($salesmanid = null) {
			if ($salesmanid == null) {
				$salesman_name = $this->name;
				$sql = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::escape($salesman_name);
				$query = DB::query($sql, 'Couldn\'t find current salesman');
				$result = DB::fetch_assoc($query);
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
			return $this->logged;
		}
    /**
     * @param $company
     */
		public function set_company($company) {
			$this->company = $company;
		}
    /**
     * @param $company
     * @param $loginname
     * @param $password
     *
     * @return bool
     */
    public function login($company, $loginname) {
			$this->set_company($company);
			$this->logged = false;
      $myrow = Users::get_for_login($loginname,$_POST['password']);
			if ($myrow) {
				if (!$myrow["inactive"]) {
					$this->role_set = array();
					$this->access = $myrow["role_id"];
					// store area codes available for current user role
					$role = Security::get_role($this->access);
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
				}
				$this->change_password = $myrow['change_password'];
				$this->logged = true;
				$this->name = $myrow["real_name"];
				$this->pos = $myrow["pos"];
				$this->username = $this->loginname = $loginname;
				$this->prefs = new userPrefs($myrow);
				$this->user = $myrow["id"];
				$this->last_act = time();
				$this->timeout = DB_Company::get_pref('login_tout');
				$this->salesmanid = $this->get_salesmanid();
				$_SESSION['HTTP_USER_AGENT'] = sha1($_SERVER['HTTP_USER_AGENT']);
				Event::register_shutdown(__CLASS__);
			}
			return $this->logged;
		}
    /**

     */
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
    /**
     * @param $page_level
     *
     * @return bool
     */
		public function can_access($page_level) {
			global $security_areas;
			if ($page_level === SA_OPEN) {
				return true;
			}
			if ($page_level === SA_DENIED || $page_level === '') {
				return false;
			}
			$access = false;
			if (isset($security_areas[$page_level])) {
				$code = $security_areas[$page_level][0];
				$access = $code && in_array($code, $this->role_set);
			}
			elseif (isset($this->access_sections) && in_array($page_level, $this->access_sections)) {
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
		public function can_access_page($page_level) {
			return $this->can_access($page_level);
		}
    /**
     * @param $price_dec
     * @param $qty_dec
     * @param $exrate_dec
     * @param $percent_dec
     * @param $showgl
     * @param $showcodes
     * @param $date_format
     * @param $date_sep
     * @param $tho_sep
     * @param $dec_sep
     * @param $theme
     * @param $pagesize
     * @param $show_hints
     * @param $profile
     * @param $rep_popup
     * @param $query_size
     * @param $graphic_links
     * @param $lang
     * @param $stickydate
     * @param $startup_tab
     */
		public function update_prefs($price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints, $profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab) {
			$user = array(
				'prices_dec' => $price_dec, 'qty_dec' => $qty_dec, 'rates_dec' => $exrate_dec, 'percent_dec' => $percent_dec,
				'show_gl' => $showgl, 'show_codes' => $showcodes, 'date_format' => $date_format, 'date_sep' => $date_sep,
				'tho_sep' => $tho_sep, 'dec_sep' => $dec_sep, 'theme' => $theme, 'page_size' => $pagesize, 'show_hints' => $show_hints,
				'print_profile' => $profile, 'rep_popup' => $rep_popup, 'query_size' => $query_size, 'graphic_links' => $graphic_links,
				'language' => $lang, 'sticky_doc_date' => $stickydate, 'startup_tab' => $startup_tab
			);
			if (!Config::get('demo_mode')) {
				Users::update_display_prefs($this->user, $price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints, $profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab);
			}
			$this->prefs = new userPrefs(Users::get($this->user));
		}
    /**
     * @static
     * @return User
     */
    static public function i() {
      if (isset($_SESSION["current_user"])) {
        static::$_instance = $_SESSION["current_user"];
      }
      elseif (static::$_instance === null) {
        static::$_instance = $_SESSION["current_user"] = new static;
      }
      return static::$_instance;
    }

    /**
     * @static
     * @return userPrefs
     */
		static public function prefs() {
			return static::i()->prefs;
		}
    /**
     * @static

     */
		static public function add_js_data() {
			$js
			 = "\nvar user = {
						 \n theme: '/themes/" . static::theme() . "/',
						 \nloadtxt: '" . _('Requesting data...') . "',
						 \ndate: '" . Dates::today() . "',
						 \ndatesys: " . Config::get('accounts.datesystem') . ",
						 \ndatefmt: " . static::date_format() . ",
						 \ndatesep: '" . Config::get('date.ui_format') . "',
						 \nts: '" . static::tho_sep() . "',
						 \nds: '" . static::dec_sep() . "',
						 \npdec: " . static::price_dec() . "}\n";
			JS::beforeload($js);
		}
    /**
     * @static
     * @return bool
     */
		static public function  fallback() {
			return static::i()->ui_mode == 0;
		}
    /**
     * @static
     *
     * @param $input
     *
     * @return bool|float|int|mixed|string
     */
		static public function  numeric($input) {
			$num = trim($input);
			$sep = static::tho_sep();
			if ($sep != '') {
				$num = str_replace($sep, '', $num);
			}
			$sep = static::dec_sep();
			if ($sep != '.') {
				$num = str_replace($sep, '.', $num);
			}
			if (!is_numeric($num)) {
				return false;
			}
			$num = (float)$num;
			if ($num == (int)$num) {
				return (int)$num;
			}
			else {
				return $num;
			}
		}
    /**
     * @static
     * @return mixed
     */
		static public function  pos() {
			return static::i()->pos;
		}
    /**
     * @static
     * @return mixed
     */
		static public function  language() {
			return static::prefs()->language();
		}
    /**
     * @static
     * @return mixed
     */
		static public function  qty_dec() {
			return static::prefs()->qty_dec();
		}
    /**
     * @static
     * @return mixed
     */
		static public function  price_dec() {
			return static::prefs()->price_dec();
		}
    /**
     * @static
     * @return mixed
     */
		static public function  exrate_dec() {
			return static::prefs()->exrate_dec();
		}
    /**
     * @static
     * @return mixed
     */
		static public function  percent_dec() {
			return static::prefs()->percent_dec();
		}
		static public function  show_gl_info() {
			return static::prefs()->show_gl_info();
		}
		static public function  show_codes() {
			return static::prefs()->show_codes();
		}
		static public function  date_format() {
			return static::prefs()->date_format();
		}
		static public function  date_display() {
			return static::prefs()->date_display();
		}
		static public function  date_sep() {
			return (isset($_SESSION["current_user"])) ? static::prefs()->date_sep() : 0;
		}
		static public function  tho_sep() {
			return static::prefs()->tho_sep();
		}
		static public function  dec_sep() {
			return static::prefs()->dec_sep();
		}
		static public function  theme() {
			return static::prefs()->get_theme();
		}
		static public function  pagesize() {
			return static::prefs()->get_pagesize();
		}
		static public function  hints() {
			return static::prefs()->show_hints();
		}
		static public function  print_profile() {
			return static::prefs()->print_profile();
		}
		static public function  rep_popup() {
			return static::prefs()->rep_popup();
		}
		static public function  query_size() {
			return static::prefs()->query_size();
		}
		static public function  graphic_links() {
			return static::prefs()->graphic_links();
		}
		static public function  sticky_date() {
			return static::prefs()->sticky_date();
		}
		static public function  startup_tab() {
			return static::prefs()->start_up_tab();
		}
		private function get_salesmanid() {
			return DB::select('salesman_code')->from('salesman')->where('user_id=', $this->user)->fetch()->one('salesman_code');
		}
		static function _shutdown() {
	//	\Modules\Jobsboard::tasks();
			Users::update_visitdate(static::i()->username);
		}
	}

?>
