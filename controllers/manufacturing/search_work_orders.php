<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(800, 500);
  if (isset($_GET['outstanding_only']) && ($_GET['outstanding_only'] == true)) {
    // curently outstanding simply means not closed
    $outstanding_only = 1;
    Page::start(_($help_context = "Search Outstanding Work Orders"), SA_MANUFTRANSVIEW);
  } else {
    $outstanding_only = 0;
    Page::start(_($help_context = "Search Work Orders"), SA_MANUFTRANSVIEW);
  }
  // Ajax updates
  //
  if (Input::post('SearchOrders')) {
    Ajax::activate('orders_tbl');
  } elseif (Input::post('_OrderNumber_changed')) {
    $disable = Input::post('OrderNumber') !== '';
    Ajax::addDisable(true, 'StockLocation', $disable);
    Ajax::addDisable(true, 'OverdueOnly', $disable);
    Ajax::addDisable(true, 'OpenOnly', $disable);
    Ajax::addDisable(true, 'SelectedStockItem', $disable);
    if ($disable) {
      JS::setFocus('OrderNumber');
    } else {
      JS::setFocus('StockLocation');
    }
    Ajax::activate('orders_tbl');
  }
  if (isset($_GET["stock_id"])) {
    $_POST['SelectedStockItem'] = $_GET["stock_id"];
  }
  Forms::start(false, $_SERVER['DOCUMENT_URI'] . "?outstanding_only=$outstanding_only");
  Table::start('tablestyle_noborder');
  Row::start();
  Forms::refCells(_("Reference:"), 'OrderNumber', '', null, '', true);
  Inv_Location::cells(_("at Location:"), 'StockLocation', null, true);
  Forms::checkCells(_("Only Overdue:"), 'OverdueOnly', null);
  if ($outstanding_only == 0) {
    Forms::checkCells(_("Only Open:"), 'OpenOnly', null);
  }
  Item_UI::manufactured_cells(_("for item:"), 'SelectedStockItem', null, true);
  Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  Row::end();
  Table::end();
  /**
   * @param $row
   *
   * @return bool
   */
  function check_overdue($row)
  {
    return (!$row["closed"] && Dates::differenceBetween(Dates::today(), Dates::sqlToDate($row["required_by"]), "d") > 0);
  }

  /**
   * @param $dummy
   * @param $order_no
   *
   * @return null|string
   */
  function view_link($dummy, $order_no)
  {
    return GL_UI::trans_view(ST_WORKORDER, $order_no);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function view_stock($row)
  {
    return Item_UI::status($row["stock_id"], $row["description"], false);
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function wo_type_name($dummy, $type)
  {
    global $wo_types_array;

    return $wo_types_array[$type];
  }

  /**
   * @param $row
   *
   * @return string
   */
  function edit_link($row)
  {
    return $row['closed'] ? '<i>' . _('Closed') . '</i>' :
      DB_Pager::link(_("Edit"), "/manufacturing/work_order_entry.php?trans_no=" . $row["id"], ICON_EDIT);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function release_link($row)
  {
    return $row["closed"] ? '' :
      ($row["released"] == 0 ? DB_Pager::link(_('Release'), "/manufacturing/work_order_release.php?trans_no=" . $row["id"]) :
        DB_Pager::link(_('Issue'), "/manufacturing/work_order_issue.php?trans_no=" . $row["id"]));
  }

  /**
   * @param $row
   *
   * @return string
   */
  function produce_link($row)
  {
    return $row["closed"] || !$row["released"] ? '' :
      DB_Pager::link(_('Produce'), "/manufacturing/work_order_add_finished.php?trans_no=" . $row["id"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function costs_link($row)
  {
    /*

                           return $row["closed"] || !$row["released"] ? '' :
                             DB_Pager::link(_('Costs'),
                               "/gl/gl_bank.php?NewPayment=1&PayType="
                               .PT_WORKORDER. "&PayPerson=" .$row["id"]);
                         */

    return $row["closed"] || !$row["released"] ? '' :
      DB_Pager::link(_('Costs'), "/manufacturing/work_order_costs.php?trans_no=" . $row["id"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function view_gl_link($row)
  {
    if ($row['closed'] == 0) {
      return '';
    }

    return GL_UI::view(ST_WORKORDER, $row['id']);
  }

  /**
   * @param $row
   * @param $amount
   *
   * @return int|string
   */
  function dec_amount($row, $amount)
  {
    return Num::format($amount, $row['decimals']);
  }

  $sql
    = "SELECT
    workorder.id,
    workorder.wo_ref,
    workorder.type,
    location.location_name,
    item.description,
    workorder.units_reqd,
    workorder.units_issued,
    workorder.date_,
    workorder.required_by,
    workorder.released_date,
    workorder.closed,
    workorder.released,
    workorder.stock_id,
    unit.decimals
    FROM workorders as workorder," . "stock_master as item," . "item_units as unit," . "locations as location
    WHERE workorder.stock_id=item.stock_id
        AND workorder.loc_code=location.loc_code
        AND item.units=unit.abbr";
  if (Forms::hasPost('OpenOnly') || $outstanding_only != 0) {
    $sql .= " AND workorder.closed=0";
  }
  if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
    $sql .= " AND workorder.loc_code=" . DB::quote($_POST['StockLocation']);
  }
  if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
    $sql .= " AND workorder.wo_ref LIKE " . DB::quote('%' . $_POST['OrderNumber'] . '%');
  }
  if (isset($_POST['SelectedStockItem']) && $_POST['SelectedStockItem'] != ALL_TEXT) {
    $sql .= " AND workorder.stock_id=" . DB::quote($_POST['SelectedStockItem']);
  }
  if (Forms::hasPost('OverdueOnly')) {
    $Today = Dates::dateToSql(Dates::today());
    $sql .= " AND workorder.required_by < '$Today' ";
  }
  $cols  = array(
    _("#")               => array('fun' => 'view_link'),
    _("Reference"),
    // viewlink 2 ?
    _("Type")            => array('fun' => 'wo_type_name'),
    _("Location"),
    _("Item")            => array('fun' => 'view_stock'),
    _("Required")        => array(
      'fun' => 'dec_amount', 'align' => 'right'
    ),
    _("Manufactured")    => array(
      'fun' => 'dec_amount', 'align' => 'right'
    ),
    _("Date")            => 'date',
    _("Required By")     => array(
      'type' => 'date', 'ord' => ''
    ),
    array(
      'insert' => true, 'fun' => 'edit_link'
    ),
    array(
      'insert' => true, 'fun' => 'release_link'
    ),
    array(
      'insert' => true, 'fun' => 'produce_link'
    ),
    array(
      'insert' => true, 'fun' => 'costs_link'
    ),
    array(
      'insert' => true, 'fun' => 'view_gl_link'
    )
  );
  $table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
  $table->set_marker('check_overdue', _("Marked orders are overdue."));
  $table->width = "90%";
  DB_Pager::display($table);
  Forms::end();
  Page::end();

