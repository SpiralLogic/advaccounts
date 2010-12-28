<?php
/**
 *
 * @package HTML
 *@method string body() static body()
 *@method string td() static td()
 *@method string div()
 *@method string table()
 *@method string img()
 *@method string input()
 *@method string br()
 *@method string tr()
 *@method string input()
 *@method string a()
 *@method string p() static p()
 * @property string $tr
 * @property string $td
 * @property string $br
 * @property string $img
 * @property mixed $input
 * @property string $div
 * @property string $p
 *
 */






class HTML {
    protected static $_instance = null;

    protected function __construct() {
    }

    /**
     *
     *
     */
    function __call($func, $args) {
        static::__callStatic($func, $args);
        return static::$_instance;
    }

    function __get($func) {
        static::__callStatic($func);
        return static::$_instance;
    }

    static function __callStatic($func, $args=array()) {
        (static::$_instance !== null) ? : static::$_instance = new static;
        (count($args) == 0) ? static::$_instance->_closeTag(($func[0] == '_') ? substr($func, 1) : $func) : static::$_instance->_Builder($func, $args);
        return static::$_instance;
    }

    protected function _openTag($type, $attr = array(), $content = '') {
        $attrs = '';
        foreach ($attr as $key => $value) $attrs .= (empty($value)) ? : " $key='$value'";
        echo  '<' . $type . ' ' . $attrs . '>' . $content;
    }

    protected function _closeTag($type) {
        echo '</' . $type . '>';
    }

    protected function _Builder($func, $args, $attr = array(), $content = '') {
        $open = ((end($args) !== true) && (end($args) !== false)) ? true : array_pop($args);
        foreach ($args as $key => $val) {
            if (($key == 0 && $key < 3 && !is_array($val))) {
                $attr['id'] = $val;
            } elseif (!isset($attr['content']) && (!is_array($val) || count($args) > 2)) {
                $content = $attr['content'] = $val;
            } elseif (is_array($val)) {
                $attr = array_merge($attr, $val);
            }
        }
        if ($open === true) {
            $this->_openTag($func, $attr);
        } elseif ($open === false) {
            $this->_closeTag($func);
        } elseif ($open === null) {
            $this->_openTag($func, $attr, $content);
            $this->_closeTag($func);
        }
    }

}