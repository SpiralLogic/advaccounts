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
	/*
		 Class for supplier/customer payment/credit allocations edition
		 and related helpers.
	 */

	class Gl_Allocation {
		public $trans_no;
		public $type;
		public $person_id = '';
		public $person_name = '';
		public $person_type;
		public $date_;
		public $amount = 0; /*Total amount of the transaction in FX */
		public $allocs; /*array of transactions allocated to */
		public function __construct($type, $trans_no) {
			$this->allocs = array();
			$this->trans_no = $trans_no;
			$this->type = $type;
			$this->read(); // read payment or credit
		}

		public function add_item(
			$type, $type_no, $date_, $due_date, $amount, $amount_allocated,
			$current_allocated
		) {
			if ($amount > 0) {
				$this->allocs[count($this->allocs)] = new allocation_item($type, $type_no,
					$date_, $due_date, $amount, $amount_allocated, $current_allocated);
				return true;
			} else {
				return false;
			}
		}

		public function update_item(
			$index, $type, $type_no, $date_, $due_date,
			$amount, $amount_allocated, $current_allocated
		) {
			if ($amount > 0) {
				$this->allocs[$index] = new allocation_item($type, $type_no,
					$date_, $due_date, $amount, $amount_allocated, $current_allocated);
				return true;
			} else {
				return false;
			}
		}

		public function add_or_update_item(
			$type, $type_no, $date_, $due_date,
			$amount, $amount_allocated, $current_allocated
		) {
			for (
				$i = 0; $i < count($this->allocs); $i++
			)
			{
				$item = $this->allocs[$i];
				if (($item->type == $type) && ($item->type_no == $type_no)) {
					return $this->update_item(
						$i, $type, $type_no, $date_, $due_date,
						$amount, $amount_allocated, $current_allocated
					);
				}
			}
			return $this->add_item(
				$type, $type_no, $date_, $due_date,
				$amount, $amount_allocated, $current_allocated
			);
		}

		//
		//	Read payment or credit current/available allocations to cart.
		//
		public function read($type = null, $trans_no = 0) {
			if ($type == null) { // re-read
				$type = $this->type;
				$trans_no = $this->trans_no;
			}
			if ($type == ST_BANKPAYMENT || $type == ST_BANKDEPOSIT) {
				$bank_trans = DB::fetch(Bank_Trans::get($type, $trans_no));
				$this->person_type = $bank_trans['person_type_id'] == PT_SUPPLIER;
			} else {
				$this->person_type = $type == ST_SUPPCREDIT || $type == ST_SUPPAYMENT;
			}
			$this->allocs = array();
			if ($trans_no) {
				$trans = $this->person_type ? Purch_Trans::get($trans_no, $type)
				 : Sales_Trans::get($trans_no, $type);
				$this->person_id = $trans[$this->person_type ? 'supplier_id' : 'debtor_no'];
				$this->person_name = $trans[$this->person_type ? "supplier_name" : "DebtorName"];
				$this->amount = $trans["Total"];
				$this->date_ = Dates::sql2date($trans["tran_date"]);
			}
			else {
				$this->person_id = Display::get_post($this->person_type ? 'supplier_id' : 'customer_id');
				$this->date_ = Display::get_post($this->person_type ? 'DatePaid' : 'DateBanked', Dates::Today());
			}
			/* Now populate the array of possible (and previous actual) allocations
																		for this customer/supplier. First get the transactions that have
																		outstanding balances ie Total-alloc >0 */
			if ($this->person_type) {
				$trans_items = Purch_Allocation::get_allocatable_to_trans($this->person_id);
			} else {
				$trans_items = Sales_Allocation::get_to_trans($this->person_id);
			}
			while ($myrow = DB::fetch($trans_items))
			{
				$this->add_item(
					$myrow["type"], $myrow["trans_no"],
					Dates::sql2date($myrow["tran_date"]),
					Dates::sql2date($myrow["due_date"]),
					$myrow["Total"], // trans total
					$myrow["alloc"], // trans total allocated
					0
				); // this allocation
			}
			if ($trans_no == 0) {
				return;
			} // this is new payment
			/* Now get trans that might have previously been allocated to by this trans
																	NB existing entries where still some of the trans outstanding entered from
																	above logic will be overwritten with the prev alloc detail below */
			if ($this->person_type) {
				$trans_items = Purch_Allocation::get_allocatable_to_trans(
					$this->person_id,
					$trans_no, $type
				);
			} else {
				$trans_items = Sales_Allocation::get_to_trans(
					$this->person_id,
					$trans_no, $type
				);
			}
			while ($myrow = DB::fetch($trans_items))
			{
				$this->add_or_update_item(
					$myrow["type"], $myrow["trans_no"],
					Dates::sql2date($myrow["tran_date"]),
					Dates::sql2date($myrow["due_date"]),
					$myrow["Total"],
				 $myrow["alloc"] - $myrow["amt"], $myrow["amt"]
				);
			}
		}

		//
		//	Update allocations in database.
		//
		public function write() {
			DB::begin_transaction();
			if ($this->person_type) {
				Purch_Allocation::clear($this->type, $this->trans_no, $this->date_);
			} else {
				Sales_Allocation::clear($this->type, $this->trans_no, $this->date_);
			}
			// now add the new allocations
			$total_allocated = 0;
			foreach (
				$this->allocs as $alloc_item
			)
			{
				if ($alloc_item->current_allocated > 0) {
					if ($this->person_type) {
						Purch_Allocation::add(
							$alloc_item->current_allocated,
							$this->type, $this->trans_no,
							$alloc_item->type, $alloc_item->type_no, $this->date_
						);
						Purch_Allocation::update(
							$alloc_item->type,
							$alloc_item->type_no, $alloc_item->current_allocated
						);
					}
					else {
						Sales_Allocation::add(
							$alloc_item->current_allocated,
							$this->type, $this->trans_no,
							$alloc_item->type, $alloc_item->type_no, $this->date_
						);
						Sales_Allocation::update(
							$alloc_item->type,
							$alloc_item->type_no, $alloc_item->current_allocated
						);
					}
					// Exchange Variations Joe Hunt 2008-09-20 ////////////////////
					Banking::exchange_variation(
						$this->type, $this->trans_no,
						$alloc_item->type, $alloc_item->type_no, $this->date_,
						$alloc_item->current_allocated,
						$this->person_type ? PT_SUPPLIER : PT_CUSTOMER
					);
					//////////////////////////////////////////////////////////////
					$total_allocated += $alloc_item->current_allocated;
				}
			} /*end of the loop through the array of allocations made */
			if ($this->person_type) {
				Purch_Allocation::update(
					$this->type, $this->trans_no,
					$total_allocated
				);
			} else {
				Sales_Allocation::update(
					$this->type, $this->trans_no,
					$total_allocated
				);
			}
			DB::commit_transaction();
		}

		public static function show_allocatable($show_totals) {
			global $systypes_array;
			$k = $counter = $total_allocated = 0;
			if (count($_SESSION['alloc']->allocs)) {
				Display::start_table(Config::get('tables_style') . "  style='width:60%'");
				$th = array(
					_("Transaction Type"), _("#"), _("Date"), _("Due Date"), _("Amount"),
					_("Other Allocations"), _("This Allocation"), _("Left to Allocate"), '', ''
				);
				Display::table_header($th);
				foreach (
					$_SESSION['alloc']->allocs as $alloc_item
				)
				{
					Display::alt_table_row_color($k);
					label_cell($systypes_array[$alloc_item->type]);
					label_cell(GL_UI::trans_view($alloc_item->type, $alloc_item->type_no));
					label_cell($alloc_item->date_, "class=right");
					label_cell($alloc_item->due_date, "class=right");
					amount_cell($alloc_item->amount);
					amount_cell($alloc_item->amount_allocated);
					$_POST['amount' . $counter] = Num::price_format($alloc_item->current_allocated);
					amount_cells(null, "amount" . $counter, Num::price_format('amount' . $counter));
					$un_allocated = round($alloc_item->amount - $alloc_item->amount_allocated, 6);
					amount_cell($un_allocated, false, '', 'maxval' . $counter);
					label_cell(
						"<a href='#' name=Alloc$counter class='button allocateAll'>"
						 . _("All") . "</a>"
					);
					label_cell(
						"<a href='#' name=DeAll$counter class='button allocateNone'>"
						 . _("None") . "</a>" . hidden(
							"un_allocated" . $counter,
							Num::price_format($un_allocated), false
						)
					);
					Display::end_row();
					$total_allocated += Validation::input_num('amount' . $counter);
					$counter++;
				}
				if ($show_totals) {
					label_row(
						_("Total Allocated"), Num::price_format($total_allocated),
						"colspan=6 class=right", "class=right id='total_allocated'", 3
					);
					$amount = $_SESSION['alloc']->amount;
					if ($_SESSION['alloc']->type == ST_SUPPCREDIT
					 || $_SESSION['alloc']->type == ST_SUPPAYMENT
					 || $_SESSION['alloc']->type == ST_BANKPAYMENT
					) {
						$amount = -$amount;
					}
					if ($amount - $total_allocated < 0) {
						$font1 = "<font color=red>";
						$font2 = "</font>";
					} else {
						$font1 = $font2 = "";
					}
					$left_to_allocate = Num::price_format($amount - $total_allocated);
					label_row(
						_("Left to Allocate"), $font1 . $left_to_allocate . $font2,
						"colspan=6 class=right", "nowrap class=right id='left_to_allocate'",
						3
					);
				}
				Display::end_table(1);
			}
			hidden('TotalNumberOfAllocs', $counter);
		}

		public static function check() {
			$total_allocated = 0;
			for (
				$counter = 0; $counter < $_POST["TotalNumberOfAllocs"]; $counter++
			)
			{
				if (!Validation::is_num('amount' . $counter, 0)) {
					Errors::error(_("The entry for one or more amounts is invalid or negative."));
					JS::set_focus('amount' . $counter);
					return false;
				}
				/*Now check to see that the AllocAmt is no greater than the
																							 amount left to be allocated against the transaction under review */
				if (Validation::input_num('amount' . $counter) > Validation::input_num('un_allocated' . $counter)) {
					Errors::error(_("At least one transaction is overallocated."));
					JS::set_focus('amount' . $counter);
					return false;
				}
				$_SESSION['alloc']->allocs[$counter]->current_allocated = Validation::input_num('amount' . $counter);
				$total_allocated += Validation::input_num('amount' . $counter);
			}
			$amount = $_SESSION['alloc']->amount;
			if (in_array($_SESSION['alloc']->type, array(ST_BANKPAYMENT, ST_SUPPCREDIT, ST_SUPPAYMENT))) {
				$amount = -$amount;
			}
			if ($total_allocated - ($amount + Validation::input_num('discount')) > Config::get('accounts_allocation_allowance')) {
				Errors::error(_("These allocations cannot be processed because the amount allocated is more than the total amount left to allocate."));
				return false;
			}
			return true;
		}

		public static function create_miscorder(Debtor $customer, $branch_id, $date, $memo, $ref, $amount, $discount = 0) {
			Sales_Order::start();
			$type = ST_SALESINVOICE;
			$doc = new Sales_Order(ST_SALESINVOICE, 0);
			$doc->trans_type = ST_SALESINVOICE;
			$doc->due_date = $doc->document_date = Dates::new_doc_date($date);
			$doc->set_customer($customer->id, $customer->name, $customer->curr_code, $customer->discount, $customer->payment_terms);
			$doc->set_branch($customer->branches[$branch_id]->id, $customer->branches[$branch_id]->tax_group_id);
			$doc->pos = User::pos();
			$doc->ship_via = 11;
			$doc->sales_type = 1;
			$doc->Location = 'MEL';
			$doc->cust_ref = $ref;
			$doc->Comments = "Invoice for Customer Payment: " . $doc->cust_ref;
			$doc->add_to_cart(0, 'MiscSale', '1',
				Taxes::get_tax_free_price_for_item('MiscSale', $amount, 0, true, $doc->tax_group_array), $discount / 100, 1, 0,
			 'Order: ' . $memo);
			$doc->write(1);
			Sales_Order::finish();
			$_SESSION['alloc']->add_or_update_item(ST_SALESINVOICE, key($doc->trans_no), $doc->document_date, $doc->due_date, $amount,
				0, $amount);
		}


		public static function display($alloc_result, $total) {
			global $systypes_array;
			if (!$alloc_result || DB::num_rows($alloc_result) == 0) {
				return;
			}
			Display::heading(_("Allocations"));
			Display::start_table(Config::get('tables_style') . "  width=90%");
			$th = array(_("Type"), _("Number"), _("Date"), _("Total Amount"), _("Left to Allocate"), _("This Allocation"));
			Display::table_header($th);
			$k = $total_allocated = 0;
			while ($alloc_row = DB::fetch($alloc_result)) {
				Display::alt_table_row_color($k);
				label_cell($systypes_array[$alloc_row['type']]);
				label_cell(GL_UI::trans_view($alloc_row['type'], $alloc_row['trans_no']));
				label_cell(Dates::sql2date($alloc_row['tran_date']));
				$alloc_row['Total'] = Num::round($alloc_row['Total'], User::price_dec());
				$alloc_row['amt'] = Num::round($alloc_row['amt'], User::price_dec());
				amount_cell($alloc_row['Total']);
				//amount_cell($alloc_row['Total'] - $alloc_row['PrevAllocs'] - $alloc_row['amt']);
				amount_cell($alloc_row['Total'] - $alloc_row['amt']);
				amount_cell($alloc_row['amt']);
				Display::end_row();
				$total_allocated += $alloc_row['amt'];
			}
			Display::start_row();
			label_cell(_("Total Allocated:"), "class=right colspan=5");
			amount_cell($total_allocated);
			Display::end_row();
			Display::start_row();
			label_cell(_("Left to Allocate:"), "class=right colspan=5");
			$total = Num::round($total, User::price_dec());
			amount_cell($total - $total_allocated);
			Display::end_row();
			Display::end_table(1);
		}


		public static function from($person_type, $person_id, $type, $type_no, $total) {
			switch ($person_type) {
				case PT_CUSTOMER :
					$alloc_result = Sales_Allocation::get_to_trans($person_id, $type_no, $type);
					GL_Allocation::display($alloc_result, $total);
					return;
				case PT_SUPPLIER :
					$alloc_result = Purch_Allocation::get_allocatable_to_trans($person_id, $type_no, $type);
					GL_Allocation::display($alloc_result, $total);
					return;
			}
		}
	}


	class allocation_item {
		public $type;
		public $type_no;
		public $date_;
		public $due_date;
		public $amount_allocated;
		public $amount;
		public $current_allocated;

		public function __construct(
			$type, $type_no, $date_, $due_date, $amount,
			$amount_allocated, $current_allocated
		) {
			$this->type = $type;
			$this->type_no = $type_no;
			$this->date_ = $date_;
			$this->due_date = $due_date;
			$this->amount = $amount;
			$this->amount_allocated = $amount_allocated;
			$this->current_allocated = $current_allocated;
		}
	}

	if (!function_exists('copy_from_cart')) {
		function copy_from_cart($cart) {
			$_POST['Comments'] = $cart->Comments;
			$_POST['OrderDate'] = $cart->document_date;
			$_POST['delivery_date'] = $cart->due_date;
			$_POST['cust_ref'] = $cart->cust_ref;
			$_POST['freight_cost'] = Num::price_format($cart->freight_cost);
			$_POST['deliver_to'] = $cart->deliver_to;
			$_POST['delivery_address'] = $cart->delivery_address;
			$_POST['name'] = $cart->name;
			$_POST['phone'] = $cart->phone;
			$_POST['Location'] = $cart->Location;
			$_POST['ship_via'] = $cart->ship_via;
			$_POST['sales_type'] = $cart->sales_type;
			$_POST['salesman'] = $cart->salesman;
			$_POST['dimension_id'] = $cart->dimension_id;
			$_POST['dimension2_id'] = $cart->dimension2_id;
			$_POST['cart_id'] = $cart->cart_id;
		}
	}
?>
