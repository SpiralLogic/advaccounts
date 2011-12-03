<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	// TAX TYPES
	function tax_types_list($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		$sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
																																		 'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	function tax_types_list_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo tax_types_list($name, $selected_id, $none_option, $submit_on_change);
		echo "</td>\n";
	}

	function tax_types_list_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		tax_types_list_cells(null, $name, $selected_id, $none_option, $submit_on_change);
		echo "</tr>\n";
	}

	// TAX GROUPS
	function tax_groups_list($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		$sql = "SELECT id, name FROM tax_groups";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
																																		 'order' => 'id', 'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	function tax_groups_list_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo tax_groups_list($name, $selected_id, $none_option, $submit_on_change);
		echo "</td>\n";
	}

	function tax_groups_list_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		tax_groups_list_cells(null, $name, $selected_id, $none_option, $submit_on_change);
		echo "</tr>\n";
	}

	// ITEM TAX TYPES
	function item_tax_types_list($name, $selected_id = null) {
		$sql = "SELECT id, name FROM item_tax_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array('order' => 'id'));
	}

	function item_tax_types_list_cells($label, $name, $selected_id = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo item_tax_types_list($name, $selected_id);
		echo "</td>\n";
	}

	function item_tax_types_list_row($label, $name, $selected_id = null) {
		echo "<tr><td class='label'>$label</td>";
		item_tax_types_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}
