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
	class Bank_UI
	{
		public static function header($order) {
			$Ajax = Ajax::i();
			$payment = $order->trans_type == ST_BANKPAYMENT;
			Display::div_start('pmt_header');
			Display::start_outer_table("width=90% " . Config::get('tables_style2')); // outer table
			Display::table_section(1);
			Bank_UI::accounts_list_row($payment ? _("From:") : _("To:"), 'bank_account', null, true);
			date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
			Display::table_section(2, "33%");
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
					Debtor_UI::select_row(_("Customer:"), 'person_id', null, false, true, false, true);
					if (Input::post('person_id') && Validation::check(Validation::BRANCHES, _("No Branches for Customer"),
						$_POST['person_id'])
					) {
						Debtor_UI::branches_list_row(_("Branch:"), $_POST['person_id'], 'PersonDetailID', null, false, true, true, true);
					} else {
						$_POST['PersonDetailID'] = ANY_NUMERIC;
						hidden('PersonDetailID');
					}
					break;
				case PT_QUICKENTRY :
					quick_entries_list_row(_("Type") . ":", 'person_id', null, ($payment ? QE_PAYMENT : QE_DEPOSIT), true);
					$qid = GL_QuickEntry::get(Display::get_post('person_id'));
					if (list_updated('person_id')) {
						unset($_POST['totamount']); // enable default
						$Ajax->activate('totamount');
					}
					amount_row($qid['base_desc'] . ":", 'totamount', Num::price_format($qid['base_amount']), null,
					 "&nbsp;&nbsp;" . submit('go', _("Go"), false, false, true));
					break;
				//case payment_person_types::Project() :
				//	Dimensions::select_row(_("Dimension:"), 'person_id', $_POST['person_id'], false, null, true);
				//	break;
			}
			$person_currency = Banking::payment_person_currency($_POST['PayType'], $_POST['person_id']);
			$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
			GL_ExchangeRate::display($bank_currency, $person_currency, $_POST['date_']);
			Display::table_section(3, "33%");
			if (isset($_GET['NewPayment'])) {
				ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKPAYMENT));
			} else {
				ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKDEPOSIT));
			}
			Display::end_outer_table(1); // outer table
			Display::div_end();
		}

		public static function items($title, &$order) {
			$dim = DB_Company::get_pref('use_dimension');
			$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
			Display::heading($title);
			Display::div_start('items_table');
			Display::start_table(Config::get('tables_style') . " colspan=7 width=95%");
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
			Display::table_header($th);
			$k = 0; //row colour counter
			$id = find_submit('Edit');
			foreach ($order->gl_items as $line => $item) {
				if ($id != $line) {
					Display::alt_table_row_color($k);
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
					Display::end_row();
				} else {
					Bank_UI::item_controls($order, $dim, $line);
				}
			}
			if ($id == -1) {
				Bank_UI::item_controls($order, $dim);
			}
			if ($order->count_gl_items()) {
				label_row(_("Total"), Num::format(abs($order->gl_items_total()), User::price_dec()),
				 "colspan=" . $colspan . " class=right", "class=right", 3);
			}
			Display::end_table();
			Display::div_end();
		}

		public static function item_controls($order, $dim, $Index = null) {
			$Ajax = Ajax::i();
			$payment = $order->trans_type == ST_BANKPAYMENT;
			Display::start_row();
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
				echo GL_UI::all('code_id', null, true, true);
				if ($dim >= 1) {
					Dimensions::select_cells(null, 'dimension_id', null, true, " ", false, 1);
				}
				if ($dim > 1) {
					Dimensions::select_cells(null, 'dimension2_id', null, true, " ", false, 2);
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
				echo GL_UI::all('code_id', null, true, true);
				if ($dim >= 1) {
					Dimensions::select_cells(null, 'dimension_id', null, true, " ", false, 1);
				}
				if ($dim > 1) {
					Dimensions::select_cells(null, 'dimension2_id', null, true, " ", false, 2);
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
			Display::end_row();
		}

		public static function option_controls() {
			echo "<br><table class='center'>";
			textarea_row(_("Memo"), 'memo_', null, 50, 3);
			echo "</table>";
		}

		public static function	accounts($name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
										FROM bank_accounts";
			return combo_input($name, $selected_id, $sql, 'id', 'bank_account_name', array(
																																										'format' => '_format_add_curr', 'select_submit' => $submit_on_change, 'async' => false));
		}

		public static function	accounts_cells($label, $name, $selected_id = null, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Bank_UI::accounts($name, $selected_id, $submit_on_change);
			echo "</td>\n";
		}

		public static function	accounts_select_row($label, $name, $selected_id = null, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			Bank_UI::accounts_cells(null, $name, $selected_id, $submit_on_change);
			echo "</tr>\n";
		}

		public static function	accounts_type($name, $selected_id = null) {
			return array_selector($name, $selected_id, unserialize(TYPE_BANK_ACCOUNTS));
		}

		public static function	accounts_type_cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Bank_UI::accounts_type($name, $selected_id);
			echo "</td>\n";
		}

		public static function	accounts_type_row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			Bank_UI::accounts_type_cells(null, $name, $selected_id);
			echo "</tr>\n";
		}

		public static function	reconcile($account, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
			$sql = "SELECT reconciled, reconciled FROM bank_trans
							WHERE bank_act=" . DB::escape($account) . " AND reconciled IS NOT NULL
							GROUP BY reconciled";
			return combo_input($name, $selected_id, $sql, 'id', 'reconciled', array(
																																						 'spec_option' => $special_option, 'format' => '_format_date', 'spec_id' => '', 'select_submit' => $submit_on_change, 'order' => 'reconciled DESC'));
		}

		public static function	reconcile_cells($label, $account, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Bank_UI::reconcile($account, $name, $selected_id, $submit_on_change, $special_option);
			echo "</td>\n";
		}

		public static function	balance_row($bank_acc, $parms = '') {
			$to = Dates::add_days(Dates::Today(), 1);
			$bal = get_balance_before_for_bank_account($bank_acc, $to);
			label_row(_("Bank Balance:"), "<a target='_blank' " . ($bal < 0 ? 'class="redfg openWindow"' :
			 '') . "href='/gl/inquiry/bank_inquiry.php?bank_account=" . $bank_acc . "'" . " >&nbsp;" . Num::price_format($bal) . "</a>", $parms);
		}

		public static function	cash_accounts_row($label, $name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
						FROM bank_accounts
						WHERE bank_accounts.account_type=3";
			if ($label != null) {
				echo "<tr><td class='label'>$label</td>\n";
			}
			echo "<td>";
			echo combo_input($name, $selected_id, $sql, 'id', 'bank_account_name', array(
																																									'format' => '_format_add_curr', 'select_submit' => $submit_on_change, 'async' => true));
			echo "</td></tr>\n";
		}

		public static function	trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($label == "") {
				$label = $trans_no;
			}
			switch ($type) {
				case ST_BANKTRANSFER:
					$viewer = "bank_transfer_view.php";
					break;
				case ST_BANKPAYMENT:
					$viewer = "gl_payment_view.php";
					break;
				case ST_BANKDEPOSIT:
					$viewer = "gl_deposit_view.php";
					break;
				default:
					return null;
			}
			return Display::viewer_link($label, "gl/view/$viewer?trans_no=$trans_no", $class, $id, $icon);
		}
	}

?>