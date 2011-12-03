<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 2:00 PM
	 * To change this template use File | Settings | File Templates.
	 */
	function systypes_list($name, $value = null, $spec_opt = false, $submit_on_change = false) {
		global $systypes_array;
		return array_selector($name, $value, $systypes_array, array(
																															 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	function systypes_list_cells($label, $name, $value = null, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo systypes_list($name, $value, false, $submit_on_change);
		echo "</td>\n";
	}

	function systypes_list_row($label, $name, $value = null, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		systypes_list_cells(null, $name, $value, $submit_on_change);
		echo "</tr>\n";
	}

	/**
	 * List of sets of active extensions
	 *
	 * @param			$name
	 * @param null $value
	 * @param bool $submit_on_change
	 *
	 * @return string
	 */
	function extset_list($name, $value = null, $submit_on_change = false) {
		$items = array();
		foreach (Config::get_all('db') as $comp) {
			$items[] = sprintf(_("Activated for '%s'"), $comp['name']);
		}
		return array_selector($name, $value, $items, array(
																											'spec_option' => _("Installed on system"), 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => true));
	}
