<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:47 PM
 * To change this template use File | Settings | File Templates.
 */
HTML::load();

class UI extends HTML
{
    static function button($id = false, $content = false, $attr = array())
    {
        if ($id)
            $attr['id'] = $id;
        if (empty($attr['name']) && ($id))
            $attr['name'] = $id;
        if (!isset($attr['class']))
            $attr['class'] = 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';

        HTML::button($id,$content,$attr,false);
        return static::$_instance;
    }

    static function select($id = false, $options = array(), $params = array())
    {
        $content = '';
        HTML::setReturn(true)->select($id,$params);
        foreach ((array)$options as  $label  => $option) {
            if (is_array($option)) { 
                $optContent = '';
                foreach ($option as $data) {
                    $optContent .= HTML::option(null,$data[0] .'('.$data[1].')', array('value'=>$data[1]),false);
                }
                HTML::optgroup(array('label' => $label,'content'=> $optContent),false);
            }
            else {
                HTML::option(array('value'=>$label),false);
            }
        }

        echo HTML::_select()->setReturn(false);
        return static::$_instance;
    }

    static function search($id = false, $attr = array(), $options = array())
    {
        if ($id)
            $attr['id'] = $id;
        $url = (!empty($attr['url'])) ? $attr['url'] : false;
        if (!isset($attr['nodiv'])) HTML::div(array('class' => 'ui-widget'));
        if ($id && isset($attr['label']))
            HTML::label(array('for' => $id), $attr['label'],false);
        $input_attr = array();
        if ($id && isset($attr['size'])) $input_attr['size'] = $attr['size'];
        if ($id && isset($attr['name'])) $input_attr['name'] = $attr['name'];

        HTML::input($id,$input_attr);
        if (!isset($attr['nodiv'])) HTML::div();
        $callback = (isset($attr['callback'])) ? $attr['callback'] : strtoupper($id[0]) . strtolower(substr($id, 1));
        JS::autocomplete($id, $callback, $url, $options);
        return static::$_instance;
    }
}