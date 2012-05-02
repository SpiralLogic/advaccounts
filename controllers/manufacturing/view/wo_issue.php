<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  JS::open_window(900, 500);
  Page::start(_($help_context = "View Work Order Issue"), SA_MANUFTRANSVIEW, TRUE);
  if ($_GET['trans_no'] != "") {
    $wo_issue_no = $_GET['trans_no'];
  }
  /**
   * @param $issue_no
   */
  function display_wo_issue($issue_no) {
    $myrow = WO_Issue::get($issue_no);
    Display::br(1);
    start_table('tablestyle');
    $th = array(
      _("Issue #"), _("Reference"), _("For Work Order #"), _("Item"), _("From Location"), _("To Work Centre"), _("Date of Issue")
    );
    table_header($th);
    start_row();
    label_cell($myrow["issue_no"]);
    label_cell($myrow["reference"]);
    label_cell(GL_UI::trans_view(ST_WORKORDER, $myrow["workorder_id"]));
    label_cell($myrow["stock_id"] . " - " . $myrow["description"]);
    label_cell($myrow["location_name"]);
    label_cell($myrow["WorkCentreName"]);
    label_cell(Dates::sql2date($myrow["issue_date"]));
    end_row();
    DB_Comments::display_row(28, $issue_no);
    end_table(1);
    Display::is_voided(28, $issue_no, _("This issue has been voided."));
  }

  /**
   * @param $issue_no
   */
  function display_wo_issue_details($issue_no) {
    $result = WO_Issue::get_details($issue_no);
    if (DB::num_rows($result) == 0) {
      Event::warning(_("There are no items for this issue."));
    }
    else {
      start_table('tablestyle');
      $th = array(_("Component"), _("Quantity"), _("Units"));
      table_header($th);
      $j = 1;
      $k = 0; //row colour counter
      $total_cost = 0;
      while ($myrow = DB::fetch($result)) {
        alt_table_row_color($k);
        label_cell($myrow["stock_id"] . " - " . $myrow["description"]);
        qty_cell($myrow["qty_issued"], FALSE, Item::qty_dec($myrow["stock_id"]));
        label_cell($myrow["units"]);
        end_row();
        ;
        $j++;
        If ($j == 12) {
          $j = 1;
          table_header($th);
        }
        //end of page full new headings if
      }
      //end of while
      end_table();
    }
  }

  Display::heading($systypes_array[ST_MANUISSUE] . " # " . $wo_issue_no);
  display_wo_issue($wo_issue_no);
  Display::heading(_("Items for this Issue"));
  display_wo_issue_details($wo_issue_no);
  echo "<br>";
  Page::end(TRUE);



