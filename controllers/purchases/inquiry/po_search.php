<?php
  use ADV\Core\Input\Input;
  use ADV\Core\JS;
  use ADV\Core\DB\DB;
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Row;
  use ADV\Core\Table;
  use ADV\Core\Ajax;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class POSearch extends \ADV\App\Controller\Base
  {
    protected $order_no;
    protected $creditor_id;
    protected $selected_stock_item;
    protected $stock_location;
    protected function before() {
      JS::_openWindow(950, 500);
      $_POST['order_number']     = Input::_getPost('order_number', Input::NUMERIC);
      $this->order_no            =& $_POST['order_number'];
      $this->creditor_id         = Input::_getPost('creditor_id', Input::NUMERIC, 0);
      $this->stock_location      = Input::_getPost('StockLocation', Input::STRING, '');
      $this->selected_stock_item = Input::_getPost('SelectStockFromList', Input::STRING, '');
      if (Input::_post('SearchOrders')) {
        Ajax::_activate('orders_tbl');
      }
      if ($this->order_no) {
        Ajax::_addFocus(true, 'order_number');
      } else {
        Ajax::_addFocus(true, 'OrdersAfterDate');
      }
      Ajax::_activate('orders_tbl');
    }
    protected function index() {
      Page::start(_($help_context = "Search Outstanding Purchase Orders"), SA_SUPPTRANSVIEW);
      // Ajax updates
      //
      Forms::start();
      Table::start('tablestyle_noborder');
      Row::start();
      Creditor::cells(_(""), 'creditor_id', Input::_post('creditor_id'), true);
      Forms::refCells(_("#:"), 'order_number');
      Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
      Forms::dateCells(_("To:"), 'OrdersToDate');
      Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
      //Item::cells(_("Item:"), 'SelectStockFromList', null, true,false,false,false,true);
      Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
      Row::end();
      Table::end();
      $this->makeTable();
      Creditor::addInfoDialog('.pagerclick');
      Forms::end();
      Page::end();
    }
    protected function makeTable() { //figure out the sql required from the inputs available
      $sql
        = "SELECT
 porder.order_no,
 porder.reference,
 supplier.name,
 supplier.creditor_id as id,
 location.location_name,
 porder.requisition_no,
 porder.ord_date,
 supplier.curr_code,
 Sum(line.unit_price*line.quantity_ordered) AS OrderValue,
 Sum(line.delivery_date < '" . Dates::_today(true) . "'
 AND (line.quantity_ordered > line.quantity_received)) As OverDue
 FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
 WHERE porder.order_no = line.order_no
 AND porder.creditor_id = supplier.creditor_id
 AND location.loc_code = porder.into_stock_location
 AND (line.quantity_ordered > line.quantity_received) ";
      if ($this->creditor_id) {
        $sql .= " AND supplier.creditor_id = " . DB::_quote($this->creditor_id);
      }
      if ($this->order_no) {
        $sql .= " AND (porder.order_no LIKE " . DB::_quote('%' . $this->order_no . '%');
        $sql .= " OR porder.reference LIKE " . DB::_quote('%' . $this->order_no . '%') . ') ';
      } else {
        $data_after  = Dates::_dateToSql($_POST['OrdersAfterDate']);
        $data_before = Dates::_dateToSql($_POST['OrdersToDate']);
        $sql .= " AND porder.ord_date >= '$data_after'";
        $sql .= " AND porder.ord_date <= '$data_before'";
        if ($this->stock_location) {
          $sql .= " AND porder.into_stock_location = " . DB::_quote($this->stock_location);
        }
        if ($this->selected_stock_item) {
          $sql .= " AND line.item_code=" . DB::_quote($this->selected_stock_item);
        }
      } //end not order number selected
      $sql .= " GROUP BY porder.order_no";
      DB::_query($sql, "No orders were returned");
      /*show a table of the orders returned by the sql */
      $cols = array(
        _("#")                                     => ['fun'     => [$this, 'formatTrans'], 'ord'     => ''], //
        _("Reference"), //
        _("Supplier")                              => ['ord' => '', 'type' => 'id'], //
        _("Supplier ID")                           => 'skip', //
        _("Location"), //
        _("Supplier's Reference"), //
        _("Order Date")                            => ['name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'], //
        _("Currency")                              => ['align' => 'center'], //
        _("Order Total")                           => 'amount', //
        ['insert' => true, 'fun' => [$this, 'formatEditBtn']], //
        ['insert' => true, 'fun' => [$this, 'formatPrintBtn']], //
        ['insert' => true, 'fun' => [$this, 'formatProcessBtn']]
        //
      );
      if (!$this->stock_location) {
        $cols[_("Location")] = 'skip';
      }
      $table = DB_Pager::new_db_pager('orders_tbl', $sql, $cols);
      $table->setMarker([$this, 'formatMarker'], _("Marked orders have overdue items."));
      $table->width = "85%";
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatMarker($row) {
      return $row['OverDue'] == 1;
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatProcessBtn($row) {
      return DB_Pager::link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatPrintBtn($row) {
      return Reporting::print_doc_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT, 'button printlink');
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatEditBtn($row) {
      return DB_Pager::link(_("Edit"), "/purchases/po_entry_items.php?ModifyOrder=" . $row["order_no"], ICON_EDIT);
    }
    /**
     * @param $row
     *
     * @return callable
     */
    public function formatTrans($row) {
      return GL_UI::viewTrans(ST_PURCHORDER, $row["order_no"]);
    }
  }

  new POSearch();
