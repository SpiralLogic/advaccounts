<?php
  use ADV\Core\Cell;
  use ADV\App\Dates;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\User;
  use ADV\Core\JS;
  use ADV\Core\Ajax;
  use ADV\Core\DB\DB;
  use ADV\Core\Input\Input;
  use ADV\Core\Row;
  use ADV\Core\Table;

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
  class DB_Pager
  {
    /** @var \ADV\App\User */
    static $User;
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /** @var Input */
    static $Input;
    /** @var JS */
    static $JS;
    /** @var Dates */
    static $dates;
    const SQL = 1;
    const ARR = 2;
    /** @var */
    public $sql;
    /**
     * @var
     */
    public $name;
    /**
     * @var
     * column definitions (head, type, order)
     */
    public $columns;
    /**
     * @var
     * marker check function
     */
    public $marker;
    /**
     * @var
     */
    public $marker_txt;
    /**
     * @var
     */
    public $marker_class;
    /**
     * @var
     */
    public $notice_class;
    /**
     * @var string
     * table width (default '95%')
     */
    public $width = "80%";
    /**
     * @var
     */
    public $footer_class;
    /**
     * @var array
     */
    public $data = [];
    /**
     * @var
     */
    public $curr_page = 1;
    /**
     * @var
     */
    public $max_page = 1;
    /**
     * @var
     */
    public $last_page;
    /**
     * @var
     */
    public $prev_page;
    /**
     * @var
     */
    public $next_page;
    /**
     * @var
     */
    public $first_page;
    /**
     * @var int|?
     */
    protected $page_length = 1;
    /**
     * @var
     */
    public $rec_count = 0;
    /**
     * @var
     */
    public $select;
    /**
     * @var
     */
    public $where;
    /**
     * @var
     */
    public $from;
    /**
     * @var
     */
    public $group;
    /**
     * @var
     */
    public $order;
    /**
     * @var
     */
    public $extra_where;
    /**
     * @var bool
     */
    public $ready;
    /**
     * @var bool
     * this var is false after change in sql before first

    and before first query.
     */
    public $inactive_ctrl = false;
    public $showInactive = null;
    /**
     * @var
     */
    public $main_tbl;
    /**
     * @var
     * table and key field name for inactive ctrl and edit/delete links
     * key field name
     * DB_Pager constructor
     * accepts $sql like 'SELECT ...[FROM ...][WHERE ...][GROUP ...][ORDER ...]'
     * $name is base name for pager controls
     */
    public $key;
    public $type;
    public $id;
    public $rowFunction;
    public $class;
    protected $hasBar = false;
    /**
     * @param      $sql
     * @param      $name
     * @param null $table
     * @param int  $page_length
     */
    public function __construct($sql, $name, $table = null, $page_length = 0)
    {
      $this->width;
      if ($page_length == 0) {
        $page_length = static::$User->_query_size();
      }
      $this->name        = $name;
      $this->page_length = $page_length;
      $this->setSQL($sql);
    }
    /**
     * @return array
     */
    public function __sleep()
    {
      foreach ($this->columns as &$column) {
        if (isset($column['fun'])) {
          unset($column['fun']);
        }
      }
      unset($this->marker);
      unset($this->rowFunction);

      return array_keys((array) $this);
    }
    /**
     * @static
     * @internal param \DB_Pager $pager
     * @return bool
     */
    public function display()
    {
      $this->select_records();
      Display::div_start("_{$this->name}_span");
      $headers = $this->makeHeaders();
      $class   = $this->class ? : 'tablestyle grid width' . rtrim($this->width, '%');
      Table::start($class);
      if (isset($this->marker_txt)) {
        Event::warning($this->marker_txt, 0, 1, "class='$this->notice_class'");
      }
      $this->displayHeaders($headers);
      foreach ($this->data as $row) {
        $this->displayRow($row);
      }
      $this->displayNavigation();
      Table::end();
      Display::div_end();

      return true;
    }
    /**
     * @return bool
     * Initialization after changing record set
     */
    protected function init()
    {
      if ($this->ready == false) {
        if ($this->type == self::SQL) {
          $sql    = $this->_sql_gen(true);
          $result = static::$DB->_query($sql, 'Error reading record set');
          if ($result == false) {
            return false;
          }
          $row             = static::$DB->_fetchRow($result);
          $this->rec_count = $row[0];
          $this->max_page  = $this->page_length ? ceil($this->rec_count / $this->page_length) : 0;
          $this->setPage(1);
        } elseif ($this->type == self::ARR) {
          $this->rec_count  = count($this->sql);
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
     * @static
     *
     * @param      $name
     * @param      $value
     * @param bool $enabled
     * @param null $title
     *
     * @internal param bool $icon
     * @return string
     */
    protected function navi($name, $value, $enabled = true, $title = null)
    {
      $id           = $this->hasBar ? " id='$name' " : '';
      $title        = $title ? : $value;
      $this->hasBar = true;

      return "<button " . ($enabled ? '' : 'disabled') . " class=\"navibutton\" type=\"submit\"  name=\"$name\"  $id value=\"$value\"><span>$title</span></button>\n";
    }
    /**
     * @return array
     */
    protected function makeHeaders()
    {
      $headers  = [];
      $inactive = !static::$Input->post('show_inactive');
      foreach ($this->columns as $num_col => $col) {
        // record status control column is displayed only when control checkbox is on
        if (isset($col['head']) && ($col['type'] != 'inactive' || $inactive)) {
          if ($col['type'] == 'active' && $this->showInactive === false) {
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
          $headers[] = $this->navi($this->name . '_sort_' . $num_col, $col['head'], true, $col['head'] . $icon);
        }
      }

      return $headers;
    }
    /**
     * @param $headers
     */
    protected function displayHeaders($headers)
    {
      Table::header($headers, '', $this->displayNavigation(true));
    }
    /**
     * @param bool $return
     *
     * @return string
     */
    protected function displayNavigation($return = false)
    {
      if ($return) {
        ob_start();
      }
      Row::start("class='navibar'");
      $colspan = count($this->columns);
      $inact   = '';
      if ($this->showInactive !== null) {
        $inact = $this->formatInactiveFooter();
      } elseif ($this->inactive_ctrl == true) {
        $inact = Forms::checkbox(null, 'show_inactive', null, true);
      }
      if ($inact) {
        $inact .= _("Show also Inactive");
      }
      echo HTML::td(null, ['colspan'=> $colspan, 'class'=> 'navibar']);
      if ($this->rec_count) {
        $button_prefix = $this->name . '_page_';
        if ($this->inactive_ctrl) {
          Forms::submit('Update', _('Update'), true, '', null);
        } // inactive update
        echo HTML::span(
          null,
          $this->navi($button_prefix . 'first', 1, $this->first_page, "<i class='icon-fast-backward'> </i>") . $this->navi(
            $button_prefix . 'prev',
            $this->curr_page - 1,
            $this->prev_page,
            '<i class="icon-backward"> </i>'
          ) . $this->navi($button_prefix . 'next', $this->curr_page + 1, $this->next_page, '<i class="icon-forward"> </i>') . $this->navi(
            $button_prefix . 'last',
            $this->max_page,
            $this->last_page,
            '<i class="icon-fast-forward"> </i>'
          ),
          ['class'=> 'floatright'],
          false
        );
        $from = ($this->curr_page - 1) * $this->page_length + 1;
        $to   = $from + $this->page_length - 1;
        if ($to > $this->rec_count) {
          $to = $this->rec_count;
        }
        $all = $this->rec_count;
        echo HTML::span(true, "Records $from-$to of $all", [], false);
        echo $inact;
      } else {
        echo HTML::span(null, _('No records') . $inact, [], false);
      }
      echo HTML::_td();
      Row::end();
      if ($return) {
        return ob_get_clean();
      }

      return true;
    }
    /**
     * @param $row
     *
     * @return mixed
     */
    protected function displayRow($row)
    {
      if ($this->marker && is_callable($this->marker) && call_user_func($this->marker, $row)) {
        Row::start("class='$this->marker_class'");
      } elseif (is_callable($this->rowFunction)) {
        echo call_user_func($this->rowFunction, $row);
      } else {
        echo "<tr>\n";
      }
      foreach ($this->columns as $col) {
        $coltype = isset($col['type']) ? $col['type'] : '';
        $cell    = isset($col['name']) ? $row[$col['name']] : '';
        if (isset($col['fun'])) { // use data input function if defined
          $fun = $col['fun'];
          if (is_callable($fun)) {
            $cell = call_user_func($fun, $row, $cell, $this);
          } elseif (is_callable([$this, $fun])) {
            $cell = $this->$fun($row, $cell);
          } else {
            $cell = '';
          }
        }
        $class = isset($col['class']) ? $col['class'] : null;
        switch ($coltype) { // format column
          case 'bool':
            Cell::label(($cell ? 'Yes' : 'No'), " class='$class width40'");
            break;
          case 'time':
            Cell::label($cell, " class='$class width40'");
            break;
          case 'date':
            Cell::label(static::$dates->sqlToDate($cell), " class='$class center nowrap'");
            break;
          case 'dstamp': // time stamp displayed as date
            Cell::label(static::$dates->sqlToDate(substr($cell, 0, 10)), " class='$class center nowrap'");
            break;
          case 'tstamp': // time stamp - FIX user format
            Cell::label(static::$dates->sqlToDate(substr($cell, 0, 10)) . ' ' . substr($cell, 10), "class='$class center'");
            break;
          case 'percent':
            Cell::percent($cell * 100);
            break;
          case 'amount':
            ($cell === '') ? Cell::label('') : Cell::amount($cell, false);
            break;
          case 'qty':
            ($cell == '') ? Cell::label('') : Cell::qty($cell, false, isset($col['dec']) ? $col['dec'] : null);
            break;
          case 'email':
            Cell::email($cell, isset($col['align']) ? "class='$class " . $col['align'] . "'" : null);
            break;
          case 'rate':
            Cell::label(Num::_format($cell, static::$User->_exrate_dec()), "class='$class center'");
            break;
          case 'inactive':
            if (static::$Input->post('show_inactive')) {
              $this->inactive_control_cell($row);
            }
            break;
          case 'active':
            $this->formatInactive($row);
            break;
          case 'id':
            if (isset($col['align'])) {
              Cell::label($cell, " class='$class " . $col['align'] . " pagerclick' data-id='" . $row['id'] . "'");
            } else {
              Cell::label($cell, " class='$class pagerclick' data-id='" . $row['id'] . "'");
            }
            break;
          default:
            $alignclass = isset($col['align']) ? " class='$class align" . $col['align'] . "'" : ($class ? "class='$class'" : "");
            Cell::label($cell, $alignclass);
            break;
          case 'skip': // column not displayed
        }
      }
      Row::end();

      return $row;
    }
    /**
     * @return string
     */
    protected function formatInactiveFooter()
    {
      $checked = ($this->showInactive) ? 'checked' : '';
      $field   = '<input ' . $checked . ' type="checkbox" name="_action" value="showInactive" onclick="JsHttpRequest.request(this)">';
      Ajax::_activate("_{$this->name}_span");

      return $field;
    }
    /**
     * @param null $page
     *
     * @return bool
     * Set query result page

     */
    protected function change_page($page = null)
    {
      $this->setPage($page);
      $this->query();

      return true;
    }
    /**
     * @param $row
     *
     * @return string
     * Helper for display inactive control cells

     */
    protected function  inactive_control_cell(&$row)
    {
      if ($this->inactive_ctrl) {
        //	return inactive_control_cell($row[$this->inactive_ctrl['key']],
        // $row['inactive'], $this->inactive_ctrl['table'],
        // $this->inactive_ctrl['key']);
        $key   = $this->key ? $this->key : $this->columns[0]['name']; // TODO - support for complex keys
        $id    = $row[$key];
        $table = $this->main_tbl;
        $name  = "Inactive" . $id;
        $value = $row['inactive'] ? 1 : 0;
        if (static::$Input->post('show_inactive')) {
          if (isset($_POST['LInact'][$id]) && (static::$Input->post('_Inactive' . $id . '_update') || static::$Input->post('Update')) && (static::$Input->hasPost(
            'Inactive' . $id
          ) != $value)
          ) {
            static::$DB->_updateRecordStatus($id, !$value, $table, $key);
            $value = !$value;
          }
          echo '<td class="center">' . Forms::checkbox(null, $name, $value, true, '', "class='center'") . Forms::hidden("LInact[$id]", $value, false) . '</td>';
        }
      } else {
        echo '';
      }
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Field
     */
    protected function formatInactive($row)
    {
      $field = '';
      if ($this->showInactive === true) {
        $checked = $row['inactive'] ? 'checked' : '';
        $field   = '<td class="center"><input ' . $checked . ' type="checkbox" name="_action" value="' . INACTIVE . $row['id'] . '" onclick="JsHttpRequest.request(this)"></td>';
      }
      echo $field;
    }
    /**
     * @return bool
     * Query database

     */
    protected function query()
    {
      Ajax::_activate("_{$this->name}_span");
      if (!$this->init()) {
        return false;
      }
      if ($this->type == self::SQL) {
        $this->data = [];
        if ($this->rec_count == 0) {
          return true;
        }
        $sql    = $this->_sql_gen(false);
        $result = static::$DB->_query($sql, 'Error browsing database: ' . $sql);
        if (!$result) {
          return false;
        }
        // setting field names for subsequent queries
        // add result field names to column defs for
        // col value retrieve and sort purposes
        while ($row = static::$DB->_fetchAssoc($result)) {
          $this->data[] = $row;
        }
      } elseif ($this->type == self::ARR) {
        $offset     = ($this->curr_page - 1) * $this->page_length;
        $this->data = array_slice($this->sql, $offset, $this->page_length);
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
    protected function select_records()
    {
      $page = Forms::findPostPrefix($this->name . '_page_', false);
      $sort = Forms::findPostPrefix($this->name . '_sort_', true);
      if ($page) {
        $this->change_page($_POST[$this->name . '_page_' . $page]);
        if ($page == 'next' && !$this->next_page || $page == 'last' && !$this->last_page
        ) {
          static::$JS->setFocus($this->name . '_page_prev');
        }
        if ($page == 'prev' && !$this->prev_page || $page == 'first' && !$this->first_page
        ) {
          static::$JS->setFocus($this->name . '_page_next');
        }
      } elseif ($sort != -1) {
        $this->sortTable($sort);
      } else {
        $this->query();
      }
    }
    /**
     * @param $to
     * Calculates page numbers for html controls.

     */
    protected function setPage($to)
    {
      switch ($to) {
        case 'next':
          $page = $this->curr_page + 1;
          break;
        case 'prev':
          $page = $this->curr_page - 1;
          break;
        case 'last':
          $page = $this->last_page;
          break;
        default:
          if (is_numeric($to)) {
            $page = $to;
            break;
          }
        case 'first':
          $page = 1;
          break;
      }
      if ($page < 1) {
        $page = 1;
      }
      $max = $this->max_page;
      if ($page > $max) {
        $page = $max;
      }
      $this->curr_page  = $page;
      $this->next_page  = ($page < $max) ? $page + 1 : null;
      $this->prev_page  = ($page > 1) ? ($page - 1) : null;
      $this->last_page  = ($page < $max) ? $max : null;
      $this->first_page = ($page != 1) ? 1 : null;
    }
    /**
     * Set column definitions
     * types: inactive|skip|insert
     *
     * @param array  $flds array( fldname1, fldname2=>type,...)
     */
    protected function setColumns($flds)
    {
      $this->columns = [];
      if (!is_array($flds)) {
        $flds = array($flds);
      }
      foreach ($flds as $colnum => $coldef) {
        if (is_string($colnum)) { // 'colname'=>params
          $h = $colnum;
          $c = $coldef;
        } else { // n=>params
          if (is_array($coldef)) {
            $h = '';
            $c = $coldef;
          } else {
            $h = $coldef;
            $c = 'text';
          }
        }
        if (is_string($c)) // params is simple column type
        {
          $c = array('type' => $c);
        }
        if (!isset($c['type'])) {
          $c['type'] = 'text';
        }
        switch ($c['type']) {
          case 'inactive':
            $this->inactive_ctrl = true;
            $c['head']           = $h;
            break;
          case 'active':
            if ($this->showInactive === null) {
              $this->showInactive = false;
            }
            $c['head'] = $h;
            break;
          case 'insert':
          default:
            $c['head'] = $h;
            break;
          case 'skip': // skip the column (no header)
            unset($c['head']);
            break;
        }
        if (isset($coldef['fun'])) {
          $c['funkey'] = $colnum;
        }
        $this->columns[] = $c;
      }
    }
    /***
     * @param        $func
     * @param string $notice
     * @param string $markercl
     * @param string $msgclass
     * Set check function to mark some rows.
     */
    public function setMarker($func, $notice = '', $markercl = 'overduebg', $msgclass = 'overduefg')
    {
      $this->marker       = $func;
      $this->marker_txt   = $notice;
      $this->marker_class = $markercl;
      $this->notice_class = $msgclass;
    }
    /**
     * @param $sql
     * Parse base sql select query.
     */
    protected function setSQL($sql)
    {
      if (is_array($sql)) {
        $this->type = self::ARR;
        $this->sql  = $sql;

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
        $parts = preg_split('/\sGROUP\s*BY\s/si', $sql, 2);
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
    protected function setWhere($where = null)
    {
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
     * @param $col
     *
     * @return bool
     * Change sort column direction
     * in order asc->desc->none->asc
     */
    protected function sortTable($col)
    {
      if ($this->type == self::ARR) {
        $this->query();

        return true;
      }
      if (is_null($col)) {
        return false;
      }
      $ord                        = (!isset($this->columns[$col]['ord'])) ? '' : $this->columns[$col]['ord'];
      $ord                        = ($ord == '') ? 'asc' : (($ord == 'asc') ? 'desc' : '');
      $this->columns[$col]['ord'] = $ord;
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
    protected function _sql_gen($count = false)
    {
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
      foreach ($this->columns as $col) {
        if (isset($col['ord'])) {
          if ($col['ord'] != '' && isset($col['name'])) {
            $ord[] = $col['name'] . ' ' . $col['ord'];
          }
        }
      }
      if (count($ord)) {
        $sql .= " ORDER BY " . implode($ord, ',');
      } else {
        if ($order) {
          $sql .= " ORDER BY $order";
        } // original base query order
      }
      $page_length = $this->page_length;
      $offset      = ($this->curr_page - 1) * $page_length;
      $sql .= " LIMIT $offset, $page_length";

      return $sql;
    }
    /**
     * @param $name
     */
    public static function kill($name)
    {
      unset($_SESSION['pager'][$name]);
    }
    /**
     * @static
     *
     * @param      $link_text
     * @param      $url
     * @param bool $icon
     *
     * @return string
     */
    public static function link($link_text, $url, $icon = false)
    {
      if (static::$User->_graphic_links() && $icon) {
        $link_text = Forms::setIcon($icon, $link_text);
      }
      $href = '/' . ltrim($url, '/');
      $href = (static::$Input->request('frame')) ? "javascript:window.parent.location='$href'" : $href;

      return '<a href="' . e($href) . '" class="button">' . $link_text . "</a>";
    }
    /**
     * @static
     *
     * @param      $name
     * @param      $sql
     * @param      $coldef
     * @param null $table
     * @param null $key
     * @param int  $page_length
     * @param null $sort
     *
     * @return DB_Pager
     */
    public static function new_db_pager($name, $sql, $coldef, $table = null, $key = null, $page_length = 0, $sort = null)
    {
      if (!isset($_SESSION['pager'])) {
        $_SESSION['pager'] = [];
      }
      if (isset($_SESSION['pager'][$name])) {
        $pager = $_SESSION['pager'][$name];
        if (is_array($sql)) {
          $pager->sql       = $sql;
          $pager->rec_count = count($pager->sql);
          $pager->max_page  = $pager->page_length ? ceil($pager->rec_count / $pager->page_length) : 0;
        } elseif ($pager->sql != $sql) {
          unset($_SESSION['pager'][$name]); // kill pager if sql has changed
        }
      }
      if (!isset($_SESSION['pager'][$name])) {
        $pager           = new static($sql, $name, $table, $page_length);
        $pager->main_tbl = $table;
        $pager->key      = $key;
        $pager->setSQL($sql);
        $pager->setColumns($coldef);
        $pager->sortTable($sort);
        $_SESSION['pager'][$name] = $pager;
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
  }

  DB_Pager::$Input = Input::i();
  DB_Pager::$JS    = JS::i();
  DB_Pager::$dates = Dates::i();
  DB_Pager::$User  = User::i();
  DB_Pager::$DB    = DB::i();
