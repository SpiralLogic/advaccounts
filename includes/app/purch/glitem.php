<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 1/11/11
	 * Time: 7:05 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Purch_GLItem
	{
		/* Contains relavent information from the purch_order_details as well to provide in cached form,
					all the info to do the necessary entries without looking up ie additional queries of the database again */
		public $id;
		public $po_detail_item;
		public $item_code;
		public $description;
		public $qty_recd;
		public $prev_quantity_inv;
		public $this_quantity_inv;
		public $order_price;
		public $chg_price;
		public $exp_price;
		public $discount;
		public $Complete;
		public $std_cost_unit;
		public $gl_code;
		public $freight;

		public		function __construct($id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv,
			$order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount = 0, $exp_price = null)
			{
				$this->id = $id;
				$this->po_detail_item = $po_detail_item;
				$this->item_code = $item_code;
				$this->description = $description;
				$this->qty_recd = $qty_recd;
				$this->prev_quantity_inv = $prev_quantity_inv;
				$this->this_quantity_inv = $this_quantity_inv;
				$this->order_price = $order_price;
				$this->chg_price = $chg_price;
				$this->exp_price = ($exp_price == null) ? $chg_price : $exp_price;
				$this->discount = $discount;
				$this->Complete = $Complete;
				$this->std_cost_unit = $std_cost_unit;
				$this->gl_code = $gl_code;
			}

		public	function setFreight($freight)
			{
				$this->freight = $freight;
			}

		public	function full_charge_price($tax_group_id, $tax_group = null)
			{
				return Tax::full_price_for_item($this->item_code, $this->chg_price * (1 - $this->discount), $tax_group_id, 0,
					$tax_group);
			}

		public	function taxfree_charge_price($tax_group_id, $tax_group = null)
			{
				//		if ($tax_group_id==null)
				//			return $this->chg_price;
				return Tax::tax_free_price($this->item_code, $this->chg_price * (1 - $this->discount / 100), $tax_group_id,
					0, $tax_group);
			}


		public	static function display_controls($creditor_trans, $k)
			{
				$accs = Creditor::get_accounts_name($creditor_trans->supplier_id);
				$_POST['gl_code'] = $accs['purchase_account'];
				alt_table_row_color($k);
				echo GL_UI::all('gl_code', null, true, true);
				$dim = DB_Company::get_pref('use_dimension');
				if ($dim >= 1) {
					Dimensions::cells(null, 'dimension_id', null, true, " ", false, 1);
					hidden('dimension_id', 0);
				}
				if ($dim > 1) {
					Dimensions::cells(null, 'dimension2_id', null, true, " ", false, 2);
					hidden('dimension2_id', 0);
				}
				textarea_cells(null, 'memo_', null, 50, 1);
				amount_cells(null, 'amount');
				submit_cells('AddGLCodeToTrans', _("Add"), "", _('Add GL Line'), true);
				submit_cells('ClearFields', _("Reset"), "", _("Clear all GL entry fields"), true);
				end_row();
			}

		// $mode = 0 none at the moment
		//		 = 1 display on invoice/credit page
		//		 = 2 display on view invoice
		//		 = 3 display on view credit
		public	static function display_items($creditor_trans, $mode = 0)
			{

				// if displaying in form, and no items, exit
				if (($mode == 2 || $mode == 3) && count($creditor_trans->gl_codes) == 0) {
					return 0;
				}
				if ($creditor_trans->is_invoice) {
					$heading = _("GL Items for this Invoice");
				} else {
					$heading = _("GL Items for this Credit Note");
				}
				start_outer_table('tablestyle2 width90');
				if ($mode == 1) {
					$qes = GL_QuickEntry::has(QE_SUPPINV);
					if ($qes !== false) {
						echo "<div style='float:right;'>";
						echo _("Quick Entry:") . "&nbsp;";
						echo GL_QuickEntry::select('qid', null, QE_SUPPINV, true);
						$qid = GL_QuickEntry::get(get_post('qid'));
						if (list_updated('qid')) {
							unset($_POST['total_amount']); // enable default
							Ajax::i()->activate('total_amount');
						}
						echo "&nbsp;" . $qid['base_desc'] . ":&nbsp;";
						$amount = Validation::input_num('total_amount', $qid['base_amount']);
						$dec = User::price_dec();
						echo "<input class='amount font7' type='text' name='total_amount' maxlength='12' dec='$dec' value='$amount'>&nbsp;";
						submit('go', _("Go"), true, false, true);
						echo "</div>";
					}
				}
				Display::heading($heading);
				end_outer_table(0, false);
				Display::div_start('gl_items');
				start_table('tablestyle width80');
				$dim = DB_Company::get_pref('use_dimension');
				if ($dim == 2) {
					$th = array(_("Account"), _("Name"), _("Dimension") . " 1", _("Dimension") . " 2", _("Memo"), _("Amount"));
				} else {
					if ($dim == 1) {
						$th = array(_("Account"), _("Name"), _("Dimension"), _("Memo"), _("Amount"));
					} else {
						$th = array(_("Account"), _("Name"), _("Memo"), _("Amount"));
					}
				}
				if ($mode == 1) {
					$th[] = "";
					$th[] = "";
				}
				table_header($th);
				$total_gl_value = 0;
				$i = $k = 0;
				if (count($creditor_trans->gl_codes) > 0) {
					foreach ($creditor_trans->gl_codes as $entered_gl_code) {
						alt_table_row_color($k);
						if ($mode == 3) {
							$entered_gl_code->amount = -$entered_gl_code->amount;
						}
						label_cell($entered_gl_code->gl_code);
						label_cell($entered_gl_code->gl_act_name);
						if ($dim >= 1) {
							label_cell(Dimensions::get_string($entered_gl_code->gl_dim, true));
						}
						if ($dim > 1) {
							label_cell(Dimensions::get_string($entered_gl_code->gl_dim2, true));
						}
						label_cell($entered_gl_code->memo_);
						amount_cell($entered_gl_code->amount, true);
						if ($mode == 1) {
							delete_button_cell("Delete2" . $entered_gl_code->Counter, _("Delete"), _('Remove line from document'));
							label_cell("");
						}
						end_row();
						/////////// 2009-08-18 Joe Hunt
						if ($mode > 1 && !Tax::is_account($entered_gl_code->gl_code)) {
							$total_gl_value += $entered_gl_code->amount;
						} else {
							$total_gl_value += $entered_gl_code->amount;
						}
						$i++;
						if ($i > 15) {
							$i = 0;
							table_header($th);
						}
					}
				}
				if ($mode == 1) {
					Purch_GLItem::display_controls($creditor_trans, $k);
				}
				$colspan = ($dim == 2 ? 5 : ($dim == 1 ? 4 : 3));
				label_row(_("Total"), Num::price_format($total_gl_value), "colspan=" . $colspan . " class='right bold'", "nowrap class='right bold'",
					($mode == 1 ? 3 : 0));
				end_table(1);
				Display::div_end();
				return $total_gl_value;
			}
	}

