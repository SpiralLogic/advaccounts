<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Page;
  use ADV\App\Sales\Area;
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
  class Areas extends \ADV\App\Controller\Manage {
    protected function before() {
      $this->object = new Area();
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
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Area';
      $form->hidden('area_code');
      $form->text('description')->label('Area Name:');
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        ['type'=> 'skip'],
        'Area Name',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
  }

