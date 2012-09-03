<?php
  use ADV\App\Forms;
  use ADV\App\Sales\Areas;
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
  class SalesArea extends \ADV\App\Controller\Base
  {

    protected $sales_area;
    protected function before() {
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if ($this->action == ADD_ITEM) {
          $this->sales_area = new Areas($_POST);
          //run the sql from either of the above possibilites
          if (!$this->sales_area->save()) {
            $result['status'] = $this->sales_area->getStatus();
            $this->JS->renderJSON($result);
          }
        }
        $id = $this->getActionId('Delete');
        if ($id > -1) {
          $this->sales_area = new Areas($id);
          //the link to delete a selected record was clicked instead of the submit button
          $this->sales_area->delete();
        }
        $id = $this->getActionId('Edit');
        if ($id > -1) {
          //editing an existing Sales-person
          $this->sales_area = new Areas($id);
          $this->JS->setFocus('description');
        } else {
          $this->sales_area = new Areas(0);
        }
        $result['status'] = $this->sales_area->getStatus();
        $this->Ajax->addJson(true, null, $result);
      }
    }
    protected function index() {
      Page::start(_($help_context = "Sales Areas"), SA_SALESAREA);
      $cols         = [
        ['type'=> 'skip'],
        'Area Name',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      $table        = DB_Pager::new_db_pager('45sales_area_table', Areas::getAll(), $cols);
      $table->class = 'width30';
      $table->display();
      echo '<br>';
      $view = new \ADV\Core\View('form/simple');
      $form = new \ADV\App\Form\Form();
      $form->hidden('area_code');
      $form->text('description')->label('Area Name:');
      $form->submit(CANCEL, 'Cancel')->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(ADD_ITEM, 'Save')->type('success')->preIcon(ICON_ADD);
      $form->setValues($this->sales_area);
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
      $button = new \ADV\App\Form\Button('_action', 'Edit' . $row['area_code'], 'Edit');
      $button['class'] .= ' btn-mini btn-primary';
      return $button;
    }
    /**
     * @param $row
     *
     * @return ADV\App\Form\Button
     */
    public function formatDeleteBtn($row) {
      $button = new \ADV\App\Form\Button('_action', 'Delete' . $row['area_code'], 'Delete');
      $button['class'] .= ' btn-mini btn-danger';
      return $button;
    }
  }

  new SalesArea();
