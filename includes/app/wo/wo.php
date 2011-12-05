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
	class WO {
		public static $bom_list = array();
		public static $qoh_stock = NULL;

		protected static function load_stock_levels($location = '') {
			$date = Dates::date2sql(Dates::Today());
			$sql = "SELECT stock_id, SUM(qty) FROM stock_moves WHERE tran_date <= '$date'";
			if ($location != '') {
				$sql .= " AND loc_code = " . DB::escape($location);
			}
			$sql .= " GROUP BY stock_id";
			$result = DB::query($sql, "QOH calulcation failed");
			while ($row = DB::fetch($result)) {
				static::$qoh_stock[$row[0]] = $row[1];
			}
		}

		// recursion fixed by Tom Moulton. Max 10 recursion levels.
		protected static function stock_demand_manufacture($stock_id, $qty, $demand_id, $location, $level = 0) {
			$demand = 0.0;
			if ($level > 10) {
				Errors::warning("BOM Too many Manufacturing levels deep $level");
				return $demand;
			}
			// Load all stock levels (stock moves) into static::$qoh_stock
			if (static::$qoh_stock == NULL) {
				static::$qoh_stock = array();
				static::load_stock_levels($location);
			}
			if (empty(static::$qoh_stock[$stock_id])) {
				$stock_qty = 0;
			}
			else {
				$stock_qty = static::$qoh_stock[$stock_id];
			}
			if ($qty <= $stock_qty) {
				return $demand;
			}
			$bom = @static::$bom_list[$stock_id];
			if ($bom == NULL) {
				$sql = "SELECT parent, component, quantity FROM "
				 . "bom WHERE parent = " . DB::escape($stock_id);
				if ($location != "") {
					$sql .= " AND loc_code = " . DB::escape($location);
				}
				$result = DB::query($sql, "Could not search bom");
				$bom = array();
				// Even if we get no results, remember that fact
				$bom[] = array($stock_id, '', 0);
				while ($row = DB::fetch_row($result)) {
					$bom[] = array($row[0], $row[1], $row[2]);
				}
				DB::free_result($result);
				static::$bom_list[$stock_id] = $bom;
			}
			$len = count($bom);
			$i = 0;
			while ($i < $len) {
				$row = $bom[$i];
				$i++;
				// Ignore the dummy entry
				if ($row[1] == '') {
					continue;
				}
				$q = $qty * $row[2];
				if ($row[1] == $demand_id) {
					$demand += $q;
				}
				$demand += static::stock_demand_manufacture($row[1], $q, $demand_id, $location, $level + 1);
			}
			return $demand;
		}

		// recursion fixed by Tom Moulton
		public static function get_demand_asm_qty($stock_id, $location) {
			$demand_qty = 0.0;
			$sql = "SELECT sales_order_details.stk_code, SUM(sales_order_details.quantity-sales_order_details.qty_sent)
				 AS Demmand
				 FROM sales_order_details,
						sales_orders,
						stock_master
				 WHERE sales_orders.order_no = sales_order_details.order_no AND
				 	sales_orders.trans_type=" . ST_SALESORDER . " AND
					sales_orders.trans_type=sales_order_details.trans_type AND ";
			if ($location != "") {
				$sql .= "sales_orders.from_stk_loc =" . DB::escape($location) . " AND ";
			}
			$sql .= "sales_order_details.quantity-sales_order_details.qty_sent > 0 AND
				 stock_master.stock_id=sales_order_details.stk_code AND
				 (stock_master.mb_flag='" . STOCK_MANUFACTURE . "' OR stock_master.mb_flag='A')
				 GROUP BY sales_order_details.stk_code";
			$result = DB::query($sql, "No transactions were returned");
			while ($row = DB::fetch_row($result)) {
				$demand_qty += static::stock_demand_manufacture($row[0], $row[1], $stock_id, $location);
			}
			return $demand_qty;
		}

		public static function get_on_porder_qty($stock_id, $location) {
			$sql = "SELECT SUM(purch_order_details.quantity_ordered - "
			 . "purch_order_details.quantity_received) AS qoo
		FROM purch_order_details INNER JOIN "
			 . "purch_orders ON purch_order_details.order_no=purch_orders.order_no
		WHERE purch_order_details.item_code=" . DB::escape($stock_id) . " ";
			if ($location != "") {
				$sql .= "AND purch_orders.into_stock_location=" . DB::escape($location) . " ";
			}
			$sql .= "AND purch_order_details.item_code=" . DB::escape($stock_id);
			$qoo_result = DB::query($sql, "could not receive quantity on order for item");
			if (DB::num_rows($qoo_result) == 1) {
				$qoo_row = DB::fetch_row($qoo_result);
				$qoo = $qoo_row[0];
			} else {
				$qoo = 0;
			}
			return $qoo;
		}

		public static function get_on_worder_qty($stock_id, $location) {
			$sql = "SELECT SUM((workorders.units_reqd-workorders.units_issued) *
		(wo_requirements.units_req-wo_requirements.units_issued)) AS qoo
		FROM wo_requirements INNER JOIN workorders
			ON wo_requirements.workorder_id=workorders.id
		WHERE wo_requirements.stock_id=" . DB::escape($stock_id) . " ";
			if ($location != "") {
				$sql .= "AND wo_requirements.loc_code=" . DB::escape($location) . " ";
			}
			$sql .= "AND workorders.released=1";
			$qoo_result = DB::query($sql, "could not receive quantity on order for item");
			if (DB::num_rows($qoo_result) == 1) {
				$qoo_row = DB::fetch_row($qoo_result);
				$qoo = $qoo_row[0];
			}
			else
			{
				$qoo = 0.0;
			}
			$flag = WO::get_mb_flag($stock_id);
			if ($flag == 'A' || $flag == STOCK_MANUFACTURE) {
				$sql = "SELECT SUM((workorders.units_reqd-workorders.units_issued)) AS qoo
			FROM workorders
			WHERE workorders.stock_id=" . DB::escape($stock_id) . " ";
				if ($location != "") {
					$sql .= "AND workorders.loc_code=" . DB::escape($location) . " ";
				}
				$sql .= "AND workorders.released=1";
				$qoo_result = DB::query($sql, "could not receive quantity on order for item");
				if (DB::num_rows($qoo_result) == 1) {
					$qoo_row = DB::fetch_row($qoo_result);
					$qoo += $qoo_row[0];
				}
			}
			return $qoo;
		}

		public static function get_mb_flag($stock_id) {
			$sql = "SELECT mb_flag FROM stock_master WHERE stock_id = "
			 . DB::escape($stock_id);
			$result = DB::query($sql, "retreive mb_flag from item");
			if (DB::num_rows($result) == 0) {
				return -1;
			}
			$myrow = DB::fetch_row($result);
			return $myrow[0];
		}

		public static function get_bom($item) {
			$sql = "SELECT bom.*, locations.location_name, workcentres.name AS WorkCentreDescription,
 	stock_master.description, stock_master.mb_flag AS ResourceType,
 	stock_master.material_cost+ stock_master.labour_cost+stock_master.overhead_cost AS standard_cost, units,
 	bom.quantity * (stock_master.material_cost+ stock_master.labour_cost+ stock_master.overhead_cost) AS ComponentCost
 	FROM (workcentres, locations, bom) INNER JOIN stock_master ON bom.component = stock_master.stock_id
 	WHERE bom.parent = " . DB::escape($item) . "
		AND workcentres.id=bom.workcentre_added
		AND bom.loc_code = locations.loc_code ORDER BY bom.id";
			return DB::query($sql, "The bill of material could not be retrieved");
		}

		public static function has_bom($item) {
			$result = WO::get_bom($item);
			return (DB::num_rows($result) != 0);
		}

		public static function display_bom($item_check) {
			$result = WO::get_bom($item_check);
			if (DB::num_rows($result) == 0) {
				Display::note(_("The bill of material for this item is empty."), 0, 1);
			} else {
				start_table('tablestyle');
				$th = array(
					_("Component"), _("Description"), _("Work Centre"), _("From Location"), _("Quantity"), _("Unit Cost"), _("Total Cost"));
				table_header($th);
				$j = 1;
				$k = 0; //row colour counter
				$total_cost = 0;
				while ($myrow = DB::fetch($result)) {
					alt_table_row_color($k);
					label_cell($myrow["component"]);
					label_cell($myrow["description"]);
					label_cell($myrow["WorkCentreDescription"]);
					label_cell($myrow["location_name"]);
					qty_cell($myrow["quantity"], false, Item::qty_dec($myrow["component"]));
					amount_cell($myrow["standard_cost"]);
					amount_cell($myrow["ComponentCost"]);
					end_row();
					$total_cost += $myrow["ComponentCost"];
					$j++;
					If ($j == 12) {
						$j = 1;
						table_header($th);
					}
					//end of page full new headings if
				}
				//end of while
				label_row("<b>" . _("Total Cost") . "</b>", "<b>" . Num::format($total_cost, User::price_dec()) . "</b>",
					"colspan=6 class=right", "nowrap class=right");
				end_table();
			}
		}

		public static function has_stock_holding($mb_flag) {
			return $mb_flag == STOCK_PURCHASED || $mb_flag == STOCK_MANUFACTURE;
		}

		//--------------------------------------------------------------------------------------
		public static function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "manufacturing/view/";
			if ($type == ST_MANUISSUE) {
				$viewer .= "wo_issue_view.php";
			}
			elseif ($type == ST_MANURECEIVE) {
				$viewer .= "wo_production_view.php";
			}
			elseif ($type == ST_WORKORDER) {
				$viewer .= "work_order_view.php";
			}
			else {
				return null;
			}
			$viewer .= "?trans_no=$trans_no";
			if ($label == "") {
				$label = $trans_no;
			}
			return viewer_link($label, $viewer, $class, $id, $icon);
		}

				public static function add($wo_ref, $loc_code, $units_reqd, $stock_id, $type, $date_, $required_by, $memo_, $costs, $cr_acc,
																	 $labour, $cr_lab_acc) {
					if (!($type == WO_ADVANCED)) {
						return WO_Quick::add($wo_ref, $loc_code, $units_reqd, $stock_id, $type, $date_, $memo_, $costs, $cr_acc, $labour,
							$cr_lab_acc);
					}
					DB::begin_transaction();
					WO_Cost::add_material($stock_id, $units_reqd, $date_);
					$date = Dates::date2sql($date_);
					$required = Dates::date2sql($required_by);
					$sql = "INSERT INTO workorders (wo_ref, loc_code, units_reqd, stock_id,
				type, date_, required_by)
		 	VALUES (" . DB::escape($wo_ref) . ", " . DB::escape($loc_code) . ", " . DB::escape($units_reqd) . ", " . DB::escape($stock_id) . ",
				" . DB::escape($type) . ", '$date', " . DB::escape($required) . ")";
					DB::query($sql, "could not add work order");
					$woid = DB::insert_id();
					DB_Comments::add(ST_WORKORDER, $woid, $required_by, $memo_);
					Ref::save(ST_WORKORDER,  $wo_ref);
					DB_AuditTrail::add(ST_WORKORDER, $woid, $date_);
					DB::commit_transaction();
					return $woid;
				}

				public static function update($woid, $loc_code, $units_reqd, $stock_id, $date_, $required_by, $memo_) {
					DB::begin_transaction();
					WO_Cost::add_material($_POST['old_stk_id'], -$_POST['old_qty'], $date_);
					WO_Cost::add_material($stock_id, $units_reqd, $date_);
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
					WO_Cost::add_material($_POST['stock_id'], -$_POST['quantity'], $_POST['date_']);
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
					$myrow = WO::get($woid);
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
					$work_order = WO::get($woid);
					if (!($work_order["type"] == WO_ADVANCED)) {
						$date = Dates::sql2date($work_order['date_']);
						$qty = $work_order['units_reqd'];
						WO_Cost::add_material($work_order['stock_id'], -$qty, $date); // remove avg. cost for qty
						$cost = WO::get_gl($woid, WO_LABOUR); // get the labour cost and reduce avg cost
						if ($cost != 0) {
							WO_Cost::add_labour($work_order['stock_id'], -$qty, $date, $cost);
						}
						$cost = WO::get_gl($woid, WO_OVERHEAD); // get the overhead cost and reduce avg cost
						if ($cost != 0) {
							WO_Cost::add_overhead($work_order['stock_id'], -$qty, $date, $cost);
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
						WO_Cost::add_material($work_order['stock_id'], -$work_order['units_reqd'], $date); // remove avg. cost for qty
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
							WO_Cost::add_issue($work_order['stock_id'], -$qty, $date, $cost);
						}
						$cost = WO::get_gl($woid, WO_LABOUR); // get the labour cost and reduce avg cost
						if ($cost != 0) {
							WO_Cost::add_labour($work_order['stock_id'], -$qty, $date, $cost);
						}
						$cost = WO::get_gl($woid, WO_OVERHEAD); // get the overhead cost and reduce avg cost
						if ($cost != 0) {
							WO_Cost::add_overhead($work_order['stock_id'], -$qty, $date, $cost);
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
						start_table('tablestyle');
						$th = array(_("#"), _("Type"), _("Date"), _("Amount"));
						table_header($th);
						$k = 0; //row colour counter
						while ($myrow = DB::fetch($result)) {
							alt_table_row_color($k);
							label_cell(GL_UI::view(ST_WORKORDER, $myrow["type_no"], $myrow["type_no"]));
							label_cell($wo_cost_types[$myrow['person_id']]);
							$date = Dates::sql2date($myrow["tran_date"]);
							label_cell($date);
							amount_cell(-($myrow['amount']));
							end_row();
						}
						end_table();
					}
				}

				public static function display($woid, $suppress_view_link = false) {
					global $wo_types_array;
					$myrow = WO::get($woid);
					if (strlen($myrow[0]) == 0) {
						Display::note(_("The work order number sent is not valid."));
						exit;
					}
					start_table('tablestyle width90');
					if ($myrow["released"] == true) {
						$th = array(
							_("#"), _("Reference"), _("Type"), _("Manufactured Item"), _("Into Location"), _("Date"), _("Required By"), _("Quantity Required"), _("Released Date"), _("Manufactured"));
					} else {
						$th = array(
							_("#"), _("Reference"), _("Type"), _("Manufactured Item"), _("Into Location"), _("Date"), _("Required By"), _("Quantity Required"));
					}
					table_header($th);
					start_row();
					if ($suppress_view_link) {
						label_cell($myrow["id"]);
					} else {
						label_cell(GL_UI::trans_view(ST_WORKORDER, $myrow["id"]));
					}
					label_cell($myrow["wo_ref"]);
					label_cell($wo_types_array[$myrow["type"]]);
					Item_UI::status_cell($myrow["stock_id"], $myrow["StockItemName"]);
					label_cell($myrow["location_name"]);
					label_cell(Dates::sql2date($myrow["date_"]));
					label_cell(Dates::sql2date($myrow["required_by"]));
					$dec = Item::qty_dec($myrow["stock_id"]);
					qty_cell($myrow["units_reqd"], false, $dec);
					if ($myrow["released"] == true) {
						label_cell(Dates::sql2date($myrow["released_date"]));
						qty_cell($myrow["units_issued"], false, $dec);
					}
					end_row();
					DB_Comments::display_row(ST_WORKORDER, $woid);
					end_table();
					if ($myrow["closed"] == true) {
						Display::note(_("This work order is closed."));
					}
				}
	}