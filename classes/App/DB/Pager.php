<?php
  use ADV\Core\Cell;
  use ADV\Core\DIC;
  use ADV\Core\DB\DB;
  use ADV\App\Dates;
  use ADV\Core\JS;
  use ADV\Core\Input\Input;
  use ADV\Core\Num;
  use ADV\Core\HTML;
  use ADV\App\Forms;
  use ADV\Core\Ajax;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au

  Controler part of database table pager with column sort.
  To display actual html object call $table->display($name) inside
  any form.

   */
  class DB_Pager implements Countable {
    const NEXT           = 'next';
    const PREV           = 'prev';
    const LAST           = 'last';
    const FIRST          = 'first';
    const TYPE_BOOL      = 'bool';
    const TYPE_TIME      = 'time';
    const TYPE_DATE      = 'date';
    const TYPE_DATESTAMP = 'dstamp';
    const TYPE_TIMESTAMP = 'tstamp';
    const TYPE_PERCENT   = 'percent';
    const TYPE_AMOUNT    = 'amount';
    const TYPE_QTY       = 'qty';
    const TYPE_EMAIL     = 'email';
    const TYPE_RATE      = 'rate';
    const TYPE_INACTIVE  = 'inactive';
    const TYPE_ID        = 'id';
    const TYPE_SKIP      = 'skip';
    const TYPE_GROUP     = 'group';
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /** @var Input */
    static $Input;
    /** @var JS */
    static $JS;
    /** @var Dates */
    static $Dates;
    const SQL = 1;
    const ARR = 2;
    /** @var */
    public $sql;
    /**@var*/
    public $name;
    /** column definitions (head, type, order) */
    public $columns = [];
    protected $rowGroup = [];
    /** @var */
    public $marker_class = 'overduebg';
    /** @var */
    public $notice_class = 'overduefg';
    /** @var string table width (default '95%') */
    public $width = "80%";
    /** @var array */
    protected $data = [];
    /** @var */
    public $curr_page = 1;
    /** @var */
    public $max_page = 1;
    /** @var */
    public $last_page;
    /** @var */
    public $prev_page;
    /** @var */
    public $next_page;
    /** @var */
    public $first_page;
    /** @var int|? */
    public $page_length = 1;
    /** @var */
    public $rec_count = 0;
    /** @var */
    public $select;
    /** @var */
    public $where;
    /** @var */
    public $from;
    /** @var */
    public $group;
    /** @var */
    public $order;
    /** @var */
    public $extra_where;
    /** @var bool */
    public $ready;
    public $showInactive = null;
    public $type;
    public $id;
    public $rowFunction;
    public $class = 'padded grid ';
    protected $hasBar = false;
    protected $dataset = [];
    protected $currentRowGroup = null;
    /**
     * @static
     *
     * @param      $name
     * @param      $sql
     * @param      $coldef
     *
     * @return DB_Pager
     */
    public static function newPager($name, $sql, $coldef) {
      $pager = DIC::get('Pager', $name, $sql, $coldef);
      if (is_array($sql)) {
        $pager->sql = $pager->sql ? : $sql;
      }
      foreach ($pager->columns as &$column) {
        if (isset($column['funkey'])) {
          $column['fun'] = $coldef[$column['funkey']]['fun'];
        }
      }
      if (Input::_post('_action') == 'showInactive') {
        $pager->showInactive = (Input::_post('_value', Input::NUMERIC) == 1);
      }

      return $pager;
    }
    /**
     * @param $name
     */
    public static function kill($name) {
      unset($_SESSION['pager'][$name]);
    }
    /**
     * @param null $sql
     */
    public function refresh($sql = null) {
      if ($sql) {
        $this->setSQL($sql);
      }
      $this->ready = false;
    }
    /**
     * @param      $name
     * @param      $sql
     * @param      $coldef
     */
    public function __construct($name, $sql, $coldef) {

      $this->name = $name;
      $this->setSQL($sql);
      $this->setColumns($coldef);
    }
    /** @return bool Initialization after changing record set */
    protected function init() {
      if ($this->ready == false) {
        if ($this->type == self::SQL) {
          $sql    = $this->sqlGen(true);
          $result = static::$DB->_query($sql, 'Error reading record set');
          if ($result == false) {
            return false;
          }
          $row             = static::$DB->_fetchRow($result);
          $this->rec_count = $row[0];
          $this->max_page  = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
          $this->setPage(1);
        } elseif ($this->type == self::ARR) {
          $this->rec_count = count($this->sql);
          $ord             = $this->rowGroup;
          $fields          = array_keys($this->sql[0]);
          $this->dataset   = [];
          foreach ($this->sql as $key => $row) {
            $row[]           = $key;
            $this->dataset[] = $row;
          }
          foreach ($this->columns as $key => $col) {
            if (isset($col['ord'])) {
              if ($col['ord']) {
                // offset by one because want to 1 indexed and columns is 0 indexed
                $ord[] = [$key + 1, $col['ord']];
              }
            }
          }
          if ($ord) {
            $ordcount = count($ord);
            for ($i = 0; $i < $ordcount; $i++) {
              $index = $ord[$i];
              if ($index[1] == 'none' && $i < $ordcount) {
                continue;
              }
              foreach ($this->dataset as $key => $row) {
                if ($index[1] == 'none') {
                  $args[$fields[$index[0] - 1]][$key] = end($row);
                  continue;
                }
                //minus 1 because fields are 0 indexed
                $args[$fields[$index[0] - 1]][$key] = $row[$fields[$index[0] - 1]];
              }
              $args[] = ($index[1] == 'desc' ? SORT_DESC : SORT_ASC);
            }
            $args[] =& $this->dataset;

            call_user_func_array('array_multisort', $args);
          }
          $this->max_page   = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
          $this->curr_page  = $this->curr_page ? : 1;
          $this->next_page  = ($this->curr_page < $this->max_page) ? $this->curr_page + 1 : null;
          $this->prev_page  = ($this->curr_page > 1) ? ($this->curr_page - 1) : null;
          $this->last_page  = ($this->curr_page < $this->max_page) ? $this->max_page : null;
          $this->first_page = ($this->curr_page != 1) ? 1 : null;
        }
        $this->ready = true;
      }
      return true;
    }
    /**
     * @param $id
     * @param $prefix
     * @param $html
     */
    protected function generateNavigation($id, $prefix, $html) {
      $this->navi($id, $html, $prefix . self::FIRST, 1, $this->first_page, "<i class='icon-fast-backward'> </i>");
      $this->navi($id, $html, $prefix . self::PREV, $this->curr_page - 1, $this->prev_page, '<i class="icon-backward"> </i>');
      $this->navi($id, $html, $prefix . self::NEXT, $this->curr_page + 1, $this->next_page, '<i class="icon-forward"> </i>');
      $this->navi($id, $html, $prefix . self::LAST, $this->max_page, $this->last_page, '<i class="icon-fast-forward"> </i>');
    }
    /** @return array */
    protected function makeHeaders() {
      $headers  = [];
      $inactive = !static::$Input->post('show_inactive');
      foreach ($this->columns as $num_col => $col) {
        if (isset($col['head']) || $inactive) {
          if ($col['type'] == self::TYPE_SKIP || $col['type'] == self::TYPE_GROUP || $col['type'] == self::TYPE_INACTIVE && $this->showInactive === false) {
            continue;
          }
          if (!isset($col['ord'])) {
            $headers[] = $col['head'];
            continue;
          }
          switch ($col['ord']) {
            case 'desc':
              $icon = " <i class='" . ICON_DESC . "'> </i>";
              break;
            case 'asc':
              $icon = " <i class='" . ICON_ASC . "'> </i>";
              break;
            default:
              $icon = '';
          }
          $html = new HTML;
          $this->navi('', $html, $this->name . '_sort_' . $num_col, $col['ord'], true, $col['head'] . $icon);
          $headers[] = (string) $html;
        }
      }
      return $headers;
    }
    /**
     * Set column definitions
     * types: inactive|skip|insert
     *
     * @param $columns
     */
    protected function setColumns($columns) {

      $columns = (array) $columns;
      foreach ($columns as $colindex => $coldef) {
        if (is_string($colindex) && is_string($coldef)) {
          $c = ['head'=> $colindex, 'type'=> $coldef];
        } elseif (is_string($colindex) && is_array($coldef)) {
          $coldef ['head'] = $colindex;
          $c               = $coldef;
        } elseif (is_array($coldef)) {
          $coldef['head'] = '';
          $c              = $coldef;
        } else {
          $c = ['head' => $coldef, 'type' => 'text'];
        }
        if (!isset($c['type'])) {
          $c['type'] = 'text';
        }
        switch ($c['type']) {
          case self::TYPE_INACTIVE:
            if ($this->showInactive === null) {
              $this->showInactive = false;
            }
            break;
          case self::TYPE_GROUP:
            $this->rowGroup[] = [count($this->columns) + 1, 'asc'];
            break;
          case 'insert':
          default:
            break;
          case self::TYPE_SKIP: // skip the column (no header)
            unset($c['head']);
            break;
        }
        if (isset($coldef['fun'])) {
          $c['funkey'] = $colindex;
        }
        $this->columns[] = $c;
      }
    }
    /**
     * @param $sql
     * Parse base sql select query.
     */
    protected function setSQL($sql) {
      if (is_array($sql)) {
        $this->sql       = $sql;
        $this->type      = self::ARR;
        $this->rec_count = count($this->sql);
        $this->max_page  = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
        $this->ready     = false;
        return;
      }
      if ($sql != $this->sql) {
        $this->sql   = $sql;
        $this->type  = self::SQL;
        $this->ready = false;
        $parts       = preg_split('/\sORDER\s*BY\s/si', $sql, 2);
        if (count($parts) == 2) {
          $sql         = $parts[0];
          $this->order = $parts[1];
        }
        $parts       = preg_split('/\sGROUP\s*BY\s/si', $sql, 2);
        $this->group = null;
        if (count($parts) == 2) {
          $sql         = $parts[0];
          $this->group = $parts[1];
        }
        $parts = preg_split('/\sWHERE\s/si', $sql, 2);
        if (count($parts) == 2) {
          $sql         = $parts[0];
          $this->where = $parts[1];
        }
        $parts = preg_split('/\sFROM\s/si', $sql, 2);
        if (count($parts) == 2) {
          $sql        = $parts[0];
          $this->from = $parts[1];
        }
        $this->select = $sql;
      }
    }
    /**
     * @param null $where
     *
     * @return mixed
     * Set additional constraint on record set
     */
    protected function setWhere($where = null) {
      if ($where) {
        if (!is_array($where)) {
          $where = array($where);
        }
        if (count($where) == count($this->extra_where) && !count(array_diff($this->extra_where, $where))
        ) {
          return;
        }
      }
      $this->extra_where = $where;
      $this->ready       = false;
    }
    /**
     * @param $to
     * Calculates page numbers for html controls.

     */
    protected function setPage($to) {
      switch ($to) {
        case self::NEXT:
          $page = $this->curr_page + 1;
          break;
        case self::PREV:
          $page = $this->curr_page - 1;
          break;
        case self::LAST:
          $page = $this->last_page;
          break;
        default:
          if (is_numeric($to)) {
            $page = $to;
            break;
          }
        case self::FIRST:
          $page = 1;
          break;
      }

      $page = ($page < 1) ? 1 : $page;
      $max  = $this->max_page;
      $page = ($page > $max) ? $max : $page;

      $this->curr_page  = $page;
      $this->next_page  = ($page < $max) ? $page + 1 : null;
      $this->prev_page  = ($page > 1) ? ($page - 1) : null;
      $this->last_page  = ($page < $max) ? $max : null;
      $this->first_page = ($page != 1) ? 1 : null;
    }
    /**
     * @return bool
     * Query database

     */
    protected function query() {
      Ajax::_activate("_{$this->name}_span");
      if (!$this->init()) {
        return false;
      }
      if ($this->type == self::SQL) {
        $this->data = [];
        if ($this->rec_count == 0) {
          return true;
        }
        $sql    = $this->sqlGen(false);
        $result = static::$DB->_query($sql, 'Error browsing database: ' . $sql);
        if (!$result) {
          return false;
        }
        $this->data = static::$DB->_fetchAll();
      } elseif ($this->type == self::ARR) {
        $offset = ($this->curr_page - 1) * $this->page_length;

        if ($offset + $this->page_length >= $this->rec_count) {
          $offset = $this->rec_count - $this->page_length;
        }
        $this->data = array_slice($this->dataset, $offset, $this->page_length);
      }
      $dbfield_names = array_keys($this->data[0]);
      $cnt           = min(count($dbfield_names), count($this->columns));
      for ($c = $i = 0; $c < $cnt; $c++) {
        if (!(isset($this->columns[$c]['insert']) && $this->columns[$c]['insert'])) {
          //	if (!@($this->columns[$c]['type']=='skip'))
          $this->columns[$c]['name'] = $dbfield_names[$c];
          if (isset($this->columns[$c]['type']) && !($this->columns[$c]['type'] == 'insert')) {
            $i++;
          }
        }
      }
      return true;
    }
    /**
     * @param $name - base name for pager controls and $_SESSION object name
     * -----------------------------F------------------------------------------------
     *              Creates new DB_Pager $_SESSION object on first page call.
     *              Retrieves from $_SESSION var on subsequent $_POST calls
     * $sql  - base sql for data inquiry. Order of fields implies
     *              pager columns order.
     * $coldef - array of column definitions. Example definitions
     *              Column with title 'User name' and default text format:
     * 'User name'
     *              Skipped field from sql query. Data for the field is not displayed:
     * 'dummy' => 'skip'
     *              Column without title, data retrieved form row data with function func():
     *              array('fun'=>'func')
     *              Inserted column with title 'Some', formated with function rowfun().
     *              formated as date:
     * 'Some' => array('type'=>'date, 'insert'=>true, 'fun'=>'rowfun')
     *              Column with name 'Another', formatted as date,
     *              sortable with ascending start order (available orders: asc,desc, '').
     * 'Another' => array('type'=>'date', 'ord'=>'asc')
     *              All available column format types you will find in DB_Pager_view.php file.
     *              If query result has more fields than count($coldef), rest of data is ignored
     *              during display, but can be used in format handlers for 'spec' and 'insert'
     *              type columns.
     *              Force pager initialization.

     */
    /**
     * Set current page in response to user control.
     */
    protected function selectRecords() {
      $page = Forms::findPostPrefix($this->name . '_page_', false);
      $sort = Forms::findPostPrefix($this->name . '_sort_', false);
      if ($page) {
        $this->changePage($_POST[$this->name . '_page_' . $page]);
        if ($page == self::NEXT && !$this->next_page || $page == self::FIRST && !$this->first_page) {
          static::$JS->setFocus($this->name . '_page_prev_top');
        }
        if ($page == self::PREV || $page == self::LAST && !$this->last_page) {
          static::$JS->setFocus(['el'=> $this->name . '_page_next_bottom', 'pos'=> 'bottom']);
        }
      } elseif ($sort !== null) {
        $this->sortTable($sort);
      } else {
        $this->query();
      }
    }
    /**
     * @param null $page
     *
     * @return bool
     * Set query result page

     */
    protected function changePage($page = null) {

      $this->setPage($page);

      $this->query();
      return true;
    }
    /**
     * @param $col
     *
     * @return bool
     * Change sort column direction
     * in order asc->desc->none->asc
     */
    protected function sortTable($col) {
      if (is_null($col)) {
        return false;
      }
      $current_order = Input::_post($this->name . '_sort_' . $col);
      switch ($current_order) {
        case 'asc':
          $ord = 'desc';
          break;
        case 'desc':
          $ord = 'none';
          break;
        case '':
        case 'none':
          $ord = 'asc';
          break;
        default:
          return false;
      }
      $this->columns[$col]['ord'] = $ord;
      $this->ready                = false;
      $this->setPage(1);
      $this->query();
      return true;
    }
    /**
     * @param bool $count
     *
     * @return string
     * Generate db query from base sql
     * $count==false - for current page data retrieval
     * $count==true  - for total records count

     */
    protected function sqlGen($count = false) {
      $select = $this->select;
      $from   = $this->from;
      $where  = $this->where;
      $group  = $this->group;
      $order  = $this->order;
      if (count($this->extra_where)) {
        $where .= ($where == '' ? '' : ' AND ') . implode($this->extra_where, ' AND ');
      }
      if ($where) {
        $where = " WHERE ($where)";
      }
      if ($count) {
        $group = $group == '' ? "*" : "DISTINCT $group";
        return "SELECT COUNT($group) FROM $from $where";
      }
      $sql = "$select FROM $from $where";
      if ($group) {
        $sql .= " GROUP BY $group";
      }

      $ord = [];
      foreach ($this->rowGroup as $group) {
        $ord[] = $group[0] . ' ' . $group[1];
      }
      foreach ($this->columns as $key=> $col) {
        if (isset($col['ord'])) {
          if ($col['ord'] && $col['ord'] != 'none') {
            $name  = isset($col['name']) ? $col['name'] : $key + 1;
            $ord[] = $name . ' ' . $col['ord'];
          }
        }
      }
      if (count($ord)) {
        $sql .= " ORDER BY " . implode($ord, ',');
      } elseif ($order) {
        $sql .= " ORDER BY $order";
      } // original base query order
      $page_length = $this->page_length;
      $offset      = ($this->curr_page - 1) * $page_length;
      $sql .= " LIMIT $offset, $page_length";
      return $sql;
    }
    /**
     * @static
     * @internal param \DB_Pager $pager
     * @return bool
     */
    public function display() {
      $this->selectRecords();
      Ajax::_start_div("_{$this->name}_span");
      $headers = $this->makeHeaders();
      $class   = $this->class . ' width' . rtrim($this->width, '%');
      echo "<div class='center'><table class='" . $class . "'>";
      echo  $this->displayHeaders($headers);
      $this->currentRowGroup = null;
      foreach ($this->data as $row) {
        $this->displayRow($row);
      }
      echo "<tfoot>";
      echo $this->displayNavigation('bottom');
      echo "</tfoot></table></div>";
      Ajax::_end_div();
      return true;
    }
    /**
     * @static
     *
     * @param          $id
     * @param HTML     $html
     * @param          $name
     * @param          $value
     * @param bool     $enabled
     * @param null     $title
     *
     * @return string
     */
    protected function navi($id, HTML $html, $name, $value, $enabled = true, $title = null) {
      $attrs = [
        'disabled'=> (bool) !$enabled,
        'class'   => 'navibutton',
        'type'    => 'submit',
        'name'    => $name,
        'value'   => $value,
      ];
      $id    = $id ? $name . '_' . $id : $name;

      $html->button($id, $attrs)->span(null, $title, false)->_button;
    }
    /**
     * @param $id
     *
     * @return string
     */
    protected function displayNavigation($id) {
      $colspan = count($this->columns);
      $inact   = '';
      if ($this->showInactive !== null) {
        $inact = $this->formatInactiveFooter();
      }
      $html = new HTML();
      $html->tr(['class'=> 'navibar']);
      $html->td(['colspan'=> $colspan, 'class'=> 'navibar']);
      if ($this->rec_count) {
        $button_prefix = $this->name . '_page_';
        $html->span(null, ['class'=> 'floatright']);
        $this->generateNavigation($id, $button_prefix, $html);
        $html->_span();
        $from = ($this->curr_page - 1) * $this->page_length + 1;
        $to   = $from + $this->page_length - 1;
        if ($to > $this->rec_count) {
          $to = $this->rec_count;
        }
        $all = $this->rec_count;
        $html->span(true, "Records $from-$to of $all " . $inact, [], false);
      } else {
        $html->span(null, _('No records') . $inact, [], false);
      }
      $html->_td()->tr;
      return $html;
    }
    /**
     * @param $headers
     *
     * @return string
     */
    protected function displayHeaders($headers) {
      $headers = (array) $headers;
      $content = '<thead>' . $this->displayNavigation('top') . '<tr class="naviheader"><th>' . implode('</th><th>', $headers) . '</th></tr></thead>';
      return $content;
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    protected function displayRow($row) {
      if ($this->rowGroup) {
        $fields = array_keys($row);
        $field  = $fields[$this->rowGroup[0][0] - 1];
        if ($this->currentRowGroup != $row[$field]) {
          $this->currentRowGroup = $row[$field];
          echo "<tr class='navigroup'><th colspan=" . count($this->columns) . ">" . $row[$field] . "</th></tr>";
        }
      }
      echo (is_callable($this->rowFunction)) ? call_user_func($this->rowFunction, $row) : "<tr>\n";
      foreach ($this->columns as $col) {
        $coltype = isset($col['type']) ? $col['type'] : '';
        $cell    = isset($col['name']) ? $row[$col['name']] : '';
        if (isset($col['fun'])) { // use data input function if defined
          $fun = $col['fun'];
          if (is_callable($fun)) {
            $cell = call_user_func($fun, $row, $col['useName'] ? $col['name'] : $cell);
          } elseif (is_callable([$this, $fun])) {
            $cell = $this->$fun($row, $cell);
          } else {
            $cell = '';
          }
        }
        $class = isset($col['class']) ? $col['class'] : null;
        switch ($coltype) { // format columnhsdaasdg
          case self::TYPE_BOOL:
            Cell::label(($cell ? 'Yes' : 'No'), " class='$class width40'");
            break;
          case self::TYPE_TIME:
            Cell::label($cell, " class='$class width40'");
            break;
          case self::TYPE_DATE:
            Cell::label(static::$Dates->sqlToDate($cell), " class='$class center nowrap'");
            break;
          case self::TYPE_DATESTAMP: // time stamp displayed as date
            Cell::label(static::$Dates->sqlToDate(substr($cell, 0, 10)), " class='$class center nowrap'");
            break;
          case self::TYPE_TIMESTAMP: // time stamp - FIX useformat
            Cell::label(static::$Dates->sqlToDate(substr($cell, 0, 10)) . ' ' . substr($cell, 10), "class='$class center'");
            break;
          case self::TYPE_PERCENT:
            Cell::percent($cell * 100);
            break;
          case self::TYPE_AMOUNT:
            ($cell === '') ? Cell::label('') : Cell::amount($cell, false);
            break;
          case self::TYPE_QTY:
            ($cell == '') ? Cell::label('') : Cell::qty($cell, false, isset($col['dec']) ? $col['dec'] : null);
            break;
          case self::TYPE_EMAIL:
            Cell::email($cell, isset($col['align']) ? "class='$class " . $col['align'] . "'" : null);
            break;
          case self::TYPE_RATE:
            Cell::label(Num::_exrateFormat($cell), "class='$class center'");
            break;
          case self::TYPE_INACTIVE:
            $this->formatInactive($row);
            break;
          case self::TYPE_ID:
            if (isset($col['align'])) {
              Cell::label($cell, " class='$class " . $col['align'] . " pagerclick' data-id='" . $row[self::TYPE_ID] . "'");
            } else {
              Cell::label($cell, " class='$class pagerclick' data-id='" . $row[self::TYPE_ID] . "'");
            }
            break;
          default:
            $alignclass = isset($col['align']) ? " class='$class align" . $col['align'] . "'" : ($class ? "class='$class'" : "");
            Cell::label($cell, $alignclass);
            break;
          case self::TYPE_SKIP: // column not displayed
          case self::TYPE_GROUP: // column not displayed
        }
      }
      echo '</tr>';
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Field
     */
    protected function formatInactive($row) {
      $field = '';
      if ($this->showInactive === true) {
        $checked = $row[self::TYPE_INACTIVE] ? 'checked' : '';
        $field   = '<td class="center"><input ' . $checked . ' type="checkbox" name="_action" value="' . INACTIVE . $row[self::TYPE_ID] . '" onclick="JsHttpRequest.request(this)"></td>';
      }
      echo $field;
    }
    /**
     * @return string
     */
    protected function formatInactiveFooter() {
      $checked = ($this->showInactive) ? 'checked' : '';
      $field   = "<label><input $checked  type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'> Show also inactive</label>";
      Ajax::_activate("_{$this->name}_span");
      return $field;
    }
    /**
     * @return array
     */
    public function __sleep() {
      unset($this->rowFunction);
      foreach ($this->columns as &$col) {
        if (isset($col['fun'])) {
          $col['fun'] = null;
        }
      }
      return array_keys((array) $this);
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     *       The return value is cast to an integer.
     */
    public function count() {
      return count($this->columns);
    }
  }

