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
	class WO_Cost
	{
		public static function add_material($stock_id, $qty, $date_) {
			$m_cost = 0;
			$result = Manufacturing::get_bom($stock_id);
			while ($bom_item = DB::fetch($result)) {
				$standard_cost = Item_Price::get_standard_cost($bom_item['component']);
				$m_cost += ($bom_item['quantity'] * $standard_cost);
			}
			$dec = User::price_dec();
			Num::price_decimal($m_cost, $dec);
			$sql = "SELECT material_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$material_cost = $myrow['material_cost'];
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$material_cost = ($qoh * $material_cost + $qty * $m_cost) / ($qoh + $qty);
			}
			$material_cost = Num::round($material_cost, $dec);
			$sql = "UPDATE stock_master SET material_cost=$material_cost
		WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add_overhead($stock_id, $qty, $date_, $costs) {
			$dec = User::price_dec();
			Num::price_decimal($costs, $dec);
			if ($qty != 0) {
				$costs /= $qty;
			}
			$sql = "SELECT overhead_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$overhead_cost = $myrow['overhead_cost'];
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$overhead_cost = ($qoh * $overhead_cost + $qty * $costs) / ($qoh + $qty);
			}
			$overhead_cost = Num::round($overhead_cost, $dec);
			$sql = "UPDATE stock_master SET overhead_cost=" . DB::escape($overhead_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add_labour($stock_id, $qty, $date_, $costs) {
			$dec = User::price_dec();
			Num::price_decimal($costs, $dec);
			if ($qty != 0) {
				$costs /= $qty;
			}
			$sql = "SELECT labour_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$labour_cost = $myrow['labour_cost'];
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$labour_cost = ($qoh * $labour_cost + $qty * $costs) / ($qoh + $qty);
			}
			$labour_cost = Num::round($labour_cost, $dec);
			$sql = "UPDATE stock_master SET labour_cost=" . DB::escape($labour_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add_issue($stock_id, $qty, $date_, $costs) {
			if ($qty != 0) {
				$costs /= $qty;
			}
			$sql = "SELECT material_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$material_cost = $myrow['material_cost'];
			$dec = User::price_dec();
			Num::price_decimal($material_cost, $dec);
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$material_cost = ($qty * $costs) / ($qoh + $qty);
			}
			$material_cost = Num::round($material_cost, $dec);
			$sql = "UPDATE stock_master SET material_cost=material_cost+" . DB::escape($material_cost) . " WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add($wo_ref, $loc_code, $units_reqd, $stock_id, $type, $date_, $required_by, $memo_, $costs, $cr_acc,
			$labour, $cr_lab_acc) {
			if (!($type == WO_ADVANCED)) {
				return WO_Quick::add($wo_ref, $loc_code, $units_reqd, $stock_id, $type, $date_, $memo_, $costs, $cr_acc, $labour,
					$cr_lab_acc);
			}
			DB::begin_transaction();
			WO_WorkOrder::add_material($stock_id, $units_reqd, $date_);
			$date = Dates::date2sql($date_);
			$required = Dates::date2sql($required_by);
			$sql = "INSERT INTO workorders (wo_ref, loc_code, units_reqd, stock_id,
		type, date_, required_by)
    	VALUES (" . DB::escape($wo_ref) . ", " . DB::escape($loc_code) . ", " . DB::escape($units_reqd) . ", " . DB::escape($stock_id) . ",
		" . DB::escape($type) . ", '$date', " . DB::escape($required) . ")";
			DB::query($sql, "could not add work order");
			$woid = DB::insert_id();
			DB_Comments::add(ST_WORKORDER, $woid, $required_by, $memo_);
			Ref::save(ST_WORKORDER, $woid, $wo_ref);
			DB_AuditTrail::add(ST_WORKORDER, $woid, $date_);
			DB::commit_transaction();
			return $woid;
		}

		public static function update($woid, $loc_code, $units_reqd, $stock_id, $date_, $required_by, $memo_) {
			DB::begin_transaction();
			WO_WorkOrder::add_material($_POST['old_stk_id'], -$_POST['old_qty'], $date_);
			WO_WorkOrder::add_material($stock_id, $units_reqd, $date_);
			$date = Dates::date2sql($date_);
			$required = Dates::date2sql($required_by);
			$sql = "UPDATE workorders SET loc_code=" . DB::escape($loc_code) . ",
		units_reqd=" . DB::escape($units_reqd) . ", stock_id=" . DB::escape($stock_id) . ",
		required_by=" . DB::escape($required) . ",
		date_='$date'
		WHERE id = " . DB::escape($woid);
			DB::query($sql, "could not update work order");
			DB_Comments::update(ST_WORKORDER, $woid, null, $memo_);
			DB_AuditTrail::add(ST_WORKORDER, $woid, $date_, _("Updated."));
			DB::commit_transaction();
		}

		public static function delete($woid) {
			DB::begin_transaction();
			WO_WorkOrder::add_material($_POST['stock_id'], -$_POST['quantity'], $_POST['date_']);
			// delete the work order requirements
			WO_Requirements::delete($woid);
			// delete the actual work order
			$sql = "DELETE FROM workorders WHERE id=" . DB::escape($woid);
			DB::query($sql, "The work order could not be deleted");
			DB_Comments::delete(ST_WORKORDER, $woid);
			DB_AuditTrail::add(ST_WORKORDER, $woid, $_POST['date_'], _("Canceled."));
			DB::commit_transaction();
		}

		public static function get($woid, $allow_null = false) {
			$sql = "SELECT workorders.*, stock_master.description As StockItemName,
		locations.location_name, locations.delivery_address
		FROM workorders, stock_master, locations
		WHERE stock_master.stock_id=workorders.stock_id
		AND	locations.loc_code=workorders.loc_code
		AND workorders.id=" . DB::escape($woid) . "
		GROUP BY workorders.id";
			$result = DB::query($sql, "The work order issues could not be retrieved");
			if (!$allow_null && DB::num_rows($result) == 0) {
				Errors::show_db_error("Could not find work order $woid", $sql);
			}
			return DB::fetch($result);
		}

		public static function has_productions($woid) {
			$sql = "SELECT COUNT(*) FROM wo_manufacture WHERE workorder_id=" . DB::escape($woid);
			$result = DB::query($sql, "query work order for productions");
			$myrow = DB::fetch_row($result);
			return ($myrow[0] > 0);
		}

		public static function has_issues($woid) {
			$sql = "SELECT COUNT(*) FROM wo_issues WHERE workorder_id=" . DB::escape($woid);
			$result = DB::query($sql, "query work order for issues");
			$myrow = DB::fetch_row($result);
			return ($myrow[0] > 0);
		}

		public static function has_payments($woid) {
			$result = GL_Trans::get_wo_cost($woid);
			return (DB::num_rows($result) != 0);
		}

		public static function release($woid, $releaseDate, $memo_) {
			DB::begin_transaction();
			$myrow = WO_WorkOrder::get($woid);
			$stock_id = $myrow["stock_id"];
			$date = Dates::date2sql($releaseDate);
			$sql = "UPDATE workorders SET released_date='$date',
		released=1 WHERE id = " . DB::escape($woid);
			DB::query($sql, "could not release work order");
			// create Work Order Requirements based on the bom
			WO_Requirements::add($woid, $stock_id);
			DB_Comments::add(ST_WORKORDER, $woid, $releaseDate, $memo_);
			DB_AuditTrail::add(ST_WORKORDER, $woid, $releaseDate, _("Released."));
			DB::commit_transaction();
		}

		public static function close($woid) {
			$sql = "UPDATE workorders SET closed=1 WHERE id = " . DB::escape($woid);
			DB::query($sql, "could not close work order");
		}

		public static function is_closed($woid) {
			$sql = "SELECT closed FROM workorders WHERE id = " . DB::escape($woid);
			$result = DB::query($sql, "could not query work order");
			$row = DB::fetch_row($result);
			return ($row[0] > 0);
		}

		public static function update_finished_quantity($woid, $quantity, $force_close = 0) {
			$sql = "UPDATE workorders SET units_issued = units_issued + " . DB::escape($quantity) . ",
		closed = ((units_issued >= units_reqd) OR " . DB::escape($force_close) . ")
		WHERE id = " . DB::escape($woid);
			DB::query($sql, "The work order issued quantity couldn't be updated");
		}

		public static function void($woid) {
			DB::begin_transaction();
			$work_order = WO_WorkOrder::get($woid);
			if (!($work_order["type"] == WO_ADVANCED)) {
				$date = Dates::sql2date($work_order['date_']);
				$qty = $work_order['units_reqd'];
				WO_WorkOrder::add_material($work_order['stock_id'], -$qty, $date); // remove avg. cost for qty
				$cost = WO_WorkOrder::get_gl($woid, WO_LABOUR); // get the labour cost and reduce avg cost
				if ($cost != 0) {
					WO_WorkOrder::add_labour($work_order['stock_id'], -$qty, $date, $cost);
				}
				$cost = WO_WorkOrder::get_gl($woid, WO_OVERHEAD); // get the overhead cost and reduce avg cost
				if ($cost != 0) {
					WO_WorkOrder::add_overhead($work_order['stock_id'], -$qty, $date, $cost);
				}
				$sql = "UPDATE workorders SET closed=1,units_reqd=0,units_issued=0 WHERE id = " . DB::escape($woid);
				DB::query($sql, "The work order couldn't be voided");
				// void all related stock moves
				Inv_Movement::void(ST_WORKORDER, $woid);
				// void any related gl trans
				GL_Trans::void(ST_WORKORDER, $woid, true);
				// clear the requirements units received
				WO_Requirements::void($woid);
			} else {
				// void everything inside the work order : issues, productions, payments
				$date = Dates::sql2date($work_order['date_']);
				WO_WorkOrder::add_material($work_order['stock_id'], -$work_order['units_reqd'], $date); // remove avg. cost for qty
				$result = WO_Produce::get_all($woid); // check the produced quantity
				$qty = 0;
				while ($row = DB::fetch($result)) {
					$qty += $row['quantity'];
					// clear the production record
					$sql = "UPDATE wo_manufacture SET quantity=0 WHERE id=" . $$row['id'];
					DB::query($sql, "Cannot void a wo production");
					Inv_Movement::void(ST_MANURECEIVE, $row['id']); // and void the stock moves;
				}
				$result = WO_Issue::get_additional($woid); // check the issued quantities
				$cost = 0;
				$issue_no = 0;
				while ($row = DB::fetch($result)) {
					$std_cost = Item_Price::get_standard_cost($row['stock_id']);
					$icost = $std_cost * $row['qty_issued'];
					$cost += $icost;
					if ($issue_no == 0) {
						$issue_no = $row['issue_no'];
					}
					// void the actual issue items and their quantities
					$sql = "UPDATE wo_issue_items SET qty_issued = 0 WHERE issue_id=" . DB::escape($row['id']);
					DB::query($sql, "A work order issue item could not be voided");
				}
				if ($issue_no != 0) {
					Inv_Movement::void(ST_MANUISSUE, $issue_no);
				} // and void the stock moves
				if ($cost != 0) {
					WO_WorkOrder::add_issue($work_order['stock_id'], -$qty, $date, $cost);
				}
				$cost = WO_WorkOrder::get_gl($woid, WO_LABOUR); // get the labour cost and reduce avg cost
				if ($cost != 0) {
					WO_WorkOrder::add_labour($work_order['stock_id'], -$qty, $date, $cost);
				}
				$cost = WO_WorkOrder::get_gl($woid, WO_OVERHEAD); // get the overhead cost and reduce avg cost
				if ($cost != 0) {
					WO_WorkOrder::add_overhead($work_order['stock_id'], -$qty, $date, $cost);
				}
				$sql = "UPDATE workorders SET closed=1,units_reqd=0,units_issued=0 WHERE id = " . DB::escape($woid);
				DB::query($sql, "The work order couldn't be voided");
				// void all related stock moves
				Inv_Movement::void(ST_WORKORDER, $woid);
				// void any related gl trans
				GL_Trans::void(ST_WORKORDER, $woid, true);
				// clear the requirements units received
				WO_Requirements::void($woid);
			}
			DB::commit_transaction();
		}

		public static function get_gl($woid, $cost_type) {
			$cost = 0;
			$result = GL_Trans::get_wo_cost($woid, $cost_type);
			while ($row = DB::fetch($result)) {
				$cost += -$row['amount'];
			}
			return $cost;
		}

		public static function display_payments($woid) {
			global $wo_cost_types;
			//$result = Bank_Trans::get(null, null, PT_WORKORDER, $woid);
			$result = GL_Trans::get_wo_cost($woid);
			if (DB::num_rows($result) == 0) {
				Display::note(_("There are no additional costs for this Order."), 0, 1);
			} else {
				Display::start_table(Config::get('tables_style'));
				$th = array(_("#"), _("Type"), _("Date"), _("Amount"));
				Display::table_header($th);
				$k = 0; //row colour counter
				while ($myrow = DB::fetch($result)) {
					Display::alt_table_row_color($k);
					label_cell(get_gl_view_str(ST_WORKORDER, $myrow["type_no"], $myrow["type_no"]));
					label_cell($wo_cost_types[$myrow['person_id']]);
					$date = Dates::sql2date($myrow["tran_date"]);
					label_cell($date);
					amount_cell(-($myrow['amount']));
					Display::end_row();
				}
				Display::end_table();
			}
		}

		public static function display($woid, $suppress_view_link = false) {
			global $wo_types_array;
			$myrow = WO_WorkOrder::get($woid);
			if (strlen($myrow[0]) == 0) {
				Display::note(_("The work order number sent is not valid."));
				exit;
			}
			Display::start_table(Config::get('tables_style') . "  width=90%");
			if ($myrow["released"] == true) {
				$th = array(
					_("#"), _("Reference"), _("Type"), _("Manufactured Item"), _("Into Location"), _("Date"), _("Required By"), _("Quantity Required"), _("Released Date"), _("Manufactured"));
			} else {
				$th = array(
					_("#"), _("Reference"), _("Type"), _("Manufactured Item"), _("Into Location"), _("Date"), _("Required By"), _("Quantity Required"));
			}
			Display::table_header($th);
			Display::start_row();
			if ($suppress_view_link) {
				label_cell($myrow["id"]);
			} else {
				label_cell(get_trans_view_str(ST_WORKORDER, $myrow["id"]));
			}
			label_cell($myrow["wo_ref"]);
			label_cell($wo_types_array[$myrow["type"]]);
			stock_status_cell($myrow["stock_id"], $myrow["StockItemName"]);
			label_cell($myrow["location_name"]);
			label_cell(Dates::sql2date($myrow["date_"]));
			label_cell(Dates::sql2date($myrow["required_by"]));
			$dec = Item::qty_dec($myrow["stock_id"]);
			qty_cell($myrow["units_reqd"], false, $dec);
			if ($myrow["released"] == true) {
				label_cell(Dates::sql2date($myrow["released_date"]));
				qty_cell($myrow["units_issued"], false, $dec);
			}
			Display::end_row();
			DB_Comments::display_row(ST_WORKORDER, $woid);
			Display::end_table();
			if ($myrow["closed"] == true) {
				Display::note(_("This work order is closed."));
			}
		}

		function display_quick($woid, $suppress_view_link = false) {
			global $wo_types_array;
			$myrow = WO_WorkOrder::get($woid);
			if (strlen($myrow[0]) == 0) {
				Display::note(_("The work order number sent is not valid."));
				exit;
			}
			Display::start_table(Config::get('tables_style') . "  width=90%");
			$th = array(
				_("#"), _("Reference"), _("Type"), _("Manufactured Item"), _("Into Location"), _("Date"), _("Quantity"));
			Display::table_header($th);
			Display::start_row();
			if ($suppress_view_link) {
				label_cell($myrow["id"]);
			} else {
				label_cell(get_trans_view_str(ST_WORKORDER, $myrow["id"]));
			}
			label_cell($myrow["wo_ref"]);
			label_cell($wo_types_array[$myrow["type"]]);
			stock_status_cell($myrow["stock_id"], $myrow["StockItemName"]);
			label_cell($myrow["location_name"]);
			label_cell(Dates::sql2date($myrow["date_"]));
			qty_cell($myrow["units_issued"], false, Item::qty_dec($myrow["stock_id"]));
			Display::end_row();
			DB_Comments::display_row(ST_WORKORDER, $woid);
			Display::end_table();
			if ($myrow["closed"] == true) {
				Display::note(_("This work order is closed."));
			}
		}
	}

?>