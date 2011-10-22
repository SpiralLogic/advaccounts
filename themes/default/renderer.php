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

	class renderer {

		public $has_header = true;
		protected static $_instance = null;

		public static function getInstance() {
			if (static::$_instance === null) static::$_instance = new static;
			return static::$_instance;
		}

		function wa_header() {
			page(_($help_context = "Main Menu"), false, true);
		}

		function wa_footer() {
			end_page(false, true);
		}

		function menu_header($title, $no_menu, $is_index) {

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

			if ($no_menu == false && !AJAX_REFERRER) {
				echo "<div id='footer'>\n";
				if (isset($_SESSION['wa_current_user'])) {
					echo "<span class='power'><a target='_blank' href='" . POWERED_URL . "'>" . POWERED_BY . "</a></span>\n";
					echo "<span class='date'>" . Dates::Today() . " | " . Dates::Now() . "</span>\n";
					if ($_SESSION['wa_current_user']->logged_in()) echo "<span class='date'>" . User::show_online() . "</span>\n";
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
	}
