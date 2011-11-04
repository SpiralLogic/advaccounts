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

	class ui_msgs {
		static function display_error($msg) {
			trigger_error($msg, E_USER_ERROR);
		}

		static function display_notification($msg) {
			trigger_error($msg, E_USER_NOTICE);
		}

		static function display_warning($msg) {
			trigger_error($msg, E_USER_WARNING);
		}

		static function display_notification($msg) {
			ui_msgs::display_notification($msg, true);
		}

		static function display_heading($msg) {
			echo "<center><span class='headingtext'>$msg</span></center>\n";
		}

		static function display_heading2($msg) {
			echo "<center><span class='headingtext2'>$msg</span></center>\n";
		}

		static function display_note($msg, $br = 0, $br2 = 0, $extra = "") {
			for ($i = 0; $i < $br; $i++)
			{
				echo "<br>";
			}
			if ($extra != "")
				echo "<center><span $extra>$msg</span></center>\n";
			else
				echo "<center><span class='note_msg'>$msg</span></center>\n";
			for ($i = 0; $i < $br2; $i++)
			{
				echo "<br>";
			}
		}

		static function stock_item_heading($stock_id) {
			if ($stock_id != "") {
				$result = DBOld::query("SELECT description, units FROM stock_master WHERE stock_id='$stock_id'");
				$myrow = DBOld::fetch_row($result);

				ui_msgs::display_heading("$stock_id - $myrow[0]");
				$units = $myrow[1];
				ui_msgs::display_heading2(_("in units of : ") . $units);
			}
		}
	}