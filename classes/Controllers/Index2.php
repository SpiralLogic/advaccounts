<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use ADV\App\Controller\Action;
  use ADV\Core\JS;
  use DB_Company;

  /** **/
  class Index2 extends Action
  {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function index() {
      echo "<a data-href='/Items/Manage/Items' class='test'>test</a><br>";
      echo "<a data-href='/Contacts/Manage/Customers' class='test2'>test2</a><br>";
      echo "<a data-href='/Contacts/Manage/Customers' class='test3'>test2</a>";
      $this->JS->onload(
        <<<JS
        $('.test').on('click',function(){
  Adv.dialogWindow.open('Items/Manage/Items');
  });
  $('.test2').on('click',function(){
  Adv.dialogWindow.open('Contacts/Manage/Customers');

  })
  ;
  $('.test3').click(function() {
    $.post('/Index2?test3=wawa',{test:'wawa'},function(data) { $('body').append(data); return false});
    })
JS
      );
      var_dump($_GET);
      var_dump(DB_Company::i());
    }
  }

