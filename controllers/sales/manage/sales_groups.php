<?php
  use ADV\App\Sales\Group;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  class SalesGroups extends \ADV\App\Controller\Manage {
    protected function before() {
      $this->object = new Group();
      $this->runPost();
    }
    protected function index() {
      Page::start(_($help_context = "Sales Areas"), SA_SALESAREA);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      Page::end(true);
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

  new SalesGroups();
