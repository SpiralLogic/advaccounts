<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  class GL_Journal {

    /**
     * @static
     *
     * @param $order
     */
    public static function header($order) {

      $qes = GL_QuickEntry::has(QE_JOURNAL);
      $new = $order->order_id == 0;
      Table::startOuter('tablestyle2 width90');
      Table::section(1);
      Row::start();
      date_cells(_("Date:"), 'date_', '', $new);
      Table::section(2, $qes ? "20%" : "50%");
      ref_cells(_("Reference:"), 'ref', '');
      hidden('ref_original');
      Row::end();
      if ($new) {
        Table::section(3, "20%");
        Row::start();
        check_cells(_("Reverse Transaction:"), 'Reverse', NULL);
        Row::end();
      }
      if ($qes !== FALSE) {
        Table::section(3, "50%");
        Row::start();
        GL_QuickEntry::cells(_("Quick Entry") . ":", 'person_id', NULL, QE_JOURNAL, TRUE);
        $qid = GL_QuickEntry::get(get_post('person_id'));
        if (list_updated('person_id')) {
          unset($_POST['total_amount']); // enable default
          Ajax::i()->activate('total_amount');
        }
        amount_cells($qid['base_desc'] . ":", 'total_amount', Num::price_format($qid['base_amount']), NULL,
          "&nbsp;&nbsp;" . submit('go', _("Go"), FALSE, FALSE, TRUE));
        Row::end();
      }
      Table::endOuter(1);
    }
    /***
     * @static
     *
     * @param            $title
     * @param Item_Order $order
     */

    public static function items($title, &$order) {
      Display::heading($title);
      $dim = DB_Company::get_pref('use_dimension');
      Display::div_start('items_table');
      Table::start('tablestyle grid width95');
      if ($dim == 2) {
        $th = array(
          _("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Debit"), _("Credit"), _("Memo"), ""
        );
      }
      else {
        if ($dim == 1) {
          $th = array(
            _("Account Code"), _("Account Description"), _("Dimension"), _("Debit"), _("Credit"), _("Memo"), ""
          );
        }
        else {
          $th = array(
            _("Account Code"), _("Account Description"), _("Debit"), _("Credit"), _("Memo"), ""
          );
        }
      }
      if (count($order->gl_items)) {
        $th[] = '';
      }
      Table::header($th);
      $k  = 0;
      $id = find_submit(MODE_EDIT);
      foreach ($order->gl_items as $line => $item) {
        if ($id != $line) {

          Cell::labels($item->code_id, $item->description);
          if ($dim >= 1) {
            Cell::label(Dimensions::get_string($item->dimension_id, TRUE));
          }
          if ($dim > 1) {
            Cell::label(Dimensions::get_string($item->dimension2_id, TRUE));
          }
          if ($item->amount > 0) {
            Cell::amount(abs($item->amount));
            Cell::label("");
          }
          else {
            Cell::label("");
            Cell::amount(abs($item->amount));
          }
          Cell::label($item->reference);
          edit_button_cell("Edit$line", _("Edit"), _('Edit journal line'));
          delete_button_cell("Delete$line", _("Delete"), _('Remove line from journal'));
          Row::end();
        }
        else {
          GL_Journal::item_controls($order, $dim, $line);
        }
      }
      if ($id == -1) {
        GL_Journal::item_controls($order, $dim);
      }
      if ($order->count_gl_items()) {
        $colspan = ($dim == 2 ? "4" : ($dim == 1 ? "3" : "2"));
        Row::start();
        Cell::label(_("Total"), "class=right colspan=" . $colspan);
        Cell::amount($order->gl_items_total_debit());
        Cell::amount(abs($order->gl_items_total_credit()));
        Cell::label('', "colspan=3");
        Row::end();
      }
      Table::end();
      Display::div_end();
    }
    /**
     * @static
     *
     * @param      $order
     * @param      $dim
     * @param null $Index
     */
    public static function item_controls($order, $dim, $Index = NULL) {

      Row::start();
      $id = find_submit(MODE_EDIT);
      if ($Index != -1 && $Index == $id) {
        // Modifying an existing row
        $item                   = $order->gl_items[$Index];
        $_POST['code_id']       = $item->code_id;
        $_POST['dimension_id']  = $item->dimension_id;
        $_POST['dimension2_id'] = $item->dimension2_id;
        if ($item->amount > 0) {
          $_POST['AmountDebit']  = Num::price_format($item->amount);
          $_POST['AmountCredit'] = "";
        }
        else {
          $_POST['AmountDebit']  = "";
          $_POST['AmountCredit'] = Num::price_format(abs($item->amount));
        }
        $_POST['description'] = $item->description;
        $_POST['LineMemo']    = $item->reference;
        hidden('Index', $id);
        $skip_bank = !User::i()->can_access(SA_BANKJOURNAL);
        echo GL_UI::all('code_id', NULL, $skip_bank, TRUE);
        if ($dim >= 1) {
          Dimensions::cells(NULL, 'dimension_id', NULL, TRUE, " ", FALSE, 1);
        }
        if ($dim > 1) {
          Dimensions::cells(NULL, 'dimension2_id', NULL, TRUE, " ", FALSE, 2);
        }
        Ajax::i()->activate('items_table');
      }
      else {
        // Adding a new row
        $_POST['AmountDebit']   = ''; //Num::price_format(0);
        $_POST['AmountCredit']  = ''; //Num::price_format(0);
        $_POST['dimension_id']  = 0;
        $_POST['dimension2_id'] = 0;
        //$_POST['LineMemo'] = ""; // let memo go to next line Joe Hunt 2010-05-30
        $_POST['_code_id_edit'] = "";
        $_POST['code_id']       = "";
        if (isset($_POST['_code_id_update'])) {
          Ajax::i()->activate('code_id');
        }
        $skip_bank = !User::i()->can_access(SA_BANKJOURNAL);
        echo GL_UI::all('code_id', NULL, $skip_bank, TRUE);
        if ($dim >= 1) {
          Dimensions::cells(NULL, 'dimension_id', NULL, TRUE, " ", FALSE, 1);
        }
        if ($dim > 1) {
          Dimensions::cells(NULL, 'dimension2_id', NULL, TRUE, " ", FALSE, 2);
        }
      }
      if ($dim < 1) {
        hidden('dimension_id', 0);
      }
      if ($dim < 2) {
        hidden('dimension2_id', 0);
      }
      small_amount_cells(NULL, 'AmountDebit');
      small_amount_cells(NULL, 'AmountCredit');
      text_cells_ex(NULL, 'LineMemo', 35, 255);
      if ($id != -1) {
        button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        JS::set_focus('amount');
      }
      else {
        submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new line to journal'), TRUE);
      }
      Row::end();
    }

    public static function option_controls() {
      echo "<br><table class='center'>";
      textarea_row(_("Memo"), 'memo_', NULL, 50, 3);
      echo "</table>";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     *
     * @return null|string
     */
    public static function  view($type, $trans_no, $label = "", $icon = FALSE, $class = '', $id = '') {
      if ($type == ST_JOURNAL || $type == ST_COSTUPDATE) {
        $viewer = "gl/view/gl_trans.php?type_id=$type&trans_no=$trans_no";
      }
      else {
        return NULL;
      }
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $viewer, $class, $id, $icon);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     */
    public static function  cells($label, $name, $value = NULL, $submit_on_change = FALSE) {
      global $systypes_array;
      echo "<td>";
      if ($label != NULL) {
        echo "<label for=\"$name\"> $label</label>";
      }
      $items = $systypes_array;
      // exclude quotes, orders and dimensions
      foreach (array(ST_PURCHORDER, ST_WORKORDER, ST_SALESORDER, ST_DIMENSION, ST_SALESQUOTE) as $excl) {
        unset($items[$excl]);
      }
      echo array_selector($name, $value, $items, array(
        'spec_option' => _("All"), 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => FALSE
      ));
      echo "</td>\n";
    }

    // Write/update journal entries.
    //
    /**
     * @static
     *
     * @param      $order
     * @param      $reverse
     * @param bool $use_transaction
     *
     * @return string
     */
    public static function write($order, $reverse, $use_transaction = TRUE) {
      $date_      = $order->tran_date;
      $ref        = $order->reference;
      $memo_      = $order->memo_;
      $trans_type = $order->trans_type;
      $new        = $order->order_id == 0;
      if ($new) {
        $order->order_id = SysTypes::get_next_trans_no($trans_type);
      }
      $trans_id = $order->order_id;
      if ($use_transaction) {
        DB::begin();
      }
      if (!$new) {
        static::void($trans_type, $trans_id, FALSE);
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
      }
      else {
        DB_Comments::update($trans_type, $trans_id, NULL, $memo_);
        Ref::update($trans_type, $trans_id, $ref);
      }
      DB_AuditTrail::add($trans_type, $trans_id, $date_);
      if ($reverse) {
        //$reversingDate = date(User::date_display(),
        //	Mktime(0,0,0,get_month($date_)+1,1,get_year($date_)));
        $reversingDate    = Dates::begin_month(Dates::add_months($date_, 1));
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
    /**
     * @static
     *
     * @param      $type
     * @param      $type_no
     * @param bool $use_transaction
     */
    public static function void($type, $type_no, $use_transaction = TRUE) {
      if ($use_transaction) {
        DB::begin();
      }
      Bank_Trans::void($type, $type_no, TRUE);
      //	static::void($type, $type_no, true);	 // this is done above
      //	static::void_tax_details($type, $type_no); // ditto
      if ($use_transaction) {
        DB::commit();
      }
    }
  }


