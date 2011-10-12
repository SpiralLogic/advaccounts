<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = 'SA_BOM';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	page(_($help_context = "Bill Of Materials"));

	check_db_has_bom_stock_items(_("There are no manufactured or kit items defined in the system."));

	check_db_has_workcentres(_("There are no work centres defined in the system. BOMs require at least one work centre be defined."));

	simple_page_mode(true);
	$selected_component = $selected_id;
	//--------------------------------------------------------------------------------------------------

	//if (isset($_GET["NewItem"]))
	//{
	//	$_POST['stock_id'] = $_GET["NewItem"];
	//}
	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $_GET['stock_id'];
		$selected_parent = $_GET['stock_id'];
	}

	/* selected_parent could come from a post or a get */
	/*if (isset($_GET["selected_parent"]))
 {
	 $selected_parent = $_GET["selected_parent"];
 }
 else if (isset($_POST["selected_parent"]))
 {
	 $selected_parent = $_POST["selected_parent"];
 }
 */
	/* selected_component could also come from a post or a get */
	/*if (isset($_GET["selected_component"]))
 {
	 $selected_component = $_GET["selected_component"];
 }
 else
 {
	 $selected_component = get_post("selected_component", -1);
 }
 */

	//--------------------------------------------------------------------------------------------------

	function check_for_recursive_bom($ultimate_parent, $component_to_check) {

		/* returns true ie 1 if the bom contains the parent part as a component
			ie the bom is recursive otherwise false ie 0 */

		$sql = "SELECT component FROM bom WHERE parent=" . DBOld::escape($component_to_check);
		$result = DBOld::query($sql, "could not check recursive bom");

		if ($result != 0) {
			while ($myrow = DBOld::fetch_row($result))
			{
				if ($myrow[0] == $ultimate_parent) {
					return 1;
				}

				if (check_for_recursive_bom($ultimate_parent, $myrow[0])) {
					return 1;
				}
			} //(while loop)
		} //end if $result is true

		return 0;
	} //end of function check_for_recursive_bom

	//--------------------------------------------------------------------------------------------------

	function display_bom_items($selected_parent) {

		$result = Manufacturing::get_bom($selected_parent);
		div_start('bom');
		start_table(Config::get('tables.style') . "  width=60%");
		$th = array(_("Code"), _("Description"), _("Location"),
			_("Work Centre"), _("Quantity"), _("Units"), '', ''
		);
		table_header($th);

		$k = 0;
		while ($myrow = DBOld::fetch($result))
		{

			alt_table_row_color($k);

			label_cell($myrow["component"]);
			label_cell($myrow["description"]);
			label_cell($myrow["location_name"]);
			label_cell($myrow["WorkCentreDescription"]);
			qty_cell($myrow["quantity"], false, get_qty_dec($myrow["component"]));
			label_cell($myrow["units"]);
			edit_button_cell("Edit" . $myrow['id'], _("Edit"));
			delete_button_cell("Delete" . $myrow['id'], _("Delete"));
			end_row();
		} //END WHILE LIST LOOP
		end_table();
		div_end();
	}

	//--------------------------------------------------------------------------------------------------

	function on_submit($selected_parent, $selected_component = -1) {
		if (!check_num('quantity', 0)) {
			ui_msgs::display_error(_("The quantity entered must be numeric and greater than zero."));
			ui_view::set_focus('quantity');
			return;
		}

		if ($selected_component != -1) {

			$sql = "UPDATE bom SET workcentre_added=" . DBOld::escape($_POST['workcentre_added'])
			 . ",loc_code=" . DBOld::escape($_POST['loc_code']) . ",
			quantity= " . input_num('quantity') . "
			WHERE parent=" . DBOld::escape($selected_parent) . "
			AND id=" . DBOld::escape($selected_component);
			Errors::check_db_error("Could not update this bom component", $sql);

			DBOld::query($sql, "could not update bom");
			ui_msgs::display_notification(_('Selected component has been updated'));
			$Mode = 'RESET';
		}
		else
		{

			/*Selected component is null cos no item selected on first time round
					so must be adding a record must be Submitting new entries in the new
					component form */

			//need to check not recursive bom component of itself!
			if (!check_for_recursive_bom($selected_parent, $_POST['component'])) {

				/*Now check to see that the component is not already on the bom */
				$sql = "SELECT component FROM bom
				WHERE parent=" . DBOld::escape($selected_parent) . "
				AND component=" . DBOld::escape($_POST['component']) . "
				AND workcentre_added=" . DBOld::escape($_POST['workcentre_added']) . "
				AND loc_code=" . DBOld::escape($_POST['loc_code']);
				$result = DBOld::query($sql, "check failed");

				if (DBOld::num_rows($result) == 0) {
					$sql = "INSERT INTO bom (parent, component, workcentre_added, loc_code, quantity)
					VALUES (" . DBOld::escape($selected_parent) . ", " . DBOld::escape($_POST['component']) . ","
					 . DBOld::escape($_POST['workcentre_added']) . ", " . DBOld::escape($_POST['loc_code']) . ", "
					 . input_num('quantity') . ")";

					DBOld::query($sql, "check failed");
					ui_msgs::display_notification(_("A new component part has been added to the bill of material for this item."));
					$Mode = 'RESET';
				}
				else
				{
					/*The component must already be on the bom */
					ui_msgs::display_error(_("The selected component is already on this bom. You can modify it's quantity but it cannot appear more than once on the same bom."));
				}
			} //end of if its not a recursive bom
			else
			{
				ui_msgs::display_error(_("The selected component is a parent of the current item. Recursive BOMs are not allowed."));
			}
		}
	}

	//--------------------------------------------------------------------------------------------------

	if ($Mode == 'Delete') {
		$sql = "DELETE FROM bom WHERE id=" . DBOld::escape($selected_id);
		DBOld::query($sql, "Could not delete this bom components");

		ui_msgs::display_notification(_("The component item has been deleted from this bom"));
		$Mode = 'RESET';
	}

	if ($Mode == 'RESET') {
		$selected_id = -1;
		unset($_POST['quantity']);
	}

	//--------------------------------------------------------------------------------------------------

	start_form();

	start_form(false, true);
	start_table("class='tablestyle_noborder'");
	stock_manufactured_items_list_row(_("Select a manufacturable item:"), 'stock_id', null, false, true);
	if (list_updated('stock_id'))
		$Ajax->activate('_page_body');
	end_table();
	br();

	end_form();
	//--------------------------------------------------------------------------------------------------

	if (get_post('stock_id') != '') { //Parent Item selected so display bom or edit component
		$selected_parent = $_POST['stock_id'];
		if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM')
			on_submit($selected_parent, $selected_id);
		//--------------------------------------------------------------------------------------

		start_form();
		display_bom_items($selected_parent);
		//--------------------------------------------------------------------------------------
		echo '<br>';

		start_table(Config::get('tables.style2'));

		if ($selected_id != -1) {
			if ($Mode == 'Edit') {
				//editing a selected component from the link to the line item
				$sql = "SELECT bom.*,stock_master.description FROM "
				 . "bom,stock_master
				WHERE id=" . DBOld::escape($selected_id) . "
				AND stock_master.stock_id=bom.component";

				$result = DBOld::query($sql, "could not get bom");
				$myrow = DBOld::fetch($result);

				$_POST['loc_code'] = $myrow["loc_code"];
				$_POST['component'] = $myrow["component"]; // by Tom Moulton
				$_POST['workcentre_added'] = $myrow["workcentre_added"];
				$_POST['quantity'] = number_format2($myrow["quantity"], get_qty_dec($myrow["component"]));
				label_row(_("Component:"), $myrow["component"] . " - " . $myrow["description"]);
			}
			hidden('selected_id', $selected_id);
		}
		else
		{
			start_row();
			label_cell(_("Component:"));

			echo "<td>";
			echo stock_component_items_list('component', $selected_parent, null, false, true);
			if (get_post('_component_update')) {
				$Ajax->activate('quantity');
			}
			echo "</td>";
			end_row();
		}
		hidden('stock_id', $selected_parent);

		locations_list_row(_("Location to Draw From:"), 'loc_code', null);
		workcenter_list_row(_("Work Centre Added:"), 'workcentre_added', null);
		$dec = get_qty_dec(get_post('component'));
		$_POST['quantity'] = number_format2(input_num('quantity', 1), $dec);
		qty_row(_("Quantity:"), 'quantity', null, null, null, $dec);

		end_table(1);
		submit_add_or_update_center($selected_id == -1, '', 'both');
		end_form();
	}
	// ----------------------------------------------------------------------------------

	end_page();

?>
