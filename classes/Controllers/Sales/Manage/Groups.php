<?php
  namespace ADV\Controllers\Sales\Manage;

  use ADV\App\Sales\Group;
  use ADV\App\Page;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  class Groups extends \ADV\App\Controller\Manage {
    protected function before() {
      $this->object = new Group();
      $this->runPost();
    }
    protected function index() {
      $this->Page->init(_($help_context = "Sales Groups"), SA_SALESGROUP);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      $this->Page->end_page(true);
    }
    /**
     * @param \ADV\App\Form\Form|\Form   $form
     * @param \ADV\Core\View|\View       $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Sales Group';
      $form->hidden('id');
      $form->text('description')->label('Group Name:');
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        ['type'=> 'skip'],
        'Group Name',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
  }

