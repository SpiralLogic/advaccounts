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

	class GL_Journal {
		static public function header($order) {

			$qes = GL_QuickEntry::has(QE_JOURNAL);
			$new = $order->order_id == 0;
			start_outer_table('tablestyle2 width90');
			table_section(1);
			start_row();
			date_cells(_("Date:"), 'date_', '', $new);
			table_section(2, $qes ? "20%" : "50%");
			ref_cells(_("Reference:"), 'ref', '');
			hidden('ref_original');
			end_row();
			if ($new) {
				table_section(3, "20%");
				start_row();
				check_cells(_("Reverse Transaction:"), 'Reverse', null);
				end_row();
			}
			if ($qes !== false) {
				table_section(3, "50%");
				start_row();
				GL_QuickEntry::cells(_("Quick Entry") . ":", 'person_id', null, QE_JOURNAL, true);
				$qid = GL_QuickEntry::get(get_post('person_id'));
				if (list_updated('person_id')) {
					unset($_POST['total_amount']); // enable default
					Ajax::i()->activate('total_amount');
				}
				amount_cells($qid['base_desc'] . ":", 'total_amount', Num::price_format($qid['base_amount']), null,
				 "&nbsp;&nbsp;" . submit('go', _("Go"), false, false, true));
				end_row();
			}
			end_outer_table(1);
		}
/***
 * @static
 * @param $title
 * @param Item_Order $order
 */

		static public function items($title, &$order) {
			Display::heading($title);
			$dim = DB_Company::get_pref('use_dimension');
			Display::div_start('items_table');
			start_table('tablestyle width95');
			if ($dim == 2) {
				$th = array(
					_("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Debit"), _("Credit"), _("Memo"), "");
			} else if ($dim == 1) {
				$th = array(
					_("Account Code"), _("Account Description"), _("Dimension"), _("Debit"), _("Credit"), _("Memo"), "");
			} else {
				$th = array(
					_("Account Code"), _("Account Description"), _("Debit"), _("Credit"), _("Memo"), "");
			}
			if (count($order->gl_items)) {
				$th[] = '';
			}
			table_header($th);
			$k = 0;
			$id = find_submit(MODE_EDIT);
			foreach ($order->gl_items as $line => $item) {
				if ($id != $line) {
					alt_table_row_color($k);
					label_cells($item->code_id, $item->description);
					if ($dim >= 1) {
						label_cell(Dimensions::get_string($item->dimension_id, true));
					}
					if ($dim > 1) {
						label_cell(Dimensions::get_string($item->dimension2_id, true));
					}
					if ($item->amount > 0) {
						amount_cell(abs($item->amount));
						label_cell("");
					} else {
						label_cell("");
						amount_cell(abs($item->amount));
					}
					label_cell($item->reference);
					edit_button_cell("Edit$line", _("Edit"), _('Edit journal line'));
					delete_button_cell("Delete$line", _("Delete"), _('Remove line from journal'));
					end_row();
				} else {
					GL_Journal::item_controls($order, $dim, $line);
				}
			}
			if ($id == -1) {
				GL_Journal::item_controls($order, $dim);
			}
			if ($order->count_gl_items()) {
				$colspan = ($dim == 2 ? "4" : ($dim == 1 ? "3" : "2"));
				start_row();
				label_cell(_("Total"), "class=right colspan=" . $colspan);
				amount_cell($order->gl_items_total_debit());
				amount_cell(abs($order->gl_items_total_credit()));
				label_cell('', "colspan=3");
				end_row();
			}
			end_table();
			Display::div_end();
		}


		static public function item_controls($order, $dim, $Index = null) {

			start_row();
			$id = find_submit(MODE_EDIT);
			if ($Index != -1 && $Index == $id) {
				// Modifying an existing row
				$item = $order->gl_items[$Index];
				$_POST['code_id'] = $item->code_id;
				$_POST['dimension_id'] = $item->dimension_id;
				$_POST['dimension2_id'] = $item->dimension2_id;
				if ($item->amount > 0) {
					$_POST['AmountDebit'] = Num::price_format($item->amount);
					$_POST['AmountCredit'] = "";
				} else {
					$_POST['AmountDebit'] = "";
					$_POST['AmountCredit'] = Num::price_format(abs($item->amount));
				}
				$_POST['description'] = $item->description;
				$_POST['LineMemo'] = $item->reference;
				hidden('Index', $id);
				$skip_bank = !User::i()->can_access(SA_BANKJOURNAL);
				echo GL_UI::all('code_id', null, $skip_bank, true);
				if ($dim >= 1) {
					Dimensions::cells(null, 'dimension_id', null, true, " ", false, 1);
				}
				if ($dim > 1) {
					Dimensions::cells(null, 'dimension2_id', null, true, " ", false, 2);
				}
				Ajax::i()->activate('items_table');
			} else {
				// Adding a new row
				$_POST['AmountDebit'] = ''; //Num::price_format(0);
				$_POST['AmountCredit'] = ''; //Num::price_format(0);
				$_POST['dimension_id'] = 0;
				$_POST['dimension2_id'] = 0;
				//$_POST['LineMemo'] = ""; // let memo go to next line Joe Hunt 2010-05-30
				$_POST['_code_id_edit'] = "";
				$_POST['code_id'] = "";
				if (isset($_POST['_code_id_update'])) {
					Ajax::i()->activate('code_id');
				}
				$skip_bank = !User::i()->can_access(SA_BANKJOURNAL);
				echo GL_UI::all('code_id', null, $skip_bank, true);
				if ($dim >= 1) {
					Dimensions::cells(null, 'dimension_id', null, true, " ", false, 1);
				}
				if ($dim > 1) {
					Dimensions::cells(null, 'dimension2_id', null, true, " ", false, 2);
				}
			}
			if ($dim < 1) {
				hidden('dimension_id', 0);
			}
			if ($dim < 2) {
				hidden('dimension2_id', 0);
			}
			small_amount_cells(null, 'AmountDebit');
			small_amount_cells(null, 'AmountCredit');
			text_cells_ex(null, 'LineMemo', 35, 255);
			if ($id != -1) {
				button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
				button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
				JS::set_focus('amount');
			} else {
				submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new line to journal'), true);
			}
			end_row();
		}


		static public function option_controls() {
			echo "<br><table class='center'>";
			textarea_row(_("Memo"), 'memo_', null, 50, 3);
			echo "</table>";
		}

		static public function	view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($type == ST_JOURNAL || $type == ST_COSTUPDATE) {
				$viewer = "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no";
			} else {
				return null;
			}
			if ($label == "") {
				$label = $trans_no;
			}
			return Display::viewer_link($label, $viewer, $class, $id, $icon);
		}

		static public function	cells($label, $name, $value = null, $submit_on_change = false) {
			global $systypes_array;
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			$items = $systypes_array;
			// exclude quotes, orders and dimensions
			foreach (array(ST_PURCHORDER, ST_WORKORDER, ST_SALESORDER, ST_DIMENSION, ST_SALESQUOTE) as $excl) {
				unset($items[$excl]);
			}
			echo array_selector($name, $value, $items, array(
				'spec_option' => _("All"), 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => false));
			echo "</td>\n";
		}

		// Write/update journal entries.
				//
				static public function write($order, $reverse, $use_transaction = true)
				{
					$date_ = $order->tran_date;
					$ref = $order->reference;
					$memo_ = $order->memo_;
					$trans_type = $order->trans_type;
					$new = $order->order_id == 0;
					if ($new) {
						$order->order_id = SysTypes::get_next_trans_no($trans_type);
					}
					$trans_id = $order->order_id;
					if ($use_transaction) {
						DB::begin();
					}
					if (!$new) {
						static::void($trans_type, $trans_id, false);
					}
					foreach ($order->gl_items as $journal_item) {
						// post to first found bank account using given gl acount code.
						$is_bank_to = Bank_Account::is($journal_item->code_id);
						GL_Trans::add($trans_type, $trans_id, $date_, $journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, $journal_item->amount);
						if ($is_bank_to) {
							Bank_Trans::add($trans_type, $trans_id, $is_bank_to, $ref, $date_, $journal_item->amount, 0, "", Bank_Currency::for_company(), "Cannot insert a destination bank transaction");
						}
						// store tax details if the gl account is a tax account
						GL_Trans::add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $date_, $memo_);
					}
					if ($new) {
						DB_Comments::add($trans_type, $trans_id, $date_, $memo_);
						Ref::save($trans_type, $ref);
					} else {
						DB_Comments::update($trans_type, $trans_id, null, $memo_);
						Ref::update($trans_type, $trans_id, $ref);
					}
					DB_AuditTrail::add($trans_type, $trans_id, $date_);
					if ($reverse) {
						//$reversingDate = date(User::date_display(),
						//	Mktime(0,0,0,get_month($date_)+1,1,get_year($date_)));
						$reversingDate = Dates::begin_month(Dates::add_months($date_, 1));
						$trans_id_reverse = SysTypes::get_next_trans_no($trans_type);
						foreach ($order->gl_items as $journal_item) {
							$is_bank_to = Bank_Account::is($journal_item->code_id);
							GL_Trans::add($trans_type, $trans_id_reverse, $reversingDate, $journal_item->code_id, $journal_item->dimension_id, $journal_item->dimension2_id, $journal_item->reference, -$journal_item->amount);
							if ($is_bank_to) {
								Bank_Trans::add($trans_type, $trans_id_reverse, $is_bank_to, $ref, $reversingDate, -$journal_item->amount, 0, "", Bank_Currency::for_company(), "Cannot insert a destination bank transaction");
							}
							// store tax details if the gl account is a tax account
							GL_Trans::add_gl_tax_details($journal_item->code_id, ST_JOURNAL, $trans_id, $journal_item->amount, 1, $reversingDate, $memo_);
						}
						DB_Comments::add($trans_type, $trans_id_reverse, $reversingDate, $memo_);
						Ref::save($trans_type, $ref);
						DB_AuditTrail::add($trans_type, $trans_id_reverse, $reversingDate);
					}
					if ($use_transaction) {
						DB::commit();
					}
					return $trans_id;
				}

				static public function void($type, $type_no, $use_transaction = true)
				{
					if ($use_transaction) {
						DB::begin();
					}
					Bank_Trans::void($type, $type_no, true);
					//	static::void($type, $type_no, true);	 // this is done above
					//	static::void_tax_details($type, $type_no); // ditto
					if ($use_transaction) {
						DB::commit();
					}
				}


	}

?>
