<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 2:00 PM
	 * To change this template use File | Settings | File Templates.
	 */
	function quick_entries_list($name, $selected_id = null, $type = null, $submit_on_change = false) {
		$where = false;
		$sql = "SELECT id, description FROM quick_entries";
		if ($type != null) {
			$sql .= " WHERE type=$type";
		}
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
																																						'spec_id' => '', 'order' => 'description', 'select_submit' => $submit_on_change, 'async' => false));
	}

	function quick_entries_list_cells($label, $name, $selected_id = null, $type, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo quick_entries_list($name, $selected_id, $type, $submit_on_change);
		echo "</td>";
	}

	function quick_entries_list_row($label, $name, $selected_id = null, $type, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		quick_entries_list_cells(null, $name, $selected_id, $type, $submit_on_change);
		echo "</tr>\n";
	}

	function quick_actions_list_row($label, $name, $selected_id = null, $submit_on_change = false) {
		global $quick_actions;
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector($name, $selected_id, $quick_actions, array('select_submit' => $submit_on_change));
		echo "</td></tr>\n";
	}

	function quick_entry_types_list_row($label, $name, $selected_id = null, $submit_on_change = false) {
		global $quick_entry_types;
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector($name, $selected_id, $quick_entry_types, array('select_submit' => $submit_on_change));
		echo "</td></tr>\n";
	}
