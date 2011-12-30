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
	$page_security = 'SA_MANUFRELEASE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "Work Order Release to Manufacturing"));
	if (isset($_GET["trans_no"])) {
		$selected_id = $_GET["trans_no"];
	}
	elseif (isset($_POST["selected_id"])) {
		$selected_id = $_POST["selected_id"];
	}
	else {
		Errors::warning("This page must be called with a work order reference");
		exit;
	}
	function can_process($myrow) {
		if ($myrow['released']) {
			Errors::error(_("This work order has already been released."));
			JS::set_focus('released');
			return false;
		}
		// make sure item has components
		if (!WO::has_bom($myrow['stock_id'])) {
			Errors::error(_("This Work Order cannot be released. The selected item to manufacture does not have a bom."));
			JS::set_focus('stock_id');
			return false;
		}
		return true;
	}

	if (isset($_POST['release'])) {
		WO::release($selected_id, $_POST['released_date'], $_POST['memo_']);
		Errors::notice(_("The work order has been released to manufacturing."));
		Display::note(GL_UI::trans_view(ST_WORKORDER, $selected_id, _("View this Work Order")));
		Display::link_no_params("search_work_orders.php", _("Select another &work order"));
		Ajax::i()->activate('_page_body');
		Page::end();
		exit;
	}
	start_form();
	$myrow = WO::get($selected_id);
	$_POST['released'] = $myrow["released"];
	$_POST['memo_'] = "";
	if (can_process($myrow)) {
		start_table('tablestyle2');
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
	Page::end();

?>