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
	class GL_BankUI
	{
		public static function header($order)
			{
				$Ajax = Ajax::i();
				$payment = $order->trans_type == ST_BANKPAYMENT;
				div_start('pmt_header');
				start_outer_table("width=90% " . Config::get('tables_style2')); // outer table
				table_section(1);
				bank_accounts_list_row($payment ? _("From:") : _("To:"), 'bank_account', null, true);
				date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
				table_section(2, "33%");
				if (!isset($_POST['PayType'])) {
					if (isset($_GET['PayType'])) {
						$_POST['PayType'] = $_GET['PayType'];
					} else {
						$_POST['PayType'] = "";
					}
				}
				if (!isset($_POST['person_id'])) {
					if (isset($_GET['PayPerson'])) {
						$_POST['person_id'] = $_GET['PayPerson'];
					} else {
						$_POST['person_id'] = "";
					}
				}
				if (isset($_POST['_PayType_update'])) {
					$_POST['person_id'] = '';
					$Ajax->activate('pmt_header');
					$Ajax->activate('code_id');
					$Ajax->activate('pagehelp');
					$Ajax->activate('editors');
				}
				payment_person_types_list_row($payment ? _("Pay To:") : _("From:"), 'PayType', $_POST['PayType'], true);
				switch ($_POST['PayType']) {
					case PT_MISC :
						text_row_ex($payment ? _("To the Order of:") : _("Name:"), 'person_id', 40, 50);
						break;
					//case PT_WORKORDER :
					//	workorders_list_row(_("Work Order:"), 'person_id', null);
					//	break;
					case PT_SUPPLIER :
						supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
						break;
					case PT_CUSTOMER :
						customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);
						if (Input::post('person_id') && Validation::check(Validation::BRANCHES, _("No Branches for Customer"),
							$_POST['person_id'])
						) {
							customer_branches_list_row(_("Branch:"), $_POST['person_id'], 'PersonDetailID', null, false, true, true, true);
						} else {
							$_POST['PersonDetailID'] = ANY_NUMERIC;
							hidden('PersonDetailID');
						}
						break;
					case PT_QUICKENTRY :
						quick_entries_list_row(_("Type") . ":", 'person_id', null, ($payment ? QE_PAYMENT : QE_DEPOSIT), true);
						$qid = GL_QuickEntry::get(get_post('person_id'));
						if (list_updated('person_id')) {
							unset($_POST['totamount']); // enable default
							$Ajax->activate('totamount');
						}
						amount_row($qid['base_desc'] . ":", 'totamount', Num::price_format($qid['base_amount']), null,
						 "&nbsp;&nbsp;" . submit('go', _("Go"), false, false, true));
						break;
					//case payment_person_types::Project() :
					//	dimensions_list_row(_("Dimension:"), 'person_id', $_POST['person_id'], false, null, true);
					//	break;
				}
				$person_currency = Banking::payment_person_currency($_POST['PayType'], $_POST['person_id']);
				$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
				GL_ExchangeRate::display($bank_currency, $person_currency, $_POST['date_']);
				table_section(3, "33%");
				if (isset($_GET['NewPayment'])) {
					ref_row(_("Reference:"), 'ref', '', Refs::get_next(ST_BANKPAYMENT));
				} else {
					ref_row(_("Reference:"), 'ref', '', Refs::get_next(ST_BANKDEPOSIT));
				}
				end_outer_table(1); // outer table
				div_end();
			}


		public static function items($title, &$order)
			{
				$dim = DB_Company::get_pref('use_dimension');
				$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
				Display::heading($title);
				div_start('items_table');
				start_table(Config::get('tables_style') . " colspan=7 width=95%");
				if ($dim == 2) {
					$th = array(
						_("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Amount"), _("Memo"), "");
				} else if ($dim == 1) {
					$th = array(
						_("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo"), "");
				} else {
					$th = array(
						_("Account Code"), _("Account Description"), _("Amount"), _("Memo"), "");
				}
				if (count($order->gl_items)) {
					$th[] = '';
				}
				table_header($th);
				$k = 0; //row colour counter
				$id = find_submit('Edit');
				foreach ($order->gl_items as $line => $item) {
					if ($id != $line) {
						alt_table_row_color($k);
						label_cell($item->code_id);
						label_cell($item->description);
						if ($dim >= 1) {
							label_cell(Dimensions::get_string($item->dimension_id, true));
						}
						if ($dim > 1) {
							label_cell(Dimensions::get_string($item->dimension2_id, true));
						}
						//amount_cell(abs($item->amount));
						if ($order->trans_type == ST_BANKDEPOSIT) {
							amount_cell(-$item->amount);
						} else {
							amount_cell($item->amount);
						}
						label_cell($item->reference);
						edit_button_cell("Edit$line", _("Edit"), _('Edit document line'));
						delete_button_cell("Delete$line", _("Delete"), _('Remove line from document'));
						end_row();
					} else {
						GL_BankUI::item_controls($order, $dim, $line);
					}
				}
				if ($id == -1) {
					GL_BankUI::item_controls($order, $dim);
				}
				if ($order->count_gl_items()) {
					label_row(_("Total"), Num::format(abs($order->gl_items_total()), User::price_dec()),
					 "colspan=" . $colspan . " align=right", "align=right", 3);
				}
				end_table();
				div_end();
			}


		public static function item_controls($order, $dim, $Index = null)
			{
				$Ajax = Ajax::i();
				$payment = $order->trans_type == ST_BANKPAYMENT;
				start_row();
				$id = find_submit('Edit');
				if ($Index != -1 && $Index == $id) {
					$item = $order->gl_items[$Index];
					$_POST['code_id'] = $item->code_id;
					$_POST['dimension_id'] = $item->dimension_id;
					$_POST['dimension2_id'] = $item->dimension2_id;
					$_POST['amount'] = Num::price_format(abs($item->amount));
					$_POST['description'] = $item->description;
					$_POST['LineMemo'] = $item->reference;
					hidden('Index', $id);
					echo gl_all_accounts_list('code_id', null, true, true);
					if ($dim >= 1) {
						dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
					}
					if ($dim > 1) {
						dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
					}
					$Ajax->activate('items_table');
				} else {
					$_POST['amount'] = Num::price_format(0);
					$_POST['dimension_id'] = 0;
					$_POST['dimension2_id'] = 0;
					//$_POST['LineMemo'] = ""; // let memo go to next line Joe Hunt 2010-05-30
					if (isset($_POST['_code_id_update'])) {
						$Ajax->activate('code_id');
					}
					if ($_POST['PayType'] == PT_CUSTOMER) {
						$acc = Sales_Branch::get_accounts($_POST['PersonDetailID']);
						$_POST['code_id'] = $acc['receivables_account'];
					} elseif ($_POST['PayType'] == PT_SUPPLIER) {
						$acc = Purch_Creditor::get_accounts_name($_POST['person_id']);
						$_POST['code_id'] = $acc['payable_account'];
					} //elseif ($_POST['PayType'] == PT_WORKORDER)
						//	$_POST['code_id'] = DB_Company::get_pref('default_assembly_act');
					else {
						$_POST['code_id'] = DB_Company::get_pref($payment ? 'default_cogs_act' : 'default_inv_sales_act');
					}
					echo gl_all_accounts_list('code_id', null, true, true);
					if ($dim >= 1) {
						dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
					}
					if ($dim > 1) {
						dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
					}
				}
				if ($dim < 1) {
					hidden('dimension_id', 0);
				}
				if ($dim < 2) {
					hidden('dimension2_id', 0);
				}
				amount_cells(null, 'amount');
				text_cells_ex(null, 'LineMemo', 35, 255);
				if ($id != -1) {
					button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
					button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
					JS::set_focus('amount');
				} else {
					submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);
				}
				end_row();
			}


		public static function option_controls()
			{
				echo "<br><table align='center'>";
				textarea_row(_("Memo"), 'memo_', null, 50, 3);
				echo "</table>";
			}

	}

?>