<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Bank_UI
  {
    /**
     * @static
     *
     * @param $order
     */
    public static function header($order)
    {
      $payment = $order->trans_type == ST_BANKPAYMENT;
      Display::div_start('pmt_header');
      Table::startOuter('tablestyle2 width90'); // outer table
      Table::section(1);
      Bank_Account::row($payment ? _("From:") : _("To:"), 'bank_account', null, true);
       Form::dateRow(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
      Table::section(2, "33%");
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
        Ajax::i()->activate('pmt_header');
        Ajax::i()->activate('code_id');
        Ajax::i()->activate('pagehelp');
        Ajax::i()->activate('editors');
      }
      GL_UI::payment_person_type_row($payment ? _("Pay To:") : _("From:"), 'PayType', $_POST['PayType'], true);
      switch ($_POST['PayType']) {
        case PT_MISC :
           Form::textRowEx($payment ? _("To the Order of:") : _("Name:"), 'person_id', 40, 50);
          break;
        //case PT_WORKORDER :
        //	workorders_list_row(_("Work Order:"), 'person_id', null);
        //	break;
        case PT_SUPPLIER :
          Creditor::row(_("Supplier:"), 'person_id', null, false, true, false, true);
          break;
        case PT_CUSTOMER :
          Debtor::row(_("Customer:"), 'person_id', null, false, true, false, true);
          if (Input::post('person_id') && Validation::check(Validation::BRANCHES, _("No Branches for Customer"), $_POST['person_id'])
          ) {
            Debtor_Branch::row(_("Branch:"), $_POST['person_id'], 'PersonDetailID', null, false, true, true, true);
          } else {
            $_POST['PersonDetailID'] = ANY_NUMERIC;
            Form::hidden('PersonDetailID');
          }
          break;
        case PT_QUICKENTRY :
          GL_QuickEntry::row(_("Type") . ":", 'person_id', null, ($payment ? QE_PAYMENT : QE_DEPOSIT), true);
          $qid = GL_QuickEntry::get(Form::getPost('person_id'));
          if (Form::isListUpdated('person_id')) {
            unset($_POST['total_amount']); // enable default
            Ajax::i()->activate('total_amount');
          }
           Form::AmountRow($qid['base_desc'] . ":", 'total_amount', Num::price_format($qid['base_amount']), null, "&nbsp;&nbsp;" . Form::submit('go', _("Go"), false, false, true));
          break;
        //case payment_person_types::Project() :
        //	Dimensions::select_row(_("Dimension:"), 'person_id', $_POST['person_id'], false, null, true);
        //	break;
      }
      $person_currency = Bank_Currency::for_payment_person($_POST['PayType'], $_POST['person_id']);
      $bank_currency   = Bank_Currency::for_company($_POST['bank_account']);
      GL_ExchangeRate::display($bank_currency, $person_currency, $_POST['date_']);
      Table::section(3, "33%");
      if (isset($_GET['NewPayment'])) {
         Form::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKPAYMENT));
      } else {
         Form::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKDEPOSIT));
      }
      Table::endOuter(1); // outer table
      Display::div_end();
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    public static function items($title, &$order)
    {
      $dim     = DB_Company::get_pref('use_dimension');
      $colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
      Display::heading($title);
      Display::div_start('items_table');
      Table::start('tables_style grid width95');
      if ($dim == 2) {
        $th = array(
          _("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Amount"), _("Memo"), ""
        );
      } else {
        if ($dim == 1) {
          $th = array(
            _("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo"), ""
          );
        } else {
          $th = array(
            _("Account Code"), _("Account Description"), _("Amount"), _("Memo"), ""
          );
        }
      }
      if (count($order->gl_items)) {
        $th[] = '';
      }
      Table::header($th);
      $k  = 0; //row colour counter
      $id = Form::findPostPrefix(MODE_EDIT);
      foreach ($order->gl_items as $line => $item) {
        if ($id != $line) {
          Cell::label($item->code_id);
          Cell::label($item->description);
          if ($dim >= 1) {
            Cell::label(Dimensions::get_string($item->dimension_id, true));
          }
          if ($dim > 1) {
            Cell::label(Dimensions::get_string($item->dimension2_id, true));
          }
          //Cell::amount(abs($item->amount));
          if ($order->trans_type == ST_BANKDEPOSIT) {
            Cell::amount(-$item->amount);
          } else {
            Cell::amount($item->amount);
          }
          Cell::label($item->reference);
          Form::buttonEditCell("Edit$line", _("Edit"), _('Edit document line'));
          Form::buttonDeleteCell("Delete$line", _("Delete"), _('Remove line from document'));
          Row::end();
        } else {
          Bank_UI::item_controls($order, $dim, $line);
        }
      }
      if ($id == -1) {
        Bank_UI::item_controls($order, $dim);
      }
      if ($order->count_gl_items()) {
        Row::label(_("Total"), Num::format(abs($order->gl_items_total()), User::price_dec()), "colspan=" . $colspan . " class='right'", "class='right'", 3);
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
      $payment = $order->trans_type == ST_BANKPAYMENT;
      Row::start();
      $id = Form::findPostPrefix(MODE_EDIT);
      if ($Index != -1 && $Index == $id) {
        $item                   = $order->gl_items[$Index];
        $_POST['code_id']       = $item->code_id;
        $_POST['dimension_id']  = $item->dimension_id;
        $_POST['dimension2_id'] = $item->dimension2_id;
        $_POST['amount']        = Num::price_format(abs($item->amount));
        $_POST['description']   = $item->description;
        $_POST['LineMemo']      = $item->reference;
        Form::hidden('Index', $id);
        echo GL_UI::all('code_id', null, true, true);
        if ($dim >= 1) {
          Dimensions::cells(null, 'dimension_id', null, true, " ", false, 1);
        }
        if ($dim > 1) {
          Dimensions::cells(null, 'dimension2_id', null, true, " ", false, 2);
        }
        Ajax::i()->activate('items_table');
      } else {
        $_POST['amount']        = Num::price_format(0);
        $_POST['dimension_id']  = 0;
        $_POST['dimension2_id'] = 0;
        //$_POST['LineMemo'] = ""; // let memo go to next line Joe Hunt 2010-05-30
        if (isset($_POST['_code_id_update'])) {
          Ajax::i()->activate('code_id');
        }
        if ($_POST['PayType'] == PT_CUSTOMER) {
          $acc              = Sales_Branch::get_accounts($_POST['PersonDetailID']);
          $_POST['code_id'] = $acc['receivables_account'];
        } elseif ($_POST['PayType'] == PT_SUPPLIER) {
          $acc              = Creditor::get_accounts_name($_POST['person_id']);
          $_POST['code_id'] = $acc['payable_account'];
        } //elseif ($_POST['PayType'] == PT_WORKORDER)
        //	$_POST['code_id'] = DB_Company::get_pref('default_assembly_act');
        else {
          $_POST['code_id'] = DB_Company::get_pref($payment ? 'default_cogs_act' : 'default_inv_sales_act');
        }
        echo GL_UI::all('code_id', null, true, true);
        if ($dim >= 1) {
          Dimensions::cells(null, 'dimension_id', null, true, " ", false, 1);
        }
        if ($dim > 1) {
          Dimensions::cells(null, 'dimension2_id', null, true, " ", false, 2);
        }
      }
      if ($dim < 1) {
        Form::hidden('dimension_id', 0);
      }
      if ($dim < 2) {
        Form::hidden('dimension2_id', 0);
      }
       Form::amountCells(null, 'amount');
       Form::textCellsEx(null, 'LineMemo', 35, 255);
      if ($id != -1) {
        Form::buttonCell('updateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        Form::buttonCell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        JS::set_focus('amount');
      } else {
        Form::submitCells('addLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      Row::end();
    }
    public static function option_controls()
    {
      echo "<br><table class='center'>";
       Form::textareaRow(_("Memo"), 'memo_', null, 50, 3);
      echo "</table>";
    }
    /**
     * @static
     *
     * @param      $account
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     *
     * @return string
     */
    public static function  reconcile($account, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
    {
      $sql
        = "SELECT reconciled FROM bank_trans
                            WHERE bank_act=" . DB::escape($account) . " AND reconciled IS NOT null AND amount!=0
                            GROUP BY reconciled";

      return Form::selectBox($name, $selected_id, $sql, 'id', 'reconciled', array(
                                                                            'spec_option'   => $special_option,
                                                                            'format'        => '_format_date',
                                                                            'spec_id'       => '',
                                                                            'select_submit' => $submit_on_change,
                                                                            'order'         => 'reconciled DESC'
                                                                       ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $account
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     */
    public static function  reconcile_cells($label, $account, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Bank_UI::reconcile($account, $name, $selected_id, $submit_on_change, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param        $bank_acc
     * @param string $parms
     */
    public static function  balance_row($bank_acc, $parms = '')
    {
      $to  = Dates::add_days(Dates::today(), 1);
      $bal = get_balance_before_for_bank_account($bank_acc, $to);
      Row::label(_("Bank Balance:"), "<a target='_blank' " . ($bal < 0 ? 'class="redfg openWindow"' :
        '') . "href='/gl/inquiry/bank.php?bank_account=" . $bank_acc . "'" . " >&nbsp;" . Num::price_format($bal) . "</a>", $parms);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    public static function  cash_accounts_row($label, $name, $selected_id = null, $submit_on_change = false)
    {
      $sql
        = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
                        FROM bank_accounts
                        WHERE bank_accounts.account_type=3";
      if ($label != null) {
        echo "<tr><td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo Form::selectBox($name, $selected_id, $sql, 'id', 'bank_account_name', array(
                                                                                 'format'        => '_format_add_curr',
                                                                                 'select_submit' => $submit_on_change,
                                                                                 'async'         => true
                                                                            ));
      echo "</td></tr>\n";
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
    public static function  trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
    {
      if ($label == "") {
        $label = $trans_no;
      }
      switch ($type) {
        case ST_BANKTRANSFER:
          $viewer = "bank_transfer.php";
          break;
        case ST_BANKPAYMENT:
          $viewer = "gl_payment.php";
          break;
        case ST_BANKDEPOSIT:
          $viewer = "gl_deposit.php";
          break;
        default:
          return null;
      }

      return Display::viewer_link($label, "gl/view/$viewer?trans_no=$trans_no", $class, $id, $icon);
    }
  }

