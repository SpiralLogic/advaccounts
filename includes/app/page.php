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
		protected $css = array();
		protected static $i = null;
		/** @var Renderer */
		public $renderer = null;
		protected $no_menu = false;
		protected $is_index = false;
		protected $header = false;
		protected $app;
		protected $sel_app;
		public $has_header = true;
		public $frame = false;
		protected $title = '';
		public static function simple_mode($numeric_id = true) {
			global $Mode, $selected_id;
			$default = $numeric_id ? -1 : '';
			$selected_id = get_post('selected_id', $default);
			foreach (array(ADD_ITEM, UPDATE_ITEM, MODE_RESET, MODE_CLONE) as $m) {
				if (isset($_POST[$m])) {
					Ajax::i()->activate('_page_body');
					if ($m == MODE_RESET || $m == MODE_CLONE) {
						$selected_id = $default;
					}
					unset($_POST['_focus']);
					$Mode = $m;
					return;
				}
			}
			foreach (array(MODE_EDIT, MODE_DELETE) as $m) {
				foreach ($_POST as $p => $pvar) {
					if (strpos($p, $m) === 0) {
						unset($_POST['_focus']); // focus on first form entry
						$selected_id = quoted_printable_decode(substr($p, strlen($m)));
						Ajax::i()->activate('_page_body');
						$Mode = $m;
						return;
					}
				}
			}
			$Mode = '';
		}
		/**
		 * @static
		 * @param $title
		 * @param bool $no_menu
		 * @param bool $is_index
		 * @return Page
		 */
		public static function start($title, $no_menu = false, $is_index = false) {
			if (static::$i === null) {
				static::$i = new static($title, $no_menu, $is_index);
			}
			return static::$i;
		}
		protected function __construct($title, $no_menu, $index) {
			global $page_security;
			if (empty($page_security)) {
				$page_security = 'SA_OPEN';
			}
			$this->title = $title;
			$this->frame = isset($_GET['frame']);
			$this->no_menu = $no_menu;
			$this->renderer = Renderer::i();
			$this->theme = User::theme();
			if (AJAX_REFERRER || Ajax::in_ajax()) {
				$this->no_menu = true;
			}
			else {
				$this->header();
				if ($this->no_menu) {
					$this->header = false;
				}
				else {
					$this->renderer->menu_header();
				}
			}
			Errors::error_box();
			if ($title && !$index && !$this->frame) {
				$this->renderer->has_header = false;
				echo "<div class='titletext'>$title" . (User::hints() ? "<span id='hints' class='floatright'></span>" : '') . "</div>";
			}
			Security::check_page($page_security);
			Display::div_start('_page_body');
		}
		protected function header() {
			JS::open_window(900, 500);
			if (isset($_SESSION["App"])) {
				$this->app = &$_SESSION["App"];
				if (is_object($this->app) && isset($this->app->selected_application) && $this->app->selected_application != "") {
					$_SESSION["sel_app"] = $this->app->selected_application;
				}
			}
			if (!isset($_SESSION["sel_app"])) {
				$_SESSION["sel_app"] = User::startup_tab();
			}
			$this->sel_app = &$_SESSION["sel_app"];
			if (is_object($this->app)) {
				$this->app->selected_application = isset($this->app->applications[$this->sel_app]) ? $this->sel_app : 'orders';
			}
			$encoding = $_SESSION['Language']->encoding;
			if (!headers_sent()) {
				header("Content-type: text/html; charset='$encoding'");
			}
			echo "<!DOCTYPE HTML>\n";
			echo "<html class='" . strtolower($this->sel_app) . "' dir='" . $_SESSION['Language']->dir . "' >\n";
			echo "<head><title>$this->title</title>";
			echo "<meta charset='$encoding'>";
			echo "<link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>";
			$this->css += Config::get('assets.css');
			$this->send_css();
			JS::renderHeader();
			echo "</head><body" . ($this->no_menu ? ' class="lite">' : '>');
			echo "<div id='content'>\n";
		}
		public static function help_url($context = null) {
			global $help_context;
			$country = $_SESSION['Language']->code;
			$clean = 0;
			if ($context != null) {
				$help_page_url = $context;
			}
			elseif (isset($help_context)) {
				$help_page_url = $help_context;
			}
			else // main menu
			{
				$help_page_url = Session::i()->App->applications[static::$i->sel_app]->help_context;
				$help_page_url = Display::access_string($help_page_url, true);
			}
			return Config::get('help_baseurl') . urlencode(strtr(ucwords($help_page_url), array(
																																												 ' ' => '', '/' => '', '&' => 'And'
																																										))) . '&ctxhelp=1&lang=' . $country;
		}
		public function footer() {
			$Validate = array();
			$this->renderer->menu_footer($this->no_menu);
			$edits = "editors = " . Ajax::i()->php2js(Display::set_editor(false, false)) . ";";
			Ajax::i()->addScript('editors', $edits);
			JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . Ajax::i()->php2js($Validate) . ";var $edits");
			User::add_js_data();
			if ($this->has_header) {
				Sidemenu::render();
			}
			Messages::show();
			if (User::get()->username == 'mike' && rand(0, 50) == 0) {
				JS::onload('window.setTimeout(function(){\$.getScript("http://www.cornify.com/js/cornify.js",function(){for(var i=0;i<100;i++){cornify_add();}})},10000);');
			}
			JS::render();
			if (AJAX_REFERRER) {
				return;
			}
			echo "</div></body>";
			JS::get_websales();
			echo	 "</html>\n";
		}
		public static function add_css($file = false) {
			static::$i->css[] = $file;
		}
		protected function send_css() {
			$path = DS . "themes" . DS . $this->theme . DS;
			$css = implode(',', $this->css);
			echo "<link href='{$path}{$css}' rel='stylesheet'> \n";
		}
		public static function footer_exit() {
			Display::br(2);
			static::$i->_end_page(false, false, true);
			exit;
		}
		public static function end($no_menu = false, $is_index = false, $hide_back_link = false) {
			static::$i->_end_page($hide_back_link);
		}
		protected function _end_page($hide_back_link) {
			if ($this->frame) {
				$is_index = $hide_back_link = $no_menu = true;
				$this->has_header = false;
			}
			if (($this->is_index && !$hide_back_link) && method_exists('Display', 'link_back')) {
				Display::link_back(true, $this->no_menu);
			}
			Display::div_end(); // end of _page_body section
			$this->footer();
		}
	}
