<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Bill Of Materials"), SA_BOM);
  Validation::check(Validation::BOM_ITEMS, _("There are no manufactured or kit items defined in the system."), STOCK_MANUFACTURE);
  Validation::check(Validation::WORKCENTRES, _("There are no work centres defined in the system. BOMs require at least one work centre be defined."));
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  $selected_component = $selected_id;
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
  /**
   * @param $ultimate_parent
   * @param $component_to_check
   *
   * @return int
   */
  function check_for_recursive_bom($ultimate_parent, $component_to_check) {
    /* returns true ie 1 if the bom contains the parent part as a component
                ie the bom is recursive otherwise false ie 0 */
    $sql = "SELECT component FROM bom WHERE parent=" . DB::escape($component_to_check);
    $result = DB::query($sql, "could not check recursive bom");
    if ($result != 0) {
      while ($myrow = DB::fetch_row($result)) {
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
  /**
   * @param $selected_parent
   */
  function display_bom_items($selected_parent) {
    $result = WO::get_bom($selected_parent);
    Display::div_start('bom');
    start_table('tablestyle width60');
    $th = array(
      _("Code"), _("Description"), _("Location"), _("Work Centre"), _("Quantity"), _("Units"), '', ''
    );
    table_header($th);
    $k = 0;
    while ($myrow = DB::fetch($result)) {
      alt_table_row_color($k);
      label_cell($myrow["component"]);
      label_cell($myrow["description"]);
      label_cell($myrow["location_name"]);
      label_cell($myrow["WorkCentreDescription"]);
      qty_cell($myrow["quantity"], FALSE, Item::qty_dec($myrow["component"]));
      label_cell($myrow["units"]);
      edit_button_cell("Edit" . $myrow['id'], _("Edit"));
      delete_button_cell("Delete" . $myrow['id'], _("Delete"));
      end_row();
    } //END WHILE LIST LOOP
    end_table();
    Display::div_end();
  }

  /**
   * @param $selected_parent
   * @param $selected_component
   *
   * @return mixed
   */
  function on_submit($selected_parent, $selected_component = -1) {
    if (!Validation::post_num('quantity', 0)) {
      Event::error(_("The quantity entered must be numeric and greater than zero."));
      JS::set_focus('quantity');
      return;
    }
    if ($selected_component != -1) {
      $sql = "UPDATE bom SET workcentre_added=" . DB::escape($_POST['workcentre_added']) . ",loc_code=" . DB::escape($_POST['loc_code']) . ",
			quantity= " . Validation::input_num('quantity') . "
			WHERE parent=" . DB::escape($selected_parent) . "
			AND id=" . DB::escape($selected_component);
      DB::query($sql, "could not update bom");
      Event::success(_('Selected component has been updated'));
      $Mode = MODE_RESET;
    }
    else {
      /*Selected component is null cos no item selected on first time round
                        so must be adding a record must be Submitting new entries in the new
                        component form */
      //need to check not recursive bom component of itself!
      if (!check_for_recursive_bom($selected_parent, $_POST['component'])) {
        /*Now check to see that the component is not already on the bom */
        $sql = "SELECT component FROM bom
				WHERE parent=" . DB::escape($selected_parent) . "
				AND component=" . DB::escape($_POST['component']) . "
				AND workcentre_added=" . DB::escape($_POST['workcentre_added']) . "
				AND loc_code=" . DB::escape($_POST['loc_code']);
        $result = DB::query($sql, "check failed");
        if (DB::num_rows($result) == 0) {
          $sql = "INSERT INTO bom (parent, component, workcentre_added, loc_code, quantity)
					VALUES (" . DB::escape($selected_parent) . ", " . DB::escape($_POST['component']) . "," . DB::escape($_POST['workcentre_added']) . ", " . DB::escape($_POST['loc_code']) . ", " . Validation::input_num('quantity') . ")";
          DB::query($sql, "check failed");
          Event::notice(_("A new component part has been added to the bill of material for this item."));
          $Mode = MODE_RESET;
        }
        else {
          /*The component must already be on the bom */
          Event::error(_("The selected component is already on this bom. You can modify it's quantity but it cannot appear more than once on the same bom."));
        }
      } //end of if its not a recursive bom
      else {
        Event::error(_("The selected component is a parent of the current item. Recursive BOMs are not allowed."));
      }
    }
  }

  if ($Mode == MODE_DELETE) {
    $sql = "DELETE FROM bom WHERE id=" . DB::escape($selected_id);
    DB::query($sql, "Could not delete this bom components");
    Event::notice(_("The component item has been deleted from this bom"));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST['quantity']);
  }
  start_form();
  start_form(FALSE);
  start_table('tablestyle_noborder');
  Item_UI::manufactured_row(_("Select a manufacturable item:"), 'stock_id', NULL, FALSE, TRUE);
  if (list_updated('stock_id')) {
    Ajax::i()->activate('_page_body');
  }
  end_table();
  Display::br();
  end_form();
  if (get_post('stock_id') != '') { //Parent Item selected so display bom or edit component
    $selected_parent = $_POST['stock_id'];
    if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
      on_submit($selected_parent, $selected_id);
    }
    start_form();
    display_bom_items($selected_parent);
    echo '<br>';
    start_table('tablestyle2');
    if ($selected_id != -1) {
      if ($Mode == MODE_EDIT) {
        //editing a selected component from the link to the line item
        $sql = "SELECT bom.*,stock_master.description FROM " . "bom,stock_master
				WHERE id=" . DB::escape($selected_id) . "
				AND stock_master.stock_id=bom.component";
        $result = DB::query($sql, "could not get bom");
        $myrow = DB::fetch($result);
        $_POST['loc_code'] = $myrow["loc_code"];
        $_POST['component'] = $myrow["component"]; // by Tom Moulton
        $_POST['workcentre_added'] = $myrow["workcentre_added"];
        $_POST['quantity'] = Num::format($myrow["quantity"], Item::qty_dec($myrow["component"]));
        label_row(_("Component:"), $myrow["component"] . " - " . $myrow["description"]);
      }
      hidden('selected_id', $selected_id);
    }
    else {
      start_row();
      label_cell(_("Component:"));
      echo "<td>";
      echo Item_UI::component('component', $selected_parent, NULL, FALSE, TRUE);
      if (get_post('_component_update')) {
        Ajax::i()->activate('quantity');
      }
      echo "</td>";
      end_row();
    }
    hidden('stock_id', $selected_parent);
    Inv_Location::row(_("Location to Draw From:"), 'loc_code', NULL);
    workcenter_list_row(_("Work Centre Added:"), 'workcentre_added', NULL);
    $dec = Item::qty_dec(get_post('component'));
    $_POST['quantity'] = Num::format(Validation::input_num('quantity', 1), $dec);
    qty_row(_("Quantity:"), 'quantity', NULL, NULL, NULL, $dec);
    end_table(1);
    submit_add_or_update_center($selected_id == -1, '', 'both');
    end_form();
  }
  // ----------------------------------------------------------------------------------
  Page::end();

