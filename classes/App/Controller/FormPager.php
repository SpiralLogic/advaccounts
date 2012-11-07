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
  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  abstract class FormPager extends \ADV\App\Controller\Pager
  {

    /** @var \ADV\App\DB\Base */
    protected $object;
    protected $defaultFocus;
    protected $tableWidth = '50';
    protected $security;
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    abstract protected function formContents(Form $form, View $view);

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
    protected function generateForm(Form $form = null, View $view = null, $object = null, $contents = true) {
      $view = $view ? : new View('form/simple');
      $form = $form ? : new Form();
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
      $cols       = $this->getPagerColumns();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
     // Pager::kill($pager_name);
      $table = Pager::newPager($pager_name, $cols);
      $table->setData($this->getTableRows($pager_name));
      $table->width = $this->tableWidth;
      $table->display();
    }
    /**
     * @return array
     */
    protected function getPagerColumns(){
      $columns = parent::getPagerColumns();
      $columns[]=['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatEditBtn']];
      $columns[]=['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatDeleteBtn']];
      return $columns;
    }
  }
