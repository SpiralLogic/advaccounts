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
	class userPrefs
	{
		public $language;
		public $qty_dec;
		public $price_dec;
		public $exrate_dec;
		public $percent_dec;
		public $show_gl_info;
		public $show_codes;
		public $date_format;
		public $date_sep;
		public $tho_sep;
		public $dec_sep;
		public $theme;
		public $print_profile;
		public $rep_popup;
		public $pagesize; // for printing
		public $show_hints;
		public $query_size; // table pager page length
		public $graphic_links; // use graphic links
		public $sticky_date; // save date on subsequent document entry
		public $startup_tab; // default start-up menu tab
		function __construct($user = null) {
			if ($user == null) {
				// set default values, used before login
				$this->date_sep = Config::get('date.ui_separator');
				$this->date_format = Config::get('date.ui_format');
				$this->tho_sep = 0;
				$this->dec_sep = 0;
				$this->price_dec = 2;
				$this->percent_dec = 2;

				$this->language = Config::get('default.lang');
				$this->theme = 'default';
			} else {
				$this->language = $user["language"];
				$_SESSION['Language']->set_language($this->language);
				$this->qty_dec = $user["qty_dec"];
				$this->price_dec = $user["prices_dec"];
				$this->exrate_dec = $user["rates_dec"];
				$this->percent_dec = $user["percent_dec"];
				$this->show_gl_info = $user["show_gl"];
				$this->show_codes = $user["show_codes"];
				$this->date_format = $user["date_format"];
				$this->date_sep = $user["date_sep"];
				$this->tho_sep = $user["tho_sep"];
				$this->dec_sep = $user["dec_sep"];
				$this->theme = $user["theme"];
				$this->pagesize = $user["page_size"];
				$this->show_hints = $user["show_hints"];
				$this->print_profile = $user["print_profile"];
				$this->rep_popup = $user["rep_popup"];
				$this->query_size = $user["query_size"];
				$this->graphic_links = $user["graphic_links"];
				if (isset($user["sticky_doc_date"])) {
					$this->sticky_date = $user["sticky_doc_date"];
					$this->startup_tab = $user['startup_tab'];
				} else {
					$this->sticky_date = 0;
					$this->startup_tab = Config::get('apps.default');
				}
			}
		}

		function language() {
			return $this->language;
		}

		function qty_dec() {
			return $this->qty_dec;
		}

		function price_dec() {
			return $this->price_dec;
		}

		function exrate_dec() {
			return $this->exrate_dec;
		}

		function percent_dec() {
			return $this->percent_dec;
		}

		function show_gl_info() {
			return $this->show_gl_info;
		}

		function show_codes() {
			return $this->show_codes;
		}

		function date_format() {
			return $this->date_format;
		}

		function date_sep() {
			return $this->date_sep;
		}

		function date_display() {
			$sep = Config::get('date.separators', $this->date_sep);
			if ($this->date_format == 0) {
				return "m" . $sep . "d" . $sep . "Y";
			} elseif ($this->date_format == 1) {
				return "d" . $sep . "m" . $sep . "Y";
			} else {
				return "Y" . $sep . "m" . $sep . "d";
			}
		}

		function tho_sep() {
			return $this->tho_sep;
		}

		function dec_sep() {
			return $this->dec_sep;
		}

		function get_theme() {
			return $this->theme;
		}

		function get_pagesize() {
			return $this->pagesize;
		}

		function show_hints() {
			return $this->show_hints;
		}

		function print_profile() {
			return $this->print_profile;
		}

		function rep_popup() {
			return $this->rep_popup;
		}

		function query_size() {
			return $this->query_size;
		}

		function graphic_links() {
			return $this->graphic_links;
		}

		function sticky_date() {
			return $this->sticky_date;
		}

		function start_up_tab() {
			return $this->startup_tab;
		}

		function set_dec($price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes) {
			$this->price_dec = $price_dec;
			$this->qty_dec = $qty_dec;
			$this->exrate_dec = $exrate_dec;
			$this->percent_dec = $percent_dec;
			$this->show_gl_info = $showgl;
			$this->show_codes = $showcodes;
		}

		function set_format($date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize) {
			$this->date_format = $date_format;
			$this->date_sep = $date_sep;
			$this->tho_sep = $tho_sep;
			$this->dec_sep = $dec_sep;
			$this->theme = $theme;
			$this->pagesize = $pagesize;
		}
	}
