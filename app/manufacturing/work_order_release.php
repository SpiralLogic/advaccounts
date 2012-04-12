<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  JS::open_window(800, 500);
  Page::start(_($help_context = "Work Order Release to Manufacturing"), SA_MANUFRELEASE);
  if (isset($_GET["trans_no"])) {
    $selected_id = $_GET["trans_no"];
  }
  elseif (isset($_POST["selected_id"])) {
    $selected_id = $_POST["selected_id"];
  }
  else {
    Event::warning("This page must be called with a work order reference");
    exit;
  }
  /**
   * @param $myrow
   *
   * @return bool
   */
  function can_process($myrow) {
    if ($myrow['released']) {
      Event::error(_("This work order has already been released."));
      JS::set_focus('released');
      return FALSE;
    }
    // make sure item has components
    if (!WO::has_bom($myrow['stock_id'])) {
      Event::error(_("This Work Order cannot be released. The selected item to manufacture does not have a bom."));
      JS::set_focus('stock_id');
      return FALSE;
    }
    return TRUE;
  }

  if (isset($_POST['release'])) {
    WO::release($selected_id, $_POST['released_date'], $_POST['memo_']);
    Event::notice(_("The work order has been released to manufacturing."));
    Display::note(GL_UI::trans_view(ST_WORKORDER, $selected_id, _("View this Work Order")));
    Display::link_no_params("search_work_orders.php", _("Select another &work order"));
    Ajax::i()->activate('_page_body');
    Page::end();
    exit;
  }
  start_form();
  $myrow = WO::get($selected_id);
  $_POST['released'] = $myrow["released"];
  $_POST['memo_'] = "";
  if (can_process($myrow)) {
    start_table('tablestyle2');
    label_row(_("Work Order #:"), $selected_id);
    label_row(_("Work Order Reference:"), $myrow["wo_ref"]);
    date_row(_("Released Date") . ":", 'released_date');
    textarea_row(_("Memo:"), 'memo_', $_POST['memo_'], 40, 5);
    end_table(1);
    submit_center('release', _("Release Work Order"), TRUE, '', 'default');
    hidden('selected_id', $selected_id);
    hidden('stock_id', $myrow['stock_id']);
  }
  end_form();
  Page::end();


