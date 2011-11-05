<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	//--------------------------------------------------------------------------
	class User
	{
		protected static $_instance = null;

		public static function get()
		{
			if (isset($_SESSION["current_user"])) {
				static::$_instance = $_SESSION["current_user"];
			} elseif (static::$_instance === null) {
				static::$_instance = $_SESSION["current_user"] = new static;
			}
			return static::$_instance;
		}

		public $user;
		public $loginname;
		public $username;
		public $name;
		public $company = 1;
		public $pos;
		public $salesmanid = false;
		public $access;
		public $timeout;
		public $last_act;
		public $role_set = false;
		public $old_db;
		public $logged = false;
		public $ui_mode = 0;
		public $prefs;

		function __construct()
		{
			$this->loginname = $this->username = $this->name = "";
			$this->company = Config::get('company_default') ? Config::get('company_default') : 1;
			$this->logged = false;
			$this->prefs = new userPrefs();
		}

		public function set_salesman($salesmanid = null)
		{
			if ($salesmanid == null) {
				$salesman_name = $this->name;
				$sql = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::escape($salesman_name);
				$query = DBOld::query($sql, 'Couldn\'t find current salesman');
				$result = DBOld::fetch_assoc($query);
				if (!empty($result['salesman_code'])) {
					$this->salesmanid = $result['salesman_code'];
				}
			}
			if ($salesmanid != null) {
				$this->salesmanid = $salesmanid;
			}
		}

		public function logged_in()
		{
			return $this->logged;
		}

		public function set_company($company)
		{
			$this->company = $company;
		}

		public function login($company, $loginname, $password)
		{
			$this->set_company($company);
			$this->logged = false;
			$Auth_Result = Users::get_for_login($loginname, $password);
			if (DBOld::num_rows($Auth_Result) > 0) {
				$myrow = DBOld::fetch($Auth_Result);
				if (!$myrow["inactive"]) {
					$this->role_set = array();
					$this->access = $myrow["role_id"];
					// store area codes available for current user role
					$role = Security::get_role($this->access);
					if (!$role) {
						return false;
					}
					foreach ($role['areas'] as $code) // filter only area codes for enabled security sections
					{
						if (in_array($code & ~0xff, $role['sections'])) {
							$this->role_set[] = $code;
						}
					}
				}
				if ($myrow['change_password']) {
					$_SESSION['change_password'] = true;
				}
				$this->name = $myrow["real_name"];
				$this->pos = $myrow["pos"];
				$this->loginname = $loginname;
				$this->username = $this->loginname;
				$this->prefs = new userPrefs($myrow);
				$this->user = $myrow["id"];
				Users::update_visitdate($this->username);
				$this->logged = true;
				$this->last_act = time();
				$this->timeout = DB_Company::get_pref('login_tout');
				$this->set_salesman();
			}
			return $this->logged;
		}

		public function timeout()
		{
			// skip timeout on logout page
			if ($this->logged) {
				$tout = $this->timeout;
				if ($tout && (time() > $this->last_act + $tout)) {
					$this->logged = false;
				}
				$this->last_act = time();
			}
		}

		public function can_access($page_level)
		{
			global $security_areas;
			if ($page_level === 'SA_OPEN') {
				return true;
			}
			if ($page_level === 'SA_DENIED' || $page_level === '') {
				return false;
			}
			$code = $security_areas[$page_level][0];
			// only first registered company has site admin privileges
			return $code && in_array($code, $this->role_set) && ($this->company == 1 || (($code & ~0xff) != SS_SADMIN));
		}

		public function can_access_page($page_level)
		{
			return $this->can_access($page_level);
		}

		public function get_db_connection($id = -1)
		{
			$id = $id == -1 ? $this->company : 1;
			$connection = Config::get('db.' . $id);
			$db = mysql_connect($connection["host"], $connection["dbuser"], $connection["dbpassword"]);
			mysql_select_db($connection["dbname"], $db);
			return $db;
		}

		public function update_prefs(
			$price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints, $profile, $rep_popup,
			$query_size, $graphic_links, $lang, $stickydate, $startup_tab
		)
		{
			$user = array(
				'prices_dec' => $price_dec,
				'qty_dec' => $qty_dec,
				'rates_dec' => $exrate_dec,
				'percent_dec' => $percent_dec,
				'show_gl' => $showgl,
				'show_codes' => $showcodes,
				'date_format' => $date_format,
				'date_sep' => $date_sep,
				'tho_sep' => $tho_sep,
				'dec_sep' => $dec_sep,
				'theme' => $theme,
				'page_size' => $pagesize,
				'show_hints' => $show_hints,
				'print_profile' => $profile,
				'rep_popup' => $rep_popup,
				'query_size' => $query_size,
				'graphic_links' => $graphic_links,
				'language' => $lang,
				'sticky_doc_date' => $stickydate,
				'startup_tab' => $startup_tab
			);
			if (!Config::get('demo_mode')) {
				Users::update_display_prefs(
					$this->user, $price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints,
					$profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab
				);
			}
			$this->prefs = new userPrefs(Users::get($this->user));
		}

		public static function prefs()
		{
			return User::get()->prefs;
		}

		public static function add_js_data()
		{
			$js
			 = "\nvar user = {
						 \n theme: '/themes/" . User::theme() . "/',
						 \nloadtxt: '" . _('Requesting data...') . "',
						 \ndate: '" . Dates::Today() . "',
						 \ndatesys: " . Config::get('accounts_datesystem') . ",
						 \ndatefmt: " . User::date_format() . ",
						 \ndatesep: '" . Config::get('ui_date_format') . "',
						 \nts: '" . Config::get('separators_thousands', User::tho_sep()) . "',
						 \nds: '" . Config::get('separators_decimal', User::dec_sep()) . "',
						 \npdec: " . User::price_dec() . "}\n";
			JS::beforeload($js);
		}

		//--------------------------------------------------------------------------
		function fallback()
		{
			return User::get()->ui_mode == 0;
		}

		function numeric($input)
		{
			$num = trim($input);
			$sep = Config::get('separators_thousands', User::tho_sep());
			if ($sep != '') {
				$num = str_replace($sep, '', $num);
			}
			$sep = Config::get('separators_decimal', User::dec_sep());
			if ($sep != '.') {
				$num = str_replace($sep, '.', $num);
			}
			if (!is_numeric($num)) {
				return false;
			}
			$num = (float)$num;
			if ($num == (int)$num) {
				return (int)$num;
			} else
			{
				return $num;
			}
		}

		function pos()
		{
			return User::get()->pos;
		}

		function language()
		{
			return User::prefs()->language();
		}

		function qty_dec()
		{
			return User::prefs()->qty_dec();
		}

		function price_dec()
		{
			return User::prefs()->price_dec();
		}

		function exrate_dec()
		{
			return User::prefs()->exrate_dec();
		}

		function percent_dec()
		{
			return User::prefs()->percent_dec();
		}

		function show_gl_info()
		{
			return User::prefs()->show_gl_info();
		}

		function show_codes()
		{
			return User::prefs()->show_codes();
		}

		function date_format()
		{
			return User::prefs()->date_format();
		}

		function date_display()
		{
			return User::prefs()->date_display();
		}

		function date_sep()
		{
			return (isset($_SESSION["current_user"])) ? User::prefs()->date_sep() : 0;
		}

		function tho_sep()
		{
			return User::prefs()->tho_sep();
		}

		function dec_sep()
		{
			return User::prefs()->dec_sep();
		}

		function theme()
		{
			return User::prefs()->get_theme();
		}

		function pagesize()
		{
			return User::prefs()->get_pagesize();
		}

		function hints()
		{
			return User::prefs()->show_hints();
		}

		function print_profile()
		{
			return User::prefs()->print_profile();
		}

		function rep_popup()
		{
			return User::prefs()->rep_popup();
		}

		function query_size()
		{
			return User::prefs()->query_size();
		}

		function graphic_links()
		{
			return User::prefs()->graphic_links();
		}

		function sticky_date()
		{
			return User::prefs()->sticky_date();
		}

		function startup_tab()
		{
			return User::prefs()->start_up_tab();
		}
	}

?>