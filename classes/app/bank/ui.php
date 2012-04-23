<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Bank_UI {

    /**
     * @static
     *
     * @param $order
     */
    static public function header($order) {

      $payment = $order->trans_type == ST_BANKPAYMENT;
      Display::div_start('pmt_header');
      start_outer_table('tablestyle2 width90'); // outer table
      table_section(1);
      Bank_Account::row($payment ? _("From:") : _("To:"), 'bank_account', NULL, TRUE);
      date_row(_("Date:"), 'date_', '', TRUE, 0, 0, 0, NULL, TRUE);
      table_section(2, "33%");
      if (!isset($_POST['PayType'])) {
        if (isset($_GET['PayType'])) {
          $_POST['PayType'] = $_GET['PayType'];
        }
        else {
          $_POST['PayType'] = "";
        }
      }
      if (!isset($_POST['person_id'])) {
        if (isset($_GET['PayPerson'])) {
          $_POST['person_id'] = $_GET['PayPerson'];
        }
        else {
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
      GL_UI::payment_person_type_row($payment ? _("Pay To:") : _("From:"), 'PayType', $_POST['PayType'], TRUE);
      switch ($_POST['PayType']) {
        case PT_MISC :
          text_row_ex($payment ? _("To the Order of:") : _("Name:"), 'person_id', 40, 50);
          break;
        //case PT_WORKORDER :
        //	workorders_list_row(_("Work Order:"), 'person_id', null);
        //	break;
        case PT_SUPPLIER :
          Creditor::row(_("Supplier:"), 'person_id', NULL, FALSE, TRUE, FALSE, TRUE);
          break;
        case PT_CUSTOMER :
          Debtor::row(_("Customer:"), 'person_id', NULL, FALSE, TRUE, FALSE, TRUE);
          if (Input::post('person_id') && Validation::check(Validation::BRANCHES, _("No Branches for Customer"),
            $_POST['person_id'])
          ) {
            Debtor_Branch::row(_("Branch:"), $_POST['person_id'], 'PersonDetailID', NULL, FALSE, TRUE, TRUE, TRUE);
          }
          else {
            $_POST['PersonDetailID'] = ANY_NUMERIC;
            hidden('PersonDetailID');
          }
          break;
        case PT_QUICKENTRY :
          GL_QuickEntry::row(_("Type") . ":", 'person_id', NULL, ($payment ? QE_PAYMENT : QE_DEPOSIT), TRUE);
          $qid = GL_QuickEntry::get(get_post('person_id'));
          if (list_updated('person_id')) {
            unset($_POST['total_amount']); // enable default
            Ajax::i()->activate('total_amount');
          }
          amount_row($qid['base_desc'] . ":", 'total_amount', Num::price_format($qid['base_amount']), NULL,
            "&nbsp;&nbsp;" . submit('go', _("Go"), FALSE, FALSE, TRUE));
          break;
        //case payment_person_types::Project() :
        //	Dimensions::select_row(_("Dimension:"), 'person_id', $_POST['person_id'], false, null, true);
        //	break;
      }
      $person_currency = Bank_Currency::for_payment_person($_POST['PayType'], $_POST['person_id']);
      $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
      GL_ExchangeRate::display($bank_currency, $person_currency, $_POST['date_']);
      table_section(3, "33%");
      if (isset($_GET['NewPayment'])) {
        ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKPAYMENT));
      }
      else {
        ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKDEPOSIT));
      }
      end_outer_table(1); // outer table
      Display::div_end();
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    static public function items($title, &$order) {
      $dim = DB_Company::get_pref('use_dimension');
      $colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
      Display::heading($title);
      Display::div_start('items_table');
      start_table('tables_style width95');
      if ($dim == 2) {
        $th = array(
          _("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Amount"), _("Memo"), ""
        );
      }
      else {
        if ($dim == 1) {
          $th = array(
            _("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo"), ""
          );
        }
        else {
          $th = array(
            _("Account Code"), _("Account Description"), _("Amount"), _("Memo"), ""
          );
        }
      }
      if (count($order->gl_items)) {
        $th[] = '';
      }
      table_header($th);
      $k = 0; //row colour counter
      $id = find_submit(MODE_EDIT);
      foreach ($order->gl_items as $line => $item) {
        if ($id != $line) {
          alt_table_row_color($k);
          label_cell($item->code_id);
          label_cell($item->description);
          if ($dim >= 1) {
            label_cell(Dimensions::get_string($item->dimension_id, TRUE));
          }
          if ($dim > 1) {
            label_cell(Dimensions::get_string($item->dimension2_id, TRUE));
          }
          //amount_cell(abs($item->amount));
          if ($order->trans_type == ST_BANKDEPOSIT) {
            amount_cell(-$item->amount);
          }
          else {
            amount_cell($item->amount);
          }
          label_cell($item->reference);
          edit_button_cell("Edit$line", _("Edit"), _('Edit document line'));
          delete_button_cell("Delete$line", _("Delete"), _('Remove line from document'));
          end_row();
        }
        else {
          Bank_UI::item_controls($order, $dim, $line);
        }
      }
      if ($id == -1) {
        Bank_UI::item_controls($order, $dim);
      }
      if ($order->count_gl_items()) {
        label_row(_("Total"), Num::format(abs($order->gl_items_total()), User::price_dec()),
          "colspan=" . $colspan . " class='right'", "class='right'", 3);
      }
      end_table();
      Display::div_end();
    }
    /**
     * @static
     *
     * @param      $order
     * @param      $dim
     * @param null $Index
     */
    static public function item_controls($order, $dim, $Index = NULL) {

      $payment = $order->trans_type == ST_BANKPAYMENT;
      start_row();
      $id = find_submit(MODE_EDIT);
      if ($Index != -1 && $Index == $id) {
        $item = $order->gl_items[$Index];
        $_POST['code_id'] = $item->code_id;
        $_POST['dimension_id'] = $item->dimension_id;
        $_POST['dimension2_id'] = $item->dimension2_id;
        $_POST['amount'] = Num::price_format(abs($item->amount));
        $_POST['description'] = $item->description;
        $_POST['LineMemo'] = $item->reference;
        hidden('Index', $id);
        echo GL_UI::all('code_id', NULL, TRUE, TRUE);
        if ($dim >= 1) {
          Dimensions::cells(NULL, 'dimension_id', NULL, TRUE, " ", FALSE, 1);
        }
        if ($dim > 1) {
          Dimensions::cells(NULL, 'dimension2_id', NULL, TRUE, " ", FALSE, 2);
        }
        Ajax::i()->activate('items_table');
      }
      else {
        $_POST['amount'] = Num::price_format(0);
        $_POST['dimension_id'] = 0;
        $_POST['dimension2_id'] = 0;
        //$_POST['LineMemo'] = ""; // let memo go to next line Joe Hunt 2010-05-30
        if (isset($_POST['_code_id_update'])) {
          Ajax::i()->activate('code_id');
        }
        if ($_POST['PayType'] == PT_CUSTOMER) {
          $acc = Sales_Branch::get_accounts($_POST['PersonDetailID']);
          $_POST['code_id'] = $acc['receivables_account'];
        }
        elseif ($_POST['PayType'] == PT_SUPPLIER) {
          $acc = Creditor::get_accounts_name($_POST['person_id']);
          $_POST['code_id'] = $acc['payable_account'];
        } //elseif ($_POST['PayType'] == PT_WORKORDER)
        //	$_POST['code_id'] = DB_Company::get_pref('default_assembly_act');
        else {
          $_POST['code_id'] = DB_Company::get_pref($payment ? 'default_cogs_act' : 'default_inv_sales_act');
        }
        echo GL_UI::all('code_id', NULL, TRUE, TRUE);
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
      amount_cells(NULL, 'amount');
      text_cells_ex(NULL, 'LineMemo', 35, 255);
      if ($id != -1) {
        button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        JS::set_focus('amount');
      }
      else {
        submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), TRUE);
      }
      end_row();
    }

    static public function option_controls() {
      echo "<br><table class='center'>";
      textarea_row(_("Memo"), 'memo_', NULL, 50, 3);
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
    static public function  reconcile($account, $name, $selected_id = NULL, $submit_on_change = FALSE, $special_option = FALSE) {
      $sql = "SELECT reconciled FROM bank_trans
							WHERE bank_act=" . DB::escape($account) . " AND reconciled IS NOT NULL AND amount!=0
							GROUP BY reconciled";
      return select_box($name, $selected_id, $sql, 'id', 'reconciled', array(
        'spec_option' => $special_option, 'format' => '_format_date', 'spec_id' => '', 'select_submit' => $submit_on_change, 'order' => 'reconciled DESC'
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
    static public function  reconcile_cells($label, $account, $name, $selected_id = NULL, $submit_on_change = FALSE, $special_option = FALSE) {
      if ($label != NULL) {
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
    static public function  balance_row($bank_acc, $parms = '') {
      $to = Dates::add_days(Dates::today(), 1);
      $bal = get_balance_before_for_bank_account($bank_acc, $to);
      label_row(_("Bank Balance:"), "<a target='_blank' " . ($bal < 0 ? 'class="redfg openWindow"' :
        '') . "href='/gl/inquiry/bank_inquiry.php?bank_account=" . $bank_acc . "'" . " >&nbsp;" . Num::price_format($bal) . "</a>", $parms);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function  cash_accounts_row($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      $sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
						FROM bank_accounts
						WHERE bank_accounts.account_type=3";
      if ($label != NULL) {
        echo "<tr><td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo select_box($name, $selected_id, $sql, 'id', 'bank_account_name', array(
        'format' => '_format_add_curr', 'select_submit' => $submit_on_change, 'async' => TRUE
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
    static public function  trans_view($type, $trans_no, $label = "", $icon = FALSE, $class = '', $id = '') {
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
          return NULL;
      }
      return Display::viewer_link($label, "gl/view/$viewer?trans_no=$trans_no", $class, $id, $icon);
    }
  }


