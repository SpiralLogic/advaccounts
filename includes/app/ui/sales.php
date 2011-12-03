<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	// SALES PERSONS
	function sales_persons_list($name, $selected_id = null, $spec_opt = false) {
		$sql = "SELECT salesman_code, salesman_name, inactive FROM salesman";
		return combo_input($name, $selected_id, $sql, 'salesman_code', 'salesman_name', array(
																																												 'order' => array('salesman_name'), 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC));
	}

	function sales_persons_list_cells($label, $name, $selected_id = null, $spec_opt = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>\n";
		echo sales_persons_list($name, $selected_id, $spec_opt);
		echo "</td>\n";
	}

	function sales_persons_list_row($label, $name, $selected_id = null, $spec_opt = false) {
		echo "<tr><td class='label'>$label</td>";
		sales_persons_list_cells(null, $name, $selected_id, $spec_opt);
		echo "</tr>\n";
	}

	// SALES AREA
	function sales_areas_list($name, $selected_id = null) {
		$sql = "SELECT area_code, description, inactive FROM areas";
		return combo_input($name, $selected_id, $sql, 'area_code', 'description', array());
	}

	function sales_areas_list_cells($label, $name, $selected_id = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo sales_areas_list($name, $selected_id);
		echo "</td>\n";
	}

	function sales_areas_list_row($label, $name, $selected_id = null) {
		echo "<tr><td class='label'>$label</td>";
		sales_areas_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	function sales_groups_list($name, $selected_id = null, $special_option = false) {
		$sql = "SELECT id, description, inactive FROM groups";
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
																																						'spec_option' => $special_option === true ? ' ' :
																																						 $special_option, 'order' => 'description', 'spec_id' => 0,));
	}

	function sales_groups_list_cells($label, $name, $selected_id = null, $special_option = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo sales_groups_list($name, $selected_id, $special_option);
		echo "</td>\n";
	}

	function sales_groups_list_row($label, $name, $selected_id = null, $special_option = false) {
		echo "<tr><td class='label'>$label</td>";
		sales_groups_list_cells(null, $name, $selected_id, $special_option);
		echo "</tr>\n";
	}

	// SALES TYPES
	function sales_types_list($name, $selected_id = null, $submit_on_change = false, $special_option = false) {
		$sql = "SELECT id, sales_type, inactive FROM sales_types";
		return combo_input($name, $selected_id, $sql, 'id', 'sales_type', array(
																																					 'spec_option' => $special_option === true ? _("All Sales Types") :
																																						$special_option, 'spec_id' => 0, 'select_submit' => $submit_on_change, //	  'async' => false,
																																			));
	}

	function sales_types_list_cells($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo sales_types_list($name, $selected_id, $submit_on_change, $special_option);
		echo "</td>\n";
	}

	function sales_types_list_row($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
		echo "<tr><td class='label'>$label</td>";
		sales_types_list_cells(null, $name, $selected_id, $submit_on_change, $special_option);
		echo "</tr>\n";
	}
