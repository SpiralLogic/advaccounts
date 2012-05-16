<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  /*
       Class for supplier/customer payment/credit allocations edition
       and related helpers.
     */
  /**

   */
  class Gl_Allocation {

    /**
     * @var
     */
    public $trans_no;
    /**
     * @var
     */
    public $type;
    /**
     * @var string
     */
    public $person_id = '';
    /**
     * @var string
     */
    public $person_name = '';
    /**
     * @var
     */
    public $person_type;
    /**
     * @var
     */
    public $date_;
    /**
     * @var int
     */
    public $amount = 0; /*Total amount of the transaction in FX */
    /**
     * @var array
     */
    public $allocs; /*array of transactions allocated to */
    /**
     * @param $type
     * @param $trans_no
     */
    public function __construct($type, $trans_no) {
      $this->allocs = array();
      $this->trans_no = $trans_no;
      $this->type = $type;
      $this->read(); // read payment or credit
    }
    /**
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $due_date
     * @param $amount
     * @param $amount_allocated
     * @param $current_allocated
     *
     * @return bool
     */
    public function add_item($type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated) {
      if ($amount > 0) {
        $this->allocs[count($this->allocs)] = new allocation_item($type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    /**
     * @param $index
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $due_date
     * @param $amount
     * @param $amount_allocated
     * @param $current_allocated
     *
     * @return bool
     */
    public function update_item($index, $type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated) {
      if ($amount > 0) {
        $this->allocs[$index] = new allocation_item($type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    /**
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $due_date
     * @param $amount
     * @param $amount_allocated
     * @param $current_allocated
     *
     * @return bool
     */
    public function add_or_update_item($type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated) {
      for ($i = 0; $i < count($this->allocs); $i++) {
        $item = $this->allocs[$i];
        if (($item->type == $type) && ($item->type_no == $type_no)) {
          return $this->update_item($i, $type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated);
        }
      }
      return $this->add_item($type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated);
    }
    /**
     * @param null $type
     * @param int  $trans_no
     *
     * @return mixed
     */
    public function read($type = NULL, $trans_no = 0) {
      if ($type == NULL) { // re-read
        $type = $this->type;
        $trans_no = $this->trans_no;
      }
      if ($type == ST_BANKPAYMENT || $type == ST_BANKDEPOSIT) {
        $result = Bank_Trans::get($type, $trans_no);
        $bank_trans = DB::fetch($result);
        $this->person_type = $bank_trans['person_type_id'] == PT_SUPPLIER;
      }
      else {
        $this->person_type = $type == ST_SUPPCREDIT || $type == ST_SUPPAYMENT;
      }
      $this->allocs = array();
      if ($trans_no) {
        $trans = $this->person_type ? Creditor_Trans::get($trans_no, $type) : Debtor_Trans::get($trans_no, $type);
        $this->person_id = $trans[$this->person_type ? 'supplier_id' : 'debtor_no'];
        $this->person_name = $trans[$this->person_type ? "supplier_name" : "DebtorName"];
        $this->amount = $trans["Total"];
        $this->date_ = Dates::sql2date($trans["tran_date"]);
      }
      else {
        $this->person_id = get_post($this->person_type ? 'supplier_id' : 'customer_id');
        $this->date_ = get_post($this->person_type ? 'DatePaid' : 'DateBanked', Dates::today());
      }
      /* Now populate the array of possible (and previous actual) allocations
                                          for this customer/supplier. First get the transactions that have
                                          outstanding balances ie Total-alloc >0 */
      if ($this->person_type) {
        Purch_Allocation::get_allocatable_to_trans($this->person_id);
      }
      else {
        Sales_Allocation::get_to_trans($this->person_id);
      }
      $results = DB::fetch_all();
      foreach ($results as $myrow) {
        $this->add_item($myrow["type"], $myrow["trans_no"], Dates::sql2date($myrow["tran_date"]), Dates::sql2date($myrow["due_date"]), $myrow["Total"], // trans total
          $myrow["alloc"], // trans total allocated
          0); // this allocation
      }
      if ($trans_no == 0) {
        return;
      } // this is new payment
      /* Now get trans that might have previously been allocated to by this trans
                                        NB existing entries where still some of the trans outstanding entered from
                                        above logic will be overwritten with the prev alloc detail below */
      if ($this->person_type) {
        Purch_Allocation::get_allocatable_to_trans($this->person_id, $trans_no, $type);
      }
      else {
        Sales_Allocation::get_to_trans($this->person_id, $trans_no, $type);
      }
      $results = DB::fetch_all();
      foreach ($results as $myrow) {
        $this->add_or_update_item($myrow["type"], $myrow["trans_no"], Dates::sql2date($myrow["tran_date"]), Dates::sql2date($myrow["due_date"]), $myrow["Total"], $myrow["alloc"] - $myrow["amt"], $myrow["amt"]);
      }
    }
    /**

     */
    public function write() {
      DB::begin();
      if ($this->person_type) {
        Purch_Allocation::clear($this->type, $this->trans_no, $this->date_);
      }
      else {
        Sales_Allocation::void($this->type, $this->trans_no, $this->date_);
      }
      // now add the new allocations
      $total_allocated = 0;
      foreach ($this->allocs as $alloc_item) {
        if ($alloc_item->current_allocated > 0) {
          if ($this->person_type) {
            Purch_Allocation::add($alloc_item->current_allocated, $this->type, $this->trans_no, $alloc_item->type, $alloc_item->type_no, $this->date_);
            Purch_Allocation::update($alloc_item->type, $alloc_item->type_no, $alloc_item->current_allocated);
          }
          else {
            Sales_Allocation::add($alloc_item->current_allocated, $this->type, $this->trans_no, $alloc_item->type, $alloc_item->type_no, $this->date_);
            Sales_Allocation::update($alloc_item->type, $alloc_item->type_no, $alloc_item->current_allocated);
          }
          // Exchange Variations Joe Hunt 2008-09-20 ////////////////////
          Bank::exchange_variation($this->type, $this->trans_no, $alloc_item->type, $alloc_item->type_no, $this->date_, $alloc_item->current_allocated, $this->person_type ?
            PT_SUPPLIER : PT_CUSTOMER);
          //////////////////////////////////////////////////////////////
          $total_allocated += $alloc_item->current_allocated;
        }
      } /*end of the loop through the array of allocations made */
      if ($this->person_type) {
        Purch_Allocation::update($this->type, $this->trans_no, $total_allocated);
      }
      else {
        Sales_Allocation::update($this->type, $this->trans_no, $total_allocated);
      }
      DB::commit();
    }
    /**
     * @static
     *
     * @param $show_totals
     */
    static public function show_allocatable($show_totals) {
      global $systypes_array;
      $k = $counter = $total_allocated = 0;
      if (count($_SESSION['alloc']->allocs)) {
        Table::start('tablestyle grid width60');
        $th = array(
          _("Transaction Type"), _("#"), _("Date"), _("Due Date"), _("Amount"), _("Other Allocations"), _("This Allocation"),
          _("Left to Allocate"), '', ''
        );
        Table::header($th);
        foreach ($_SESSION['alloc']->allocs as $alloc_item) {

          Cell::label($systypes_array[$alloc_item->type]);
          Cell::label(GL_UI::trans_view($alloc_item->type, $alloc_item->type_no));
          Cell::label($alloc_item->date_, "class='right'");
          Cell::label($alloc_item->due_date, "class='right'");
          Cell::amount($alloc_item->amount);
          Cell::amount($alloc_item->amount_allocated);
          $_POST['amount' . $counter] = Num::price_format($alloc_item->current_allocated+$_POST['amount'.$counter]);
          amount_cells(NULL, "amount" . $counter, Num::price_format('amount' . $counter));
          $un_allocated = $alloc_item->amount - $alloc_item->amount_allocated;
          Cell::amount($un_allocated, FALSE, '');
          Cell::label("<a href='#' name=Alloc$counter class='button allocateAll'>" . _("All") . "</a>");
          Cell::label("<a href='#' name=DeAll$counter class='button allocateNone'>" . _("None") . "</a>" . hidden("un_allocated" . $counter, Num::price_format($un_allocated), FALSE));
          Row::end();
          $total_allocated += Validation::input_num('amount' . $counter);
          $counter++;
        }
        if ($show_totals) {
          Row::label(_("Total Allocated"), Num::price_format($total_allocated), "colspan=6 class='right'", "class=right id='total_allocated'", 3);
          $amount = $_SESSION['alloc']->amount;
          if ($_SESSION['alloc']->type == ST_SUPPCREDIT || $_SESSION['alloc']->type == ST_SUPPAYMENT || $_SESSION['alloc']->type == ST_BANKPAYMENT
          ) {
            $amount = -$amount;
          }
          if ($amount - $total_allocated < 0) {
            $font1 = "<span class='red'>";
            $font2 = "</span>";
          }
          else {
            $font1 = $font2 = "";
          }
          $left_to_allocate = Num::price_format($amount - $total_allocated);
          Row::label(_("Left to Allocate"), $font1 . $left_to_allocate . $font2, "colspan=6 class='right'", " class='right nowrap' id='left_to_allocate'", 3);
        }
        Table::end(1);
      }
      hidden('TotalNumberOfAllocs', $counter);
    }
    /**
     * @static
     * @return bool
     */
    static public function check() {
      $total_allocated = 0;
      for ($counter = 0; $counter < $_POST["TotalNumberOfAllocs"]; $counter++) {
        if (!Validation::post_num('amount' . $counter, 0)) {
          Event::error(_("The entry for one or more amounts is invalid or negative."));
          JS::set_focus('amount' . $counter);
          return FALSE;
        }
        if (Validation::input_num('amount' . $counter) > Validation::input_num('un_allocated' . $counter)) {
          Event::error(_("At least one transaction is overallocated."));
          JS::set_focus('amount' . $counter);
          return FALSE;
        }
        $_SESSION['alloc']->allocs[$counter]->current_allocated = Validation::input_num('amount' . $counter);
        $total_allocated += Validation::input_num('amount' . $counter);
      }
      $amount = $_SESSION['alloc']->amount;
      if (in_array($_SESSION['alloc']->type, array(ST_BANKPAYMENT, ST_SUPPCREDIT, ST_SUPPAYMENT))) {
        $amount = -$amount;
      }
      if ($total_allocated - ($amount + Validation::input_num('discount')) > Config::get('accounts.allocation_allowance')) {
        Event::error(_("These allocations cannot be processed because the amount allocated is more than the total amount left to allocate."));
        return FALSE;
      }
      return TRUE;
    }
    /**
     * @static
     *
     * @param Debtor $customer
     * @param        $branch_id
     * @param        $date
     * @param        $memo
     * @param        $ref
     * @param        $amount
     * @param int    $discount
     *
     * @return bool
     */
    static public function create_miscorder(Debtor $customer, $branch_id, $date, $memo, $ref, $amount, $discount = 0) {
      $type = ST_SALESINVOICE;
      if (!User::i()->salesmanid) {
        Event::error(_("You do not have a salesman id, this is needed to create an invoice."));
        return FALSE;
      }
      $doc = new Sales_Order($type, 0);
      $doc->start();
      $doc->trans_type = $type;
      $doc->due_date = $doc->document_date = Dates::new_doc_date($date);
      $doc->set_customer($customer->id, $customer->name, $customer->curr_code, $customer->discount, $customer->payment_terms);
      $doc->set_branch($customer->branches[$branch_id]->id, $customer->branches[$branch_id]->tax_group_id);
      $doc->pos = User::pos();
      $doc->ship_via = Config::get('default.ship_via',1);
      $doc->sales_type = 1;
      $doc->location = Config::get('default.location');
      $doc->cust_ref = $ref;
      $doc->Comments = "Invoice for Customer Payment: " . $doc->cust_ref;
      $doc->salesman = User::i()->salesmanid;
      $doc->add_to_order(0, 'MiscSale', '1', Tax::tax_free_price('MiscSale', $amount, 0, TRUE, $doc->tax_group_array), $discount / 100, 1, 0, 'Order: ' . $memo);
      $doc->write(1);
      $doc->finish();
      $_SESSION['alloc']->add_or_update_item($type, key($doc->trans_no), $doc->document_date, $doc->due_date, $amount, 0, $amount);
    }
    /**
     * @static
     *
     * @param $alloc_result
     * @param $total
     *
     * @return mixed
     */
    static public function display($alloc_result, $total) {
      global $systypes_array;
      if (!$alloc_result || DB::num_rows() == 0) {
        return;
      }
      Display::heading(_("Allocations"));
      Table::start('tablestyle grid width90');
      $th = array(_("Type"), _("Number"), _("Date"), _("Total Amount"), _("Left to Allocate"), _("This Allocation"));
      Table::header($th);
      $k = $total_allocated = 0;
      while ($alloc_row = DB::fetch($alloc_result)) {

        Cell::label($systypes_array[$alloc_row['type']]);
        Cell::label(GL_UI::trans_view($alloc_row['type'], $alloc_row['trans_no']));
        Cell::label(Dates::sql2date($alloc_row['tran_date']));
        $alloc_row['Total'] = Num::round($alloc_row['Total'], User::price_dec());
        $alloc_row['amt'] = Num::round($alloc_row['amt'], User::price_dec());
        Cell::amount($alloc_row['Total']);
        //Cell::amount($alloc_row['Total'] - $alloc_row['PrevAllocs'] - $alloc_row['amt']);
        Cell::amount($alloc_row['Total'] - $alloc_row['amt']);
        Cell::amount($alloc_row['amt']);
        Row::end();
        $total_allocated += $alloc_row['amt'];
      }
      Row::start();
      Cell::label(_("Total Allocated:"), "class=right colspan=5");
      Cell::amount($total_allocated);
      Row::end();
      Row::start();
      Cell::label(_("Left to Allocate:"), "class=right colspan=5");
      $total = Num::round($total, User::price_dec());
      Cell::amount($total - $total_allocated);
      Row::end();
      Table::end(1);
    }
    /**
     * @static
     *
     * @param $person_type
     * @param $person_id
     * @param $type
     * @param $type_no
     * @param $total
     *
     * @return mixed
     */
    static public function from($person_type, $person_id, $type, $type_no, $total) {
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

  /**

   */
  class allocation_item {

    /**
     * @var
     */
    public $type;
    /**
     * @var
     */
    public $type_no;
    /**
     * @var
     */
    public $date_;
    /**
     * @var
     */
    public $due_date;
    /**
     * @var
     */
    public $amount_allocated;
    /**
     * @var
     */
    public $amount;
    /**
     * @var
     */
    public $current_allocated;
    /**
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $due_date
     * @param $amount
     * @param $amount_allocated
     * @param $current_allocated
     */
    public function __construct($type, $type_no, $date_, $due_date, $amount, $amount_allocated, $current_allocated) {
      $this->type = $type;
      $this->type_no = $type_no;
      $this->date_ = $date_;
      $this->due_date = $due_date;
      $this->amount = $amount;
      $this->amount_allocated = $amount_allocated;
      $this->current_allocated = $current_allocated;
    }
  }

  if (!function_exists('copy_from_order')) {
    /**
     * @param $order
     */
    function copy_from_order($order) {
      $_POST['Comments'] = $order->Comments;
      $_POST['OrderDate'] = $order->document_date;
      $_POST['delivery_date'] = $order->due_date;
      $_POST['cust_ref'] = $order->cust_ref;
      $_POST['freight_cost'] = Num::price_format($order->freight_cost);
      $_POST['deliver_to'] = $order->deliver_to;
      $_POST['delivery_address'] = $order->delivery_address;
      $_POST['name'] = $order->name;
      $_POST['phone'] = $order->phone;
      $_POST['location'] = $order->location;
      $_POST['ship_via'] = $order->ship_via;
      $_POST['sales_type'] = $order->sales_type;
      $_POST['salesman'] = $order->salesman;
      $_POST['dimension_id'] = $order->dimension_id;
      $_POST['dimension2_id'] = $order->dimension2_id;
      $_POST['order_id'] = $order->order_id;
    }
  }

