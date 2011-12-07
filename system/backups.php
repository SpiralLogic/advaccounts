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
	$page_security = 'SA_BACKUP';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	if (get_post('view')) {
		if (!get_post('backups')) {
			Errors::error(_('Select backup file first.'));
		} else {
			$filename = BACKUP_PATH . get_post('backups');
			if (Ajax::in_ajax()) {
				$Ajax->popup($filename);
			}
			else {
				header('Content-type: application/octet-stream');
				header('Content-Length: ' . filesize($filename));
				header("Content-Disposition: inline; filename=$filename");
				readfile($filename);
				exit();
			}
		}
	}
	;
	if (get_post('download')) {
		download_file(BACKUP_PATH . get_post('backups'));
		exit;
	}
	Page::start(_($help_context = "Backup and Restore Database"), false, false, '', '');
	check_paths();
	function check_paths()
	{
		if (!file_exists(BACKUP_PATH)) {
			Errors::error(
				_("Backup paths have not been set correctly.")
				 . _("Please contact System Administrator.") . "<br>"
				 . _("cannot find backup directory") . " - " . BACKUP_PATH . "<br>"
			);
			end_page();
			exit;
		}
	}

	function generate_backup($conn, $ext = 'no', $comm = '')
	{
		$filename = DB_Utils::backup($conn, $ext, $comm);
		if ($filename) {
			Errors::notice(
				_("Backup successfully generated.") . ' '
				 . _("Filename") . ": " . $filename
			);
		} else {
			Errors::error(_("Database backup failed."));
		}
		return $filename;
	}

	function get_backup_file_combo()
	{
		$Ajax = Ajax::i();
		$ar_files = array();
		JS::default_focus('backups');
		$dh = opendir(BACKUP_PATH);
		while (($file = readdir($dh)) !== false)
		{
			$ar_files[] = $file;
		}
		closedir($dh);
		rsort($ar_files);
		$opt_files = "";
		foreach (
			$ar_files as $file
		)
		{
			if (preg_match("/.sql(.zip|.gz)?$/", $file)) {
				$opt_files .= "<option value='$file'>$file</option>";
			}
		}
		$selector = "<select name='backups' size=2 style='height:160px;min-width:230px'>$opt_files</select>";
		$Ajax->addUpdate('backups', "_backups_sel", $selector);
		$selector = "<span id='_backups_sel'>" . $selector . "</span>\n";
		return $selector;
	}

	function compress_list_row($label, $name, $value = null)
	{
		$ar_comps = array('no' => _("No"));
		if (function_exists("gzcompress")) {
			$ar_comps['zip'] = "zip";
		}
		if (function_exists("gzopen")) {
			$ar_comps['gzip'] = "gzip";
		}
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector('comp', $value, $ar_comps);
		echo "</td></tr>";
	}

	function download_file($filename)
	{
		if (empty($filename) || !file_exists($filename)) {
			Errors::error(_('Select backup file first.'));
			return false;
		}
		$saveasname = basename($filename);
		header('Content-type: application/octet-stream');
		header('Content-Length: ' . filesize($filename));
		header('Content-Disposition: attachment; filename="' . $saveasname . '"');
		readfile($filename);
		return true;
	}

	$db_name = User::get()->company;
	$connections = Config::get_all('db');
	$conn = $connections[$db_name];
	if (get_post('creat')) {
		generate_backup($conn, get_post('comp'), get_post('comments'));
		$Ajax->activate('backups');
	}
	;
	if (get_post('restore')) {
		if (DB_Utils::import(BACKUP_PATH . get_post('backups'), $conn)) {
			Errors::notice(_("Restore backup completed."));
		}
	}
	if (get_post('deldump')) {
		if (unlink(BACKUP_PATH . get_post('backups'))) {
			Errors::notice(
				_("File successfully deleted.") . " "
				 . _("Filename") . ": " . get_post('backups')
			);
			$Ajax->activate('backups');
		} else {
			Errors::error(_("Can't delete backup file."));
		}
	}
	;
	if (get_post('upload')) {
		$tmpname = $_FILES['uploadfile']['tmp_name'];
		$fname = $_FILES['uploadfile']['name'];
		if (!preg_match("/.sql(.zip|.gz)?$/", $fname)) {
			Errors::error(_("You can only upload *.sql backup files"));
		}
		elseif (is_uploaded_file($tmpname)) {
			rename($tmpname, BACKUP_PATH . $fname);
			Errors::notice("File uploaded to backup directory");
			$Ajax->activate('backups');
		} else
		{
			Errors::error(_("File was not uploaded into the system."));
		}
	}

	start_form(true);
	start_outer_table('tablestyle2');
	table_section(1);
	table_section_title(_("Create backup"));
	textarea_row(_("Comments:"), 'comments', null, 30, 8);
	compress_list_row(_("Compression:"), 'comp');
	submit_row('creat', _("Create Backup"), false, "colspan=2 class='center'", '', 'process');
	table_section(2);
	table_section_title(_("Backup scripts maintenance"));
	start_row();
	echo "<td style='padding-left:20px'class='left'>" . get_backup_file_combo() . "</td>";
	echo "<td class='top'>";
	start_table();
	submit_row('view', _("View Backup"), false, '', '', true);
	submit_row('download', _("Download Backup"), false, '', '', false);
	submit_row('restore', _("Restore Backup"), false, '', '', 'process');
	submit_js_confirm('restore', _("You are about to restore database from backup file.\nDo you want to continue?"));
	submit_row('deldump', _("Delete Backup"), false, '', '', true);
	// don't use 'delete' name or IE js errors appear
	submit_js_confirm('deldump', sprintf(_("You are about to remove selected backup file.\nDo you want to continue ?")));
	end_table();
	echo "</td>";
	end_row();
	start_row();
	echo "<td style='padding-left:20px' class='left'><input name='uploadfile' type='file'></td>";
	submit_cells('upload', _("Upload file"), '', '', true);
	end_row();
	end_outer_table();
	end_form();
	end_page();
?>