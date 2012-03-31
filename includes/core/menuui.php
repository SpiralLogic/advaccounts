<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace Core;
  class MenuUI extends Menu {

    /**
     * @var array
     */
    protected $options = array();
    /**
     * @var bool
     */
    public $firstPage = 0;
    /**
     * @var int
     */
    static public $menuCount = 0;
    /**
     * @param array $options
     */
    public function __construct($options = array()) {
      $this->options = $options;
      ob_start();
    }
    /**
     * @param        $title
     * @param string $tooltip
     * @param string $link
     *
     * @return MenuUI
     */
    protected function addTab($title, $tooltip = '', $link = '#') {
      $this->items[] = new MenuUi_item($title, $tooltip, $link);
      return $this;
    }
    /**
     * @param              $title
     * @param string       $tooltip
     * @param string       $link
     * @param   string     $param_element element id to get extra paramater from
     * @param null         $target
     *
     * @return MenuUI
     */
    public function addLink($title, $tooltip = '', $link, $param_element, $target = NULL) {
      $this->items[] = new MenuUi_item($title, $tooltip, $link, $param_element, $target);
      $this->options['hasLinks'] = TRUE;
      return $this;
    }
    /**
     * @param        $title
     * @param string $tooltip
     * @param        $name
     * @param        $onselect
     *
     * @return MenuUI
     */
    public function addJSLink($title, $tooltip = '', $name, $onselect) {
      $this->items[] = new MenuUi_item($title, $tooltip, '#' . $name);
      $this->options['hasLinks'] = TRUE;
      JS::onload($onselect);
      return $this;
    }
    /**
     * @param        $title
     * @param        $tooltip
     * @param string $link
     * @param string $style
     *
     * @return MenuUI
     */
    public function startTab($title, $tooltip, $link = '#', $style = '') {
      $count = count($this->items);
      $this->addTab($title, $tooltip, $link);
      echo '<div id="tabs' . MenuUI::$menuCount . '-' . $count . '" ';
      if ($count > 0 || $this->firstPage != $count) {
        echo 'class="ui-tabs-hide"';
      }
      if (!empty($style)) {
        echo ' style="' . $style . '" ';
      }
      echo '>';
      return $this;
    }
    /**
     * @return MenuUI
     */
    public function endTab() {
      echo '</div>';
      return $this;
    }
    /**

     */
    public function render() {
      $menu = "<div class='width90 center ui-tabs ui-widget ui-widget-content ui-corner-all tabs' id='tabs" . MenuUI::$menuCount . "'><ul class='ui-tabs-nav
			ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>";
      foreach ($this->items as $key => $item) {
        $num = $key;
        $menu .= "<li class='ui-state-default ui-corner-top'><a title='{$item->label}'";
        if ($item->link != "#") {
          $menu .= ' href="' . $item->link . '" ';
          $menu .= ($item->param_element) ? ' data-paramel="' . $item->param_element . '" ' : '';
          $menu .= ($item->target) ? ' data-target="' . $item->target . '" ' : '';
        }
        else {
          $menu .= " href='#tabs" . MenuUI::$menuCount . "-{$num}'";
        }
        $menu .= "><span>{$item->label}</span></a></li>";
      }
      $menu .= "</ul>";
      $content = ob_get_clean();
      echo $menu . $content . '</div>';
      JS::tabs('tabs' . MenuUI::$menuCount, $this->options, $this->firstPage);
      MenuUI::$menuCount++;
    }
  }

  /**

   */
  class MenuUi_item extends menu_item {

    /**
     * @var string
     */
    public $tooltip = '';
    public $param_element = '';
    public $target = '';
    /**
     * @param        $label
     * @param string $tooltip
     * @param string $link
     */
    public function __construct($label, $tooltip = '', $link = '#', $param_element = NULL, $target = NULL) {
      $this->label = $label;
      $this->link = $link;
      $this->tooltip = $tooltip;
      $this->param_element = $param_element;
      $this->target = $target;
    }
  }
