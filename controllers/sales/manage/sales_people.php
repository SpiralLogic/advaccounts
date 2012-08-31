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
    protected function before()
    {
      if ($this->action == ADD_ITEM || $this->action == UPDATE_ITEM) {
        if ($this->selected_id != -1) {
          $this->addNew();
        } else {
          $this->update();
        }
        //run the sql from either of the above possibilites
        $this->action = MODE_RESET;
      }
      $id = $this->getActionId('Delete');
      if ($id>-1) {
        //the link to delete a selected record was clicked instead of the submit button
        $this->delete($id);
        $this->Mode = MODE_RESET;
      }
      if ($this->action == MODE_RESET) {
        $this->reset();
      }
      $this->getSalesPersons();
    }
    protected function getSalesPersons()
    {
      $sql = "SELECT s.*,u.user_id,u.id FROM salesman s, users u WHERE s.user_id=u.id";
      if (!Input::_hasPost('show_inactive')) {
        $sql .= " AND !s.inactive";
      }
      $this->result = $sql;
    }
    protected function reset()
    {
      $this->selected_id = -1;
      $sav               = Input::_post('show_inactive');
      unset($_POST);
      $_POST['show_inactive'] = $sav;
    }
    protected function delete($id)
    { // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtors'
      $sql    = "SELECT COUNT(*) FROM branches WHERE salesman=" . DB::_escape($id);
      $result = DB::_query($sql, "check failed");
      $myrow  = DB::_fetchRow($result);
      if ($myrow[0] > 0) {
        Event::error("Cannot delete this sales-person because branches are set up referring to this sales-person - first alter the branches concerned.");

        return false;
      } else {
        $sql    = "DELETE FROM salesman WHERE salesman_code=" . DB::_escape($id);
        $result = DB::_query($sql, "The sales-person could not be deleted");
        if ($result) {
          Event::notice(_('Selected sales person data have been deleted'));
        }

        return $result;
      }
    }
    protected function addNew()
    {
      $this->canProcess();
      /*Selected group is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new Sales-person form */
      $sql    = "INSERT INTO salesman (salesman_name, user_id, salesman_phone, salesman_fax, salesman_email,
   			provision, break_pt, provision2)
   			VALUES (" . DB::_escape($_POST['salesman_name']) . ", " . DB::_escape($_POST['user_id']) . ", " . DB::_escape($_POST['salesman_phone']) . ", " . DB::_escape(
        $_POST['salesman_fax']
      ) . ", " . DB::_escape($_POST['salesman_email']) . ", " . Validation::input_num('provision') . ", " . Validation::input_num(
        'break_pt'
      ) . ", " . Validation::input_num('provision2') . ")";
      $result = DB::_query($sql, "The insert or update of the sales person failed");
      if ($result) {
        Event::success(_('Selected sales person data have been updated'));
      }

      return $result;
    }
    protected function update()
    {
      $this->canProcess();
      /*selected_id could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the delete code below*/
      $sql = "UPDATE salesman SET salesman_name=" . DB::_escape($_POST['salesman_name']) . ",
   			user_id=" . DB::_escape($_POST['user_id']) . ",
   			salesman_phone=" . DB::_escape($_POST['salesman_phone']) . ",
   			salesman_fax=" . DB::_escape($_POST['salesman_fax']) . ",
   			salesman_email=" . DB::_escape($_POST['salesman_email']) . ",
   			provision=" . Validation::input_num('provision') . ",
   			break_pt=" . Validation::input_num('break_pt') . ",
   			provision2=" . Validation::input_num('provision2') . "
   			WHERE salesman_code = " . DB::_escape($this->selected_id);
      $result = DB::_query($sql, "The insert or update of the sales person failed");
      if ($result) {
        Event::success(_('New sales person data have been added'));
      }
      ;

      return $result;
    }
    protected function canProcess()
    { //initialise no input errors assumed initially before we test
      $input_error = 0;
      if (strlen($_POST['salesman_name']) == 0) {
        $input_error = 1;
        Event::error(_("The sales person name cannot be empty."));
        JS::_setFocus('salesman_name');
      }
      $pr1 = Validation::post_num('provision', 0, 100);
      if (!$pr1 || !Validation::post_num('provision2', 0, 100)) {
        $input_error = 1;
        Event::error(_("Salesman provision cannot be less than 0 or more than 100%."));
        JS::_setFocus(!$pr1 ? 'provision' : 'provision2');
      }
      if (!Validation::post_num('break_pt', 0)) {
        $input_error = 1;
        Event::error(_("Salesman provision breakpoint must be numeric and not less than 0."));
        JS::_setFocus('break_pt');

        return $input_error;
      }

      return $input_error;
    }
    protected function index()
    {
      Page::start(_($help_context = "Sales Persons"), SA_SALESMAN);
      Forms::start();
      $cols  = array(
        _("User ID"),
        _("Name"),
        _("User"),
        _("Phone"),
        _("Fax"),
        _("Email"),
        _("Provision"),
        _("Break Pt."),
        _("Provision") . " 2",
        ['type'=>"skip"],
        ['type'=>"skip"],
        ['insert'=> true, 'fun'=> [$this, 'formatEditBtn']],
        ['insert'=> true, 'fun'=> [$this, 'formatDeleteBtn']]
      );
      $table = DB_Pager::new_db_pager('sales_persons11', $this->result, $cols);
      $table->display();
      echo '<br>';
      $_POST['salesman_email'] = "";
      $id                      = $this->getActionId('Edit');
      if ($id>-1) {
        //editing an existing Sales-person
        $sql                     = "SELECT * FROM salesman WHERE salesman_code=" . DB::_escape($id);
        $result                  = DB::_query($sql, "could not get sales person");
        $myrow                   = DB::_fetch($result);
        $_POST['user_id']        = $myrow["user_id"];
        $_POST['salesman_name']  = $myrow["salesman_name"];
        $_POST['salesman_phone'] = $myrow["salesman_phone"];
        $_POST['salesman_fax']   = $myrow["salesman_fax"];
        $_POST['salesman_email'] = $myrow["salesman_email"];
        $_POST['provision']      = Num::_percentFormat($myrow["provision"]);
        $_POST['break_pt']       = Num::_priceFormat($myrow["break_pt"]);
        $_POST['provision2']     = Num::_percentFormat($myrow["provision2"]);
        $this->Ajax->activate('edit_user');
      } elseif ($this->action != ADD_ITEM) {
        $_POST['provision']  = Num::_percentFormat(0);
        $_POST['break_pt']   = Num::_priceFormat(0);
        $_POST['provision2'] = Num::_percentFormat(0);
      }
      Display::div_start('edit_user');
      $form = new Form('edit_user');
      $form->custom(Users::select('user_id'))->label('User:');
      $form->text('salesman_name',null,['maxlength'=>30])->label('Name: ');
      $form->text('salesman_phone',null,['maxlength'=>20])->label('Telephone number:: ');
      Forms::textRowEx(_("Fax number:"), 'salesman_fax', 20);
      Forms::emailRowEx(_("E-mail:"), 'salesman_email', 40);
      Forms::percentRow(_("Provision") . ':', 'provision');
      Forms::AmountRow(_("Break Pt.:"), 'break_pt');
      Forms::percentRow(_("Provision") . " 2:", 'provision2');
      Table::end(1);
      $form = new Form();
      $form->submit(ADD_ITEM, $id== -1 ? 'Add' : 'Update');
      Forms::submitAddUpdateCenter($id== -1, '', 'both');
      Display::div_end();
      Forms::end();
      Page::end();
    }
    public function formatEditBtn($row)
    {
      $button = new \ADV\App\Form\Button('_action', 'Edit' . $row['salesman_code'], 'Edit');
      $button['class'] .= ' btn-mini btn-primary';

      return $button;
    }
    public function formatDeleteBtn($row)
    {
      $button = new \ADV\App\Form\Button('_action', 'Delete' . $row['salesman_code'], 'Delete');
      $button['class'] .= ' btn-mini btn-danger';

      return $button;
    }
  }

  new SalesPeople();
