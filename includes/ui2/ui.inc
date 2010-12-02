<?php
/**
 *
 *@package UI
 *@method string body() static body()
 *@method string td() static td()
 *@method string button()
 *@method string div()
 *@method string table()
 *@method string img()
 *@method string input()
 *@method string br()
 *@method string tr()
 *@method string input()
 *@method string a()
 *@method string p()
 *@property string $tr
 *@property string $td
 *@property string $br
 *@property string $img
 *@property mixed $input
 *@property string $div
 *
 */
class UI {
	private static $_instance = null;
	private function __construct() {
	}
	/**
	 *
	 *
	 */
	 function __call($func, $args) {
		self::__callStatic($func, $args);
		return self::$_instance;
	}
	 function __get($func) {
		self::$_instance->_closeTag($func);
		return self::$_instance;
	}
	static function __callStatic($func, $args) {

		(self::$_instance !== null)?:self::$_instance = new self;
		($func[0] == '_' || count($args)==0) ? self::$_instance->_closeTag(substr($func,1)): self::$_instance->_Builder($func, $args);
		return self::$_instance;
	}

	private function _openTag($type, $attr = array(), $content = '') {
		$attrs = '';
		foreach ($attr as $key => $value)
			$attrs .= (empty($value)) ? : " $key='$value'";
		echo  '<' . $type . ' ' . $attrs . '>' . $content;
	}
	private function _closeTag($type) {
		echo '</' . $type . '>';
	}
	private function _Builder($func, $args, $attr = array(), $content='') {
		$open = ((end($args) !== true) || (end($args) !== false))? true: array_pop($args);
		foreach ($args as $key => $val) {
			if ($key == 'id' || ($key == 0 && $key < 3 && !is_array($val))) {
				$attr['id'] = $val;
			} elseif ( !isset($attr['content']) && (!is_array($val) || count($args)>2)) {
				$content = $attr['content'] = $val;
			} elseif (is_array($val)) {
				$attr = array_merge($attr, $val);
			}
		}
		if (method_exists(self::$_instance, $func))
			return self::$_instance->$func( $attr);
		if ($open === true) {
			$this->_openTag($func, $attr);
		} elseif ($open === false) {
			$this->_closeTag($func);
		} elseif ($open === null) {
			$this->_openTag($func, $attr,$content);
			$this->_closeTag($func);
		}
	}
	private function button($attr = array(), $content = '') {
		$id = (isset($attr['id'])) ? array_shift($attr) : null;
		if (count($attr) > 0)
			$content = $attr['content'] = array_shift($attr);
		if (!empty($id))
			$attr['id'] = $id;
		if (empty($attr['name']) && !empty($id))
			$attr['name'] = $id;
		$attr['class'] = (@isset($attr['class']) && @empty($attr['class'])) ? $attr['class'] : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
		$this->_openTag('button', $attr, $content);
		$this->_closeTag('button');
	}
	/**
	 * @static
	 * @param array $attr
	 * @param string $content
	 * @return void
	 * @access public
	 */
	private	function select($attr = array(),$content=''   ) {
		$params['id'] = array_shift($attr);
		$options = array_shift($attr);
		$params = array_merge($params, $attr);
		foreach ($options as $option => $value)
			$content .= "<option value='$value'>$option</option>";
		$this->_openTag("select", $params,$content);
		$this->_closeTag("select");
	}
	private	function search($attr = array()) {
		$id = $attr['id'];
		$this->_openTag('div', array('class'=>'ui-widget'));
		$this->_openTag('label', array('for'=>$id),$attr['label']);
		$this->_closeTag('label');
		$this->_openTag('input',array('id'=>$id, 'size'=>$attr['size']));
		$this->_closeTag('div');
	}

}