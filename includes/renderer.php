<?php

	/*     * ********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	* ********************************************************************* */

	class Renderer {


		public $has_header = false;
		protected static $_instance = null;

		public static function getInstance() {
			if (static::$_instance === null) static::$_instance = new static;
			return static::$_instance;
		}

		function wa_header() {
			static::page(_($help_context = "Main Menu"), false, true);
		}

		function wa_footer() {
			static::end_page(false, true);
		}

		public static function page($title, $no_menu = false, $is_index = false, $onload = "", $js = "", $script_only = false) {

			global $page_security;
			if (empty($page_security)) $page_security = 'SA_OPEN';
			$hide_menu = $no_menu;

			static::page_header($title, $no_menu, $is_index, $onload, $js);
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

		public static function page_header($title, $no_menu = false, $is_index = false, $onload = "", $js = "") {
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

			static::add_css('default.css,jquery-ui-1.8.7.css,jquery.calculator.css,jquery.fileupload-ui.css');

			static::send_css();
			//if (!$_GET['frame'])
			JS::renderHeader();
			echo "</head> \n";
			if ($onload == "")
				echo "<body";
			else
				echo "body onload='$onload'";
			echo	($no_menu) ? ' class="lite">' : '>';
			$rend = static::getInstance();
			$rend->menu_header($title, $no_menu, $is_index);
			Errors::error_box();
		}

		public static function end_page($no_menu = false, $is_index = false, $hide_back_link = false) {

			static::page_footer($no_menu, $is_index, $hide_back_link);
		}

		public static function page_footer($no_menu = false, $is_index = false, $hide_back_link = false) {
			global $Editors;
			$Validate = array();
			$Ajax = Ajax::instance();
			$rend = static::getInstance();
			if (!isset($no_menu) && isset($_REQUEST['frame']) && $_REQUEST['frame']) {
				$nomenu = $is_index = $hide_back_link = true;
			}
			if ($rend->has_header && !$is_index && !$hide_back_link && function_exists('hyperlink_back')) {
				hyperlink_back(true, $no_menu);
			}
			div_end(); // end of _page_body section

			//if (!$_REQUEST['frame'])

			$rend->menu_footer($no_menu, $is_index);
			$edits = "editors = " . $Ajax->php2js(set_editor(false, false)) . ";";
			$Ajax->addScript('editors', $edits);
			JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . $Ajax->php2js($Validate) . ";var $edits");
			add_user_js_data();
			if ($rend->has_header) {
				Sidemenu::render();
			}
			Messages::showNewMessages();
			JS::render();

			if (AJAX_REFERRER) return;
			var_dump(static::convert(memory_get_usage(true)));
			echo "<br>";
			var_dump(static::convert(memory_get_peak_usage(true)));
			echo "<br>";
			var_dump(static::getReadableTime(microtime(true) - ADV_START_TIME));
			echo "<br>";
			echo "<p></p>";

			echo "</div></body>";
			ui_view::get_websales();

			echo	 "</html>\n";
		}

		function menu_header($title, $no_menu, $is_index) {
			$this->has_header = true;
			$sel_app = $_SESSION['sel_app'];
			echo "<div id='content'>\n";
			if (!$no_menu || AJAX_REFERRER) {

				$applications = $_SESSION['App']->applications;
				echo "<div id='top'>\n";
				echo "<p>" . Config::get($_SESSION["wa_current_user"]->company, 'name', 'db') . " | " .
				 $_SERVER['SERVER_NAME'] . " | " . $_SESSION["wa_current_user"]->name . "</p>\n";
				echo "<ul>\n";
				echo "  <li><a href='" . PATH_TO_ROOT . "/admin/display_prefs.php?'>" . _("Preferences") . "</a></li>\n";
				echo "  <li><a href='" . PATH_TO_ROOT . "/admin/change_current_user_password.php?selected_id=" .
				 $_SESSION["wa_current_user"]->username . "'>" . _("Change password") . "</a></li>\n";
				if (Config::get('help.baseurl') != null) {
					echo "  <li><a target = '_blank' onclick=" . '"' . "javascript:openWindow(this.href,this.target); return false;" . '" ' . "href='" . help_url() . "'>" . _("Help") . "</a></li>";
				}
				echo "  <li><a href='" . PATH_TO_ROOT . "/access/logout.php?'>" . _("Logout") . "</a></li>";
				echo "</ul>\n";
				echo "</div>\n";
				echo "<div id='logo'>\n";
				$indicator = "/themes/" . user_theme() . "/images/ajax-loader.gif";
				echo "<h1>" . APP_TITLE . " " . VERSION . "<span style='padding-left:280px;'><img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;'></span></h1>\n";
				echo "</div>\n";

				echo '<div id="_tabs2"><div class="menu_container">';
				echo "<ul class='menu'>\n";
				foreach ($applications as $app) {
					$acc = access_string($app->name);
					if ($app->direct) {
						echo "<li " . ($sel_app == $app->id ? "class='active' "
						 : "") . "><a href='/{$app->direct}'$acc[1]>" . $acc[0] . "</a></li>\n";
					} else {
						echo "<li " . ($sel_app == $app->id ? "class='active' "
						 : "") . "><a href='/index.php?application=" . $app->id . "'$acc[1]>" .
						 $acc[0] . "</a></li>\n";
					}
				}
				echo "</ul></div></div>\n";
			}

			echo "<div id='wrapper'>\n";
			if ($no_menu) {
				$this->has_header = false;
				echo "<br>";
			} elseif ($title && !$is_index) {

				echo "<center><table id='title'><tr><td width='100%' class='titletext'>$title</td>" . "<td align=right>" . (user_hints()
				 ? "<span id='hints'></span>" : '') . "</td>" . "</tr></table></center>";
			}
		}

		function menu_footer($no_menu, $is_index) {
			if ($this->has_header == false) return;
			if ($no_menu == false && !AJAX_REFERRER) {
				echo "<div id='footer'>\n";
				if (isset($_SESSION['wa_current_user'])) {
					echo "<span class='power'><a target='_blank' href='" . POWERED_URL . "'>" . POWERED_BY . "</a></span>\n";
					echo "<span class='date'>" . Dates::Today() . " | " . Dates::Now() . "</span>\n";
					if ($_SESSION['wa_current_user']->logged_in()) echo "<span class='date'>" . show_users_online() . "</span>\n";
				}
				echo "</div>\n";
			}
			echo "</div>\n";
			echo "</div>\n";
		}

		function display_applications(&$waapp) {

			$selected_app = $waapp->get_selected_application();

			foreach ($selected_app->modules as $module) {
				// image
				echo "<table width='100%'><tr>";
				echo "<td valign='top' class='menu_group'>";
				echo "<table border=0 width='100%'>";
				echo "<tr><td class='menu_group' colspan=2>";
				echo $module->name;
				echo "</td></tr><tr>";
				echo "<td width='50%' class='menu_group_items'>";
				echo "<ul>\n";
				if ($_SESSION["language"]->dir == "rtl") {
					$class = "right";
				} else {
					$class = "left";
				}
				foreach ($module->lappfunctions as $appfunction) {
					if ($appfunction->label == "") {
						echo "<li class='empty'>&nbsp;</li>\n";
					} elseif ($_SESSION["wa_current_user"]->can_access_page($appfunction->access)) {
						echo "<li>" . menu_link($appfunction->link, $appfunction->label) . "</li>";
					} else {
						echo "<li><span class='inactive'>" . access_string($appfunction->label, true) . "</span></li>\n";
					}
				}
				echo "</ul></td>\n";
				if (sizeof($module->rappfunctions) > 0) {
					echo "<td width='50%' class='menu_group_items'>";
					echo "<ul>\n";
					foreach ($module->rappfunctions as $appfunction) {
						if ($appfunction->label == "") {
							echo "<li class='empty'>&nbsp;</li>\n";
						} elseif (
							$_SESSION["wa_current_user"]->can_access_page($appfunction->access)
						)
						{
							echo "<li>" . menu_link($appfunction->link, $appfunction->label) . "</li>";
						} else
						{
							echo "<li><span class='inactive'>" . access_string($appfunction->label, true) . "</span></li>\n";
						}
					}
					echo "</ul></td>\n";
				}
				echo "</tr></table></td></tr></table>\n";
			}
		}

		function help_url($context = null) {
			global $help_context;
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

			if ($clean)
				$help_page_url = access_string($help_page_url, true);
			return Config::get('help.baseurl') . urlencode(strtr(ucwords($help_page_url), array(' ' => '', '/' => '', '&' => 'And'))) . '&ctxhelp=1&lang=' . $country;
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

		public static function convert($size) {
			$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
			return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
		}

		public static function getReadableTime($time) {
			$ret = $time;
			$formatter = 0;
			$formats = array('ms', 's', 'm');
			if ($time >= 1000 && $time < 60000) {
				$formatter = 1;
				$ret = ($time / 1000);
			}
			if ($time >= 60000) {
				$formatter = 2;
				$ret = ($time / 1000) / 60;
			}
			$ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
			return $ret;
		}
	}
