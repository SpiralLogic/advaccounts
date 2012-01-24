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

	JS::open_window(900, 500);
	JS::footerFile('/js/allocate.js');
Page::start(_($help_context = "Allocate Supplier Payment or Credit Note"), SA_SUPPLIERALLOC);

	if (isset($_POST['Process'])) {
		if (Gl_Allocation::check()) {
			$_SESSION['alloc']->write();
			clear_allocations();
			$_POST['Cancel'] = 1;
		}
	}
	if (isset($_POST['Cancel'])) {
		clear_allocations();
		Display::meta_forward("/purchases/allocations/supplier_allocation_main.php");
	}
	if (isset($_GET['trans_no']) && isset($_GET['trans_type'])) {
		$_SESSION['alloc'] = new Gl_Allocation($_GET['trans_type'], $_GET['trans_no']);
	}
	if (get_post('UpdateDisplay')) {
		$_SESSION['alloc']->read();
		Ajax::i()->activate('alloc_tbl');
	}
	if (isset($_SESSION['alloc'])) {
		edit_allocations_for_transaction($_SESSION['alloc']->type, $_SESSION['alloc']->trans_no);
	}
	Page::end();
	function clear_allocations() {
			if (isset($_SESSION['alloc'])) {
				unset($_SESSION['alloc']->allocs);
				unset($_SESSION['alloc']);
			}
			//session_register("alloc");
		}

		function edit_allocations_for_transaction($type, $trans_no) {
			global $systypes_array;
			start_form();
			Display::heading(_("Allocation of") . " " . $systypes_array[$_SESSION['alloc']->type] . " # " . $_SESSION['alloc']->trans_no);
			Display::heading($_SESSION['alloc']->person_name);
			Display::heading(_("Date:") . " <span class='bold'>" . $_SESSION['alloc']->date_ . "</span>");
			Display::heading(_("Total:") . " <span class='bold'>" . Num::price_format(-$_SESSION['alloc']->amount) . "</span>");
			echo "<br>";
			Display::div_start('alloc_tbl');
			if (count($_SESSION['alloc']->allocs) > 0) {
				Gl_Allocation::show_allocatable(true);
				submit_center_first('UpdateDisplay', _("Refresh"), _('Start again allocation of selected amount'), true);
				submit('Process', _("Process"), true, _('Process allocations'), 'default');
				submit_center_last('Cancel', _("Back to Allocations"), _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
			}
			else {
				Event::warning(_("There are no unsettled transactions to allocate."), 0, 1);
				submit_center('Cancel', _("Back to Allocations"), true, _('Abandon allocations and return to selection of allocatable amounts'), 'cancel');
			}
			Display::div_end();
			end_form();
		}

?>
