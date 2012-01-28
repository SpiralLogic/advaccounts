<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 31/10/11
	 * Time: 6:57 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Page
	{
		/**@var Page null*/
		public $renderer = null;
		protected $frame = false;
		protected $menu = true;
		protected $is_index = false;
		protected $css = array();
		protected $header = true;
		protected $theme = 'default';
		/** @var ADVAccounting */
		protected $app;
		protected $sel_app;
		protected $title = '';
		/** @var Page */
		static protected $i = null;
		static protected $security = null;
		public $encoding;
		public $ajaxpage;
		public $lang_dir = '';
		protected function __construct($title, $index = false) {
			$this->is_index = $index;
			$this->title = $title;
			$this->frame = isset($_GET['frame']);
		}
		protected function init($menu) {
			$this->app = $_SESSION['App'];
			$this->sel_app = $this->app->selected;
			$this->ajaxpage = (AJAX_REFERRER || Ajax::in_ajax());
			$this->menu = ($this->frame) ? false : $menu;
			$this->renderer = new Renderer();
			$this->theme = User::theme();
			$this->encoding = $_SESSION['Language']->encoding;
			$this->lang_dir = $_SESSION['Language']->dir;
			if (!$this->ajaxpage) {
				$this->header();
				if ($this->menu) {
					$this->menu_header();
				}
			}
			echo Errors::error_box();
			if (!$this->ajaxpage && $this->menu) {
				echo "<div id='wrapper'>";
			}
			if ($this->title && !$this->is_index && !$this->frame) {
				echo "<div class='titletext'>$this->title" . (User::hints() ? "<span id='hints' class='floatright'
										style='display:none'></span>" : '') . "</div>";
			}
			Security::check_page(static::$security);
			Display::div_start('_page_body');
		}
		static public function start($title, $security = SA_OPEN, $no_menu = false, $is_index = false) {
			static::set_security($security);
			if (static::$i === null) {
				static::$i = new static($title, $is_index);
			}
			static::$i->init(!$no_menu);
			return static::$i;
		}
		static public function simple_mode($numeric_id = true) {
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
		static public function add_css($file = false) {
			static::$i->css[] = $file;
		}
		static public function set_security($security) {
			static::$security = $security;
		}
		public static function get_security() { return static::$security; }
		static public function footer_exit() {
			Display::br(2);
			static::$i->end_page(true);
			exit;
		}
		protected function header() {
			$this->header = true;
			JS::open_window(900, 500);
			if (!headers_sent()) {
				header("Content-type: text/html; charset='{$this->encoding}'");
			}
			echo "<!DOCTYPE HTML>\n";
			echo "<html " . (is_object($this->sel_app) ? "class='" . strtolower($this->sel_app->id) . "'" :
			 '') . "' dir='" . $this->lang_dir . "' >\n";
			echo "<head><title>" . $this->title . "</title>";
			echo "<meta charset='{$this->encoding}'>";
			echo "<link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>";
			$this->renderCSS();
			if (class_exists('JS', false)) {
				JS::renderHeader();
			}
			echo "</head><body" . (!$this->menu ? ' class="lite">' : '>');
			echo "<div id='content'>\n";
		}
		protected function menu_header() {
			echo "<div id='top'>\n";
			echo "<p>" . Config::get('db.' . User::get()->company, 'name') . " | " . $_SERVER['SERVER_NAME'] . " | " . User::get()->name . "</p>\n";
			echo "<ul>\n";
			echo	 " <li><a href='" . PATH_TO_ROOT . "/system/display_prefs.php?'>" . _("Preferences") . "</a></li>\n" . " <li><a
		href='" . PATH_TO_ROOT . "/system/change_current_user_password.php?selected_id=" . User::get()->username . "'>" . _("Change password") . "</a></li>\n";
			if (Config::get('help_baseurl') != null) {
				echo " <li><a target = '_blank' class='.openWindow' href='" . $this->help_url() . "'>" . _("Help") . "</a></li>";
			}
			echo " <li><a href='" . PATH_TO_ROOT . "/access/logout.php?'>" . _("Logout") . "</a></li></ul></div>" . "<div
			id='logo'><h1>" . APP_TITLE . " " . VERSION . "<span style='padding-left:280px;'>" . "<img alt='Ajax Loading'
			id='ajaxmark'
			src='/themes/" . User::theme() . "/images/ajax-loader.gif' class='center' style='visibility:hidden;'>" . "</span></h1></div>" . '<div id="_tabs2"><div class="menu_container">';
			$this->renderer->menu();
			echo "</div></div>";
		}
		protected function help_url($context = null) {
			global $help_context;
			$country = $_SESSION['Language']->code;
			if ($context != null) {
				$help_page_url = $context;
			}
			elseif (isset($help_context)) {
				$help_page_url = $help_context;
			}
			else // main menu
			{
				$help_page_url = Session::i()->App->applications[Session::i()->App->selected->id]->help_context;
				$help_page_url = Display::access_string($help_page_url, true);
			}
			return Config::get('help_baseurl') . urlencode(strtr(ucwords($help_page_url), array(
																																												 ' ' => '', '/' => '', '&' => 'And'
																																										))) . '&ctxhelp=1&lang=' . $country;
		}
		static public function end($hide_back_link = false) {
			if (static::$i) {
				static::$i->end_page($hide_back_link);
			}
		}
		protected function end_page($hide_back_link) {
			if ($this->frame) {
				$hide_back_link = true;
				$this->header = false;
			}
			if ((!$this->is_index && !$hide_back_link) && method_exists('Display', 'link_back')) {
				Display::link_back(true, !$this->menu);
			}
			Display::div_end(); // end of _page_body section
			$this->footer();
		}
		protected function footer() {
			$Validate = array();
			$this->menu_footer();
			$edits = "editors = " . Ajax::i()->php2js(Display::set_editor(false, false)) . ";";
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
			JS::get_websales();
			echo	 "</html>\n";
		}
		protected function menu_footer() {
			echo "</div>"; //end wrapper div
			if ($this->menu && !AJAX_REFERRER) {
				echo "<div id='footer'>\n";
				if (isset($_SESSION['current_user'])) {
					echo "<span class='power'><a target='_blank' href='" . POWERED_URL . "'>" . POWERED_BY . "</a></span>\n";
					echo "<span class='date'>" . Dates::Today() . " | " . Dates::Now() . "</span>\n";
					if ($_SESSION['current_user']->logged_in()) {
						echo "<span class='date'> " . Users::show_online() . "</span>\n";
					}
					echo "<span> </span>| <span>mem/peak: " . Files::convert_size(memory_get_usage(true)) . '/' . Files::convert_size(memory_get_peak_usage(true)) . ' </span><span>|</span><span> load time: ' . Dates::getReadableTime(microtime(true) - ADV_START_TIME) . "</span>";
				}
			}
			if (Config::get('debug')) {
				$this->display_loaded();
			}
			echo "</div>\n"; //end footer div
		}
		protected function display_loaded() {
			$loaded = Autoloader::getPerf();
			$row = "<table id='loaded'>";
			while ($v1 = array_shift($loaded)) {
				$v2 = array_shift($loaded);
				$row .= "<tr><td>{$v1[0]}</td><td>{$v1[1]}</td><td>{$v1[2]}</td><td>{$v1[3]}</td><td>{$v2[0]}</td><td>{$v2[1]}</td><td>{$v2[2]}</td><td>{$v2[3]}</td></tr>";
			}
			echo $row . "</table>";
		}
		protected function renderCSS() {
			$this->css += Config::get('assets.css') ? : array('default.css');
			$path = DS . "themes" . DS . $this->theme . DS;
			$css = implode(',', $this->css);
			echo "<link href='{$path}{$css}' rel='stylesheet'> \n";
		}
		public static function error_exit($text) {
			ob_get_clean();
			$page = new static('Fatal Error.', false);
			$page->header();
			echo "<div id='msgbox'>$text</div>";
			echo "</div></body></html>";
			exit();
		}
	}

