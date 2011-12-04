<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:49 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class UI_Languages
	{
		function combo($name, $selected_id = null) {
			$items = array();
			$langs = Config::get('languages.installed');
			foreach ($langs as $lang) {
				$items[$lang['code']] = $lang['name'];
			}
			return array_selector($name, $selected_id, $items);
		}

		function cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo UI_Languages::combo($name, $selected_id);
			echo "</td>\n";
		}

		function row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			UI_Languages::cells(null, $name, $selected_id);
			echo "</tr>\n";
		}
	}