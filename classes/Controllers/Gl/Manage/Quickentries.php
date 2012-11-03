<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\GL\Manage;

  use ADV\App\Controller\Pager;
  use GL_UI;
  use Tax_Type;
  use ADV\App\Pager\Pager;
  use GL_QuickEntry;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /**

   */
  class Quickentries extends \ADV\App\Controller\FormPager
  {
    protected $tableWidth = '70';
    public $linesid;
    /** @var  \ADV\App\GL\QuickEntryLine */
    public $line;
    protected function before() {
      $this->object = new \ADV\App\GL\QuickEntry();
      $this->line = new \ADV\App\GL\QuickEntryLine();
      $this->runPost();
      if (!$this->object->id) {
        $this->object->load($this->Input->post('qid'));
      }
      $this->line->qid = $this->object->id;
    }
    protected function index() {
      $this->Page->start(_($help_context = "Quick Entries"), SA_QUICKENTRY);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      $cols = [
        ['type' => 'skip'],
        ['type' => 'skip'],
        'Action' => ['fun' => [$this, 'formatActionLine'], 'edit' => [$this, 'formatActionLineEdit']],
        'Account/Tax Type' => ['edit' => 'skip'],
        ['type' => 'skip', 'edit' => [$this, 'formatAccountLineEdit']],
        'Amount' => ['type' => 'amount', 'edit' => [$this, 'formatAmountLineEdit']],
        ['type' => 'skip'],
        ['type' => 'skip'],
      ];
      $pager_name = 'QE_Lines';
      $linestable = \ADV\App\Pager\Edit::newPager($pager_name, $cols);
      $linestable->setObject($this->line);
      $linestable->editing->qid = $this->object->id;
      $linestable->width = $this->tableWidth;
      $linestable->setData($this->object->getLines());
      $linestable->display();
      $this->Page->end_page(true);
    }
    /**≈
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Quick Entry';
      $form->hidden('id');
      $form->text('description')->label('Description')->focus($this->action == EDIT);
      $form->arraySelect('type', GL_QuickEntry::$types)->label('Type');
      $form->amount('base_amount')->label('Base Amount');
      $form->text('base_desc')->label('Base Description');
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        ['type' => 'skip'],
        'Type' => ['fun' => [$this, 'formatType']],
        'Description',
        'Base Amount' => ['type' => Pager::TYPE_AMOUNT],
        'Description',
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatEditBtn']],
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
    /**
     * @param $id
     */
    protected function onEdit($id) {
      $this->linesid = $id;
      parent::onEdit($id);
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatType($row) {
      return GL_QuickEntry::$types[$row['type']];
    }
    /**
     * @param $form
     */
    public function formatAmountLineEdit(Form $form) {
      $actn = $this->line->action;
      if ($actn != '=') {
        if ($actn == '%') {
          return $form->number('amount', $this->User->prefs->exrate_dec);
        } else {
          return $form->amount('amount');
        }
      } else {
        return $form->hidden('amount');
      }
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @return mixed
     */
    public function formatActionLineEdit(Form $form) {
      $this->Ajax->addFocus(true, 'action');
      $field = $form->arraySelect('action', GL_QuickEntry::$actions);
      $field['class'] .= ' async';
      return $field;
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @return mixed
     */
    public function formatAccountLineEdit(Form $form) {
      $actn = $this->line->action;
      if (strtolower($actn[0]) == 't') {
        //Tax_ItemType::row(_("Item Tax Type").":",'dest_id', null);
        return $form->custom(Tax_Type::select('dest_id'));
      } else {
        return $form->custom(GL_UI::all('dest_id', null, $_POST['type'] == QE_DEPOSIT || $_POST['type'] == QE_PAYMENT));
      }
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatActionLine($row) {
      return GL_QuickEntry::$actions[$row['action']];
    }
  }
