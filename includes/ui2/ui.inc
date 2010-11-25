<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 16/11/10
 * Time: 12:35 AM
 * To change this template use File | Settings | File Templates.
 */

class UI
{

	static protected function _openTag($type, $attr=array()) {

		$content = '<'. $type;
	    foreach ($attr as $key =>$value) {
		    $content .= " $key='$value'";
	    }
	    return $content.'>';
	}
	static function button($name, $caption, $id = null, $value = null, $type = 'button', $class = null, $attr = null)
	{
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
	static function tdStart($id=null,$attr=null) {
		if (!is_array($attr)) {
			$attr=array();
		}
		if ($id != null) {
			$attr['id']=$id;
		}
	    $content = self::_openTag('td',$attr);
	    echo $content;
	}
static function tdEnd() {
	echo '</td>';
}
	static function search($id, $label, $size = 50)
	{
		echo "
	<div class='ui-widget'>
	<label for='$id'>$label</label>
	<input id='$id' size='$size' />
	</div>";
	}

	static function divStart($id, $class = null, $attr = null)
	{
		$params['id'] = $id;
		if ($class != null) {
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

	static function divEnd()
	{
		echo '</div>';
	}
static function select($id=false,$options=array(),$attr=array()) {
	if ($id!==false) $params['id']=$id;
    $params = array_merge($params, $attr);
	$content = self::_openTag("select",$params);
    foreach($options as $option => $value) {
		$content.="<option value='$value'>$option</option>";
    }
    $content.= "</select>";
    echo $content;
}
	static function p($text='', $attr = array())
	{
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
		$content.='>';
		echo $content;
	    $open=true;
	}
}
