<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:49 PM
	 * To change this template use File | Settings | File Templates.
	 */
	//  LOCATIONS
	function languages_list($name, $selected_id = null) {
		$items = array();
		$langs = Config::get('languages.installed');
		foreach ($langs as $lang) {
			$items[$lang['code']] = $lang['name'];
		}
		return array_selector($name, $selected_id, $items);
	}

	function languages_list_cells($label, $name, $selected_id = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo languages_list($name, $selected_id);
		echo "</td>\n";
	}

	function languages_list_row($label, $name, $selected_id = null) {
		echo "<tr><td class='label'>$label</td>";
		languages_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}
