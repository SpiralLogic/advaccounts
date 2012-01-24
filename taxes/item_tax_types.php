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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Page::start(_($help_context = "Item Tax Types"), SA_ITEMTAXTYPE);
	list($Mode, $selected_id) = Page::simple_mode(true);
	if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
		$input_error = 0;
		if (strlen($_POST['name']) == 0) {
			$input_error = 1;
			Event::error(_("The item tax type description cannot be empty."));
			JS::set_focus('name');
		}
		if ($input_error != 1) {
			// create an array of the exemptions
			$exempt_from = array();
			$tax_types = Tax_Types::get_all_simple();
			$i = 0;
			while ($myrow = DB::fetch($tax_types)) {
				if (check_value('ExemptTax' . $myrow["id"])) {
					$exempt_from[$i] = $myrow["id"];
					$i++;
				}
			}
			if ($selected_id != -1) {
				Tax_ItemType::update($selected_id, $_POST['name'], $_POST['exempt'], $exempt_from);
				Event::notice(_('Selected item tax type has been updated'));
			}
			else {
				Tax_ItemType::add($_POST['name'], $_POST['exempt'], $exempt_from);
				Event::notice(_('New item tax type has been added'));
			}
			$Mode = MODE_RESET;
		}
	}
	if ($Mode == MODE_DELETE) {
		if (can_delete($selected_id)) {
			delete($selected_id);
			Event::notice(_('Selected item tax type has been deleted'));
		}
		$Mode = MODE_RESET;
	}
	if ($Mode == MODE_RESET) {
		$selected_id = -1;
		$sav = get_post('show_inactive');
		unset($_POST);
		$_POST['show_inactive'] = $sav;
	}
	$result2 = $result = Tax_ItemType::get_all(check_value('show_inactive'));
	start_form();
	start_table('tablestyle width30');
	$th = array(_("Name"), _("Tax exempt"), '', '');
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	while ($myrow = DB::fetch($result2)) {
		alt_table_row_color($k);
		if ($myrow["exempt"] == 0) {
			$disallow_text = _("No");
		}
		else {
			$disallow_text = _("Yes");
		}
		label_cell($myrow["name"]);
		label_cell($disallow_text);
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'item_tax_types', 'id');
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);
	start_table('tablestyle2');
	if ($selected_id != -1) {
		if ($Mode == MODE_EDIT) {
			$myrow = Tax_ItemType::get($selected_id);
			unset($_POST); // clear exemption checkboxes
			$_POST['name'] = $myrow["name"];
			$_POST['exempt'] = $myrow["exempt"];
			// read the exemptions and check the ones that are on
			$exemptions = Tax_ItemType::get_exemptions($selected_id);
			if (DB::num_rows($exemptions) > 0) {
				while ($exmp = DB::fetch($exemptions)) {
					$_POST['ExemptTax' . $exmp["tax_type_id"]] = 1;
				}
			}
		}
		hidden('selected_id', $selected_id);
	}
	text_row_ex(_("Description:"), 'name', 50);
	yesno_list_row(_("Is Fully Tax-exempt:"), 'exempt', null, "", "", true);
	end_table(1);
	if (!isset($_POST['exempt']) || $_POST['exempt'] == 0) {
		Event::warning(_("Select which taxes this item tax type is exempt from."), 0, 1);
		start_table('tablestyle2');
		$th = array(_("Tax Name"), _("Rate"), _("Is exempt"));
		table_header($th);
		$tax_types = Tax_Types::get_all_simple();
		while ($myrow = DB::fetch($tax_types)) {
			alt_table_row_color($k);
			label_cell($myrow["name"]);
			label_cell(Num::percent_format($myrow["rate"]) . " %", ' class="right nowrap"');
			check_cells("", 'ExemptTax' . $myrow["id"], null);
			end_row();
		}
		end_table(1);
	}
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	Page::end();
	function can_delete($selected_id) {
		$sql = "SELECT COUNT(*) FROM stock_master WHERE tax_type_id=" . DB::escape($selected_id);
		$result = DB::query($sql, "could not query stock master");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			Event::error(_("Cannot delete this item tax type because items have been created referring to it."));
			return false;
		}
		return true;
	}

?>
