<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 20/10/12
   * Time: 4:03 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Pager;

  use ADV\App\Form\Form;
  use ADV\Core\Arr;
  use ADV\Core\Ajax;
  use ADV\Core\View;

  /**
   *
   */
  class Edit extends Pager
  {
    public $editing = null;
    public $editid;
    /**
     * @param $columns
     */
    protected function setColumns($columns) {
      Arr::append(
        $columns,
        [
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineEditBtn'], 'edit' => [$this, 'formatSaveLineBtn']],
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineDeleteBtn'], 'edit' => [$this, 'formatCancelLineBtn']]
        ]
      );
      parent::setColumns($columns);
      foreach ($this->columns as &$c) {
        if (isset($c['edit'])) {
          if ($c['edit'] === true && isset($c['fun']) && is_array($c['fun'])) {
            $c['edit'] = $c['fun'];
            $c['edit'][1] .= 'Edit';
          }
        }
      }
    }
    /**
     * @return bool
     */
    public function display() {
      $this->selectRecords();
      Ajax::_start_div("_{$this->name}_span");
      $headers = $this->generateHeaders();
      $class   = $this->class . ' width' . rtrim($this->width, '%');
      $form    = null;
      if ($this->editing) {
        $form = new Form();
        echo $form['_start'];
      }
      echo "<div class='center'><table class='" . $class . "'>";
      echo  $this->displayHeaders($headers);
      $this->currentRowGroup = null;
      $this->fieldnames      = array_keys(reset($this->data));
      foreach ($this->data as $row) {
        $this->displayRow($row, $form);
      }
      if (is_object($this->editing) && !$this->editid) {
        $row = end($this->data) ? : get_object_vars($this->editing);
        if (!$this->fieldnames) {
          $this->fieldnames = array_keys($row);
        }
        $this->editRow($form);
      }
      echo "<tfoot>";
      echo $this->displayNavigation('bottom');
      echo "</tfoot></table></div>";
      if ($this->editing) {
        echo $form['_end'];
      }
      Ajax::_end_div();
      return true;
    }
    /**
     * @param      $row
     * @param Form $form
     *
     * @return mixed
     */
    protected function displayRow($row, Form $form = null) {
      if ($this->editid == $row['id'] && $form) {
        return $this->editRow($form);
      }
      return parent::displayRow($row);
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @internal param bool $setvals
     * @return mixed
     */
    protected function editRow(Form $form) {
      $view = new View('form/pager');
      $view->set('form', $form);
      $group  = 'first';
      $fields = $this->fieldnames;
      foreach ($this->columns as $key => $col) {
        $field   = '';
        $coltype = isset($col['type']) ? $col['type'] : '';
        $name    = isset($col['name']) ? $col['name'] : '';
        $name    = $name ? : $fields[$key];
        if (isset($col['edit'])) { // use data input function if defined
          $coltype = 'fun';
        }
        $form->group($group);
        $class      = isset($col['class']) ? $col['class'] : null;
        $alignclass = isset($col['align']) ? " class='$class align" . $col['align'] . "'" : ($class ? "class='$class'" : "");
        switch ($coltype) { // format columnhsdaasdg
          case 'fun': // column not displayed
            $fun = $col['edit'];
            if (is_callable($fun)) {
              $field = call_user_func($fun, $form);
            }
            $group = 'rest';
            break;
          case self::TYPE_SKIP: // column not displayed
          case self::TYPE_GROUP: // column not displayed
            $field = $form->group('hidden')->hidden($name);
            break;
          case self::TYPE_AMOUNT: // column not displayed
            $field = $form->amount($name);
            $group = 'rest';
            break;
          default:
            $field = $form->text($name);
            $group = 'rest';
        }
        if (is_a($field, '\\ADV\\App\\Form\\Field')) {
          if (is_object($this->editing) && $name) {
            $field->initial($this->editing->$name);
          }
        }
      }
      $view->render();
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
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @return \ADV\App\Form\Button
     */
    protected function formatSaveLineBtn(Form $form) {
      return $form->button('_action', 'Line' . SAVE, SAVE)->preIcon(ICON_SAVE)->type('mini')->type('success');
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @return \ADV\App\Form\Button
     */
    protected function formatCancelLineBtn(Form $form) {
      return $form->button('_action', 'Line' . CANCEL, CANCEL)->preIcon(ICON_CANCEL)->type('mini')->type('danger');
    }
    /**
     * @param $coldef
     */
    protected function restoreColumnFunction($coldef) {
      foreach ($this->columns as &$column) {
        if (isset($column['funkey'])) {
          $column['fun']  = $coldef[$column['funkey']]['fun'];
          $column['edit'] = $coldef[$column['funkey']]['edit'];
        }
      }
      Arr::append(
        $this->columns,
        [
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineEditBtn'], 'edit' => [$this, 'formatSaveLineBtn']],
        ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineDeleteBtn'], 'edit' => [$this, 'formatCancelLineBtn']]
        ]
      );
    }
    public function __sleep() {
      $this->columns = array_slice($this->columns, 0, count($this->columns) - 2);
      foreach ($this->columns as &$col) {
        if (isset($col['edit'])) {
          $col['edhit'] = null;
        }
      }
      return parent::__sleep();
    }
  }
