<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 31/10/11
	 * Time: 6:57 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Page {
		public static function start($title, $no_menu = false, $is_index = false, $onload = "", $js = "", $script_only = false) {
			global $page_security;
			if (empty($page_security)) {
				$page_security = 'SA_OPEN';
			}
			$hide_menu = $no_menu;
			Page::header($title, $no_menu, $is_index, $onload, $js);
			Security::check_page($page_security);
			//	Errors::error_box();
			if ($script_only) {
				echo '<noscript>';
				echo ui_msgs::display_heading(_('This page is usable only with javascript enabled browsers.'));
				echo '</noscript>';
				div_start('_page_body', null, true);
			} else {
				div_start('_page_body'); // whole page content for ajax reloading
			}
		}

		public static function header($title, $no_menu = false, $is_index = false, $onload = "", $js = "") {
			// titles and screen header
			if (Ajax::in_ajax() || AJAX_REFERRER) {
				Renderer::getInstance()->has_header = false;
				return; // just for speed up
			}
			$theme = user_theme();
			JS::get_js_open_window(900, 500);
			JS::beforeload($js);
			if (!isset($no_menu)) {
				$no_menu = false;
			}
			if (isset($_SESSION["App"]) && is_object($_SESSION["App"]) && isset($_SESSION["App"]->selected_application) && $_SESSION["App"]->selected_application != "") {
				$sel_app = $_SESSION["App"]->selected_application;
			}
			elseif (isset($_SESSION["sel_app"]) && $_SESSION["sel_app"] != "")
			{
				$sel_app = $_SESSION["sel_app"];
			}
			else
			{
				$sel_app = user_startup_tab();
			}
			$_SESSION["sel_app"] = $sel_app;
			// When startup tab for current user was set to already
			// removed/inactivated extension module select Sales tab as default.
			if (isset($_SESSION["App"]) && is_object($_SESSION["App"])) {
				$_SESSION["App"]->selected_application = isset($_SESSION["App"]->applications[$sel_app]) ? $sel_app : 'orders';
			}
			$encoding = $_SESSION['language']->encoding;
			if (!headers_sent()) {
				header("Content-type: text/html; charset='$encoding'");
			}
			echo "<!DOCTYPE HTML>\n";
			echo "<html class='" . $_SESSION['sel_app'] . "' dir='" . $_SESSION['language']->dir . "' >\n";
			echo "<head><title>$title</title>";
			echo "<meta http-equiv='Content-type' content='text/html; charset=$encoding'>";
			echo "<link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>";
			static::add_css('default.css,jquery-ui-1.8.7.css,jquery.calculator.css,jquery.fileupload-ui.css');
			static::send_css();
			JS::renderHeader();
			echo "</head> \n";
			if ($onload == "") {
				echo "<body";
			}
			else
			{
				echo "body onload='$onload'";
			}
			echo	($no_menu) ? ' class="lite">' : '>';
			$rend = renderer::getInstance();
			$rend->menu_header($title, $no_menu, $is_index);
			Errors::error_box();
		}

		public static function help_url($context = null) {
			global $help_context, $old_style_help;
			$country = $_SESSION['language']->code;
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
				$help_page_url = $_SESSION['App']->applications[$app]->help_context;
				$clean = 1;
			}
			if (@$old_style_help) {
				$help_page_url = _($help_page_url);
			}
			if ($clean) {
				$help_page_url = access_string($help_page_url, true);
			}
			return Config::get('help_baseurl') . urlencode(
				strtr(
					ucwords($help_page_url), array(
					' ' => '',
					'/' => '',
					'&' => 'And'
				)
				)
			) . '&ctxhelp=1&lang=' . $country;
		}

		public static function footer($no_menu = false, $is_index = false, $hide_back_link = false) {
			$Validate = array();
			$Ajax = Ajax::instance();
			$rend = renderer::getInstance();
			$rend->menu_footer($no_menu, $is_index);
			$edits = "editors = " . $Ajax->php2js(set_editor(false, false)) . ";";
			$Ajax->addScript('editors', $edits);
			JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . $Ajax->php2js($Validate) . ";var $edits");
			CurrentUser::add_js_data();
			if ($rend->has_header) {
				Sidemenu::render();
			}
			Messages::showNewMessages();
			JS::render();
			if (AJAX_REFERRER) {
				return;
			}
			$load_info = array(Files::convert_size(memory_get_usage(true)), Files::convert_size(memory_get_peak_usage(true)), Dates::getReadableTime(microtime(true) - FUEL_START_TIME));
			echo implode(($rend->has_header) ? "<br>" : "|", $load_info);
			echo "</div></body>";
			ui_view::get_websales();
			echo	 "</html>\n";
		}

		public static function add_css($file = false) {
			static $css = array();
			if ($file == false) {
				return $css;
			}
			$css[] = $file;
		}

		public static function send_css() {
			$theme = user_theme();
			$path = "/themes/$theme/";
			$css = implode(',', static::add_css());
			echo "<link href='{$path}{$css}' rel='stylesheet' type='text/css'> \n";
		}
	}