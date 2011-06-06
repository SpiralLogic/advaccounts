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
		global $help_base_url, $help_context, $old_style_help;
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
		return $help_base_url . urlencode(strtr(ucwords($help_page_url), array(' ' => '', '/' => '', '&' => 'And'))) . '&ctxhelp=1&lang=' . $country;
	}

	function add_css($file = false) {
		static $css = array();
		if ($file == false) {
			return $css;
		}
		$css[] = $file;

	}
 
	function send_css() {
		global $path_to_root;
		$theme = user_theme();
		$path = "$path_to_root/themes/$theme/";
		$css = implode(',', add_css());
		echo "<link href='{$path}{$css}' rel='stylesheet' type='text/css'> \n";
	}

	function send_scripts() {
		global $js_static, $js_path, $js_userlib, $go_debug, $js_huserlib;
		//foreach ($js_static as &$jsfile) {
			//$fpath = $comp_path . '/' . user_company() . '/js_cache/' . $jsfile;
			$fpath = $js_path . $jsfile;
			// compress also static files
			//if (!file_exists($fpath) || $go_debug)
			//	cache_js_file($fpath, file_get_contents($js_path . $jsfile));
		//}
		$js = '<script language="javascript" type="text/javascript" src="/js/' . implode(',',$js_static) . '"></script>';
		$js .= '<script language="javascript" type="text/javascript" src="/js/js2/' . implode(',', $js_huserlib) . '"></script>';
		foreach ($js_userlib as $jsfile) {
			$js .= '<script language="javascript" type="text/javascript" src="' . $jsfile. '"></script>';
		}
		echo $js;
	}

	function send_fscripts() {
		global $js_fstatic, $js_fuserlib;
		$js ='<script language="javascript" type="text/javascript" src="/js/js2/' . implode(',', $js_fstatic) . '"></script>';
		foreach ($js_fuserlib as $jsfile) {
			$js .= '<script language="javascript" type="text/javascript" src="' . $jsfile . '"></script>';
		}
		echo $js;


	}

	function page_header($title, $no_menu = false, $is_index = false, $onload = "", $js = "") {
		// titles and screen header
		global $path_to_root, $def_app, $use_popup_windows, $help_base_url;
		if (in_ajax())
			return; // just for speed up
		$theme = user_theme();
		$local_path_to_root = $path_to_root;
		if ($help_base_url != null && $use_popup_windows && $js == '') {
			if (!function_exists('get_js_open_window')) {
				include_once($path_to_root . '/includes/ui/ui_view.inc');
			}
			add_js_source(get_js_open_window(900, 500));
		}
		if ($js != '')
			add_js_source($js);
		if (!isset($no_menu)) {
			$no_menu = false;

		}
		//if (!isset($_SESSION["sel_app"]))
		//	session_register("sel_app");
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
		echo "<html dir='" . $_SESSION['language']->dir . "' >\n";
		echo "<head><title>$title</title>";
		echo "<meta http-equiv='Content-type' content='text/html; charset=$encoding'>";
		echo "<link rel='apple-touch-icon' href='$local_path_to_root/company/" . user_company() . "/images/advanced-icon.png'/>";
		add_css('default.css');
		if ($no_menu) {
			add_css('default-lite.css');
		}
		if (file_exists("$local_path_to_root/themes/$theme/{$_SESSION['sel_app']}.css"))
			add_css($_SESSION['sel_app'].'.css');
		add_css('jquery-ui-1.8.6.css,jquery.calculator.css,jquery.fileupload-ui.css');
		send_css();
		//if (!$_GET['frame'])
		send_scripts($js);
		echo "</head> \n";
		if ($onload == "")
			echo "<body>";
		else
			echo "<body onload=\"$onload\">";
		include_once($local_path_to_root . "/themes/" . user_theme() . "/renderer.php");
		$rend = new renderer();
		$rend->menu_header($title, $no_menu, $is_index);
		error_box();
	}

?>