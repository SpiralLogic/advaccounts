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
		public function menu() {
			/** @var ADVAccounting $application */
			$application = Session::i()->App;
 echo '<ul class="menu" id="topmenu">';
			foreach ($application->applications as $app) {
				$acc = Display::access_string($app->name);
				if ($app->direct) {
					echo "<li " . ($application->selected->id == $app->id ? "class='active' " : "") . "><a href='/{$app->direct}'$acc[1]>" . $acc[0] . "</a></li>\n";
				}
				else {
					echo "<li " . ($application->selected->id == $app->id ? "class='active' " : "") . "><a href='/index.php?application=" . $app->id . "'$acc[1]>" . $acc[0] . "</a></li>\n";
				}
			}
			echo '</ul>';
		}
		public function display_application(ADVAccounting $application) {
			if ($application->selected->direct) {
				Display::meta_forward($application->selected->direct);
			}
			foreach ($application->selected->modules as $module) {
				// image
				echo "<table class='width100'><tr>";
				echo "<td class='menu_group top'>";
				echo "<table class='width100'>";
				$colspan=(count($module->rappfunctions)>0) ?'colspan=2':'';
				echo "<tr><td class='menu_group' ".$colspan.">";
				echo $module->name;
				echo "</td></tr><tr>";
				echo "<td class='width50 menu_group_items'>";
				echo "<ul>\n";
				foreach ($module->lappfunctions as $appfunction) {
					if ($appfunction->label == "") {
						echo "<li class='empty'>&nbsp;</li>\n";
					}
					elseif (User::get()->can_access_page($appfunction->access)) {
						echo "<li>" . Display::menu_link($appfunction->link, $appfunction->label) . "</li>";
					}
					else {
						echo "<li><span class='inactive'>" . Display::access_string($appfunction->label, true) . "</span></li>\n";
					}
				}
				echo "</ul></td>\n";
				if (count($module->rappfunctions) > 0) {
					echo "<td class='width50 menu_group_items'>";
					echo "<ul>\n";
					foreach ($module->rappfunctions as $appfunction) {
						if ($appfunction->label == "") {
							echo "<li class='empty'>&nbsp;</li>\n";
						}
						elseif (User::get()->can_access_page($appfunction->access)
						) {
							echo "<li>" . Display::menu_link($appfunction->link, $appfunction->label) . "</li>";
						}
						else {
							echo "<li><span class='inactive'>" . Display::access_string($appfunction->label, true) . "</span></li>\n";
						}
					}
					echo "</ul></td>\n";
				}
				echo "</tr></table></td></tr></table>\n";
			}
		}
	}


