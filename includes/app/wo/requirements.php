<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	class WO_Requirements
	{
		public static function get($woid) {
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

		public static function add($woid, $stock_id) {
			// create Work Order Requirements based on the bom
			$result = Manufacturing::get_bom($stock_id);
			while ($myrow = DB::fetch($result)) {
				$sql = "INSERT INTO wo_requirements (workorder_id, stock_id, workcentre, units_req, loc_code)
			VALUES (" . DB::escape($woid) . ", '" . $myrow["component"] . "', '" . $myrow["workcentre_added"] . "', '" . $myrow["quantity"] . "', '" . $myrow["loc_code"] . "')";
				DB::query($sql, "The work order requirements could not be added");
			}
		}

		public static function delete($woid) {
			$sql = "DELETE FROM wo_requirements WHERE workorder_id=" . DB::escape($woid);
			DB::query($sql, "The work order requirements could not be deleted");
		}

		public static function update($woid, $stock_id, $quantity) {
			$sql = "UPDATE wo_requirements SET units_issued = units_issued + " . DB::escape($quantity) . "
		WHERE workorder_id = " . DB::escape($woid) . " AND stock_id = " . DB::escape($stock_id);
			DB::query($sql, "The work requirements issued quantity couldn't be updated");
		}

		public static function void($woid) {
			$sql = "UPDATE wo_requirements SET units_issued = 0 WHERE workorder_id = " . DB::escape($woid);
			DB::query($sql, "The work requirements issued quantity couldn't be voided");
		}

		function display($woid, $quantity, $show_qoh = false, $date = null) {
			$result = WO_Requirements::get($woid);
			if (DB::num_rows($result) == 0) {
				Display::note(_("There are no Requirements for this Order."), 1, 0);
			} else {
				Display::start_table(Config::get('tables_style') . "  width=90%");
				$th = array(
					_("Component"), _("From Location"), _("Work Centre"), _("Unit Quantity"), _("Total Quantity"), _("Units Issued"), _("On Hand"));
				Display::table_header($th);
				$k = 0; //row colour counter
				$has_marked = false;
				if ($date == null) {
					$date = Dates::Today();
				}
				while ($myrow = DB::fetch($result)) {
					$qoh = 0;
					$show_qoh = true;
					// if it's a non-stock item (eg. service) don't show qoh
					if (!Manufacturing::has_stock_holding($myrow["mb_flag"])) {
						$show_qoh = false;
					}
					if ($show_qoh) {
						$qoh = Item::get_qoh_on_date($myrow["stock_id"], $myrow["loc_code"], $date);
					}
					if ($show_qoh && ($myrow["units_req"] * $quantity > $qoh) && !DB_Company::get_pref('allow_negative_stock')
					) {
						// oops, we don't have enough of one of the component items
						Display::start_row("class='stockmankobg'");
						$has_marked = true;
					} else {
						Display::alt_table_row_color($k);
					}
					if (User::show_codes()) {
						label_cell($myrow["stock_id"] . " - " . $myrow["description"]);
					} else {
						label_cell($myrow["description"]);
					}
					label_cell($myrow["location_name"]);
					label_cell($myrow["WorkCentreDescription"]);
					$dec = Item::qty_dec($myrow["stock_id"]);
					qty_cell($myrow["units_req"], false, $dec);
					qty_cell($myrow["units_req"] * $quantity, false, $dec);
					qty_cell($myrow["units_issued"], false, $dec);
					if ($show_qoh) {
						qty_cell($qoh, false, $dec);
					} else {
						label_cell("");
					}
					Display::end_row();
				}
				Display::end_table();
				if ($has_marked) {
					Display::note(_("Marked items have insufficient quantities in stock."), 0, 0, "class='red'");
				}
			}
		}
	}

?>