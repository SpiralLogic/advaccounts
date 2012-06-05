<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_($help_context = "Tax Groups"), SA_TAXGROUPS);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  Validation::check(Validation::TAX_TYPES, _("There are no tax types defined. Define tax types before defining tax groups."));
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The tax group name cannot be empty."));
      JS::set_focus('name');
    }
    /* Editable rate has been removed 090920 Joe Hunt
             else
             {
               // make sure any entered rates are valid
               for ($i = 0; $i < 5; $i++)
               {
                 if (isset($_POST['tax_type_id' . $i]) &&
                   $_POST['tax_type_id' . $i] != ALL_NUMERIC	&&
                   !Validation::post_num('rate' . $i, 0))
                 {
                 Event::error( _("An entered tax rate is invalid or less than zero."));
                   $input_error = 1;
                 JS::set_focus('rate');
                 break;
                 }
               }
             }
             */
    if ($input_error != 1) {
      // create an array of the taxes and array of rates
      $taxes = array();
      $rates = array();
      for ($i = 0; $i < 5; $i++) {
        if (isset($_POST['tax_type_id' . $i]) && $_POST['tax_type_id' . $i] != ANY_NUMERIC
        ) {
          $taxes[] = $_POST['tax_type_id' . $i];
          $rates[] = Tax_Types::get_default_rate($_POST['tax_type_id' . $i]);
          //Editable rate has been removed 090920 Joe Hunt
          //$rates[] = Validation::input_num('rate' . $i);
        }
      }
      if ($selected_id != -1) {
        Tax_Groups::update($selected_id, $_POST['name'], $_POST['tax_shipping'], $taxes, $rates);
        Event::success(_('Selected tax group has been updated'));
      }
      else {
        Tax_Groups::add($_POST['name'], $_POST['tax_shipping'], $taxes, $rates);
        Event::success(_('New tax group has been added'));
      }
      $Mode = MODE_RESET;
    }
  }


  if ($Mode == MODE_DELETE) {
      Tax_Groups::delete($selected_id);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = Form::getPost('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Tax_Groups::get_all(Form::hasPost('show_inactive'));
  Form::start();
  Table::start('tablestyle grid');
  $th = array(_("Description"), _("Shipping Tax"), "", "");
   Form::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["name"]);
    if ($myrow["tax_shipping"]) {
      Cell::label(_("Yes"));
    }
    else {
      Cell::label(_("No"));
    }
    /*for ($i=0; $i< 5; $i++)
                  if ($myrow["type" . $i] != ALL_NUMERIC)
                    echo "<td>" . $myrow["type" . $i] . "</td>";*/
     Form::inactiveControlCell($myrow["id"], $myrow["inactive"], 'tax_groups', 'id');
    Form::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Form::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    Row::end();
    ;
  }
   Form::inactiveControlRow($th);
  Table::end(1);
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    //editing an existing status code
    if ($Mode == MODE_EDIT) {
      $group = Tax_Groups::get($selected_id);
      $_POST['name'] = $group["name"];
      $_POST['tax_shipping'] = $group["tax_shipping"];
      $items = Tax_Groups::get_for_item($selected_id);
      $i = 0;
      while ($tax_item = DB::fetch($items)) {
        $_POST['tax_type_id' . $i] = $tax_item["tax_type_id"];
        $_POST['rate' . $i] = Num::percent_format($tax_item["rate"]);
        $i++;
      }
      while ($i < 5) {
        unset($_POST['tax_type_id' . $i++]);
      }
    }
    Form::hidden('selected_id', $selected_id);
  }
   Form::textRowEx(_("Description:"), 'name', 40);
   Form::yesnoListRow(_("Tax applied to Shipping:"), 'tax_shipping', NULL, "", "", TRUE);
  Table::end();
  Event::warning(_("Select the taxes that are included in this group."), 1);
  Table::start('tablestyle2');
  //$th = array(_("Tax"), _("Default Rate (%)"), _("Rate (%)"));
  //Editable rate has been removed 090920 Joe Hunt
  $th = array(_("Tax"), _("Rate (%)"));
  Table::header($th);
  for ($i = 0; $i < 5; $i++) {
    Row::start();
    if (!isset($_POST['tax_type_id' . $i])) {
      $_POST['tax_type_id' . $i] = 0;
    }
    Tax_Types::cells(NULL, 'tax_type_id' . $i, $_POST['tax_type_id' . $i], _("None"), TRUE);
    if ($_POST['tax_type_id' . $i] != 0 && $_POST['tax_type_id' . $i] != ALL_NUMERIC) {
      $default_rate = Tax_Types::get_default_rate($_POST['tax_type_id' . $i]);
      Cell::label(Num::percent_format($default_rate), ' class="right nowrap"');
      //Editable rate has been removed 090920 Joe Hunt
      //if (!isset($_POST['rate' . $i]) || $_POST['rate' . $i] == "")
      //	$_POST['rate' . $i] = Num::percent_format($default_rate);
      // Form::amountCellsSmall(null, 'rate' . $i, $_POST['rate' . $i], null, null,
      // User::percent_dec());
    }
    Row::end();
  }
  Table::end(1);
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Form::end();
  Page::end();


