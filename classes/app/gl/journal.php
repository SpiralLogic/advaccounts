<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GL_Journal
  {
    /**
     * @static
     *
     * @param $order
     */
    public static function header($order)
    {
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
        check_cells(_("Reverse Transaction:"), 'Reverse', null);
        Row::end();
      }
      if ($qes !== false) {
        Table::section(3, "50%");
        Row::start();
        GL_QuickEntry::cells(_("Quick Entry") . ":", 'person_id', null, QE_JOURNAL, true);
        $qid = GL_QuickEntry::get(get_post('person_id'));
        if (list_updated('person_id')) {
          unset($_POST['total_amount']); // enable default
          Ajax::i()->activate('total_amount');
        }
        amount_cells($qid['base_desc'] . ":", 'total_amount', Num::price_format($qid['base_amount']), null, "&nbsp;&nbsp;" . submit('go', _("Go"), false, false, true));
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
    public static function items($title, &$order)
    {
      Display::heading($title);
      $dim = DB_Company::get_pref('use_dimension');
      Display::div_start('items_table');
      Table::start('tablestyle grid width95');
      if ($dim == 2) {
        $th = array(
          _("Account Code"),
          _("Account Description"),
          _("Dimension") . " 1",
          _("Dimension") . " 2",
          _("Debit"),
          _("Credit"),
          _("Memo"),
          ""
        );
      } else {
        if ($dim == 1) {
          $th = array(
            _("Account Code"), _("Account Description"), _("Dimension"), _("Debit"), _("Credit"), _("Memo"), ""
          );
        } else {
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
            Cell::label(Dimensions::get_string($item->dimension_id, true));
          }
          if ($dim > 1) {
            Cell::label(Dimensions::get_string($item->dimension2_id, true));
          }
          if ($item->amount > 0) {
            Cell::amount(abs($item->amount));
            Cell::label("");
          } else {
            Cell::label("");
            Cell::amount(abs($item->amount));
          }
          Cell::label($item->reference);
          edit_button_cell("Edit$line", _("Edit"), _('Edit journal line'));
          delete_button_cell("Delete$line", _("Delete"), _('Remove line from journal'));
          Row::end();
        } else {
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
    public static function item_controls($order, $dim, $Index = null)
    {
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
        } else {
          $_POST['AmountDebit']  = "";
          $_POST['AmountCredit'] = Num::price_format(abs($item->amount));
        }
        $_POST['description'] = $item->description;
        $_POST['LineMemo']    = $item->reference;
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
        button_cell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        JS::set_focus('amount');
      } else {
        submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new line to journal'), true);
      }
      Row::end();
    }
    public static function option_controls()
    {
      echo "<br><table class='center'>";
      textarea_row(_("Memo"), 'memo_', null, 50, 3);
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
    public static function  view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
    {
      if ($type == ST_JOURNAL || $type == ST_COSTUPDATE) {
        $viewer = "gl/view/gl_trans.php?type_id=$type&trans_no=$trans_no";
      } else {
        return null;
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
    public static function  cells($label, $name, $value = null, $submit_on_change = false)
    {
      global $systypes_array;
      echo "<td>";
      if ($label != null) {
        echo "<label for=\"$name\"> $label</label>";
      }
      $items = $systypes_array;
      // exclude quotes, orders and dimensions
      foreach (array(ST_PURCHORDER, ST_WORKORDER, ST_SALESORDER, ST_DIMENSION, ST_SALESQUOTE) as $excl) {
        unset($items[$excl]);
      }
      echo array_selector($name, $value, $items, array(
                                                      'spec_option'   => _("All"),
                                                      'spec_id'       => -1,
                                                      'select_submit' => $submit_on_change,
                                                      'async'         => false
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
    public static function write($order, $reverse, $use_transaction = true)
    {
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
    public static function void($type, $type_no, $use_transaction = true)
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

