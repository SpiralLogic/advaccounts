<?php
  use ADV\App\Inv\Location;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class InvLocation extends \ADV\App\Controller\Manage {
    protected $tableWidth = '80';
    protected function before() {
      $this->object = new Location();
      $this->runPost();
    }
    protected function index() {
      Page::start(_($help_context = "Inventory Locations"), SA_INVENTORYLOCATION);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      Page::end(true);
    }
    /**
     * @param ADV\App\Form\Form $form
     * @param ADV\Core\View     $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Inventory Location';
      $form->hidden('id');
      $form->text('loc_code')->label('Location Code:');
      $form->text('location_name')->label('Location Name:');
      $form->textarea('delivery_address')->label('Location Address:');
      $form->text('phone')->label('Phone:');
      $form->text('phone2')->label('Phone2:');
      $form->text('fax')->label('Fax:');
      $form->text('email')->label('Email:');
      $form->text('contact')->label('Contact Name:');
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        ['type'=> 'skip'],
        _("Location Code"), //
        _("Location Name"), //
        _("Address"), //
        _("Phone"), //
        _("Secondary Phone"),
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
  }

  new InvLocation();

