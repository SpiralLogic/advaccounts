<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:47 PM
 * To change this template use File | Settings | File Templates.
 */
class UI extends HTML {

    static function button($id = false, $content = false, $attr = array()) {
        if ($id) $attr['id'] = $id;
        if (empty($attr['name']) && ($id)) $attr['name'] = $id;
        if (!isset($attr['class'])) $attr['class'] = 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
        static::$_instance->_openTag('button',$attr, $content);
        static::$_instance->_closeTag('button');
        return static::$_instance;
    }

    static function select($id = false, $options = array(), $params = array()) {
        if ($id) $params['id'] = $id;
        $content = '';
        foreach ((array)$options as $option => $value) $content .= "<option value='$value'>$option</option>";
        static::$_instance->_openTag("select", $params, $content);
        static::$_instance->_closeTag("select");
        return static::$_instance;
    }

    static function search($id = false, $attr = array()) {
        if ($id) $attr['id'] = $id;
        $url = (!empty($attr['url'])) ? $attr['url'] : false;
        static::$_instance->_openTag('div', array('class' => 'ui-widget'));
        if ($id && isset($attr['label'])) static::$_instance->_openTag('label', array('for' => $id), $attr['label']);
        if ($id && isset($attr['size'])) static::$_instance->_openTag('input', array('id' => $id, 'size' => $attr['size']));
        static::$_instance->_closeTag('div');
        $callback = (isset($attr['callback'])) ? $attr['callback'] : strtoupper($id[0]) . strtolower(substr($id, 1));
        JS::autocomplete($id, $callback, $url);
        return static::$_instance;
    }
}