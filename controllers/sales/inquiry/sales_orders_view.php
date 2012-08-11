<?php
  use ADV\App\Debtor\Debtor;
  use ADV\Core\Ajax;
  use ADV\App\Item\Item;

  /* * ********************************************************************
Copyright (C) Advanced Group PTY LTD
Released under the terms of the GNU General Public License, GPL,
as published by the Free Software Foundation, either version 3
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
* ********************************************************************* */
  // first check is this is not start page call
  class SalesOrderInquiry extends \ADV\App\Controller\Base
  {

    protected $security;
    protected $trans_type;
    protected $debtor_id;
    protected $stock_id;
    protected $searchArray = [];
    protected function before() {
      $this->setSecurity();
      // then check session value
      JS::openWindow(950, 600);
      if (AJAX_REFERRER && !empty($_POST['q'])) {
        $this->searchArray = explode(' ', $_POST['q']);
      }
      if ($this->searchArray && $this->searchArray[0] == 'o') {
        $this->trans_type = ST_SALESORDER;
      } elseif ($this->searchArray && $this->searchArray[0] == 'q') {
        $this->trans_type = ST_SALESQUOTE;
      } elseif ($this->searchArray) {
        $this->trans_type = ST_SALESORDER;
      } elseif (Input::post('type')) {
        $this->trans_type = $_POST['type'];
      } elseif (isset($_GET['type']) && ($_GET['type'] == ST_SALESQUOTE)) {
        $this->trans_type = ST_SALESQUOTE;
      } else {
        $this->trans_type = ST_SALESORDER;
      }
      if ($this->trans_type == ST_SALESORDER) {
        if (Input::get('OutstandingOnly')) {
          $_POST['order_view_mode'] = 'OutstandingOnly';
          $this->setTitle("Search Outstanding Sales Orders");
        } elseif ($this->Input->get('InvoiceTemplates')) {
          $_POST['order_view_mode'] = 'InvoiceTemplates';
          $this->setTitle("Search Template for Invoicing");
        } elseif ($this->Input->get('DeliveryTemplates')) {
          $_POST['order_view_mode'] = 'DeliveryTemplates';
          $this->setTitle("Select Template for Delivery");
        } elseif (!isset($_POST['order_view_mode'])) {
          $_POST['order_view_mode'] = false;
          $this->setTitle("Search All Sales Orders");
        }
      } else {
        $_POST['order_view_mode'] = "Quotations";
        $this->setTitle("Search All Sales Quotations");
      }
      $this->debtor_id = Input::getPost('debtor_id', Input::NUMERIC, -1);
      if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
      ) {
        $this->stock_id = $_POST['SelectStockFromList'];
      }
      $id = Forms::findPostPrefix('_chgtpl');
      if ($id != -1) {
        $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
        DB::query($sql, "Can't change sales order type");
        Ajax::activate('orders_tbl');
      }
      if (isset($_POST['Update']) && isset($_POST['last'])) {
        foreach ($_POST['last'] as $id => $value) {
          if ($value != Input::hasPost('chgtpl' . $id)) {
            $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
            DB::query($sql, "Can't change sales order type");
            Ajax::activate('orders_tbl');
          }
        }
      }
      //	Order range form
      //
      if (Input::post('_OrderNumber_changed')) { // enable/disable selection controls
        $disable = Input::post('OrderNumber') !== '';
        if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
          Ajax::addDisable(true, 'OrdersAfterDate', $disable);
          Ajax::addDisable(true, 'OrdersToDate', $disable);
        }
        Ajax::addDisable(true, 'StockLocation', $disable);
        Ajax::addDisable(true, '_SelectStockFromList_edit', $disable);
        Ajax::addDisable(true, 'SelectStockFromList', $disable);
        if ($disable) {
          Ajax::addFocus(true, 'OrderNumber');
        } else {
          Ajax::addFocus(true, 'OrdersAfterDate');
        }
        Ajax::activate('orders_tbl');
      }
    }
    protected function setSecurity() {
      if (Input::get('OutstandingOnly') || Input::post('order_view_mode') == 'OutstandingOnly') {
        $this->security = SA_SALESDELIVERY;
      } elseif (Input::get('InvoiceTemplates') || Input::post('order_view_mode') == 'InvoiceTemplates') {
        $this->security = SA_SALESINVOICE;
      } else {
        $this->security = SA_SALESAREA;
      }
    }
    protected function index() {
      Page::start($this->title, $this->security);
      Forms::start();
      Table::start('tablestyle_noborder');
      Row::start();
      Debtor::cells(_(""), 'debtor_id', $this->debtor_id, true);
      Forms::refCellsSearch(_("#:"), 'OrderNumber', '', null, '', true);
      if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
        Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
        Forms::dateCells(_("To:"), 'OrdersToDate', '', null, 1);
      }
      Inv_Location::cells(_(""), 'StockLocation', null, true);
      Item::cells(_("Item:"), 'SelectStockFromList', null, true);
      if ($this->trans_type == ST_SALESQUOTE) {
        Forms::checkCells(_("Show All:"), 'show_all');
      }
      Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
      Row::end();
      Table::end(1);
      Forms::hidden('order_view_mode', $_POST['order_view_mode']);
      Forms::hidden('type', $this->trans_type);
      $this->displayTable($this->searchArray);
      UI::emailDialogue(CT_CUSTOMER);
      Forms::submitCenter('Update', _("Update"), true, '', null);
      Forms::end();
      Page::end();
    }
    protected function displayTable() { //	Orders inquiry table
      //
      $sql
        = "SELECT
 		sorder.trans_type,
 		sorder.order_no,
 		sorder.reference," . ($_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates' ?
        "sorder.comments, " : "sorder.customer_ref, ") . "
 		sorder.ord_date,
 		sorder.delivery_date,
 		debtor.name,
 		debtor.debtor_id,
 		branch.br_name,
 		sorder.deliver_to,
 		Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,
 		sorder.type,
 		debtor.curr_code,
 		Sum(line.qty_sent) AS TotDelivered,
 		Sum(line.quantity) AS TotQuantity
 	FROM sales_orders as sorder, sales_order_details as line, debtors as debtor, branches as branch
 		WHERE sorder.order_no = line.order_no
 		AND sorder.trans_type = line.trans_type";
      if ($this->searchArray[0] == 'o') {
        $sql .= " AND sorder.trans_type = 30 ";
      } elseif ($this->searchArray[0] == 'q') {
        $sql .= " AND sorder.trans_type = " . ST_SALESQUOTE . " ";
      } elseif ($this->searchArray) {
        $sql .= " AND ( sorder.trans_type = 30 OR sorder.trans_type = " . ST_SALESQUOTE . ") ";
      } else {
        $sql .= " AND sorder.trans_type = " . $this->trans_type;
      }
      $sql
        .= " AND sorder.debtor_id = debtor.debtor_id
 		AND sorder.branch_id = branch.branch_id
 		AND debtor.debtor_id = branch.debtor_id";
      if ($this->debtor_id != -1) {
        $sql .= " AND sorder.debtor_id = " . DB::quote($this->debtor_id);
      }
      if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
        // search orders with number like
        $number_like = "%" . $_POST['OrderNumber'];
        $sql .= " AND sorder.order_no LIKE " . DB::quote($number_like) . " GROUP BY sorder.order_no";
        $number_like = "%" . $_POST['OrderNumber'] . "%";
        $sql .= " OR sorder.reference LIKE " . DB::quote($number_like) . " GROUP BY sorder.order_no";
      } elseif (AJAX_REFERRER && isset($this->searchArray) && !empty($_POST['q'])) {
        foreach ($this->searchArray as $quicksearch) {
          if (empty($quicksearch)) {
            continue;
          }
          $quicksearch = DB::quote("%" . trim($quicksearch) . "%");
          $sql
            .= " AND ( debtor.debtor_id = $quicksearch OR debtor.name LIKE $quicksearch OR sorder.order_no LIKE $quicksearch
 			OR sorder.reference LIKE $quicksearch OR sorder.contact_name LIKE $quicksearch
 			OR sorder.customer_ref LIKE $quicksearch
 			 OR sorder.customer_ref LIKE $quicksearch OR branch.br_name LIKE $quicksearch)";
        }
        $sql
          .= " GROUP BY sorder.ord_date,
 				 sorder.order_no,
 				sorder.debtor_id,
 				sorder.branch_id,
 				sorder.customer_ref,
 				sorder.deliver_to";
      } else { // ... or select inquiry constraints
        if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates' && !isset($_POST['q'])
        ) {
          $date_after  = Dates::dateToSql($_POST['OrdersAfterDate']);
          $date_before = Dates::dateToSql($_POST['OrdersToDate']);
          $sql .= " AND sorder.ord_date >= '$date_after' AND sorder.ord_date <= '$date_before'";
        }
        if ($this->trans_type == 32 && !Input::hasPost('show_all')) {
          $sql .= " AND sorder.delivery_date >= '" . Dates::today(true) . "'";
        }
        if ($this->debtor_id != -1) {
          $sql .= " AND sorder.debtor_id=" . DB::quote($this->debtor_id);
        }
        if ($this->stock_id) {
          $sql .= " AND line.stk_code=" . DB::quote($this->stock_id);
        }
        if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
          $sql .= " AND sorder.from_stk_loc = " . DB::quote($_POST['StockLocation']);
        }
        if ($_POST['order_view_mode'] == 'OutstandingOnly') {
          $sql .= " AND line.qty_sent < line.quantity";
        } elseif ($_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates'
        ) {
          $sql .= " AND sorder.type=1";
        }
        $sql
          .= " GROUP BY sorder.ord_date,
 sorder.order_no,
 				sorder.debtor_id,
 				sorder.branch_id,
 				sorder.customer_ref,
 				sorder.deliver_to";
      }
      $ord = null;
      if ($this->trans_type == ST_SALESORDER) {
        $ord  = "Order #";
        $cols = array(
          array('type' => 'skip'),
          _("Order #")  => array('fun' => [$this, 'formatRef'], 'ord' => ''), //
          _("Ref")      => array('ord' => ''), //
          _("PO#")      => array('ord' => ''), //
          _("Date")     => array('type' => 'date', 'ord' => 'asc'), //
          _("Required") => array('type' => 'date', 'ord' => ''), //
          _("Customer") => array('ord' => 'asc'), //
          array('type' => 'skip'),
          _("Branch")   => array('ord' => ''), //
          _("Address"),
          _("Total")    => array('type' => 'amount', 'ord' => ''),
        );
      } else {
        $ord  = "Quote #";
        $cols = array(
          array('type' => 'skip'), //
          _("Quote #")     => array('fun' => [$this, 'formatRef'], 'ord' => ''), //
          _("Ref")         => array('ord' => ''), //
          _("PO#")         => array('type' => 'skip'), //
          _("Date")        => array('type' => 'date', 'ord' => 'desc'), //
          _("Valid until") => array('type' => 'date', 'ord' => ''), //
          _("Customer")    => array('ord' => 'asc'),
          array('type' => 'skip'), //
          _("Branch")      => array('ord' => ''), //
          _("Delivery To"), //
          _("Total")       => array('type' => 'amount', 'ord' => ''), //
        );
      }
      if ($_POST['order_view_mode'] == 'OutstandingOnly') {
        Arr::append($cols, array(['type' => 'skip'], array('fun' => [$this, 'formatDeliveryBtn'])));
      } elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
        Arr::substitute($cols, 3, 1, _("Description"));
        Arr::append($cols, array(array('insert' => true, 'fun' => [$this, 'formatInvoiceBtn'])));
      } else {
        if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
          Arr::substitute($cols, 3, 1, _("Description"));
          Arr::append($cols, array(array('insert' => true, 'fun' => [$this, 'formatDeliveryBtn2'])));
        } elseif ($this->trans_type == ST_SALESQUOTE || $this->trans_type == ST_SALESORDER) {
          Arr::append($cols, array(
            array('insert' => true, 'fun' => [$this, 'formatOrderBtn']), //
            array('insert' => true, 'fun' => [$this, 'formatDropdown']) //
          ));
        }
      }
      $table = DB_Pager::new_db_pager('orders_tbl', $sql, $cols, null, null, 0, 4);
      $table->setMarker([$this, 'formatMarker'], _("Marked items are overdue."));
      $table->width = "80%";
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    function formatMarker($row) {
      if ($this->trans_type == ST_SALESQUOTE) {
        return (Dates::isGreaterThan(Dates::today(), Dates::sqlToDate($row['delivery_date'])));
      } else {
        return ($row['type'] == 0 && Dates::isGreaterThan(Dates::today(), Dates::sqlToDate($row['delivery_date'])) && ($row['TotDelivered'] < $row['TotQuantity']));
      }
    }
    /**
     * @param $row
     * @param $order_no
     *
     * @return null|string
     */
    function formatRef($row, $order_no) {
      return Debtor::viewTrans($row['trans_type'], $order_no);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatDeliveryBtn($row) {
      if ($row['trans_type'] == ST_SALESORDER) {
        return DB_Pager::link(_("Dispatch"), "/sales/customer_delivery.php?OrderNumber=" . $row['order_no'], ICON_DOC);
      } else {
        return DB_Pager::link(_("Sales Order"), "/sales/sales_order_entry.php?OrderNumber=" . $row['order_no'], ICON_DOC);
      }
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatInvoiceBtn($row) {
      if ($row['trans_type'] == ST_SALESORDER) {
        return DB_Pager::link(_("Invoice"), "/sales/sales_order_entry.php?NewInvoice=" . $row["order_no"], ICON_DOC);
      } else {
        return '';
      }
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatDeliveryVBtn2($row) {
      return DB_Pager::link(_("Delivery"), "/sales/sales_order_entry.php?NewDelivery=" . $row['order_no'], ICON_DOC);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatOrderBtn($row) {
      if ($row['trans_type'] == ST_SALESQUOTE) {
        return DB_Pager::link(_("Create Order"), "/sales/sales_order_entry?QuoteToOrder=" . $row['order_no'], ICON_DOC);
      }
      $name  = "chgtpl" . $row['order_no'];
      $value = $row['type'] ? 1 : 0;
      // save also in hidden field for testing during 'Update'
      return Forms::checkbox(null, $name, $value, true, _('Set this order as a template for direct deliveries/invoices')) . Forms::hidden('last[' . $row
      ['order_no'] . ']', $value, false);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatEditBtn($row) {
      return DB_Pager::link(_("Edit"), "/sales/sales_order_entry?update=" . $row['order_no'] . "&type=" . $row['trans_type'], ICON_EDIT);
    }
    /**
     * @param $row
     *
     * @return ADV\Core\HTML|string
     */
    function formatEmailBtn($row) {
      return Reporting::emailDialogue($row['debtor_id'], $row['trans_type'], $row['order_no']);
    }
    /**
     * @param $row
     *
     * @return string
     */
    function formatProformaBtn($row) {
      return Reporting::print_doc_link($row['order_no'], _("Proforma"), true, ($row['trans_type'] == ST_SALESORDER ?
        ST_PROFORMA : ST_PROFORMAQ), ICON_PRINT, 'button printlink');
    }
    /**
     *
     * @param $row
     *
     * @return string
     */
    function formatPrintBtn($row) {
      return Reporting::print_doc_link($row['order_no'], _("Print"), true, $row['trans_type'], ICON_PRINT, 'button printlink');
    }
    function formatDropdown($row) {
      $dropdown = new View('ui/dropdown');
      $title    = 'Menu';
      $items[]  = ['label'=> 'Edit', 'href'=> '/sales/sales_order_entry?update=' . $row['order_no'] . "&type=" . $row['trans_type']];
      $items[]  = ['class'=> 'email-button', 'label'=> 'Email', 'href'=> '#', 'data'=> ['emailid' => $row['debtor_id'] . '-' . $row['trans_type'] . '-' . $row['order_no']]];
      $href = Reporting::print_doc_link($row['order_no'], _("Proforma"), true, ($row['trans_type'] == ST_SALESORDER ?ST_PROFORMA : ST_PROFORMAQ), ICON_PRINT, 'button printlink','',0,0,true);
      $items[]  = ['class'=> 'printlink', 'label'=> 'Proforma', 'href'=> $href];
      $href = Reporting::print_doc_link($row['order_no'], _("Print"), true, $row['trans_type'], ICON_PRINT, 'button printlink','',0,0,true);
      $items[]  = ['class'=> 'printlink', 'label'=> 'Print', 'href'=> $href];
      $menus[] = ['title'=> $title, 'items'=> $items];
      $dropdown->set('menus', $menus);
      return $dropdown->render(true);
    }
  }

  new SalesOrderInquiry();
