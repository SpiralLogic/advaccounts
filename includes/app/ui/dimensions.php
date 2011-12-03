<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 3/12/11
 * Time: 2:00 PM
 * To change this template use File | Settings | File Templates.
 */

		//  DIMENSIONS
		function dimensions_list($name, $selected_id = null, $no_option = false, $showname = ' ', $submit_on_change = false, $showclosed = false, $showtype = 1) {
			$sql = "SELECT id, CONCAT(reference,'  ',name) as ref FROM dimensions";
			$options = array(
				'order' => 'reference', 'spec_option' => $no_option ? $showname :
				 false, 'spec_id' => 0, 'select_submit' => $submit_on_change, 'async' => false);
			if (!$showclosed) {
				$options['where'][] = "closed=0";
			}
			if ($showtype) {
				$options['where'][] = "type_=$showtype";
			}
			return combo_input($name, $selected_id, $sql, 'id', 'ref', $options);
		}

		function dimensions_list_cells($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo dimensions_list($name, $selected_id, $no_option, $showname, $submit_on_change, $showclosed, $showtype);
			echo "</td>\n";
		}

		function dimensions_list_row($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			dimensions_list_cells(null, $name, $selected_id, $no_option, $showname, $showclosed, $showtype, $submit_on_change);
			echo "</tr>\n";
		}

		function get_dimensions_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($type == ST_DIMENSION) {
				$viewer = "dimensions/view/view_dimension.php?trans_no=$trans_no";
			}
			else {
				return null;
			}
			if ($label == "") {
				$label = $trans_no;
			}
			return Display::viewer_link($label, $viewer, $class, $id, $icon);
		}
