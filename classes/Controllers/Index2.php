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

  /** **/
  class Index2 extends Action
  {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function index() {
      echo "<a data-href='/Items/Manage/items' class='test'>test</a><div id='test'></div>";
      $this->JS->onload(
        <<<JS
        $('.test').on('click',function(){
        $.get($(this).data('href'),function(data) { $( data ).appendTo('#test'); $('#test').find('[name=_action]').off('click mousedown').end().on('click','button', function(e){ e.stopPropagation();$('#test') . empty();
    return false;})});});
JS
      );
    }
  }
