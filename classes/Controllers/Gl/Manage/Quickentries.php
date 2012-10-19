<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\GL\Manage;
  use ADV\App\Controller\Manage;
  use GL_UI;
  use Tax_Type;
  use ADV\App\Pager\Pager;
  use GL_QuickEntry;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /**

   */
  class Quickentries extends Manage
  {

    protected $tableWidth = '70';
    public $linesid;
    /** @var  \ADV\App\GL\QuickEntryLine */
    public $line;
    protected function before() {
      $this->object = new \ADV\App\GL\QuickEntry();
      $this->line   = new \ADV\App\GL\QuickEntryLine();
      $this->runPost();
    }
    protected function  runPost() {
      if (REQUEST_POST) {
        $lineid = $this->getActionId(['Line' . DELETE, 'Line' . EDIT, 'Line' . INACTIVE]);
        switch ($this->action) {
          case 'Line' . DELETE:
            $this->onDelete($lineid, $this->line);
            break;
          case 'Line' . EDIT:
            $this->line->load($lineid);
            $this->object->load($this->line->qid);
            break;
          case 'Line' . SAVE:
            $changes = isset($changes) ? $changes : $_POST;
            $status  = $this->onSave($changes, $this->line);
            $this->object->load($this->line->qid);
            break;
          case 'Line' . CANCEL:
            $status = $this->line->getStatus();
            break;
          default:
            parent::runPost();
        }
        if (isset($status)) {
          $this->Ajax->addStatus($status);
        }
      }
    }
    protected function index() {
      $this->Page->start(_($help_context = "Quick Entries"), SA_QUICKENTRY);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      $cols       = [
        ['type' => 'skip'],
        ['type' => 'skip'],
        'Action'           => ['fun' => [$this, 'formatActionLine'], 'edit' => [$this, 'formatActionLineEdit']],
        'Account/Tax Type' => ['edit' => [$this, 'formatAccountLineEdit']],
        'Amount'           => ['edit' => [$this, 'formatAmountLineEdit']],
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineEditBtn'], 'edit' => [$this, 'formatSaveLineBtn']],
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineDeleteBtn'], 'edit' => [$this, 'formatCancelLineBtn']],
      ];
      $pager_name = 'QE_Lines';
      //\ADV\App\Pager\Pager::kill($pager_name);
      $linestable          = \ADV\App\Pager\Pager::newPager($pager_name, $this->object->getLines(), $cols);
      $this->line->qid     = $this->object->id;
      $this->line->action  = $this->Input->hasPost('action', null, $this->line->action);
      $linestable->width   = $this->tableWidth;
      $linestable->editing =$this->line;
      $linestable->editid =$this->line->id;
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
        'Type'        => ['fun' => [$this, 'formatType']],
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
          $form->number('amount', $this->User->prefs->exrate_dec);
        } else {
          $form->number('amount', $this->User->prefs->exrate_dec);
        }
      } else {
        $form->hidden('amount');
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
      $this->JS->addLive('Adv.o.wrapper.on("change","#action",function() {    JsHttpRequest.request(this);} )');
      return $form->arraySelect('action', GL_QuickEntry::$actions);
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @return mixed
     */
    public function formatAccountLineEdit(Form $form) {
      $actn = $this->line->action;
      if (strtolower($actn) == 't') {
        //Tax_ItemType::row(_("Item Tax Type").":",'dest_id', null);
        return $form->custom(Tax_Type::select('dest_id'));
      } else {
        return $form->custom(GL_UI::all('dest_id', null, $_POST['type'] == QE_DEPOSIT || $_POST['type'] == QE_PAYMENT));
      }
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @return \ADV\App\Form\Button
     */
    public function formatSaveLineBtn(Form $form) {
      return $form->button('_action', 'Line' . SAVE, SAVE)->preIcon(ICON_SAVE)->type('mini')->type('success');
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @return \ADV\App\Form\Button
     */
    public function formatCancelLineBtn(Form $form) {
      return $form->button('_action', 'Line' . CANCEL, CANCEL)->preIcon(ICON_CANCEL)->type('mini')->type('danger');
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    public function formatActionLine($row) {
      return GL_QuickEntry::$actions[$row['action']];
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatLineEditBtn($row) {
      $button = new \ADV\App\Form\Button('_action', 'Line' . EDIT . $row['id'], EDIT);
      $button->type('mini')->type('primary');
      return $button;
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatLineDeleteBtn($row) {
      $button = new \ADV\App\Form\Button('_action', 'Line' . DELETE . $row['id'], DELETE);
      $button->preIcon(ICON_DELETE);
      $button->type('mini')->type('danger');
      return $button;
    }
  }
