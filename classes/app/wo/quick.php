<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class WO_Quick {
    /**
     * @static
     *
     * @param $wo_ref
     * @param $loc_code
     * @param $units_reqd
     * @param $stock_id
     * @param $type
     * @param $date_
     * @param $memo_
     * @param $costs
     * @param $cr_acc
     * @param $labour
     * @param $cr_lab_acc
     *
     * @return string
     */
    static public function add($wo_ref, $loc_code, $units_reqd, $stock_id, $type, $date_, $memo_, $costs, $cr_acc, $labour,
                               $cr_lab_acc) {
      DB::begin();
      // if unassembling, reverse the stock movements
      if ($type == WO_UNASSEMBLY) {
        $units_reqd = -$units_reqd;
      }
      WO_Cost::add_material($stock_id, $units_reqd, $date_);
      $date = Dates::date2sql($date_);
      if (!isset($costs) || ($costs == "")) {
        $costs = 0;
      }
      WO_Cost::add_overhead($stock_id, $units_reqd, $date_, $costs);
      if (!isset($labour) || ($labour == "")) {
        $labour = 0;
      }
      WO_Cost::add_labour($stock_id, $units_reqd, $date_, $labour);
      $sql = "INSERT INTO workorders (wo_ref, loc_code, units_reqd, units_issued, stock_id,
		type, additional_costs, date_, released_date, required_by, released, closed)
 	VALUES (" . DB::escape($wo_ref) . ", " . DB::escape($loc_code) . ", " . DB::escape($units_reqd) . ", " . DB::escape($units_reqd) . ", " . DB::escape($stock_id) . ",
		" . DB::escape($type) . ", " . DB::escape($costs) . ", '$date', '$date', '$date', 1, 1)";
      DB::query($sql, "could not add work order");
      $woid = DB::insert_id();
      // create Work Order Requirements based on the bom
      $result = WO::get_bom($stock_id);
      while ($bom_item = DB::fetch($result)) {
        $unit_quantity = $bom_item["quantity"];
        $item_quantity = $bom_item["quantity"] * $units_reqd;
        $sql = "INSERT INTO wo_requirements (workorder_id, stock_id, workcentre, units_req, units_issued, loc_code)
			VALUES ($woid, '" . $bom_item["component"] . "',
			'" . $bom_item["workcentre_added"] . "',
			$unit_quantity,	$item_quantity, '" . $bom_item["loc_code"] . "')";
        DB::query($sql, "The work order requirements could not be added");
        // insert a -ve stock move for each item
        Inv_Movement::add(ST_WORKORDER, $bom_item["component"], $woid, $bom_item["loc_code"], $date_, $wo_ref, -$item_quantity,
          0);
      }
      // -------------------------------------------------------------------------
      // insert a +ve stock move for the item being manufactured
      Inv_Movement::add(ST_WORKORDER, $stock_id, $woid, $loc_code, $date_, $wo_ref, $units_reqd, 0);
      // -------------------------------------------------------------------------
      WO_Quick::costs($woid, $stock_id, $units_reqd, $date_, 0, $costs, $cr_acc, $labour, $cr_lab_acc);
      // -------------------------------------------------------------------------
      DB_Comments::add(ST_WORKORDER, $woid, $date_, $memo_);
      Ref::save(ST_WORKORDER, $wo_ref);
      DB_AuditTrail::add(ST_WORKORDER, $woid, $date_, _("Quick production."));
      DB::commit();
      return $woid;
    }
    /**
     * @static
     *
     * @param        $woid
     * @param        $stock_id
     * @param        $units_reqd
     * @param        $date_
     * @param int    $advanced
     * @param int    $costs
     * @param string $cr_acc
     * @param int    $labour
     * @param string $cr_lab_acc
     */
    static public function costs($woid, $stock_id, $units_reqd, $date_, $advanced = 0, $costs = 0, $cr_acc = "", $labour = 0,
                                 $cr_lab_acc = "") {
      global $wo_cost_types;
      $result = WO::get_bom($stock_id);
      // credit all the components
      $total_cost = 0;
      while ($bom_item = DB::fetch($result)) {
        $bom_accounts = Item::get_gl_code($bom_item["component"]);
        $bom_cost = $bom_item["ComponentCost"] * $units_reqd;
        if ($advanced) {
          WO_Requirements::update($woid, $bom_item['component'], $bom_item["quantity"] * $units_reqd);
          // insert a -ve stock move for each item
          Inv_Movement::add(ST_MANURECEIVE, $bom_item["component"], $advanced, $bom_item["loc_code"], $date_, "",
            -$bom_item["quantity"] * $units_reqd, 0);
        }
        $total_cost += GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $bom_accounts["inventory_account"], 0, 0, NULL,
          -$bom_cost);
      }
      if ($advanced) {
        // also take the additional issues
        $res = WO_Issue::get_additional($woid);
        $wo = WO::get($woid);
        $issue_total = 0;
        while ($item = DB::fetch($res)) {
          $standard_cost = Item_Price::get_standard_cost($item['stock_id']);
          $issue_cost = $standard_cost * $item['qty_issued'] * $units_reqd / $wo['units_reqd'];
          $issue = Item::get_gl_code($item['stock_id']);
          $total_cost += GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $issue["inventory_account"], 0, 0, NULL,
            -$issue_cost);
          $issue_total += $issue_cost;
        }
        if ($issue_total != 0) {
          WO_Cost::add_issue($stock_id, $units_reqd, $date_, $issue_total);
        }
        $lcost = WO::get_gl($woid, WO_LABOUR);
        WO_Cost::add_labour($stock_id, $units_reqd, $date_, $lcost * $units_reqd / $wo['units_reqd']);
        $ocost = WO::get_gl($woid, WO_OVERHEAD);
        WO_Cost::add_overhead($stock_id, $units_reqd, $date_, $ocost * $units_reqd / $wo['units_reqd']);
      }
      // credit additional costs
      $item_accounts = Item::get_gl_code($stock_id);
      if ($costs != 0.0) {
        GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $cr_acc, 0, 0, $wo_cost_types[WO_OVERHEAD], -$costs, PT_WORKORDER,
          WO_OVERHEAD);
        $is_bank_to = Bank_Account::is($cr_acc);
        if ($is_bank_to) {
          Bank_Trans::add(ST_WORKORDER, $woid, $is_bank_to, "", $date_, -$costs, PT_WORKORDER, WO_OVERHEAD,
            Bank_Currency::for_company(), "Cannot insert a destination bank transaction");
        }
        GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $item_accounts["assembly_account"], $item_accounts["dimension_id"],
          $item_accounts["dimension2_id"], $wo_cost_types[WO_OVERHEAD], $costs, PT_WORKORDER, WO_OVERHEAD);
      }
      if ($labour != 0.0) {
        GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $cr_lab_acc, 0, 0, $wo_cost_types[WO_LABOUR], -$labour,
          PT_WORKORDER, WO_LABOUR);
        $is_bank_to = Bank_Account::is($cr_lab_acc);
        if ($is_bank_to) {
          Bank_Trans::add(ST_WORKORDER, $woid, $is_bank_to, "", $date_, -$labour, PT_WORKORDER, WO_LABOUR,
            Bank_Currency::for_company(), "Cannot insert a destination bank transaction");
        }
        GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $item_accounts["assembly_account"], $item_accounts["dimension_id"],
          $item_accounts["dimension2_id"], $wo_cost_types[WO_LABOUR], $labour, PT_WORKORDER, WO_LABOUR);
      }
      // debit total components $total_cost
      GL_Trans::add_std_cost(ST_WORKORDER, $woid, $date_, $item_accounts["inventory_account"], 0, 0, NULL, -$total_cost);
    }
    /**
     * @static
     *
     * @param      $woid
     * @param bool $suppress_view_link
     */
    static public function display($woid, $suppress_view_link = FALSE) {
      global $wo_types_array;
      $myrow = WO::get($woid);
      if (strlen($myrow[0]) == 0) {
        Display::note(_("The work order number sent is not valid."));
        exit;
      }
      start_table('tablestyle width90');
      $th = array(
        _("#"), _("Reference"), _("Type"), _("Manufactured Item"), _("Into Location"), _("Date"), _("Quantity")
      );
      table_header($th);
      start_row();
      if ($suppress_view_link) {
        label_cell($myrow["id"]);
      }
      else {
        label_cell(GL_UI::trans_view(ST_WORKORDER, $myrow["id"]));
      }
      label_cell($myrow["wo_ref"]);
      label_cell($wo_types_array[$myrow["type"]]);
      Item_UI::status_cell($myrow["stock_id"], $myrow["StockItemName"]);
      label_cell($myrow["location_name"]);
      label_cell(Dates::sql2date($myrow["date_"]));
      qty_cell($myrow["units_issued"], FALSE, Item::qty_dec($myrow["stock_id"]));
      end_row();
      DB_Comments::display_row(ST_WORKORDER, $woid);
      end_table();
      if ($myrow["closed"] == TRUE) {
        Display::note(_("This work order is closed."));
      }
    }
  }

