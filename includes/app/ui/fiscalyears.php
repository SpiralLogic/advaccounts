<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 3/12/11
 * Time: 2:02 PM
 * To change this template use File | Settings | File Templates.
 */


		//  FISCALYEARS
		function fiscalyears_list($name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT * FROM fiscal_year";
			// default to the company current fiscal year
			return combo_input($name, $selected_id, $sql, 'id', '', array(
																																	 'order' => 'begin', 'default' => DB_Company::get_pref('f_year'), 'format' => '_format_fiscalyears', 'select_submit' => $submit_on_change, 'async' => false));
		}


		function fiscalyears_list_cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo fiscalyears_list($name, $selected_id);
			echo "</td>\n";
		}

		function fiscalyears_list_row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			fiscalyears_list_cells(null, $name, $selected_id);
			echo "</tr>\n";
		}
