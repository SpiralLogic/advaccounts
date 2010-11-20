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

	static function button($name, $caption, $id = null, $value = null, $type = 'button', $class = null, $opts = null)
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
		if (is_array($opts)) {
			$params = array_merge($params, $opts);
		}
		$content = '<button ';
		foreach ($params as $key => $value) {
			$content .= "$key='$value' ";
		}
		$content .= ">$caption</button>";
		echo $content;
	}

	static function search($id, $label, $size = 50)
	{
		echo "
	<div class='ui-widget'>
	<label for='$id'>$label</label>
	<input id='$id' size='$size' />
	</div>";
	}

	static function divStart($id, $class = null, $opts = null)
	{
		$params['id'] = $id;
		if ($class != null) {
			$params['class'] = $class;
		}
		if (is_array($opts)) {
			$params = array_merge($params, $opts);
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

	static function p($text, $params = array())
	{
		static $open = false;
		if (!$open) {
		echo '</p>';
		$open = false;
		return;
		}
		$content = '<p ';
		if (is_array($params)) {
			foreach ($params as $key => $value) {
				$content .= " $key='$value'";
			}
		}
		$content.='>';
		echo $content;
	    $open=true;
	}
}
