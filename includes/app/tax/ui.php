<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */

class Tax_UI {
	// TAX TYPES
	public static function  types($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		$sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";
		return select_box($name, $selected_id, $sql, 'id', 'name', array(
																																		 'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	public static function  types_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo Tax_UI::types($name, $selected_id, $none_option, $submit_on_change);
		echo "</td>\n";
	}

	public static function  types_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		Tax_UI::types_cells(null, $name, $selected_id, $none_option, $submit_on_change);
		echo "</tr>\n";
	}

	// TAX GROUPS
	public static function  groups($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		$sql = "SELECT id, name FROM tax_groups";
		return select_box($name, $selected_id, $sql, 'id', 'name', array(
																																		 'order' => 'id', 'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	public static function  groups_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo Tax_UI::groups($name, $selected_id, $none_option, $submit_on_change);
		echo "</td>\n";
	}

	public static function  groups_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		Tax_UI::groups_cells(null, $name, $selected_id, $none_option, $submit_on_change);
		echo "</tr>\n";
	}

	// ITEM TAX TYPES
	public static function  item_types($name, $selected_id = null) {
		$sql = "SELECT id, name FROM item_tax_types";
		return select_box($name, $selected_id, $sql, 'id', 'name', array('order' => 'id'));
	}

	public static function  item_types_cells($label, $name, $selected_id = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo Tax_UI::item_types($name, $selected_id);
		echo "</td>\n";
	}

	public static function  item_types_row($label, $name, $selected_id = null) {
		echo "<tr><td class='label'>$label</td>";
		Tax_UI::item_types_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}
}