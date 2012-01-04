<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_SECROLES;
	Page::start(_($help_context = "Access setup"));
	$new_role = get_post('role') == '' || get_post('cancel') || get_post('clone');
	// Following compare function is used for sorting areas
	// in such a way that security areas defined by module/plugin
	// is properly placed under related section regardless of
	// unique extension number, with order inside sections preserved.
	//
	function comp_areas($area1, $area2) {
		$sec_comp = ($area1[0] & 0xff00) - ($area2[0] & 0xff00);
		return $sec_comp == 0 ? ($area1[2] - $area2[2]) : $sec_comp;
	}

	function sort_areas($areas) {
		$old_order = 0;
		foreach ($areas as $key => $area) {
			$areas[$key][] = $old_order++;
		}
		uasort($areas, 'comp_areas');
		return $areas;
	}

	if (list_updated('role')) {
		Ajax::i()->activate('details');
		Ajax::i()->activate('controls');
	}
	function clear_data() {
		unset($_POST);
	}

	if (get_post('addupdate')) {
		$input_error = 0;
		if ($_POST['description'] == '') {
			$input_error = 1;
			Errors::error(_("Role description cannot be empty."));
			JS::set_focus('description');
		}
		elseif ($_POST['name'] == '') {
			$input_error = 1;
			Errors::error(_("Role name cannot be empty."));
			JS::set_focus('name');
		}
		// prevent accidental editor lockup by removing SA_SECROLES
		if (get_post('role') == $_SESSION['current_user']->access) {
			if (!isset($_POST['Area' . $security_areas[SA_SECROLES][0]]) || !isset($_POST[Section . SS_SETUP])
			) {
				Errors::error(_("Access level edition in Company setup section have to be enabled for your account."));
				$input_error = 1;
				JS::set_focus(!isset($_POST['Section' . SS_SETUP]) ? 'Section' . SS_SETUP : 'Area' . $security_areas[SA_SECROLES][0]);
			}
		}
		if ($input_error == 0) {
			$sections = array();
			$areas = array();
			foreach ($_POST as $p => $val) {
				if (substr($p, 0, 4) == 'Area') {
					$a = substr($p, 4);
					if (($a & ~0xffff) && (($a & 0xff00) < (99 << 8))) {
						$sections[] = $a & ~0xff; // add extended section for plugins
					}
					$areas[] = $a;
				}
				if (substr($p, 0, 7) == 'Section') {
					$sections[] = substr($p, 7);
				}
			}
			//		$areas = sort_areas($areas);
			$sections = array_values($sections);
			if ($new_role) {
				Security::add_role($_POST['name'], $_POST['description'], $sections, $areas);
				Errors::notice(_("New security role has been added."));
			}
			else {
				Security::update_role($_POST['role'], $_POST['name'], $_POST['description'], $sections, $areas);
				DB::update_record_status($_POST['role'], get_post('inactive'), 'security_roles', 'id');
				Errors::notice(_("Security role has been updated."));
			}
			$new_role = true;
			clear_data();
			Ajax::i()->activate('_page_body');
		}
	}
	if (get_post('delete')) {
		if (Security::check_role_used(get_post('role'))) {
			Errors::error(_("This role is currently assigned to some users and cannot be deleted"));
		}
		else {
			Security::get_profile(get_post('role'));
			Errors::notice(_("Security role has been sucessfully deleted."));
			unset($_POST['role']);
		}
		Ajax::i()->activate('_page_body');
	}
	if (get_post('cancel')) {
		unset($_POST['role']);
		Ajax::i()->activate('_page_body');
	}
	if (!isset($_POST['role']) || get_post('clone') || list_updated('role')) {
		$id = get_post('role');
		$clone = get_post('clone');
		unset($_POST);
		if ($id) {
			$row = Security::get_role($id);
			$_POST['description'] = $row['description'];
			$_POST['name'] = $row['role'];
			//	if ($row['inactive']
			//		$_POST['inactive'] = 1;
			$_POST['inactive'] = $row['inactive'];
			$access = $row['areas'];
			$sections = $row['sections'];
		}
		else {
			$_POST['description'] = $_POST['name'] = '';
			unset($_POST['inactive']);
			$access = $sections = array();
		}
		foreach ($access as $a) {
			$_POST['Area' . $a] = 1;
		}
		foreach ($sections as $s) {
			$_POST['Section' . $s] = 1;
		}
		if ($clone) {
			JS::set_focus('name');
			Ajax::i()->activate('_page_body');
		}
		else {
			$_POST['role'] = $id;
		}
	}
	start_form();
	start_table('tablestyle_noborder');
	start_row();
	Security::roles_cells(_("Role:") . "&nbsp;", 'role', null, true, true, check_value('show_inactive'));
	$new_role = get_post('role') == '';
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	echo "<hr>";
	if (get_post('_show_inactive_update')) {
		Ajax::i()->activate('role');
		JS::set_focus('role');
	}
	if (find_submit('_Section')) {
		Ajax::i()->activate('details');
	}
	Display::div_start('details');
	start_table('tablestyle2');
	text_row(_("Role name:"), 'name', null, 20, 22);
	text_row(_("Role description:"), 'description', null, 50, 52);
	record_status_list_row(_("Current status:"), 'inactive');
	end_table(1);
	start_table('tablestyle width40');
	$k = $j = 0; //row colour counter
	$ext = $sec = $m = -1;
	foreach (sort_areas($security_areas) as $area => $parms) {
		// system setup areas are accessable only for site admins i.e.
		// admins of first registered company
		if ((($parms[0] & 0xff00) == SS_SADMIN)) {
			continue;
		}
		$newsec = ($parms[0] >> 8) & 0xff;
		$newext = $parms[0] >> 16;
		if ($newsec != $sec || (($newext != $ext) && ($newsec > 99))) { // features set selection
			$ext = $newext;
			$sec = $newsec;
			$m = $parms[0] & ~0xff;
			//			if(!isset($security_sections[$m]))
			//			 Errors::error(sprintf("Bad section %X:", $m));
			label_row($security_sections[$m] . ':', checkbox(null, 'Section' . $m, null, true, _("On/off set of features")), "class='tableheader2'", "class='tableheader'");
		}
		if (check_value('Section' . $m)) {
			alt_table_row_color($k);
			check_cells($parms[1], 'Area' . $parms[0], null, false, '', "class='center'");
			end_row();
		}
		else {
			hidden('Area' . $parms[0]);
		}
	}
	end_table(1);
	Display::div_end();
	Display::div_start('controls');
	if ($new_role) {
		submit_center_first('Update', _("Update view"), '', null);
		submit_center_last('addupdate', _("Insert New Role"), '', 'default');
	}
	else {
		submit_center_first('addupdate', _("Save Role"), '', 'default');
		submit('Update', _("Update view"), true, '', null);
		submit('clone', _("Clone This Role"), true, '', true);
		submit('delete', _("Delete This Role"), true, '', true);
		submit_center_last('cancel', _("Cancel"), _("Cancel Edition"), 'cancel');
	}
	Display::div_end();
	end_form();
	Page::end();

?>