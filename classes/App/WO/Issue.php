<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class WO_Issue
  {
    /**
     * @static
     *
     * @param $woid
     * @param $ref
     * @param $to_work_order
     * @param $items
     * @param $location
     * @param $workcentre
     * @param $date_
     * @param $memo_
     */
    public static function add($woid, $ref, $to_work_order, $items, $location, $workcentre, $date_, $memo_)
    {
      DB::begin();
      $details = WO::get($woid);
      if (strlen($details[0]) == 0) {
        echo _("The order number sent is not valid.");
        DB::cancel();
        exit;
      }
      if (WO::is_closed($woid)) {
        Event::error("UNEXPECTED : Issuing items for a closed Work Order");
        DB::cancel();
        exit;
      }
      // insert the actual issue
      $sql
        = "INSERT INTO wo_issues (workorder_id, reference, issue_date, loc_code, workcentre_id)
        VALUES (" . DB::escape($woid) . ", " . DB::escape($ref) . ", '" . Dates::dateToSql($date_) . "', " . DB::escape($location) . ", " . DB::escape($workcentre) . ")";
      DB::query($sql, "The work order issue could not be added");
      $number = DB::insertId();
      foreach ($items as $item) {
        if ($to_work_order) {
          $item->quantity = -$item->quantity;
        }
        // insert a -ve stock move for each item
        Inv_Movement::add(ST_MANUISSUE, $item->stock_id, $number, $location, $date_, $memo_, -$item->quantity, 0);
        $sql
          = "INSERT INTO wo_issue_items (issue_id, stock_id, qty_issued)
            VALUES (" . DB::escape($number) . ", " . DB::escape($item->stock_id) . ", " . DB::escape($item->quantity) . ")";
        DB::query($sql, "A work order issue item could not be added");
      }
      if ($memo_) {
        DB_Comments::add(ST_MANUISSUE, $number, $date_, $memo_);
      }
      Ref::save(ST_MANUISSUE, $ref);
      DB_AuditTrail::add(ST_MANUISSUE, $number, $date_);
      DB::commit();
    }
    /**
     * @static
     *
     * @param $woid
     *
     * @return null|PDOStatement
     */
    public static function getAll($woid)
    {
      $sql = "SELECT * FROM wo_issues WHERE workorder_id=" . DB::escape($woid) . " ORDER BY issue_no";

      return DB::query($sql, "The work order issues could not be retrieved");
    }
    /**
     * @static
     *
     * @param $woid
     *
     * @return null|PDOStatement
     */
    public static function get_additional($woid)
    {
      $sql
        = "SELECT wo_issues.*, wo_issue_items.*
        FROM wo_issues, wo_issue_items
        WHERE wo_issues.issue_no=wo_issue_items.issue_id
        AND wo_issues.workorder_id=" . DB::escape($woid) . " ORDER BY wo_issue_items.id";

      return DB::query($sql, "The work order issues could not be retrieved");
    }
    /**
     * @static
     *
     * @param $issue_no
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($issue_no)
    {
      $sql
              = "SELECT DISTINCT wo_issues.*, workorders.stock_id,
        stock_master.description, locations.location_name, " . "workcentres.name AS WorkCentreName
        FROM wo_issues, workorders, stock_master, " . "locations, workcentres
        WHERE issue_no=" . DB::escape($issue_no) . "
        AND workorders.id = wo_issues.workorder_id
        AND locations.loc_code = wo_issues.loc_code
        AND workcentres.id = wo_issues.workcentre_id
        AND stock_master.stock_id = workorders.stock_id";
      $result = DB::query($sql, "A work order issue could not be retrieved");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $issue_no
     *
     * @return null|PDOStatement
     */
    public static function get_details($issue_no)
    {
      $sql = "SELECT wo_issue_items.*," . "stock_master.description, stock_master.units
        FROM wo_issue_items, stock_master
        WHERE issue_id=" . DB::escape($issue_no) . "
        AND stock_master.stock_id=wo_issue_items.stock_id
        ORDER BY wo_issue_items.id";

      return DB::query($sql, "The work order issue items could not be retrieved");
    }
    /**
     * @static
     *
     * @param $issue_no
     *
     * @return bool
     */
    public static function exists($issue_no)
    {
      $sql    = "SELECT issue_no FROM wo_issues WHERE issue_no=" . DB::escape($issue_no);
      $result = DB::query($sql, "Cannot retreive a wo issue");

      return (DB::numRows($result) > 0);
    }
    /**
     * @static
     *
     * @param $woid
     */
    public static function display($woid)
    {
      $result = WO_Issue::getAll($woid);
      if (DB::numRows($result) == 0) {
        Display::note(_("There are no Issues for this Order."), 0, 1);
      } else {
        Table::start('tablestyle grid');
        $th = array(_("#"), _("Reference"), _("Date"));
        Table::header($th);
        $k = 0; //row colour counter
        while ($myrow = DB::fetch($result)) {
          Cell::label(GL_UI::viewTrans(28, $myrow["issue_no"]));
          Cell::label($myrow['reference']);
          Cell::label(Dates::sqlToDate($myrow["issue_date"]));
          Row::end();
        }
        Table::end();
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no)
    {
      if ($type != ST_MANUISSUE) {
        $type = ST_MANUISSUE;
      }
      DB::begin();
      // void the actual issue items and their quantities
      $sql = "UPDATE wo_issue_items Set qty_issued = 0 WHERE issue_id=" . DB::escape($type_no);
      DB::query($sql, "A work order issue item could not be voided");
      // void all related stock moves
      Inv_Movement::void($type, $type_no);
      // void any related gl trans
      GL_Trans::void($type, $type_no, true);
      DB::commit();
    }
    /**
     * @static
     *
     * @param $order
     * @param $new_item
     * @param $new_item_qty
     * @param $standard_cost
     */
    public static function add_to($order, $new_item, $new_item_qty, $standard_cost)
    {
      if ($order->find_order_item($new_item)) {
        Event::error(_("For Part: '") . $new_item . "' This item is already on this issue. You can change the quantity issued of the existing line if necessary.");
      } else {
        $order->add_to_order(count($order->line_items), $new_item, $new_item_qty, $standard_cost);
      }
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    public static function display_items($title, &$order)
    {
      Display::heading($title);
      Display::div_start('items_table');
      Table::start('tablestyle width90');
      $th = array(
        _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Unit Cost"), ''
      );
      if (count($order->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      //	$total = 0;
      $k  = 0; //row colour counter
      $id = Forms::findPostPrefix(MODE_EDIT);
      foreach ($order->line_items as $line_no => $stock_item) {
        //		$total += ($stock_item->standard_cost * $stock_item->quantity);
        if ($id != $line_no) {
          Item_UI::status_cell($stock_item->stock_id);
          Cell::label($stock_item->description);
          Cell::qty($stock_item->quantity, false, Item::qty_dec($stock_item->stock_id));
          Cell::label($stock_item->units);
          Cell::amount($stock_item->standard_cost);
          //			Cell::amount($stock_item->standard_cost * $stock_item->quantity);
          Forms::buttonEditCell("Edit$line_no", _("Edit"), _('Edit document line'));
          Forms::buttonDeleteCell("Delete$line_no", _("Delete"), _('Remove line from document'));
          Row::end();
        } else {
          WO_Issue::edit_controls($order, $line_no);
        }
      }
      if ($id == -1) {
        WO_Issue::edit_controls($order);
      }
      //	Row::label(_("Total"), Num::format($total,User::price_dec()), "colspan=5", "class='right'");
      Table::end();
      Display::div_end();
    }
    /**
     * @static
     *
     * @param $order
     * @param $line_no
     */
    public static function edit_controls($order, $line_no = -1)
    {
      Row::start();
      $id = Forms::findPostPrefix(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $_POST['qty']      = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
        $_POST['std_cost'] = Num::priceFormat($order->line_items[$id]->standard_cost);
        $_POST['units']    = $order->line_items[$id]->units;
        Forms::hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id']);
        Cell::label($order->line_items[$id]->description);
        Ajax::activate('items_table');
      } else {
        $wo_details = WO::get($_SESSION['issue_items']->order_id);
        Item_UI::component_cells(null, 'stock_id', $wo_details["stock_id"], null, false, true);
        if (Forms::isListUpdated('stock_id')) {
          Ajax::activate('units');
          Ajax::activate('qty');
          Ajax::activate('std_cost');
        }
        $item_info         = Item::get_edit_info($_POST['stock_id']);
        $dec               = $item_info["decimals"];
        $_POST['qty']      = Num::format(0, $dec);
        $_POST['std_cost'] = Num::priceFormat($item_info["standard_cost"]);
        $_POST['units']    = $item_info["units"];
      }
      Forms::qtyCells(null, 'qty', $_POST['qty'], null, null, $dec);
      Cell::label($_POST['units'], '', 'units');
      Forms::amountCells(null, 'std_cost', $_POST['std_cost']);
      if ($id != -1) {
        Forms::buttonCell('updateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        Forms::buttonCell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        Forms::hidden('LineNo', $line_no);
        JS::setFocus('qty');
      } else {
        Forms::submitCells('addLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      Row::end();
    }
    public static function option_controls()
    {
      echo "<br>";
      Table::start();
      Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_MANUISSUE));
      if (!isset($_POST['IssueType'])) {
        $_POST['IssueType'] = 0;
      }
      Forms::yesnoListRow(_("Type:"), 'IssueType', $_POST['IssueType'], _("Return Items to Location"), _("Issue Items to Work order"));
      Inv_Location::row(_("From Location:"), 'location');
      workcenter_list_row(_("To Work Centre:"), 'WorkCentre');
      Forms::dateRow(_("Issue Date:"), 'date_');
      Forms::textareaRow(_("Memo"), 'memo_', null, 50, 3);
      Table::end(1);
    }
  }
