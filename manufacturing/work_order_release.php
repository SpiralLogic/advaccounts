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
	$page_security = 'SA_MANUFRELEASE';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "manufacturing/includes/manufacturing_ui.php");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(800, 500);

	page(_($help_context = "Work Order Release to Manufacturing"), false, false, "", $js);

	if (isset($_GET["trans_no"])) {
		$selected_id = $_GET["trans_no"];
	}
	elseif (isset($_POST["selected_id"]))
	{
		$selected_id = $_POST["selected_id"];
	}
	else
	{
		ui_msgs::display_note("This page must be called with a work order reference");
		exit;
	}

	//------------------------------------------------------------------------------------

	function can_process($myrow) {
		if ($myrow['released']) {
			ui_msgs::display_error(_("This work order has already been released."));
			ui_view::set_focus('released');
			return false;
		}

		// make sure item has components
		if (!has_bom($myrow['stock_id'])) {
			ui_msgs::display_error(_("This Work Order cannot be released. The selected item to manufacture does not have a bom."));
			ui_view::set_focus('stock_id');
			return false;
		}

		return true;
	}

	//------------------------------------------------------------------------------------
	if (isset($_POST['release'])) {
		release_work_order($selected_id, $_POST['released_date'], $_POST['memo_']);

		ui_msgs::display_notification(_("The work order has been released to manufacturing."));

		ui_msgs::display_note(ui_view::get_trans_view_str(ST_WORKORDER, $selected_id, _("View this Work Order")));

		hyperlink_no_params("search_work_orders.php", _("Select another &work order"));

		$Ajax->activate('_page_body');
		end_page();
		exit;
	}

	//------------------------------------------------------------------------------------

	start_form();

	$myrow = get_work_order($selected_id);

	$_POST['released'] = $myrow["released"];
	$_POST['memo_'] = "";

	if (can_process($myrow)) {
		start_table(Config::get('tables.style2'));

		label_row(_("Work Order #:"), $selected_id);
		label_row(_("Work Order Reference:"), $myrow["wo_ref"]);

		date_row(_("Released Date") . ":", 'released_date');

		textarea_row(_("Memo:"), 'memo_', $_POST['memo_'], 40, 5);

		end_table(1);

		submit_center('release', _("Release Work Order"), true, '', 'default');

		hidden('selected_id', $selected_id);
		hidden('stock_id', $myrow['stock_id']);
	}

	end_form();

	end_page();

?>