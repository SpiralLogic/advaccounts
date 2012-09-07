<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\App\Form\Form;
  use ADV\App\Sales\Types;
  use ADV\Core\View;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class SalesTypes extends \ADV\App\Controller\Manage
  {
    protected function before() {
      $this->object = new Types();
      $this->runPost();
    }
    protected function index() {
      Page::start(_($help_context = "Sales Types"), SA_SALESTYPES);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      Page::end(true);
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Types';
      $form->hidden('id');
      $form->text('sales_type')->label('Sales Type:');
      $form->percent('factor')->label('Factor:');
      $form->arraySelect('tax_included', ['No', 'Yes'])->label('Tax Included:');
      $form->arraySelect('inactive', ['No', 'Yes'])->label('Inactive:');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
    }
    protected function generateTable() {
      $cols = [
        ['type'=> 'skip'],
        'Sales Type',
        'Tax Incl.',
        'Factor'  => ['type'=> 'percent'],
        'Inactive'=> ['type'=> 'inactive'],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      \ADV\App\Forms::start();
      $table        = DB_Pager::new_db_pager('sales_type_table3', Types::getAll($this->Input->post('show_inactive')), $cols);
      $table->class = 'width50';
      $table->display();
      \ADV\App\Forms::end();
    }
  }

  new SalesTypes();
