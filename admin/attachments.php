<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/

	$page_security = 'SA_ATTACHDOCUMENT';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	if (isset($_GET['vw']))
		$view_id = $_GET['vw'];
	else
		$view_id = find_submit('view');
	if ($view_id != -1) {
		$row = get_attachment($view_id);
		if ($row['filename'] != "") {
			if (Ajax::in_ajax()) {
				$Ajax->popup($_SERVER['PHP_SELF'] . '?vw=' . $view_id);
			} else {
				$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';
				header("Content-type: " . $type);
				header('Content-Length: ' . $row['filesize']);
				if ($type == 'application/octet-stream')
					header('Content-Disposition: attachment; filename=' . $row['filename']);
				else
					header("Content-Disposition: inline");
				echo file_get_contents(COMPANY_PATH . "/attachments/" . $row['unique_name']);
				exit();
			}
		}
	}
	if (isset($_GET['dl']))
		$download_id = $_GET['dl'];
	else
		$download_id = find_submit('download');

	if ($download_id != -1) {
		$row = get_attachment($download_id);
		if ($row['filename'] != "") {
			if (Ajax::in_ajax()) {
				$Ajax->redirect($_SERVER['PHP_SELF'] . '?dl=' . $download_id);
			} else {
				$type = ($row['filetype']) ? $row['filetype'] : 'application/octet-stream';
				header("Content-type: " . $type);
				header('Content-Length: ' . $row['filesize']);
				header('Content-Disposition: attachment; filename=' . $row['filename']);
				echo file_get_contents(COMPANY_PATH . "/attachments/" . $row['unique_name']);
				exit();
			}
		}
	}

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(800, 500);
	page(_($help_context = "Attach Documents"), false, false, "", $js);

	simple_page_mode(true);
	//----------------------------------------------------------------------------------------
	if (isset($_GET['filterType'])) // catch up external links
		$_POST['filterType'] = $_GET['filterType'];
	if (isset($_GET['trans_no']))
		$_POST['trans_no'] = $_GET['trans_no'];

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		if (isset($_FILES['filename']) && $_FILES['filename']['size'] > 0) {
			//$content = base64_encode(file_get_contents($_FILES['filename']['tmp_name']));
			$tmpname = $_FILES['filename']['tmp_name'];

			$dir = COMPANY_PATH . "/attachments";
			if (!file_exists($dir)) {
				mkdir($dir, 0777);
				$index_file = "<?php\nheader(\"Location: ../index.php\");\n?>";
				$fp = fopen($dir . "/index.php", "w");
				fwrite($fp, $index_file);
				fclose($fp);
			}
			if ($Mode == 'UPDATE_ITEM' && file_exists($dir . "/" . $_POST['unique_name']))
				unlink($dir . "/" . $_POST['unique_name']);

			$unique_name = uniqid('');
			move_uploaded_file($tmpname, $dir . "/" . $unique_name);
			//save the file
			$filename = $_FILES['filename']['name'];
			$filesize = $_FILES['filename']['size'];
			$filetype = $_FILES['filename']['type'];
		}
		else
		{
			$unique_name = $filename = $filetype = "";
			$filesize = 0;
		}
		$date = Dates::date2sql(Dates::Today());
		if ($Mode == 'ADD_ITEM') {
			$sql = "INSERT INTO attachments (type_no, trans_no, description, filename, unique_name,
			filesize, filetype, tran_date) VALUES (" . DBOld::escape($_POST['filterType']) . ","
			 . DBOld::escape($_POST['trans_no']) . "," . DBOld::escape($_POST['description']) . ", "
			 . DBOld::escape($filename) . ", " . DBOld::escape($unique_name) . ", " . DBOld::escape($filesize)
			 . ", " . DBOld::escape($filetype) . ", '$date')";
			DBOld::query($sql, "Attachment could not be inserted");
			ui_msgs::display_notification(_("Attachment has been inserted."));
		}
		else
		{
			$sql = "UPDATE attachments SET
			type_no=" . DBOld::escape($_POST['filterType']) . ",
			trans_no=" . DBOld::escape($_POST['trans_no']) . ",
			description=" . DBOld::escape($_POST['description']) . ", ";
			if ($filename != "") {
				$sql .= "filename=" . DBOld::escape($filename) . ",
			unique_name=" . DBOld::escape($unique_name) . ",
			filesize=" . DBOld::escape($filesize) . ",
			filetype=" . DBOld::escape($filetype);
			}
			$sql .= "tran_date='$date' WHERE id=" . DBOld::escape($selected_id);
			DBOld::query($sql, "Attachment could not be updated");
			ui_msgs::display_notification(_("Attachment has been updated."));
		}
		$Mode = 'RESET';
	}

	if ($Mode == 'Delete') {
		$row = get_attachment($selected_id);
		$dir = COMPANY_PATH . "/attachments";
		if (file_exists($dir . "/" . $row['unique_name']))
			unlink($dir . "/" . $row['unique_name']);
		$sql = "DELETE FROM attachments WHERE id = " . DBOld::escape($selected_id);
		DBOld::query($sql, "Could not delete attachment");
		ui_msgs::display_notification(_("Attachment has been deleted."));
		$Mode = 'RESET';
	}

	if ($Mode == 'RESET') {
		unset($_POST['trans_no']);
		unset($_POST['description']);
		$selected_id = -1;
	}

	function viewing_controls() {
		start_form();

		start_table("class='tablestyle_noborder'");

		systypes_list_row(_("Type:"), 'filterType', null, true);

		end_table(1);

		end_form();
	}

	//----------------------------------------------------------------------------------------

	function get_attached_documents($type) {
		$sql = "SELECT * FROM attachments WHERE type_no=" . DBOld::escape($type)
		 . " ORDER BY trans_no";
		return DBOld::query($sql, "Could not retrieve attachments");
	}

	function get_attachment($id) {
		$sql = "SELECT * FROM attachments WHERE id=" . DBOld::escape($id);
		$result = DBOld::query($sql, "Could not retrieve attachments");
		return DBOld::fetch($result);
	}

	function display_rows($type) {

		$rows = get_attached_documents($type);
		$th = array(_("#"), _("Description"), _("Filename"), _("Size"), _("Filetype"), _("Date Uploaded"), "", "", "", "");

		div_start('transactions');
		start_form();
		start_table(Config::get('tables_style'));
		table_header($th);
		$k = 0;
		while ($row = DBOld::fetch($rows))
		{
			alt_table_row_color($k);

			label_cell(ui_view::get_trans_view_str($type, $row['trans_no']));
			label_cell($row['description']);
			label_cell($row['filename']);
			label_cell($row['filesize']);
			label_cell($row['filetype']);
			label_cell(Dates::sql2date($row['tran_date']));
			edit_button_cell("Edit" . $row['id'], _("Edit"));
			button_cell("view" . $row['id'], _("View"), false, ICON_VIEW);
			button_cell("download" . $row['id'], _("Download"), false, ICON_DOWN);
			delete_button_cell("Delete" . $row['id'], _("Delete"));
			end_row();
		}
		end_table(1);
		hidden('filterType', $type);
		end_form();
		div_end();
	}

	//----------------------------------------------------------------------------------------

	viewing_controls();

	if (isset($_POST['filterType']))
		display_rows($_POST['filterType']);

	start_form(true);

	start_table(Config::get('tables_style2'));

	if ($selected_id != -1) {
		if ($Mode == 'Edit') {
			$row = get_attachment($selected_id);
			$_POST['trans_no'] = $row["trans_no"];
			$_POST['description'] = $row["description"];
			hidden('trans_no', $row['trans_no']);
			hidden('unique_name', $row['unique_name']);
			label_row(_("Transaction #"), $row['trans_no']);
		}
		hidden('selected_id', $selected_id);
	}
	else
		text_row_ex(_("Transaction #") . ':', 'trans_no', 10);
	text_row_ex(_("Description") . ':', 'description', 40);
	file_row(_("Attached File") . ":", 'filename', 'filename');

	end_table(1);
	if (isset($_POST['filterType']))
		hidden('filterType', $_POST['filterType']);

	submit_add_or_update_center($selected_id == -1, '', 'both');

	end_form();

	end_page();

?>
