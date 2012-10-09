<?php
  namespace ADV\Controllers\Items\Manage;

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
  class Locations extends \ADV\App\Controller\Manage {
    protected $tableWidth = '90';
    protected function before() {
      $this->object = new Location();
      $this->runPost();
    }
    protected function index() {
      $this->Page->init(_($help_context = "Inventory Locations"), SA_INVENTORYLOCATION);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      $this->Page->end_page(true);
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Inventory Location';
      $form->hidden('id');
      $form->text('loc_code')->label('Location Code:');
      $form->text('location_name')->label('Location Name:');
      $form->arraySelect('type', [Location::BOTH=> 'Both', Location::INWARD=> 'Inward', Location::OUTWARD=> 'Outward'])->label('Type:');
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
        ['type'=> 'skip'],
        _("Fax"),
        _("Email"),
        ['type'=> 'skip'],
        _("Inactive")=> ['type'=> 'inactive'],
        _("type"),
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
    /**
     * @param $pager_name
     *
     * @return mixed
     */
    protected function getTableRows($pager_name) {
      $inactive = $this->getShowInactive($pager_name);
      return $this->object->getAll($inactive);
    }
  }


