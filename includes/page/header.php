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
	/*
	 If no context is set current page/menu screen is selected.
 */
	function help_url($context = null) {
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
		if (@$old_style_help)
			$help_page_url = _($help_page_url);
		if ($clean)
			$help_page_url = access_string($help_page_url, true);
		return Config::get('help.baseurl') . urlencode(strtr(ucwords($help_page_url), array(' ' => '', '/' => '', '&' => 'And'))) . '&ctxhelp=1&lang=' . $country;
	}

	function add_css($file = false) {
		static $css = array();
		if ($file == false) {
			return $css;
		}
		$css[] = $file;
	}

	function send_css() {

		$theme = user_theme();
		$path = "/themes/$theme/";
		$css = implode(',', add_css());
		echo "<link href='{$path}{$css}' rel='stylesheet' type='text/css'> \n";
	}

	function send_scripts() {
		JS::renderHeader();
	}

	function send_fscripts() {
	}

	function page_header($title, $no_menu = false, $is_index = false, $onload = "", $js = "") {
		// titles and screen header

		if (Ajax::in_ajax() || AJAX_REFERRER)
			return; // just for speed up
		$theme = user_theme();

		if (Config::get('help.baseurl') != null && Config::get('ui.windows.popups') && $js == '') {

			JS::beforeload(ui_view::get_js_open_window(900, 500));
		}
		if ($js != '')
			JS::beforeload($js);
		if (!isset($no_menu)) {
			$no_menu = false;
		}
		if (isset($_SESSION["App"]) && is_object($_SESSION["App"]) && isset($_SESSION["App"]->selected_application) && $_SESSION["App"]->selected_application != "")
			$sel_app = $_SESSION["App"]->selected_application;
		elseif (isset($_SESSION["sel_app"]) && $_SESSION["sel_app"] != "")
			$sel_app = $_SESSION["sel_app"];
		else
			$sel_app = user_startup_tab();
		$_SESSION["sel_app"] = $sel_app;
		// When startup tab for current user was set to already
		// removed/inactivated extension module select Sales tab as default.
		if (isset($_SESSION["App"]) && is_object($_SESSION["App"]))
			$_SESSION["App"]->selected_application = isset($_SESSION["App"]->applications[$sel_app]) ? $sel_app : 'orders';
		$encoding = $_SESSION['language']->encoding;
		if (!headers_sent()) {
			header("Content-type: text/html; charset='$encoding'");
		}
		echo "<!DOCTYPE HTML>\n";
		echo "<html class='" . $_SESSION['sel_app'] . "' dir='" . $_SESSION['language']->dir . "' >\n";
		echo "<head><title>$title</title>";
		echo "<meta http-equiv='Content-type' content='text/html; charset=$encoding'>";
		echo "<link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>";

		add_css('default.css,jquery-ui-1.8.7.css,jquery.calculator.css,jquery.fileupload-ui.css');

		send_css();
		//if (!$_GET['frame'])
		send_scripts($js);
		echo "</head> \n";
		if ($onload == "")
			echo "<body";
		else
			echo "body onload='$onload'";
		echo	($no_menu) ? ' class="lite">' : '>';
		include_once(APP_PATH . "/themes/" . user_theme() . "/renderer.php");
		$rend = renderer::getInstance();
		$rend->menu_header($title, $no_menu, $is_index);
		Errors::error_box();
	}

?>