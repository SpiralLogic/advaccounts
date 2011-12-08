<?php

	/* * ********************************************************************
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
		protected static $_i = null;

		/***
		 * @static
		 * @return Renderer
		 */
		public static function i() {
			if (static::$_i === null) {
				static::$_i = new static;
			}
			return static::$_i;
		}

		public function header() {
			Page::start(_($help_context = "Main Menu"), false, true);
		}

		public function footer() {
			Renderer::end_page(false, true);
		}

		public static function end_page($no_menu = false, $is_index = false, $hide_back_link = false) {
			if (Input::request('frame' || Input::get('popup'))) {
				$is_index = $hide_back_link = true;
			}
			if (!$is_index && !$hide_back_link && function_exists('link_back')) {
				Display::link_back(true, $no_menu);
			}
			Display::div_end(); // end of _page_body section
			Page::footer($no_menu, $is_index, $hide_back_link);
		}

		public function menu_header($title, $no_menu, $is_index) {
			$sel_app = $_SESSION['sel_app'];
			echo "<div id='content'>\n";
			if (!$no_menu || AJAX_REFERRER) {
				$applications = Session::i()->App->applications;
				echo "<div id='top'>\n";
				echo "<p>" . Config::get('db.' . User::get()->company,
					'name') . " | " . $_SERVER['SERVER_NAME'] . " | " . User::get()->name . "</p>\n";
				echo "<ul>\n";
				echo " <li><a href='" . PATH_TO_ROOT . "/system/display_prefs.php?'>" . _("Preferences") . "</a></li>\n";
				echo " <li><a href='" . PATH_TO_ROOT . "/system/change_current_user_password.php?selected_id=" . User::get()->username . "'>" . _("Change password") . "</a></li>\n";
				if (Config::get('help_baseurl') != null) {
					echo " <li><a target = '_blank' class='.openWindow' href='" . Page::help_url() . "'>" . _("Help") . "</a></li>";
				}
				echo " <li><a href='" . PATH_TO_ROOT . "/access/logout.php?'>" . _("Logout") . "</a></li>";
				echo "</ul>\n";
				echo "</div>\n";
				echo "<div id='logo'>\n";
				$indicator = "/themes/" . User::theme() . "/images/ajax-loader.gif";
				echo "<h1>" . APP_TITLE . " " . VERSION . "<span style='padding-left:280px;'><img id='ajaxmark' src='$indicator' class='center' style='visibility:hidden;'></span></h1>\n";
				echo "</div>\n";
				echo '<div id="_tabs2"><div class="menu_container">';
				echo "<ul class='menu'>\n";
				foreach ($applications as $app) {
					$acc = Display::access_string($app->name);
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
				echo "<div class='center'><table id='title'><tr><td class='width100 titletext'>$title</td><td class=right>" . (User::hints() ?
				 "<span id='hints'></span>" : '') . "</td></tr></table></div>";
			}
		}

		public function menu_footer($no_menu, $is_index) {
			if ($no_menu == false && !AJAX_REFERRER) {
				echo "<div id='footer'>\n";
				if (isset($_SESSION['current_user'])) {
					echo "<span class='power'><a target='_blank' href='" . POWERED_URL . "'>" . POWERED_BY . "</a></span>\n";
					echo "<span class='date'>" . Dates::Today() . " | " . Dates::Now() . "</span>\n";
					if ($_SESSION['current_user']->logged_in()) {
						echo "<span class='date'> " . Users::show_online() . "</span>\n";
					}
					echo "<span> </span>| <span>mem/peak: " . Files::convert_size(memory_get_usage(true)) . '/' . Files::convert_size(memory_get_peak_usage(true)) . ' </span><span>|</span><span> load time: ' . Dates::getReadableTime(microtime(true) - ADV_START_TIME) .
					 "</span>";
				}
				echo "</div>";
			}
			if (Config::get('debug')) {
				$this->display_loaded();
			}
			echo "</div>\n";
			echo "</div>\n";
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

		public function display_applications(&$waapp) {
			$selected_app = $waapp->get_selected_application();
			if ($selected_app->direct) {
				Display::meta_forward($selected_app->direct);
			}
			foreach ($selected_app->modules as $module) {
				// image
				echo "<table style='width:100%'><tr>";
				echo "<td class='menu_group top'>";
				echo "<table style='width:100%'>";
				echo "<tr><td class='menu_group' colspan=2>";
				echo $module->name;
				echo "</td></tr><tr>";
				echo "<td style='width:50%' class='menu_group_items'>";
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
						echo "<li>" . Display::menu_link($appfunction->link, $appfunction->label) . "</li>";
					} else {
						echo "<li><span class='inactive'>" . Display::access_string($appfunction->label, true) . "</span></li>\n";
					}
				}
				echo "</ul></td>\n";
				if (sizeof($module->rappfunctions) > 0) {
					echo "<td style='width:50%' class='menu_group_items'>";
					echo "<ul>\n";
					foreach ($module->rappfunctions as $appfunction) {
						if ($appfunction->label == "") {
							echo "<li class='empty'>&nbsp;</li>\n";
						} elseif (User::get()->can_access_page($appfunction->access)
						) {
							echo "<li>" . Display::menu_link($appfunction->link, $appfunction->label) . "</li>";
						} else {
							echo "<li><span class='inactive'>" . Display::access_string($appfunction->label, true) . "</span></li>\n";
						}
					}
					echo "</ul></td>\n";
				}
				echo "</tr></table></td></tr></table>\n";
			}
		}
	}


