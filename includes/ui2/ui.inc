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

	private function _openTag($type, $attr = array()) {

		$content = '<' . $type;
		foreach ($attr as $key => $value) {
			$content .= " $key='$value'";
		}
		return $content . '>';
	}

	private function _closeTag($type) {
		return '</' . $type . '>';
	}

	private function button($name, $caption, $id = null, $value = null, $type = 'button', $class = null, $attr = null) {
		if ($class == null) {
			$class = 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
		}
		$params = array(
			'name' => $name,
			'value' => $value,
			'type' => $type,
			'class' => $class
		);
		if ($id != null) {
			$params['id'] = $id;
		}
		if (is_array($attr)) {
			$params = array_merge($params, $attr);
		}
		$content = '<button ';
		foreach ($params as $key => $value) {
			$content .= "$key='$value' ";
		}
		$content .= ">$caption</button>";
		echo $content;
	}

	private function tdStart($id = null, $attr = null) {
		if (!is_array($attr)) {
			$attr = array();
		}
		if ($id != null) {
			$attr['id'] = $id;
		}
		$content = $this->_openTag('td', $attr);
		echo $content;
	}

	private function tdEnd() {
		echo '</td>';
	}

	private function search($id, $label, $size = 50) {
		echo "
	<div class='ui-widget'>
	<label for='$id'>$label</label>
	<input id='$id' size='$size' />
	</div>";
	}

	 static function __callStatic($name, $args = array()) {
		 if (self::$_instance===null)  self::$_instance = new self;
		  if (method_exists(self::$_instance, $name)) call_user_func_array(array(self::$_instance,$name),$args);
		else call_user_func_array(array(self::$_instance,'__call'),array($name,$args));
		 return self::$_instance;
	}
	public function __call($name, $args = array()) {
		$attr = (isset($args[1])) ? array_merge(array('id' => array_shift($args)), $args) : $args;
		if (count($args) == 0 || (isset($attr['id']) && $attr['id'] != 'end')) {
			echo $this->_openTag($name, $attr);
		}
		if (count($args) == 0 || (isset($attr['id']) && $attr['id'] != 'start')) {
			echo $this->_closeTag($name);
		}
		return $this;
	}
	private function divStart($id, $class = null, $attr = null) {
		$params['id'] = $id;
		if (is_array($class)) {
			$attr = $class;
		} elseif ($class != null) {
			$params['class'] = $class;
		}
		if (is_array($attr)) {
			$params = array_merge($params, $attr);
		}
		$content = '<div ';
		foreach ($params as $key => $value) {
			$content .= "$key='$value' ";
		}
		$content .= '>';
		echo $content;
	}

	private function divEnd() {
		echo '</div>';
	}

	private function select($id = false, $options = array(), $attr = array()) {
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

	private function p($text = '', $attr = array()) {
		static $open = false;
		if (!$open) {
			echo '</p>';
			$open = false;
			return;
		}
		$content = '<p ';
		if (is_array($attr)) {
			foreach ($attr as $key => $value) {
				$content .= " $key='$value'";
			}
		}
		$content .= '>';
		echo $content;
		$open = true;
	}
}
