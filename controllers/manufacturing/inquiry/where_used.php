<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Inventory Item Where Used Inquiry"), SA_WORKORDERANALYTIC);
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  start_form(FALSE);
  if (!Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id',$_POST['stock_id']);
  }
  echo "<div class='center'>" . _("Select an item to display its parent item(s).") . "&nbsp;";
  echo Item::select('stock_id', $_POST['stock_id'], FALSE, TRUE);
  echo "<hr></div>";
  Session::i()->setGlobal('stock_id',$_POST['stock_id']);
  /**
   * @param $row
   *
   * @return string
   */
  function select_link($row) {
    return DB_Pager::link($row["parent"] . " - " . $row["description"], "/manufacturing/manage/bom_edit.php?stock_id=" . $row["parent"]);
  }

  $sql = "SELECT
		bom.parent,
		workcentre.name As WorkCentreName,
		location.location_name,
		bom.quantity,
		parent.description
		FROM bom as bom, stock_master as parent, workcentres as workcentre, locations as location
		WHERE bom.parent = parent.stock_id 
			AND bom.workcentre_added = workcentre.id
			AND bom.loc_code = location.loc_code
			AND bom.component=" . DB::quote($_POST['stock_id']);
  $cols = array(
    _("Parent Item") => array('fun' => 'select_link'), _("Work Centre"), _("Location"), _("Quantity Required")
  );
  $table =& db_pager::new_db_pager('usage_table', $sql, $cols);
  $table->width = "80%";
  DB_Pager::display($table);
  end_form();
  Page::end();

