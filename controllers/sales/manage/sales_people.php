<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\App\Controller\Base;
  use ADV\App\Sales\Person;
  use ADV\App\Display;
  use ADV\App\Form\Form;
  use ADV\App\Users;
  use ADV\Core\Row;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Input\Input;
  use ADV\Core\DB\DB;
  use ADV\App\Validation;
  use ADV\Core\JS;

  /**

   */
  class SalesPeople extends Base
  {
    protected $result;
    protected $selected_id;
    protected $Mode;
    protected $sales_person;
    protected function before() {
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($this->action == ADD_ITEM) {
          $this->sales_person = new \ADV\App\Sales\Person($_POST);
          //run the sql from either of the above possibilites
          if (!$this->sales_person->save()) {
            $result['status'] = $this->sales_person->getStatus();
            $this->JS->renderJSON($result);
          }
        }
        $id = $this->getActionId('Delete');
        if ($id > -1) {
          $this->sales_person = new \ADV\App\Sales\Person($id);
          //the link to delete a selected record was clicked instead of the submit button
          $this->sales_person->delete();
        }
        $id = $this->getActionId('Edit');
        if ($id > -1) {
          //editing an existing Sales-person
          $this->sales_person = new \ADV\App\Sales\Person($id);
          $this->Ajax->activate('edit_user');
          $this->JS->setFocus('salesman_name');
        } else {
          $this->sales_person = new \ADV\App\Sales\Person(0);
        }
        $result['status'] = $this->sales_person->getStatus();
        $this->Ajax->addJson(true, null, $result);
      }
    }
    protected function index() {
      Page::start(_($help_context = "Sales Persons"), SA_SALESMAN);
      $cols  = array(
        _("Salesman ID"),
        _("Name"),
        _("User"),
        _("Phone"),
        _("Fax"),
        _("Email"),
        _("Provision"),
        _("Break Pt."),
        _("Provision") . " 2",
        ['type'=> "skip"],
        ['type'=> "skip"],
        ['insert'=> true, "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['insert'=> true, "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']]
      );
      $table = DB_Pager::new_db_pager('sales_persons', Person::getAll(), $cols);
      $table->display();
      echo '<br>';
      $view              = new View('form/simple');
      $form              = new Form();
      $form->useDefaults = ($this->action == CANCEL);
      $form->hidden('salesman_code');
      $form->text('salesman_name', null, ['maxlength'=> 30])->label('Name: ');
      $form->custom(Users::select('user_id', null, " ", true))->label('User:');
      $form->text('salesman_phone', null, ['maxlength'=> 20])->label('Telephone number: ');
      $form->text('salesman_fax', null, ['maxlength'=> 20])->label('Fax number: ');
      $form->text('salesman_email', null, ['maxlength'=> 40])->label('E-mail: ');
      $form->percent('provision')->label("Provision: ");
      $form->amount('break_pt')->label("Break Pt.:");
      $form->percent('provision2')->label("Provision 2: ");
      $form->group('buttons');
      $form->submit(CANCEL, 'Cancel Changes')->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(ADD_ITEM, 'Save')->type('success')->preIcon(ICON_SUBMIT);
      $form->setValues($this->sales_person);
      $view->set('form', $form);
      $view->render();
      $this->Ajax->addJson(true, 'setFormValues', $form);
      Page::end();
    }
    /**
     * @param $row
     *
     * @return ADV\App\Form\Button
     */
    public function formatEditBtn($row) {
      $button = new \ADV\App\Form\Button('_action', 'Edit' . $row['salesman_code'], 'Edit');
      $button['class'] .= ' btn-mini btn-primary';

      return $button;
    }
    /**
     * @param $row
     *
     * @return ADV\App\Form\Button
     */
    public function formatDeleteBtn($row) {
      $button = new \ADV\App\Form\Button('_action', 'Delete' . $row['salesman_code'], 'Delete');
      $button['class'] .= ' btn-mini btn-danger';

      return $button;
    }
  }

  new SalesPeople();
