<?php
  use ADV\App\Form\Form;
  use ADV\App\Sales\Areas;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class SalesArea extends \ADV\App\Controller\Manage
  {
    protected function before() {
      $this->object = new Areas();
      $this->runPost();
    }
    protected function index() {
      Page::start(_($help_context = "Sales Areas"), SA_SALESAREA);
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
    protected function formContents(\ADV\App\Form\Form $form, \ADV\Core\View $view) {
      $form->hidden('area_code');
      $form->text('description')->label('Area Name:');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
    }
    protected function generateTable() {
      $cols         = [
        ['type'=> 'skip'],
        'Area Name',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      $table        = DB_Pager::new_db_pager('sales_area_table', Areas::getAll(), $cols);
      $table->class = 'width30';
      $table->display();
    }
  }

  new SalesArea();
