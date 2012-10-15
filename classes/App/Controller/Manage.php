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
  use ADV\Core\Input\Input;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  abstract class Manage extends Action {
    /** @var \ADV\App\DB\Base */
    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected $security;
    protected function runPost() {
      if (REQUEST_POST) {
        $id = $this->getActionId([DELETE, EDIT, INACTIVE]);
        switch ($this->action) {
          case DELETE:
            $this->object->load($id);
            $this->object->delete();
            $status = $this->object->getStatus();
            break;
          case EDIT:
            $this->object->load($id);
            break;
          case INACTIVE:
            $this->object->load($id);
            $changes['inactive'] = $this->Input->post('_value', Input::NUMERIC);
          case SAVE:
            $changes = isset($changes) ? $changes : $_POST;
            $this->object->save($changes);
            //run the sql from either of the above possibilites
            $status = $this->object->getStatus();
            if ($status['status'] == Status::ERROR) {
              $this->JS->renderStatus($status);
            }
            $this->object->load(0);
            break;
          case CANCEL:
            $status = $this->object->getStatus();
            break;
          case 'showInactive':
            $this->generateTable();
            exit();
        }
        if (isset($status)) {
          $this->Ajax->addStatus($status);
        }
      }
    }
    protected function index() {
      $this->Page->init($this->title, $this->security);
      $this->beforeTable();
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      $this->Page->end_page(true);
    }
    protected function beforeTable() {
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
      if ($this->action == EDIT) {
        return;
      }
      $cols       = $this->generateTableCols();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
      //    DB_Pager::kill($pager_name);
      $table        = DB_Pager::newPager($pager_name, $this->getTableRows($pager_name), $cols);
      $table->width = $this->tableWidth;
      $table->display();
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
    /**
     * @param $pager_name
     *
     * @return bool
     */
    protected function getShowInactive($pager_name) {
      $inactive = false;
      if (isset($_SESSION['pager'][$pager_name])) {
        $inactive = ($this->action == 'showInactive' && $this->Input->post(
          '_value',
          Input::NUMERIC
        ) == 1) || ($this->action != 'showInactive' && $_SESSION['pager'][$pager_name]->showInactive);
        return $inactive;
      }
      return $inactive;
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
