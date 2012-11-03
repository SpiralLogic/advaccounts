<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      5/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Controller;

  use ADV\Core\Status;
  use InvalidArgumentException;
  use ADV\App\Pager\Edit;

  /**

   */
  abstract class InlinePager extends \ADV\App\Controller\Pager
  {
    public $editing;
    protected function index() {
      $this->Page->init($this->title, $this->security);
      $this->beforeTable();
      $this->generateTable();
      echo '<br>';
      $this->Page->end_page(true);
    }
    protected function beforeTable() {
    }
    /**
     * @return \ADV\App\Pager\Pager
     */
    protected function generateTable() {
      $cols = $this->generateTableCols();
      $pager_name = end(explode('\\', ltrim(get_called_class(), '\\'))) . '_table';
      Edit::kill($pager_name);
      $table = Edit::newPager($pager_name, $cols);
      $this->getEditing($table);
      $table->setData($this->getTableRows($pager_name));
      $table->width = $this->tableWidth;
      $table->display();
    }
    /**
     * @param \ADV\App\Pager\Pager $table
     */
    protected function getEditing(Edit $table) {
      if (!$table->editing instanceof \ADV\App\DB\Base) {
        throw new InvalidArgumentException('Editing must be of type DB\Base');
      }
    }
  }
