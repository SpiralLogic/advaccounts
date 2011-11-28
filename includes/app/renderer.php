<?php

	/*     * ********************************************************************
		Copyright (C) Advanced Group PTY LTD
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */
	class Renderer
	{
		public $has_header = true;
		protected static $_instance = null;

		/***
		 * @static
		 * @return Renderer
		 */
		public static function get()
			{
				if (static::$_instance === null) {
					static::$_instance = new static;
				}
				return static::$_instance;
			}

		function header()
			{
				Page::start(_($help_context = "Main Menu"), false, true);
			}

		function footer()
			{
				end_page(false, true);
			}

		function menu_header($title, $no_menu, $is_index)
			{
				$sel_app = $_SESSION['sel_app'];
				echo "<div id='content'>\n";
				if (!$no_menu || AJAX_REFERRER) {
					$applications = Session::i()->App->applications;
					echo "<div id='top'>\n";
					echo "<p>" . Config::get('db.' . User::get()->company,
						'name') . " | " . $_SERVER['SERVER_NAME'] . " | " . User::get()->name . "</p>\n";
					echo "<ul>\n";
					echo "  <li><a href='" . PATH_TO_ROOT . "/system/display_prefs.php?'>" . _("Preferences") . "</a></li>\n";
					echo "  <li><a href='" . PATH_TO_ROOT . "/system/change_current_user_password.php?selected_id=" . User::get()->username . "'>" . _("Change password") . "</a></li>\n";
					if (Config::get('help_baseurl') != null) {
						echo "  <li><a target = '_blank' class='.openWindow' href='" . Page::help_url() . "'>" . _("Help") . "</a></li>";
					}
					echo "  <li><a href='" . PATH_TO_ROOT . "/access/logout.php?'>" . _("Logout") . "</a></li>";
					echo "</ul>\n";
					echo "</div>\n";
					echo "<div id='logo'>\n";
					$indicator = "/themes/" . User::theme() . "/images/ajax-loader.gif";
					echo "<h1>" . APP_TITLE . " " . VERSION . "<span style='padding-left:280px;'><img id='ajaxmark' src='$indicator' align='center' style='visibility:hidden;'></span></h1>\n";
					echo "</div>\n";
					echo '<div id="_tabs2"><div class="menu_container">';
					echo "<ul class='menu'>\n";
					foreach ($applications as $app) {
						$acc = access_string($app->name);
						if ($app->direct) {
							echo "<li " . ($sel_app == $app->id ? "class='active' " :
							 "") . "><a href='/{$app->direct}'$acc[1]>" . $acc[0] . "</a></li>\n";
						} else {
							echo "<li " . ($sel_app == $app->id ? "class='active' " :
							 "") . "><a href='/index.php?application=" . $app->id . "'$acc[1]>" . $acc[0] . "</a></li>\n";
						}
					}
					echo "</ul></div></div>\n";
				}
				echo "<div id='wrapper'>\n";
				if ($no_menu) {
					$this->has_header = false;
					echo "<br>";
				} elseif ($title && !$is_index) {
					echo "<center><table id='title'><tr><td width='100%' class='titletext'>$title</td><td align=right>" . (User::hints() ?
					 "<span id='hints'></span>" : '') . "</td></tr></table></center>";
				}
			}

		function menu_footer($no_menu, $is_index)
			{
				if ($no_menu == false && !AJAX_REFERRER) {
					echo "<div id='footer'>\n";
					if (isset($_SESSION['current_user'])) {
						echo "<span class='power'><a target='_blank' href='" . POWERED_URL . "'>" . POWERED_BY . "</a></span>\n";
						echo "<span class='date'>" . Dates::Today() . " | " . Dates::Now() . "</span>\n";
						if ($_SESSION['current_user']->logged_in()) {
							echo "<span class='date'> " . Users::show_online() . "</span>\n";
						}
						echo "<span> </span>| <span>mem: " . Files::convert_size(memory_get_usage(true)) . "</span><span> | </span><span>peak mem: " . Files::convert_size(memory_get_peak_usage(true)) . ' </span><span>|</span><span> load time: ' . Dates::getReadableTime(microtime(true) - ADV_START_TIME) . "</span>";
					}
					echo "</div>";
				}
				echo "</div>\n";
				echo "</div>\n";
			}

		function display_loaded()
			{
				$loaded = Autoloader::getLoaded();
				$row = "<table id='loaded'>";
				while ($v1 = array_shift($loaded)) {
					$v2 = array_shift($loaded);
					$row .= "<tr><td>{$v1[0]}</td><td>{$v1[1]}</td><td>{$v1[2]}</td><td>{$v2[0]}</td><td>{$v2[1]}</td><td>{$v2[2]}</td></tr>";
				}
				echo $row . "</table>";
			}

		function display_applications(&$waapp)
			{
				$selected_app = $waapp->get_selected_application();
				if ($selected_app->direct) {
					meta_forward($selected_app->direct);
				}
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
					if ($_SESSION["Language"]->dir == "rtl") {
						$class = "right";
					} else {
						$class = "left";
					}
					foreach ($module->lappfunctions as $appfunction) {
						if ($appfunction->label == "") {
							echo "<li class='empty'>&nbsp;</li>\n";
						} elseif (User::get()->can_access_page($appfunction->access)) {
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
							} elseif (User::get()->can_access_page($appfunction->access)
							) {
								echo "<li>" . menu_link($appfunction->link, $appfunction->label) . "</li>";
							} else {
								echo "<li><span class='inactive'>" . access_string($appfunction->label, true) . "</span></li>\n";
							}
						}
						echo "</ul></td>\n";
					}
					echo "</tr></table></td></tr></table>\n";
				}
			}
	}


