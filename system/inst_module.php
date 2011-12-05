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
	$page_security = 'SA_CREATEMODULES';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Install/Activate extensions"));
	Page::simple_mode(true);

	function update_extensions($extensions)
	{
		if (!advaccounting::write_extensions($extensions)) {
			Errors::notice(_("Cannot update system extensions list."));
			return false;
		}
		// update per company files
		$cnt = count(Config::get_all('db'));
		for (
			$i = 0; $i < $cnt; $i++
		)
		{
			$newexts = $extensions;
			// update 'active' status
			$exts = DB_Company::get_company_extensions($i);
			foreach (
				$exts as $key => $ext
			)
			{
				if (isset($newexts[$key])) {
					$newexts[$key]['active'] = $exts[$key]['active'];
				}
			}
			if (!advaccounting::write_extensions($newexts, $i)) {
				Errors::notice(
					sprintf(
						_("Cannot update extensions list for company '%s'."),
						Config::get('db.' . $i, 'name')
					)
				);
				return false;
			}
		}
		return true;
	}

	function check_data($id, $exts)
	{
		if ($_POST['name'] == "") {
			Errors::error(_("Extension name cannot be empty."));
			return false;
		}
		foreach (
			$exts as $n => $ext
		) {
			if ($_POST['name'] == $ext['name'] && $id != $n) {
				Errors::error(_("Extension name have to be unique."));
				return false;
			}
		}
		if ($_POST['title'] == "") {
			Errors::error(_("Extension title cannot be empty."));
			return false;
		}
		if ($_POST['path'] == "") {
			Errors::error(_("Extension folder name cannot be empty."));
			return false;
		}
		if ($id == -1 && !is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
			Errors::error(_("You have to select plugin file to upload"));
			return false;
		}
		return true;
	}


	function handle_submit()
	{
		global $selected_id, $next_extension_id;
		$extensions = DB_Company::get_company_extensions();
		if (!check_data($selected_id, $extensions)) {
			return false;
		}
		$id = $selected_id == -1 ? $next_extension_id : $selected_id;
		if ($selected_id != -1 && $extensions[$id]['type'] != 'plugin') {
			Errors::error(_('Module installation support is not implemented yet. You have to do it manually.'));
			return;
		}
		$extensions[$id]['tab'] = $_POST['tab'];
		$extensions[$id]['name'] = $_POST['name'];
		$extensions[$id]['path'] = $_POST['path'];
		$extensions[$id]['title'] = $_POST['title'];
		$extensions[$id]['active'] = check_value('active');
		// Currently we support only plugin extensions here.
		$extensions[$id]['type'] = 'plugin';
		$directory = DOCROOT . "modules/" . $_POST['path'];
		if (!file_exists($directory)) {
			mkdir($directory);
		}
		if (is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
			$extensions[$id]['filename'] = $_FILES['uploadfile']['name'];
			$file1 = $_FILES['uploadfile']['tmp_name'];
			$file2 = $directory . "/" . $_FILES['uploadfile']['name'];
			if (file_exists($file2)) {
				unlink($file2);
			}
			move_uploaded_file($file1, $file2);
		} else {
			$extensions[$id]['filename'] = get_post('filename');
		}
		if (is_uploaded_file($_FILES['uploadfile2']['tmp_name'])) {
			$file1 = $_FILES['uploadfile2']['tmp_name'];
			$file2 = $directory . "/" . $_FILES['uploadfile2']['name'];
			if (file_exists($file2)) {
				unlink($file2);
			}
			move_uploaded_file($file1, $file2);
			$db_name = User::get()->company;
			DB_Utils::import($file2, Config::get('db.' . $db_name));
		}
		if (is_uploaded_file($_FILES['uploadfile3']['tmp_name'])) {
			$extensions[$id]['acc_file'] = $_FILES['uploadfile3']['name'];
			$file1 = $_FILES['uploadfile3']['tmp_name'];
			$file2 = $directory . "/" . $_FILES['uploadfile3']['name'];
			if (file_exists($file2)) {
				unlink($file2);
			}
			move_uploaded_file($file1, $file2);
		} else {
			$extensions[$id]['acc_file'] = get_post('acc_file');
		}
		// security area guess for plugins
		if ($extensions[$id]['type'] == 'plugin') {
			$exttext = file_get_contents(
				PATH_TO_ROOT . '/modules/'
				 . $extensions[$id]['path'] . '/' . $extensions[$id]['filename']
			);
			$area = 'SA_OPEN';
			if (preg_match('/.*\$page_security\s*=\s*[\'"]([^\'"]*)/', $exttext, $match)) {
				$area = trim($match[1]);
			}
			$extensions[$id]['access'] = $area;
		}
		if ($selected_id == -1) {
			$next_extension_id++;
		}
		if (!update_extensions($extensions)) {
			return false;
		}
		return true;
	}

	function handle_delete()
	{
		global $selected_id;
		$extensions = DB_Company::get_company_extensions();
		$id = $selected_id;
		$filename = PATH_TO_ROOT
		 . ($extensions[$id]['type'] == 'plugin' ? "/modules/" : '/')
		 . $extensions[$id]['path'];
		Files::flush_dir($filename);
		rmdir($filename);
		unset($extensions[$id]);
		if (update_extensions($extensions)) {
			Errors::notice(_("Selected extension has been successfully deleted"));
		}
		return true;
	}


	function display_extensions()
	{
		start_table('tablestyle');
		$th = array(
			_("Name"), _("Tab"), _("Link text"), _("Folder"), _("Filename"),
			_("Access extensions"), "", ""
		);
		table_header($th);
		$k = 0;
		$mods = DB_Company::get_company_extensions();
		$mods = Arr::natsort($mods, null, 'name');
		foreach (
			$mods as $i => $mod
		)
		{
			$is_mod = $mod['type'] == 'module';
			Display::alt_table_row_color($k);
			label_cell($mod['name']);
			label_cell(
				$is_mod ?
				 $mod['title'] : Display::access_string(Session::i()->App->applications[$mod['tab']]->name, true)
			);
			$ttl = Display::access_string($mod['title']);
			label_cell($ttl[0]);
			label_cell($mod['path']);
			label_cell($mod['filename']);
			label_cell(@$mod['acc_file']);
			if ($is_mod) {
				label_cell(''); // not implemented (yet)
			} else {
				edit_button_cell("Edit" . $i, _("Edit"));
			}
			delete_button_cell("Delete" . $i, _("Delete"));
			submit_js_confirm('Delete' . $i, _('You are about to delete this extension\nDo you want to continue?'));
			end_row();
		}
		end_table(1);
	}

	function company_extensions($id)
	{
		start_table('tablestyle');
		$th = array(_("Name"), _("Tab"), _("Link text"), _("Active"));
		// get all available extensions and display
		// with current status stored in company directory.
		$mods = DB_Company::get_company_extensions();
		$exts = DB_Company::get_company_extensions($id);
		foreach (
			$mods as $key => $ins
		) {
			foreach (
				$exts as $ext
			)
			{
				if ($ext['name'] == $ins['name']) {
					$mods[$key]['active'] = @$ext['active'];
					continue 2;
				}
			}
		}
		$mods = Arr::natsort($mods, null, 'name');
		table_header($th);
		$k = 0;
		foreach (
			$mods as $i => $mod
		)
		{
			Display::alt_table_row_color($k);
			label_cell($mod['name']);
			label_cell(
				$mod['type'] == 'module' ?
				 $mod['title'] : Display::access_string(Session::i()->App->applications[$mod['tab']]->name, true)
			);
			$ttl = Display::access_string($mod['title']);
			label_cell($ttl[0]);
			check_cells(
				null, 'Active' . $i, @$mod['active'] ? 1 : 0,
				false, false, "class='center'"
			);
			end_row();
		}
		end_table(1);
		submit_center('Update', _('Update'), true, false, 'default');
	}


	function display_ext_edit($selected_id)
	{
		global $Mode;
		$extensions = DB_Company::get_company_extensions();
		start_table('tablestyle2');
		if ($selected_id != -1 && $extensions[$selected_id]['type'] == 'plugin') {
			if ($Mode == 'Edit') {
				$mod = $extensions[$selected_id];
				$_POST['tab'] = $mod['tab'];
				$_POST['name'] = $mod['name'];
				$_POST['title'] = $mod['title'];
				$_POST['path'] = $mod['path'];
				$_POST['filename'] = $mod['filename'];
				$_POST['acc_file'] = @$mod['acc_file'];
				hidden('filename', $_POST['filename']);
				hidden('acc_file', $_POST['acc_file']);
			}
			hidden('selected_id', $selected_id);
		}
		text_row_ex(_("Name"), 'name', 30);
		text_row_ex(_("Folder"), 'path', 20);
		Users::tabs_row(_("Menu Tab"), 'tab', null, true);
		text_row_ex(_("Menu Link Text"), 'title', 30);
		record_status_list_row(_("Default status"), 'active');
		file_row(_("Module File"), 'uploadfile');
		file_row(_("Access Levels Extensions"), 'uploadfile3');
		file_row(_("SQL File"), 'uploadfile2');
		end_table(0);
		Errors::warning(_("Select your module PHP file from your local harddisk."), 0, 1);
		submit_add_or_update_center($selected_id == -1, '', 'both');
	}


	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (handle_submit()) {
			if ($selected_id != -1) {
				Errors::notice(_("Extension data has been updated."));
			} else {
				Errors::notice(_("Extension has been installed."));
			}
			$Mode = 'RESET';
		}
	}
	if ($Mode == 'Delete') {
		handle_delete();
		$Mode = 'RESET';
	}
	if (get_post('Update')) {
		$exts = DB_Company::get_company_extensions();
		foreach (
			$exts as $i => $ext
		) {
			$exts[$i]['active'] = check_value('Active' . $i);
		}
		advaccounting::write_extensions($exts, get_post('extset'));
		$installed_extensions = $exts;
		Errors::notice(_('Current active extensions set has been saved.'));
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		unset($_POST);
	}

	start_form(true);
	if (list_updated('extset')) {
		$Ajax->activate('_page_body');
	}
	echo "<div class='center'>" . _('Extensions:') . "&nbsp;&nbsp;";
	echo Extensions::view('extset', null, true);
	echo "</div><br>";
	$set = get_post('extset', -1);
	if ($set == -1) {
		display_extensions();
		display_ext_edit($selected_id);
	} else {
		company_extensions($set);
	}

	end_form();
	end_page();

?>