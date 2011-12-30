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
		static $css = array();

		//
		// Helper function for simple db table editor pages
		//
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

		public static function start($title, $no_menu = false, $is_index = false) {
			global $page_security;
			if (empty($page_security)) {
				$page_security = 'SA_OPEN';
			}
			if (AJAX_REFERRER || Ajax::in_ajax()) {

				$no_menu = true;
			}
			else {
				Page::header($title, $no_menu, $is_index);

			if ($no_menu) {
				Renderer::i()->has_header = false;

			}else {
				Renderer::i()->menu_header();
			}
			}
			Errors::error_box();
			if ($title && !$is_index && !isset($_GET['frame'])) {				Renderer::i()->has_header = false;

				echo "<div class='titletext'>$title" . (User::hints() ? "<span id='hints' class='floatright'></span>" : '') . "</div>";
			}
			Security::check_page($page_security);
			Display::div_start('_page_body'); // whole page content for ajax reloading
		}

		public static function header($title, $no_menu = false) {
			// titles and screen header
			User::theme();
			JS::open_window(900, 500);
			if (isset($_SESSION["App"]) && is_object($_SESSION["App"]) && isset($_SESSION["App"]->selected_application) && $_SESSION["App"]->selected_application != "") {
				$sel_app = $_SESSION["App"]->selected_application;
			}
			elseif (isset($_SESSION["sel_app"]) && $_SESSION["sel_app"] != "") {
				$sel_app = $_SESSION["sel_app"];
			}
			else {
				$sel_app = User::startup_tab();
			}
			$_SESSION["sel_app"] = $sel_app;
			// When startup tab for current user was set to already
			// removed/inactivated extension module select Sales tab as default.
			if (isset($_SESSION["App"]) && is_object($_SESSION["App"])) {
				$_SESSION["App"]->selected_application = isset($_SESSION["App"]->applications[$sel_app]) ? $sel_app : 'orders';
			}
			$encoding = $_SESSION['Language']->encoding;
			if (!headers_sent()) {
				header("Content-type: text/html; charset='$encoding'");
			}
			echo "<!DOCTYPE HTML>\n";
			echo "<html class='" . strtolower($_SESSION['sel_app']) . "' dir='" . $_SESSION['Language']->dir . "' >\n";
			echo "<head><title>$title</title>";
			echo "<meta charset='$encoding'>";
			echo "<link rel='apple-touch-icon' href='/company/images/apple-touch-icon.png'/>";
			static::add_css(Config::get('assets.css'));
			static::send_css();
			JS::renderHeader();
			echo "</head><body" . ($no_menu ? ' class="lite">' : '>');
			echo "<div id='content'>\n";
		}

		public static function help_url($context = null) {
			global $help_context, $old_style_help;
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
				$app = $_SESSION['sel_app'];
				$help_page_url = Session::i()->App->applications[$app]->help_context;
				$clean = 1;
			}
			if (@$old_style_help) {
				$help_page_url = _($help_page_url);
			}
			if ($clean) {
				$help_page_url = Display::access_string($help_page_url, true);
			}
			return Config::get('help_baseurl') . urlencode(strtr(ucwords($help_page_url), array(
																																												 ' ' => '', '/' => '',
																																												 '&' => 'And'))) . '&ctxhelp=1&lang=' . $country;
		}

		public static function footer($no_menu = false, $is_index = false) {
			$Validate = array();
			$rend = Renderer::i();
			$rend->menu_footer($no_menu, $is_index);
			$edits = "editors = " . Ajax::i()->php2js(Display::set_editor(false, false)) . ";";
			Ajax::i()->addScript('editors', $edits);
			JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . Ajax::i()->php2js($Validate) . ";var $edits");
			User::add_js_data();
			if ($rend->has_header) {
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
			static::$css += $file;
		}

		public static function send_css() {
			$theme = User::theme();
			$path = "/themes/$theme/";
			$css = implode(',', static::$css);
			echo "<link href='{$path}{$css}' rel='stylesheet'> \n";
		}

		public static function footer_exit() {
			Display::br(2);
			Renderer::end_page(false, false, true);
			exit;
		}
	}
