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
        static::$_instance->_openTag('button', $attr, $content);
        static::$_instance->_closeTag('button');
        return static::$_instance;
    }

    static function select($id = false, $options = array(), $params = array())
    {
        if ($id)
            $params['id'] = $id;
        $content = '';
        static::$_instance->_openTag("select", $params);
        foreach ((array)$options as $option => $value) {
            if (is_array($value)) {
                $optContent = '';
                foreach ($value as $data) {
                    $optContent .= "<option value='{$data[1]}'>{$data[0]} ({$data[1]})</option>";
                }
                static::$_instance->_openTag("optgroup", array('label' => $option), $optContent);
                static::$_instance->_closeTag("optgroup");
            }
            else {
                $content .= "<option value='$value'>$option</option>";
            }
        }
        echo $content;
        static::$_instance->_closeTag("select");
        return static::$_instance;
    }

    static function search($id = false, $attr = array(), $options = array())
    {
        if ($id)
            $attr['id'] = $id;
        $url = (!empty($attr['url'])) ? $attr['url'] : false;
        if (!isset($attr['nodiv'])) static::$_instance->_openTag('div', array('class' => 'ui-widget'));
        if ($id && isset($attr['label']))
            static::$_instance->_openTag('label', array('for' => $id), $attr['label']);
        $input_attr = array();
        if ($id && isset($attr['size'])) $input_attr['size'] = $attr['size'];
        if ($id && isset($attr['name'])) $input_attr['name'] = $attr['name'];

        static::$_instance->_openTag('input', array_merge($input_attr, array('id' => $id)));
        if (!isset($attr['nodiv'])) static::$_instance->_closeTag('div');
        $callback = (isset($attr['callback'])) ? $attr['callback'] : strtoupper($id[0]) . strtolower(substr($id, 1));
        JS::autocomplete($id, $callback, $url, $options);
        return static::$_instance;
    }
}