<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 16/11/10
 * Time: 12:35 AM
 * To change this template use File | Settings | File Templates.
 */
 
class UI {

	static function button($name,$caption,$id=null, $value = null,$type='button', $class =null,$opts=null) {
		if ($class==null) $class = 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
		$params = array(
			'name' => $name,
			'value'=>$value,
			'type'=>$type,
			'class'=>$class
		);
		if ($id!=null) $params['id']=$id;
		if (is_array($opts)) $params = array_merge($params,$opts);
		$content = '<button ';
		foreach ($params as $key => $value) {
			$content .= "$key='$value' ";
		}
		$content .= ">$caption</button>";
		echo $content;
	}




}
