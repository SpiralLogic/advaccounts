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
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  abstract class Manage extends Base
  {
    /** @var \ADV\App\DB\Base */
    protected $object;
    public $_form;
    public $_view;
    protected function runPost() {
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $id = $this->getActionId('Delete');
        if ($id > -1) {
          $this->object->load($id);
          //the link to delete a selected record was clicked instead of the submit button
          $this->object->delete();
          $result['status'] = $this->object->getStatus();
          $this->Ajax->addJson(true, null, $result);
        }
        $id = $this->getActionId('Edit');
        if ($id > -1) {
          //editing an existing Sales-person
          $this->object->load($id);
          $this->JS->setFocus('location_name');
        }
        if ($this->action == SAVE) {
          $this->object->save($_POST);
          //run the sql from either of the above possibilites
          $result['status'] = $this->object->getStatus();
          if ($result['status']['status'] == Status::ERROR) {
            $this->JS->renderJSON($result);
          }
          $this->object->load(0);
        } else {
          $result['status'] = $this->object->getStatus();
        }
        $this->Ajax->addJson(true, null, $result);
      }
    }
    protected function generateForm() {

      $view = new \ADV\Core\View('form/simple');
      $form = new \ADV\App\Form\Form();
      $this->formContents($form, $view);
      $form->setValues($this->object);
      $view->set('form', $form);
      $view->render();
      $this->Ajax->addJson(true, 'setFormValues', $form);
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatEditBtn($row) {
      $button = new \ADV\App\Form\Button('_action', EDIT . $row[$this->object->getIDColumn()], EDIT);
      $button['class'] .= ' btn-mini btn-primary';

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
      $button['class'] .= ' btn-mini btn-danger';

      return $button;
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    abstract protected function formContents(Form $form, View $view);
  }
