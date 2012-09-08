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
  use ADV\App\Forms;
  use ADV\App\Sales\Type;
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
      $this->object = new Type();
      $this->runPost();
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
      $form->text('sales_type')->label('Sales Type:')->focus();
      $form->percent('factor')->label('Factor:');
      $form->checkbox('tax_included')->label('Tax Included:');
      $form->checkbox('inactive')->label('Inactive:');
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      return $cols = [
        ['type'=> 'skip'],
        'Sales Type',
        'Tax Incl.'=> ['type'=> 'bool'],
        'Factor'   => ['type'=> 'percent'],
        'Inactive' => ['type'=> 'inactive2'],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
    }
  }

  new SalesTypes();
