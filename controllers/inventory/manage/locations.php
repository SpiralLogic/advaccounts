<?php
  use ADV\App\Inv\Location;
  use ADV\Core\Row;
  use ADV\Core\DB\DB;
  use ADV\Core\Cell;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class InvLocation extends \ADV\App\Controller\Base
  {
    protected $inv_location;
    protected function before() {
      $this->inv_location = new Location(0);

      if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $id = $this->getActionId('Delete');
        if ($id > -1) {
          $this->inv_location->load($id);
          //the link to delete a selected record was clicked instead of the submit button
          $this->inv_location->delete();
          $result['status'] = $this->inv_location->getStatus();
          $this->Ajax->addJson(true, null, $result);
        }
        $id = $this->getActionId('Edit');
        if ($id > -1) {
          //editing an existing Sales-person
          $this->inv_location->load($id);
          $this->JS->setFocus('location_name');
        }
        if ($this->action == SAVE) {
          $this->inv_location->save($_POST);
          //run the sql from either of the above possibilites
          $result['status'] = $this->inv_location->getStatus();
          if ($result['status']['status'] == Status::ERROR) {
            $this->JS->renderJSON($result);
          }
          $this->inv_location->load(0);
        } else {
          $result['status'] = $this->inv_location->getStatus();
        }
        $this->Ajax->addJson(true, null, $result);
      }
    }
    protected function index() {
      Page::start(_($help_context = "Inventory Locations"), SA_INVENTORYLOCATION);
      $cols         = [
        ['type'=> 'skip'],
        _("Location Code"), //
        _("Location Name"), //
        _("Address"), //
        _("Phone"), //
        _("Secondary Phone"),
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      $table        = DB_Pager::new_db_pager('inv_location_table', Location::getAll(), $cols);
      $table->class = 'width90';
      $table->display();
      echo '<br>';
      $view = new \ADV\Core\View('form/simple');
      $form = new \ADV\App\Form\Form();
      $form->hidden('id');
      $form->text('loc_code')->label('Location Code:');
      $form->text('location_name')->label('Location Name:');
      $form->textarea('delivery_address')->label('Location Address:');
      $form->text('phone')->label('Phone:');
      $form->text('phone2')->label('Phone2:');
      $form->text('fax')->label('Fax:');
      $form->text('email')->label('Email:');
      $form->text('contact')->label('Contact Name:');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
      $form->setValues($this->inv_location);
      $view->set('form', $form);
      $view->render();
      $this->Ajax->addJson(true, 'setFormValues', $form);
      Page::end(true);
    }
    /**
     * @param $row
     *
     * @return ADV\App\Form\Button
     */
    public function formatEditBtn($row) {
      $button = new \ADV\App\Form\Button('_action', EDIT . $row['id'], EDIT);
      $button['class'] .= ' btn-mini btn-primary';

      return $button;
    }
    /**
     * @param $row
     *
     * @return ADV\App\Form\Button
     */
    public function formatDeleteBtn($row) {
      $button = new \ADV\App\Form\Button('_action', DELETE . $row['id'], DELETE);
      $button->preIcon(ICON_DELETE);
      $button['class'] .= ' btn-mini btn-danger';

      return $button;
    }
  }

  new InvLocation();


/**
 * PHP version 5.4
 * @category  PHP
 * @package   ADVAccounts
 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
 * @copyright 2010 - 2012
 * @link      http://www.advancedgroup.com.au
 **/


