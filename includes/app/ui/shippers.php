<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 2:02 PM
	 * To change this template use File | Settings | File Templates.
	 */
	// SHIPPERS
	function shippers_list($name, $selected_id = null) {
		$sql = "SELECT shipper_id, shipper_name, inactive FROM shippers";
		return combo_input($name, $selected_id, $sql, 'shipper_id', 'shipper_name', array('order' => array('shipper_name')));
	}

	function shippers_list_cells($label, $name, $selected_id = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo shippers_list($name, $selected_id);
		echo "</td>\n";
	}

	function shippers_list_row($label, $name, $selected_id = null) {
		echo "<tr><td class='label'>$label</td>";
		shippers_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	function policy_list_cells($label, $name, $selected = null) {
		if ($label != null) {
			label_cell($label);
		}
		echo "<td>\n";
		echo array_selector($name, $selected, array(
																							 '' => _("Automatically put balance on back order"), 'CAN' => _("Cancel any quantites not delivered")));
		echo "</td>\n";
	}

	function policy_list_row($label, $name, $selected = null) {
		echo "<tr><td class='label'>$label</td>";
		policy_list_cells(null, $name, $selected);
		echo "</tr>\n";
	}

