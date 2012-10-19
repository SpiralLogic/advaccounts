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
  use ADV\App\Pager\Pager;
  use ADV\Core\Input\Input;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  abstract class Manage extends Action
  {
    /** @var \ADV\App\GL\QuickEntry */
    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected $security;
    protected function runPost() {
      if (REQUEST_POST) {
        $id = $this->getActionId([DELETE, EDIT, INACTIVE]);
        switch ($this->action) {
          case DELETE:
            $status = $this->onDelete($id);
            break;
          case EDIT:
            $this->onEdit($id);
            break;
          /** @noinspection PhpMissingBreakStatementInspection */
          case INACTIVE:
            $this->object->load($id);
            $changes['inactive'] = $this->Input->post('_value', Input::NUMERIC);
          case SAVE:
            $changes = isset($changes) ? $changes : $_POST;
            $status  = $this->onSave($changes);
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
    /**
     * @param      $changes
     * @param null $object
     *
     * @return \ADV\Core\Status|array
     */
    protected function onSave($changes, $object = null) {
      $object = $object ? : $this->object;
      $object->save($changes);
      //run the sql from either of the above possibilites
      $status = $object->getStatus();
      if ($status['status'] == Status::ERROR) {
        $this->JS->renderStatus($status);
      }
      $object->load(0);
      return $status;
    }
    /**
     * @param      $id
     * @param null $object
     */
    protected function onEdit($id, $object = null) {
      $object = $object ? : $this->object;
      $object->load($id);
    }
    /**
     * @param      $id
     * @param null $object
     *
     * @return array|string
     */
    protected function onDelete($id, $object = null) {
      $object = $object ? : $this->object;
      $object->load($id);
      $object->delete();
      $status = $object->getStatus();
      return $status;
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
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     * @param null               $object
     * @param bool               $contents
     */
    protected function generateForm(\ADV\App\Form\Form $form = null, \ADV\Core\View $view = null, $object = null, $contents = true) {
      $view = $view ? : new \ADV\Core\View('form/simple');
      $form = $form ? : new \ADV\App\Form\Form();
      if ($contents) {
        $view['title'] = $this->title;
        $this->formContents($form, $view);
      }
      $form->group('buttons');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
      $form->setValues($object ? : $this->object);
      $view->set('form', $form);
      $view->render();
      $this->Ajax->addJson(true, 'setFormValues', $form);
    }
    /**
     * @return \ADV\App\Pager\Pager
     */
    protected function generateTable() {
      $cols       = $this->generateTableCols();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
      \ADV\App\Pager\Pager::kill($pager_name);
      $table = \ADV\App\Pager\Pager::newPager($pager_name, $this->getTableRows($pager_name), $cols);
      $this->getEditing($table);
      $table->width = $this->tableWidth;
      $table->display();
    }
    protected function getEditing(Pager $table) {

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
      return $this->formatBtn(EDIT, $row[$this->object->getIDColumn()], ICON_EDIT);
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatDeleteBtn($row) {
      return $this->formatBtn(DELETE, $row[$this->object->getIDColumn()], ICON_DELETE, 'danger');
    }
    /**
     * @param        $action
     * @param string $id
     * @param null   $icon
     * @param string $type
     *
     * @return \ADV\App\Form\Button
     */
    public function formatBtn($action, $id = '', $icon = null, $type = 'primary') {
      $button = new \ADV\App\Form\Button('_action', $action . $id, $action);
      $button->preIcon($icon);
      $button->type('mini')->type($type);
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
