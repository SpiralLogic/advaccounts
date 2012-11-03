<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Sales\Area;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Areas extends \ADV\App\Controller\FormPager
  {
    protected $security = SA_SALESAREA;
    protected function before() {
      $this->object = new Area();
      $this->runPost();
    }
    protected function index() {
      $this->Page->init(_($help_context = "Sales Areas"), SA_SALESAREA);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      $this->Page->end_page(true);
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
        ['type' => 'skip'],
        'Area Name',
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatEditBtn']],
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
  }

