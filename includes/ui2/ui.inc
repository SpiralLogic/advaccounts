<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 16/11/10
 * Time: 12:35 AM
 * To change this template use File | Settings | File Templates.
 */

class UI {


	private static $_instance = null;

	private function __construct() {
	}

	private function _openTag($type, $attr) {
		$content = '';
		foreach ($attr as $key => $value)
			$content .= " $key='$value'";
		echo '<' . $type . ' ' . $content . '>';
		return $this;
	}

	private function _closeTag($type) {
		echo '</' . $type . '>';
		return $this;
	}

	private function button($func = null, $attr) {
		$id = array_shift($attr);
		if (count($attr) > 0) {
			$content = $attr['content'] = array_shift($attr);
		}
		if (!empty($id)) {
			$attr['id'] = $id;
		}
		if (empty($attr['name'])) {
			$attr['name'] = $id;
		}
		$attr['class'] = (isset($attr['class']) && !empty($attr['class'])) ? : 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
		$this->_openTag('button', $attr);
		echo $content;
		$this->_closeTag('button');
		return $this;
	}

	public function __call($func, $args) {
		self::__callStatic($func, $args);
		return self::$_instance;
	}

	static function __callStatic($func, $args) {
		if (self::$_instance === null) {
			self::$_instance = new self;
		}
		self::$_instance->_Builder($func, $args);
		return self::$_instance;
	}

	private function _Builder($func, $args) {
		if ((end($args) !== true) && (end($args) !== false)) {
			array_push($args, null);
		}
		if ($content = '' || count($args) > 0) {
			$open = array_pop($args);
		}
		FB::info($open);FB::info('gomango');FB::info($open===false);
		$attr = array('id' => '');
		foreach ($args as $key => $val) {
			if ((($key == 0 && $key < 3 && !is_array($val)) || $key == 'id')) {
				$attr['id'] = $val;
			} elseif (isset($val) && !is_array($val) && ($open === null)) {
				$content = $attr['content'] = $val;
			} elseif ($key != 'id' && is_array($val)) {
				$attr = array_merge($attr, $val);
			}
		}
		if (method_exists(self::$_instance, $func)) {
			return self::$_instance->$func($func, $attr);
		}
		FB::info($open);
		if ($open === true) {
			$this->_openTag($func, $attr);
		} elseif
		($open === false) {
			FB::info('closing');
			$this->_closeTag($func);
		} elseif
		($open === null) {
			$this->_openTag($func, $attr) . $content . $this->_closeTag($func);
		}
		return $this;
	}

	private
	function select($id = false, $options = array(), $attr = array()) {
		if ($id !== false) {
			$params['id'] = $id;
		}
		$params = array_merge($params, $attr);
		$content = $this->_openTag("select", $params);
		foreach ($options as $option => $value) {
			$content .= "<option value='$value'>$option</option>";
		}
		$content .= "</select>";
		echo $content;
	}

	private
	function search($id, $label, $size = 50) {
		echo "<div class='ui-widget'>	<label for='$id'>$label</label>	<input id='$id' size='$size' />	</div>";
	}
}