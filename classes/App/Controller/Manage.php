<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      5/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Controller;

  use ADV\Core\Status;
  use DB_Pager;
  use ADV\App\Page;
  use ADV\Core\Input\Input;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  abstract class Manage extends Base {
    /** @var \ADV\App\DB\Base */
    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected function runPost() {
      if ($this->method == 'POST') {
        $id = $this->getActionId(DELETE);
        if ($id > -1) {
          $this->object->load($id);
          //the link to delete a selected record was clicked instead of the submit button
          $this->object->delete();
          $status = $this->object->getStatus();
          $this->Ajax->addStatus(true, null, $status);
        }
        $id = $this->getActionId(EDIT);
        if ($id > -1) {
          //editing an existing Sales-person
          $this->object->load($id);
        }
        $id = $this->getActionId(INACTIVE);
        if ($id > -1) {
          //editing an existing Sales-person
          $this->object->load($id);
          $changes['inactive'] = $this->Input->post('_value', Input::NUMERIC);
          $this->action        = SAVE;
        }
        if ($this->action == SAVE) {
          $changes = isset($changes) ? $changes : $_POST;
          $this->object->save($changes);
          //run the sql from either of the above possibilites
          $status = $this->object->getStatus();
          if ($status['status'] == Status::ERROR) {
            $this->JS->renderStatus($status);
          }
          $this->object->load(0);
        } elseif ($this->action == CANCEL) {
          $status = $this->object->getStatus();
        } elseif ($this->action == 'showInactive') {
          $this->generateTable();
          exit();
        }
        if (isset($status)) {
          $this->Ajax->addStatus($status);
        }
      }
    }
    protected function index() {
      Page::start($this->title, SA_SALESTYPES);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      Page::end(true);
    }
    protected function generateForm() {
      $view          = new \ADV\Core\View('form/simple');
      $form          = new \ADV\App\Form\Form();
      $view['title'] = $this->title;
      $this->formContents($form, $view);
      $form->group('buttons');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
      $form->setValues($this->object);
      $view->set('form', $form);
      $view->render();
      $this->Ajax->addJson(true, 'setFormValues', $form);
    }
    /**
     * @return \DB_Pager
     */
    protected function generateTable() {
      $cols       = $this->generateTableCols();
      $pager_name = get_called_class() . '_table';
      $inactive   = false;
      if (isset($_SESSION['pager'][$pager_name])) {
        $inactive = ($this->action == 'showInactive' && $this->Input->post(
          '_value',
          Input::NUMERIC
        ) == 1) || ($this->action != 'showInactive' && $_SESSION['pager'][$pager_name]->showInactive);
      }
      //DB_Pager::kill($pager_name);
      $table        = DB_Pager::newPager($pager_name, $this->object->getAll($inactive), $cols);
      $table->width = $this->tableWidth;
      $table->display();
      return $table;
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatEditBtn($row) {
      $button = new \ADV\App\Form\Button('_action', EDIT . $row[$this->object->getIDColumn()], EDIT);
      $button->type('mini')->type('primary');
      return $button;
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatDeleteBtn($row) {
      $button = new \ADV\App\Form\Button('_action', DELETE . $row[$this->object->getIDColumn()], DELETE);
      $button->preIcon(ICON_DELETE);
      $button->type('mini')->type('danger');
      return $button;
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    abstract protected function formContents(Form $form, View $view);
    abstract protected function generateTableCols();
  }
