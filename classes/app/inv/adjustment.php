<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Inv_Adjustment {
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
    static public function add($items, $location, $date_, $type, $increase, $reference, $memo_) {
      DB::begin();
      $adj_id = SysTypes::get_next_trans_no(ST_INVADJUST);
      foreach ($items as $line_item) {
        if (!$increase) {
          $line_item->quantity = -$line_item->quantity;
        }
        static::add_item($adj_id, $line_item->stock_id, $location, $date_, $type, $reference,
          $line_item->quantity, $line_item->standard_cost, $memo_);
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
    static public function void($type, $type_no) {
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
    static public function get($trans_no) {
      $result = Inv_Movement::get(ST_INVADJUST, $trans_no);
      if (DB::num_rows($result) == 0) {
        return NULL;
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
    static public function add_item($adj_id, $stock_id, $location, $date_, $type, $reference, $quantity, $standard_cost, $memo_) {
      $mb_flag = WO::get_mb_flag($stock_id);
      if (Input::post('mb_flag') == STOCK_SERVICE) {
        Errors::db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
      }
      Purch_GRN::update_average_material_cost(NULL, $stock_id, $standard_cost, $quantity, $date_);
      Inv_Movement::add(ST_INVADJUST, $stock_id, $adj_id, $location,
        $date_, $reference, $quantity, $standard_cost, $type);
      if ($standard_cost > 0) {
        $stock_gl_codes = Item::get_gl_code($stock_id);
        GL_Trans::add_std_cost(ST_INVADJUST, $adj_id, $date_,
          $stock_gl_codes['adjustment_account'], $stock_gl_codes['dimension_id'],
          $stock_gl_codes['dimension2_id'], $memo_, ($standard_cost * -($quantity)));
        GL_Trans::add_std_cost(ST_INVADJUST, $adj_id, $date_,
          $stock_gl_codes['inventory_account'], 0, 0, $memo_, ($standard_cost * $quantity));
      }
    }
    /**
     * @static
     *
     * @param $order
     */
    static public function header($order) {
      Table::startOuter('tablestyle2 width70'); // outer table
      Table::section(1);
      Inv_Location::row(_("Location:"), 'StockLocation', NULL);
      ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_INVADJUST));
      Table::section(2, "33%");
      date_row(_("Date:"), 'AdjDate', '', TRUE);
      Table::section(3, "33%");
      Inv_Movement::row(_("Detail:"), 'type', NULL);
      if (!isset($_POST['Increase'])) {
        $_POST['Increase'] = 1;
      }
      yesno_list_row(_("Type:"), 'Increase', $_POST['Increase'], _("Positive Adjustment"), _("Negative Adjustment"));
      Table::endOuter(1); // outer table
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    static public function display_items($title, $order) {
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
      $k = 0; //row colour counter
      $id = find_submit(MODE_EDIT);
      foreach ($order->line_items as $line_no => $stock_item) {
        $total += ($stock_item->standard_cost * $stock_item->quantity);
        if ($id != $line_no) {

          Item_UI::status_cell($stock_item->stock_id);
          Cell::label($stock_item->description);
          Cell::qty($stock_item->quantity, FALSE, Item::qty_dec($stock_item->stock_id));
          Cell::label($stock_item->units);
          Cell::amountDecimal($stock_item->standard_cost);
          Cell::amount($stock_item->standard_cost * $stock_item->quantity);
          edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
          delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
          Row::end();
        }
        else {
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
    static public function item_controls($order, $line_no = -1) {

      Row::start();
      $dec2 = 0;
      $id = find_submit(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $_POST['qty'] = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
        //$_POST['std_cost'] = Num::price_format($order->line_items[$id]->standard_cost);
        $_POST['std_cost'] = Num::price_decimal($order->line_items[$id]->standard_cost, $dec2);
        $_POST['units'] = $order->line_items[$id]->units;
        hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id']);
        Cell::label($order->line_items[$id]->description, ' class="nowrap"');
        Ajax::i()->activate('items_table');
      }
      else {
        Item_UI::costable_cells(NULL, 'stock_id', NULL, FALSE, TRUE);
        if (list_updated('stock_id')) {
          Ajax::i()->activate('units');
          Ajax::i()->activate('qty');
          Ajax::i()->activate('std_cost');
        }
        $item_info = Item::get_edit_info((isset($_POST['stock_id']) ? $_POST['stock_id'] : ''));
        $dec = $item_info['decimals'];
        $_POST['qty'] = Num::format(0, $dec);
        //$_POST['std_cost'] = Num::price_format($item_info["standard_cost"]);
        $_POST['std_cost'] = Num::price_decimal($item_info["standard_cost"], $dec2);
        $_POST['units'] = $item_info["units"];
      }
      qty_cells(NULL, 'qty', $_POST['qty'], NULL, NULL, $dec);
      Cell::label($_POST['units'], '', 'units');
      //amount_cells(null, 'std_cost', $_POST['std_cost']);
      amount_cells(NULL, 'std_cost', NULL, NULL, NULL, $dec2);
      Cell::label("&nbsp;");
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

    static public function option_controls() {
      echo "<br>";
      Table::start('center');
      textarea_row(_("Memo"), 'memo_', NULL, 50, 3);
      Table::end(1);
    }
  }


