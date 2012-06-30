<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Inv_Adjustment
  {
    /**
     * @static
     *
     * @param $items
     * @param $location
     * @param $date_
     * @param $type
     * @param $increase
     * @param $reference
     * @param $memo_
     *
     * @return int
     */
    public static function add($items, $location, $date_, $type, $increase, $reference, $memo_)
    {
      DB::begin();
      $adj_id = SysTypes::get_next_trans_no(ST_INVADJUST);
      foreach ($items as $line_item) {
        if (!$increase) {
          $line_item->quantity = -$line_item->quantity;
        }
        static::add_item($adj_id, $line_item->stock_id, $location, $date_, $type, $reference, $line_item->quantity, $line_item->standard_cost, $memo_);
      }
      DB_Comments::add(ST_INVADJUST, $adj_id, $date_, $memo_);
      Ref::save(ST_INVADJUST, $reference);
      DB_AuditTrail::add(ST_INVADJUST, $adj_id, $date_);
      DB::commit();
      return $adj_id;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no)
    {
      if ($type != ST_INVADJUST) {
        $type = ST_INVADJUST;
      }
      GL_Trans::void($type, $type_no);
      Inv_Movement::void($type, $type_no);
    }
    /**
     * @static
     *
     * @param $trans_no
     *
     * @return null|PDOStatement
     */
    public static function get($trans_no)
    {
      $result = Inv_Movement::get(ST_INVADJUST, $trans_no);
      if (DB::num_rows($result) == 0) {
        return null;
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $adj_id
     * @param $stock_id
     * @param $location
     * @param $date_
     * @param $type
     * @param $reference
     * @param $quantity
     * @param $standard_cost
     * @param $memo_
     */
    public static function add_item($adj_id, $stock_id, $location, $date_, $type, $reference, $quantity, $standard_cost, $memo_)
    {
      $mb_flag = WO::get_mb_flag($stock_id);
      if (Input::post('mb_flag') == STOCK_SERVICE) {
        Errors::db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
      }
      Purch_GRN::update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);
      Inv_Movement::add(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $type);
      if ($standard_cost > 0) {
        $stock_gl_codes = Item::get_gl_code($stock_id);
        GL_Trans::add_std_cost(ST_INVADJUST, $adj_id, $date_, $stock_gl_codes['adjustment_account'], $stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, ($standard_cost * -($quantity)));
        GL_Trans::add_std_cost(ST_INVADJUST, $adj_id, $date_, $stock_gl_codes['inventory_account'], 0, 0, $memo_, ($standard_cost * $quantity));
      }
    }
    /**
     * @static
     *
     * @param $order
     */
    public static function header($order)
    {
      Table::startOuter('tablestyle2 width70'); // outer table
      Table::section(1);
      Inv_Location::row(_("Location:"), 'StockLocation', null);
       Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_INVADJUST));
      Table::section(2, "33%");
       Forms::dateRow(_("Date:"), 'AdjDate', '', true);
      Table::section(3, "33%");
      Inv_Movement::row(_("Detail:"), 'type', null);
      if (!isset($_POST['Increase'])) {
        $_POST['Increase'] = 1;
      }
       Forms::yesnoListRow(_("Type:"), 'Increase', $_POST['Increase'], _("Positive Adjustment"), _("Negative Adjustment"));
      Table::endOuter(1); // outer table
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    public static function display_items($title, $order)
    {
      Display::heading($title);
      Display::div_start('items_table');
      Table::start('tablestyle grid width90');
      $th = array(
        _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Unit Cost"), _("Total"), ""
      );
      if (count($order->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      $total = 0;
      $k     = 0; //row colour counter
      $id    = Forms::findPostPrefix(MODE_EDIT);
      foreach ($order->line_items as $line_no => $stock_item) {
        $total += ($stock_item->standard_cost * $stock_item->quantity);
        if ($id != $line_no) {

          Item_UI::status_cell($stock_item->stock_id);
          Cell::label($stock_item->description);
          Cell::qty($stock_item->quantity, false, Item::qty_dec($stock_item->stock_id));
          Cell::label($stock_item->units);
          Cell::amountDecimal($stock_item->standard_cost);
          Cell::amount($stock_item->standard_cost * $stock_item->quantity);
          Forms::buttonEditCell("Edit$line_no", _("Edit"), _('Edit document line'));
          Forms::buttonDeleteCell("Delete$line_no", _("Delete"), _('Remove line from document'));
          Row::end();
        } else {
          Inv_Adjustment::item_controls($order, $line_no);
        }
      }
      if ($id == -1) {
        Inv_Adjustment::item_controls($order);
      }
      Row::label(_("Total"), Num::format($total, User::price_dec()), "class=right colspan=5", "class='right'", 2);
      Table::end();
      Display::div_end();
    }
    /**
     * @static
     *
     * @param $order
     * @param $line_no
     */
    public static function item_controls($order, $line_no = -1)
    {

      Row::start();
      $dec2 = 0;
      $id   = Forms::findPostPrefix(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $_POST['qty']      = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
        //$_POST['std_cost'] = Num::price_format($order->line_items[$id]->standard_cost);
        $_POST['std_cost'] = Num::price_decimal($order->line_items[$id]->standard_cost, $dec2);
        $_POST['units']    = $order->line_items[$id]->units;
        Forms::hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id']);
        Cell::label($order->line_items[$id]->description, ' class="nowrap"');
        Ajax::activate('items_table');
      } else {
        Item_UI::costable_cells(null, 'stock_id', null, false, true);
        if (Forms::isListUpdated('stock_id')) {
          Ajax::activate('units');
          Ajax::activate('qty');
          Ajax::activate('std_cost');
        }
        $item_info    = Item::get_edit_info((isset($_POST['stock_id']) ? $_POST['stock_id'] : ''));
        $dec          = $item_info['decimals'];
        $_POST['qty'] = Num::format(0, $dec);
        //$_POST['std_cost'] = Num::price_format($item_info["standard_cost"]);
        $_POST['std_cost'] = Num::price_decimal($item_info["standard_cost"], $dec2);
        $_POST['units']    = $item_info["units"];
      }
       Forms::qtyCells(null, 'qty', $_POST['qty'], null, null, $dec);
      Cell::label($_POST['units'], '', 'units');
      // Forms::amountCells(null, 'std_cost', $_POST['std_cost']);
       Forms::amountCells(null, 'std_cost', null, null, null, $dec2);
      Cell::label("&nbsp;");
      if ($id != -1) {
        Forms::buttonCell('updateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        Forms::buttonCell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        Forms::hidden('LineNo', $line_no);
        JS::set_focus('qty');
      } else {
        Forms::submitCells('addLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      Row::end();
    }
    public static function option_controls()
    {
      echo "<br>";
      Table::start('center');
       Forms::textareaRow(_("Memo"), 'memo_', null, 50, 3);
      Table::end(1);
    }
  }


