<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:49 PM
 * To change this template use File | Settings | File Templates.
 */
class Dialog
{

    protected $height = "'auto'";
    protected $width = 300;
    protected $buttons = array();
    protected $autoopen = false;
    protected $modal = false;
    protected $name;
    protected $resizable = true;
    protected $contents = '';
    protected $events = array();
    protected $data = false;
    protected $_template = false;

    function __construct($title, $name = false, $contents = false)
    {
        $this->name = $name;
        $this->title = ($title) ? $title : "Message Box";
        if ($contents) {
            $this->setContents($contents);
        }
    }

    function setContents($contents)
    {
        if (empty($contents)) {
            return false;
        }
        if (is_array($contents)) {
            foreach ($contents as $content) {
                $this->contents .= $content . "<hr>";
            }
            ;
        } else {
            $this->contents = $contents;
        }
    }

    public function setTemplateData($data='')
    {
        $this->data = $data;
        $this->_template = true;
    }

    function addOpenEvent($selector, $type = 'click')
    {
        $this->events[] = array($selector, $type, "\$({$this->name}).dialog('open');");
    }

    function addButtons($buttons = array())
    {
        foreach ($buttons as $button => $action) {
            $this->addButton($button, $action);
        }
    }

    function addButton($button, $action)
    {
        $this->buttons[$button] = $action;
    }

    function setOptions($options = array())
    {
        if (is_array($options) && count($options) > 0) {
            foreach ($options as $option => $value) {
                if (property_exists($this, $option)) {
                    $this->$option = $value;
                }
            }
        }
    }

    function show()
    {
        if (count($this->buttons > 0)) {
            $buttons = 'buttons: [ ';
            foreach ($this->buttons as $button => $action) {
                $buttons .= '{ "text":"' . $button . '", click: function() { ' . $action . '}}, ';
            }
            $buttons .= '] ';
        }
        if ($this->_template) $this->contents = '<script id="'.$this->name.'-template" type="text/x-jquery-tmpl">'.$this->contents.'</script>';
        echo '<div id="' . $this->name . '" title="' . $this->title . '">' . $this->contents . '</div>';

        $js = '$' . $this->name . '=$("#' . $this->name . '").dialog({';
        $js .= 'autoOpen: ' . (($this->autoopen) ? 'true' : 'false') . ',';

        if ($this->modal) $js .= 'modal: true, ';
        if ($this->width) $js .= 'width: ' . $this->width . ', ';
        $js .= 'height: ' . $this->height . ', ';
        if (!$this->resizable) $js .= 'resizable: false, ';
        if (!empty($buttons)) $js .= $buttons;
        $js .= '});';
        if (!empty($js)) {
            JS::addEvents($this->events);
            JS::addLive($js);
            if ($this->_template) {
                JS::footerFile('/js/js2/jquery-tmpl.min.js');
                $js = '$("#' . $this->name . '-template").template("' . $this->name . '"); Adv.o.' . $this->name . ' = { render: function(data) { $.tmpl("' . $this->name . '",data).appendTo("#' . $this->name . '")} }; ';
                if (!empty($this->data)) $js.='Adv.o.' . $this->name . '.render(' . json_encode($this->data) . ');';
                JS::onload($js);
            }
        }
    }
}
