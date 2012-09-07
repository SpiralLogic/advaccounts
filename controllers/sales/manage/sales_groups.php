<?php
  use ADV\App\Sales\Group;
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  class SalesGroups extends \ADV\App\Controller\Manage
  {
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
      $form->text('description')->label('Area Name:');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL);
      $form->submit(SAVE)->type('success')->preIcon(ICON_ADD);
    }
    protected function generateTable() {
      $cols         = [
        ['type'=> 'skip'],
        'Group Name',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      $table        = DB_Pager::new_db_pager('sales_group_table', Group::getAll(), $cols);
      $table->class = 'width30';
      $table->display();
    }
  }

  new SalesGroups();
