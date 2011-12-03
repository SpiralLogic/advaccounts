<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:58 PM
	 * To change this template use File | Settings | File Templates.
	 */
	function security_roles_list($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
		$sql = "SELECT id, role, inactive FROM security_roles";
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
																																						'spec_option' => $new_item ? _("New role") :
																																						 false, 'spec_id' => '', 'select_submit' => $submit_on_change, 'show_inactive' => $show_inactive));
	}

	function security_roles_list_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo security_roles_list($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
		echo "</td>\n";
	}

	function security_roles_list_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
		echo "<tr><td class='label'>$label</td>";
		security_roles_list_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
		echo "</tr>\n";
	}

	function themes_list_row($label, $name, $value = null) {
		$themes = array();
		$themedir = opendir(THEME_PATH);
		while (false !== ($fname = readdir($themedir))) {
			if ($fname != '.' && $fname != '..' && $fname != 'CVS' && is_dir(THEME_PATH . $fname)) {
				$themes[$fname] = $fname;
			}
		}
		ksort($themes);
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, $themes);
		echo "</td></tr>\n";
	}

	function tab_list_row($label, $name, $selected_id = null, $all = false) {
		global $installed_extensions;
		$tabs = array();
		foreach (Session::i()->App->applications as $app) {
			$tabs[$app->id] = Display::access_string($app->name, true);
		}
		if ($all) { // add also not active ext. modules
			foreach ($installed_extensions as $ext) {
				if ($ext['type'] == 'module' && !$ext['active']) {
					$tabs[$ext['tab']] = Display::access_string($ext['title'], true);
				}
			}
		}
		echo "<tr>\n";
		echo "<td class='label'>$label</td><td>\n";
		echo array_selector($name, $selected_id, $tabs);
		echo "</td></tr>\n";
	}

	function user_list($name, $selected_id = null, $spec_opt = false) {
		$sql = "SELECT id, real_name, inactive FROM users";
		return combo_input($name, $selected_id, $sql, 'id', 'real_name', array(
																																					'order' => array('real_name'), 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC));
	}

	function user_list_cells($label, $name, $selected_id = null, $spec_opt = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>\n";
		echo user_list($name, $selected_id, $spec_opt);
		echo "</td>\n";
	}

	function user_list_row($label, $name, $selected_id = null, $spec_opt = false) {
		echo "<tr><td class='label'>$label</td>";
		user_list_cells(null, $name, $selected_id, $spec_opt);
		echo "</tr>\n";
	}