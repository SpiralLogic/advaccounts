<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class WO_Requirements {
    /**
     * @static
     *
     * @param $woid
     *
     * @return null|PDOStatement
     */
    static public function get($woid) {
      $sql = "SELECT wo_requirements.*, stock_master.description,
		stock_master.mb_flag,
		locations.location_name,
		workcentres.name AS WorkCentreDescription FROM
		(wo_requirements, locations, " . "workcentres) INNER JOIN stock_master ON
		wo_requirements.stock_id = stock_master.stock_id
		WHERE workorder_id=" . DB::escape($woid) . "
		AND locations.loc_code = wo_requirements.loc_code
		AND workcentres.id=workcentre";
      return DB::query($sql, "The work order requirements could not be retrieved");
    }
    /**
     * @static
     *
     * @param $woid
     * @param $stock_id
     */
    static public function add($woid, $stock_id) {
      // create Work Order Requirements based on the bom
      $result = WO::get_bom($stock_id);
      while ($myrow = DB::fetch($result)) {
        $sql = "INSERT INTO wo_requirements (workorder_id, stock_id, workcentre, units_req, loc_code)
			VALUES (" . DB::escape($woid) . ", '" . $myrow["component"] . "', '" . $myrow["workcentre_added"] . "', '" . $myrow["quantity"] . "', '" . $myrow["loc_code"] . "')";
        DB::query($sql, "The work order requirements could not be added");
      }
    }
    /**
     * @static
     *
     * @param $woid
     */
    static public function delete($woid) {
      $sql = "DELETE FROM wo_requirements WHERE workorder_id=" . DB::escape($woid);
      DB::query($sql, "The work order requirements could not be deleted");
    }
    /**
     * @static
     *
     * @param $woid
     * @param $stock_id
     * @param $quantity
     */
    static public function update($woid, $stock_id, $quantity) {
      $sql = "UPDATE wo_requirements SET units_issued = units_issued + " . DB::escape($quantity) . "
		WHERE workorder_id = " . DB::escape($woid) . " AND stock_id = " . DB::escape($stock_id);
      DB::query($sql, "The work requirements issued quantity couldn't be updated");
    }
    /**
     * @static
     *
     * @param null $type
     * @param      $woid
     */
    static public function void($type = NULL, $woid) {
      $sql = "UPDATE wo_requirements SET units_issued = 0 WHERE workorder_id = " . DB::escape($woid);
      DB::query($sql, "The work requirements issued quantity couldn't be voided");
    }
    /**
     * @static
     *
     * @param      $woid
     * @param      $quantity
     * @param bool $show_qoh
     * @param null $date
     */
    static public function display($woid, $quantity, $show_qoh = FALSE, $date = NULL) {
      $result = WO_Requirements::get($woid);
      if (DB::num_rows($result) == 0) {
        Display::note(_("There are no Requirements for this Order."), 1, 0);
      }
      else {
        Table::start('tablestyle grid width90');
        $th = array(
          _("Component"), _("From Location"), _("Work Centre"), _("Unit Quantity"), _("Total Quantity"), _("Units Issued"), _("On Hand")
        );
        Table::header($th);
        $k = 0; //row colour counter
        $has_marked = FALSE;
        if ($date == NULL) {
          $date = Dates::today();
        }
        while ($myrow = DB::fetch($result)) {
          $qoh = 0;
          $show_qoh = TRUE;
          // if it's a non-stock item (eg. service) don't show qoh
          if (!WO::has_stock_holding($myrow["mb_flag"])) {
            $show_qoh = FALSE;
          }
          if ($show_qoh) {
            $qoh = Item::get_qoh_on_date($myrow["stock_id"], $myrow["loc_code"], $date);
          }
          if ($show_qoh && ($myrow["units_req"] * $quantity > $qoh) && !DB_Company::get_pref('allow_negative_stock')
          ) {
            // oops, we don't have enough of one of the component items
            Row::start("class='stockmankobg'");
            $has_marked = TRUE;
          }
          else {

          }
          if (User::show_codes()) {
            Cell::label($myrow["stock_id"] . " - " . $myrow["description"]);
          }
          else {
            Cell::label($myrow["description"]);
          }
          Cell::label($myrow["location_name"]);
          Cell::label($myrow["WorkCentreDescription"]);
          $dec = Item::qty_dec($myrow["stock_id"]);
          Cell::qty($myrow["units_req"], FALSE, $dec);
          Cell::qty($myrow["units_req"] * $quantity, FALSE, $dec);
          Cell::qty($myrow["units_issued"], FALSE, $dec);
          if ($show_qoh) {
            Cell::qty($qoh, FALSE, $dec);
          }
          else {
            Cell::label("");
          }
          Row::end();
        }
        Table::end();
        if ($has_marked) {
          Display::note(_("Marked items have insufficient quantities in stock."), 0, 0, "class='red'");
        }
      }
    }
  }


