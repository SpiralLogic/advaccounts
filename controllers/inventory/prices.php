<?php
  use ADV\App\Form\Form;
  use ADV\App\Display;
  use ADV\App\UI;
  use ADV\App\Validation;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class SalesPrice extends \ADV\App\Controller\Manage {
    protected $stock_id;
    protected $security = SA_SALESPRICE;
    protected $frame = false;
    protected function before() {
      $this->frame    = $this->Input->request('frame');
      $this->stock_id = $this->Input->getPostGlobal('stock_id');
      $this->object   = new Item_Price();
      $this->runPost();
      $this->object->stock_id = $this->stock_id;
    }
    protected function beforeTable() {
      if (!$this->frame) {
        echo "<div class='bold center pad10 margin10 font15'>" . _("Item:") . '</span>';
        UI::search(
          'stock_id',
          [
          'url'              => 'Item',
          'idField'          => 'stock_id',
          'name'             => 'stock_id', //
          'focus'            => true,
          ]
        );
        $this->Session->setGlobal('stock_id', $this->stock_id);
        echo "</div>";
      }
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Item Selling Prices';
      $form->hidden('id');
      $form->hidden('item_code_id');
      $form->hidden('stock_id');
      $form->custom(Sales_Type::select('sales_type_id'))->label("Sales Type:");
      $form->custom(GL_Currency::select('curr_abrev'))->label('Currency:');
      if (!isset($_POST['price'])) {
        $_POST['price'] = Num::_priceFormat(Item_Price::get_kit($this->Input->post('stock_id'), $this->Input->post('curr_abrev'), $this->Input->post('sales_type_id')));
      }
      $kit = Item_Code::get_defaults($_POST['stock_id']);
      $form->amount('price')->label(_("Price:"))->append(_('per') . ' ' . $kit["units"])->focus();
    }
    protected function generateTable() {
      Display::div_start('table');
      if ($this->stock_id) {
        parent::generateTable();
      }
      if ($this->Input->post('_control') == 'stock_id') {
        $this->Ajax->activate('table');
      }
      Display::div_end();
    }
    protected function generateTableCols() {
      $cols = [
        'Type',
        ['type'=> 'skip'],
        ['type'=> 'skip'],
        'stock_id',
        ['type'=> 'skip'],
        'Currency',
        'Price',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],

      ];
      return $cols;
    }
    protected function getTableRows($pagername) {
      return Item_Price::getAll($this->stock_id)->fetchAll(PDO::FETCH_ASSOC);
    }
    protected function runValidation() {
      Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
      Validation::check(Validation::SALES_TYPES, _("There are no sales types in the system. Please set up sales types befor entering pricing."));
    }
  }

  new SalesPrice();
/*
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (!Validation::post_num('price', 0)) {
      $input_error = 1;
      Event::error(_("The price entered must be numeric."));
      JS::_setFocus('price');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        //editing an existing price
        $result = Item_Price::update($selected_id, $_POST['sales_type_id'], $_POST['curr_abrev'], Validation::input_num('price'));
        if ($result) {
          $msg = _("This price has been updated.");
        }
      } else {
        $result = Item_Price::add($_POST['stock_id'], $_POST['sales_type_id'], $_POST['curr_abrev'], Validation::input_num('price'));
        if ($result) {
          $msg = _("The new price has been added.");
        }
      }
      Event::success($msg);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    //the link to delete a selected record was clicked
    Item_Price::delete($selected_id);
    Event::notice(_("The selected price has been deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
  }
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('price_table');
    Ajax::_activate('price_details');
  }
  if (Forms::isListUpdated('stock_id') || isset($_POST['_curr_abrev_update']) || isset($_POST['_sales_type_id_update'])) {
    // after change of stock, currency or salestype selector
    // display default calculated price for new settings.
    // If we have this price already in db it is overwritten later.
    unset($_POST['price']);
    Ajax::_activate('price_details');
  }
  $prices_list = Item_Price::getAll($_POST['stock_id']);
  Display::div_start('price_table');
  Table::start('padded grid width90 ');
  $th = array(_("Currency"), _("Sales Type"), _("Price"), "", "");
  Table::header($th);
  $k          = 0; //row colour counter
  $calculated = false;
  while ($myrow = DB::_fetch($prices_list)) {
    Cell::label($myrow["curr_abrev"]);
    Cell::label($myrow["sales_type"]);
    Cell::amount($myrow["price"]);
    Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    echo '</tr>';
  }
  Table::end();
  if (DB::_numRows($prices_list) == 0) {
    if (DB_Company::get_pref('add_pct') != -1) {
      $calculated = true;
    }
    Event::warning(_("There are no prices set up for this part."), false);
  }
  Display::div_end();
  echo "<br>";
  if ($Mode == MODE_EDIT) {
    $myrow                  = Item_Price::get($selected_id);
    $_POST['curr_abrev']    = $myrow["curr_abrev"];
    $_POST['sales_type_id'] = $myrow["sales_type_id"];
    $_POST['price']         = Num::_priceFormat($myrow["price"]);
  }
  Forms::hidden('selected_id', $selected_id);
  Forms::hidden('stock_id');
  Display::div_start('price_details');
  Table::start('tableinfo');
  GL_Currency::row(_("Currency:"), 'curr_abrev', null, true);
  Sales_Type::row(_("Sales Type:"), 'sales_type_id', null, true);
  if (!isset($_POST['price'])) {
    $_POST['price'] = Num::_priceFormat(Item_Price::get_kit(Input::_post('stock_id'), Input::_post('curr_abrev'), Input::_post('sales_type_id')));
  }
  $kit = Item_Code::get_defaults($_POST['stock_id']);
  Forms::SmallAmountRow(_("Price:"), 'price', null, '', _('per') . ' ' . $kit["units"]);
  Table::end(1);
  if ($calculated) {
    Event::warning(_("The price is calculated."), 0, 1);
  }
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Display::div_end();
  Forms::end();
  if (Input::_request('frame')) {
    Page::end(true);
  } else {
    Page::end();
  }*/
