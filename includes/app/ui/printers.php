<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 2:03 PM
	 * To change this template use File | Settings | File Templates.
	 */
	function print_profiles_list_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = true) {
		$sql = "SELECT profile FROM print_profiles GROUP BY profile";
		$result = DB::query($sql, 'cannot get all profile names');
		$profiles = array();
		while ($myrow = DB::fetch($result)) {
			$profiles[$myrow['profile']] = $myrow['profile'];
		}
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo array_selector($name, $selected_id, $profiles, array(
																														 'select_submit' => $submit_on_change, 'spec_option' => $spec_opt, 'spec_id' => ''));
		echo "</td></tr>\n";
	}

	function printers_list($name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
		static $printers; // query only once for page display
		if (!$printers) {
			$sql = "SELECT id, name, description FROM printers";
			$result = DB::query($sql, 'cannot get all printers');
			$printers = array();
			while ($myrow = DB::fetch($result)) {
				$printers[$myrow['id']] = $myrow['name'] . '&nbsp;-&nbsp;' . $myrow['description'];
			}
		}
		return array_selector($name, $selected_id, $printers, array(
																															 'select_submit' => $submit_on_change, 'spec_option' => $spec_opt, 'spec_id' => ''));
	}

	function pagesizes_list_row($label, $name, $value = null) {
		$items = array();
		foreach (Config::get('formats_paper_size') as $pz) {
			$items[$pz] = $pz;
		}
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, $items);
		echo "</td></tr>\n";
	}
