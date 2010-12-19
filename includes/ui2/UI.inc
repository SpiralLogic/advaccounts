<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:47 PM
 * To change this template use File | Settings | File Templates.
 */
 
class UI extends HTML {
	protected static $_instance = null;
	protected function button($attr = array(), $content = '') {
				$id = (isset($attr['id'])) ? array_shift($attr) : null;
		if (count($attr) > 0)
			$content = $attr['content'] = array_shift($attr);
		if (!empty($id))
			$attr['id'] = $id;
		if (empty($attr['name']) && !empty($id))
			$attr['name'] = $id;
		$attr['class'] = (@isset($attr['class']) && @!empty($attr['class'])) ? $attr['class'] : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
		static::_openTag('button', $attr, $content);
		static::_closeTag('button');
	}
	static  function __callStatic($func, $args) {
		(static::$_instance !== null) ? : static::$_instance = new static;
		($func[0] == '_' || count($args) == 0) ? static::$_instance->_closeTag(substr($func, 1)) : static::$_instance->_Builder($func, $args);
		return static::$_instance;
	}
	function __call($func,$args) {
		return self::__callStatic($func,$args);
	}
	function __get($func) {
		return parent::__get($func);
	}
	/**
	 * @static
	 * @param array $attr
	 * @param string $content
	 * @return void
	 * @access public
	 */
	protected function select($attr = array(), $content = '') {
		$params['id'] = array_shift($attr);
		$options = array_shift($attr);
		$params = array_merge($params, $attr);
		foreach ($options as $option => $value)
			$content .= "<option value='$value'>$option</option>";
		static::_openTag("select", $params, $content);
		static::_closeTag("select");
	}
	protected function search($attr = array()) {
		$id = $attr['id'];
		$url = (!empty($attr['url'])) ? $attr['url'] : false ;
		static::_openTag('div', array('class' => 'ui-widget'));
		static::_openTag('label', array('for' => $id), $attr['label']);
		static::_closeTag('label');
		static::_openTag('input', array('id' => $id, 'size' => $attr['size']));
		static::_closeTag('div');
		$callback = strtoupper($id[0]) . strtolower(substr($id, 1));
		JS::autocomplete($id, $callback, $url);
	}
}