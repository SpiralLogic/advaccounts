<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Inv_Transfer {

    /**
     * @static
     *
     * @param $Items
     * @param $location_from
     * @param $location_to
     * @param $date_
     * @param $type
     * @param $reference
     * @param $memo_
     *
     * @return int
     */
    public static function add($Items, $location_from, $location_to, $date_, $type, $reference, $memo_) {
      DB::begin();
      $transfer_id = SysTypes::get_next_trans_no(ST_LOCTRANSFER);
      foreach ($Items as $line_item) {
        Inv_Transfer::add_item($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $type, $reference,
          $line_item->quantity);
      }
      DB_Comments::add(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);
      Ref::save(ST_LOCTRANSFER, $reference);
      DB_AuditTrail::add(ST_LOCTRANSFER, $transfer_id, $date_);
      DB::commit();
      return $transfer_id;
    }

    /***
     * @static
     *
     * @param $transfer_id
     * @param $stock_id
     * @param $location_from
     * @param $location_to
     * @param $date_ is display date (not sql)
     * @param $type
     * @param $reference
     * @param $quantity
     *               add 2 stock_moves entries for a stock transfer

     */
    public static function add_item($transfer_id, $stock_id, $location_from, $location_to, $date_, $type, $reference, $quantity) {
      Inv_Movement::add(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from, $date_, $reference, -$quantity, 0, $type);
      Inv_Movement::add(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0, $type);
    }
    /**
     * @static
     *
     * @param $trans_no
     *
     * @return array
     */
    public static function get($trans_no) {
      $result = Inv_Transfer::get_items($trans_no);
      if (DB::num_rows($result) < 2) {
        Errors::db_error("transfer with less than 2 items : $trans_no", "");
      }
      // this public static function is very bad that it assumes that 1st record and 2nd record contain the
      // from and to locations - if get_stock_moves uses a different ordering than trans_no then
      // it will bomb
      $move1 = DB::fetch($result);
      $move2 = DB::fetch($result);
      // return an array of (From, To)
      if ($move1['qty'] < 0) {
        return array($move1, $move2);
      }
      else {
        return array($move2, $move1);
      }
    }
    /**
     * @static
     *
     * @param $trans_no
     *
     * @return null|PDOStatement
     */
    public static function get_items($trans_no) {
      $result = Inv_Movement::get(ST_LOCTRANSFER, $trans_no);
      if (DB::num_rows($result) == 0) {
        return NULL;
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      Inv_Movement::void($type, $type_no);
    }
    /**
     * @static
     *
     * @param $type
     * @param $stock_id
     * @param $from
     * @param $to
     * @param $pid
     * @param $cost
     */
    public static function update_pid($type, $stock_id, $from, $to, $pid, $cost) {
      $from = Dates::date2sql($from);
      $to   = Dates::date2sql($to);
      $sql  = "UPDATE stock_moves SET standard_cost=" . DB::escape($cost) . " WHERE type=" . DB::escape($type) . "	AND stock_id=" . DB::escape($stock_id) . " AND tran_date>='$from' AND tran_date<='$to'
				AND person_id = " . DB::escape($pid);
      DB::query($sql, "The stock movement standard_cost cannot be updated");
    }

    public static function header() {
      Table::startOuter('tablestyle width70');
      Table::section(1);
      Inv_Location::row(_("From Location:"), 'FromStockLocation', NULL);
      Inv_Location::row(_("To Location:"), 'ToStockLocation', NULL);
      Table::section(2, "33%");
      ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_LOCTRANSFER));
      date_row(_("Date:"), 'AdjDate', '', TRUE);
      Table::section(3, "33%");
      Inv_Movement::row(_("Transfer Type:"), 'type', NULL);
      Table::endOuter(1); // outer table
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    public static function display_items($title, $order) {
      Display::heading($title);
      Display::div_start('items_table');
      Table::start('tablestyle grid width90');
      $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), '');
      if (count($order->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      $k  = 0; //row colour counter
      $id = find_submit(MODE_EDIT);
      foreach ($order->line_items as $line_no => $stock_item) {
        if ($id != $line_no) {

          Item_UI::status_cell($stock_item->stock_id);
          Cell::label($stock_item->description);
          Cell::qty($stock_item->quantity, FALSE, Item::qty_dec($stock_item->stock_id));
          Cell::label($stock_item->units);
          edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
          delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
          Row::end();
        }
        else {
          Inv_Transfer::item_controls($order, $line_no);
        }
      }
      if ($id == -1) {
        Inv_Transfer::item_controls($order);
      }
      Table::end();
      Display::div_end();
    }
    /**
     * @static
     *
     * @param $order
     * @param $line_no
     */
    public static function item_controls($order, $line_no = -1) {

      Row::start();
      $id = find_submit(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $_POST['qty']      = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
        $_POST['units']    = $order->line_items[$id]->units;
        hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id']);
        Cell::label($order->line_items[$id]->description);
        Ajax::i()->activate('items_table');
      }
      else {
        Item_UI::costable_cells(NULL, 'stock_id', NULL, FALSE, TRUE);
        if (list_updated('stock_id')) {
          Ajax::i()->activate('units');
          Ajax::i()->activate('qty');
        }
        $item_info      = Item::get_edit_info(Input::post('stock_id'));
        $dec            = $item_info['decimals'];
        $_POST['qty']   = Num::format(0, $dec);
        $_POST['units'] = $item_info["units"];
      }
      small_qty_cells(NULL, 'qty', $_POST['qty'], NULL, NULL, $dec);
      Cell::label($_POST['units'], '', 'units');
      if ($id != -1) {
        button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        hidden('LineNo', $line_no);
        JS::set_focus('qty');
      }
      else {
        submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), TRUE);
      }
      Row::end();
    }

    public static function option_controls() {
      echo "<br>";
      Table::start();
      textarea_row(_("Memo"), 'memo_', NULL, 50, 3);
      Table::end(1);
    }
  }
