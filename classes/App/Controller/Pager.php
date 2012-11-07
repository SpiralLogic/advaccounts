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
  use ADV\App\Pager\Edit;
  use ADV\Core\Input\Input;

  /**

   */
  abstract class Pager extends Action
  {

    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected $security;
    protected $form_id = null;
    protected function runPost() {
      if (REQUEST_POST) {
        $this->form_id = $this->Input->post('_form_id');
        $id            = $this->getActionId([DELETE, EDIT, INACTIVE]);
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
            $this->object->getStatus();
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
     * @param                       $changes
     * @param \ADV\App\DB\Base|null $object
     * @param int                   $id
     *
     * @return \ADV\Core\Status|array
     */
    protected function onSave($changes, \ADV\App\DB\Base $object = null, $id = 0) {
      $object = $object ? : $this->object;
      $object->save($changes);
      //run the sql from either of the above possibilites
      $status = $object->getStatus();
      if ($status['status'] == Status::ERROR) {
        $this->JS->renderStatus($status);
      }
      $object->load($id);
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
    abstract protected function beforeTable();
    /**
     * @return \ADV\App\Pager\Pager
     */
    abstract protected function generateTable();
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
     * @return mixed
     * @throws \UnexpectedValueException
     */
    protected function getPagerColumns() {
      if ($this->object instanceof \ADV\App\Pager\Pageable) {
        return $this->object->getPagerColumns();
      }
      throw new \UnexpectedValueException(get_class($this->object) . " is not pageable!");
    }
  }
