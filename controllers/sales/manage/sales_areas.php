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
        $this->sales_area = new Areas(0);
        $id               = $this->getActionId(DELETE);
        if ($id > -1) {
          $this->sales_area = new Areas($id);
          //the link to delete a selected record was clicked instead of the submit button
          $this->sales_area->delete();
        }
        $id = $this->getActionId(EDIT);
        if ($id > -1) {
          //editing an existing Sales-person
          $this->sales_area = new Areas($id);
          $this->JS->setFocus('description');
        }
        if ($this->action == SAVE) {
          $this->sales_area->save($_POST);
          //run the sql from either of the above possibilites
          $result['status'] = $this->sales_area->getStatus();
          if (!$result['status']['status']) {
            $this->JS->renderJSON($result);
          }
          $this->sales_area = new Areas(0);
        } else {
          $result['status'] = $this->sales_area->getStatus();
        }
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
      $table        = DB_Pager::new_db_pager('sales_area_table', Areas::getAll(), $cols);
      $table->class = 'width30';
      $table->display();
      echo '<br>';
      $view = new \ADV\Core\View('form/simple');
      $form = new \ADV\App\Form\Form();
      $form->hidden('area_code');
      $form->text('description')->label('Area Name:');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
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
      $button = new \ADV\App\Form\Button('_action', EDIT . $row['area_code'], EDIT);
      $button['class'] .= ' btn-mini btn-primary';

      return $button;
    }
    /**
     * @param $row
     *
     * @return ADV\App\Form\Button
     */
    public function formatDeleteBtn($row) {
      $button = new \ADV\App\Form\Button('_action', DELETE . $row['area_code'], DELETE);
      $button->preIcon(ICON_DELETE);
      $button['class'] .= ' btn-mini btn-danger';

      return $button;
    }
  }

  new SalesArea();
