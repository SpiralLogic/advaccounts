<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class UI extends HTML {

    /**
     * @static
     *
     * @param bool  $id
     * @param bool  $content
     * @param array $attr
     *
     * @return ADV\Core\HTML|null
     */
    public static function button($id = false, $content = false, $attr = array())
    {
      if ($id) {
        $attr['id'] = $id;
      }
      if (empty($attr['name']) && ($id)) {
        $attr['name'] = $id;
      }
      if (!isset($attr['class'])) {
        $attr['class'] = 'button';
      }
      HTML::button($id, $content, $attr, false);

      return static::$_instance;
    }
    /**
     * @static
     *
     * @param bool  $id
     * @param array $options
     * @param array $params
     *
     * @return ADV\Core\HTML|null
     */
    public static function select($id = false, $options = array(), $params = array())
    {
      HTML::setReturn(true)->select($id, $params);
      foreach ((array) $options as $label => $option) {
        if (is_array($option)) {
          HTML::optgroup(array('label' => $label));
          foreach ($option as $data) {
            HTML::option(null, $data[0] . ' (' . $data[1] . ')', array('value' => $data[1]), false);
          }
          HTML::optgroup();
        } else {
          HTML::option(null, $option, array('value' => $label), false);
        }
      }
      echo HTML::_select()->setReturn(false);

      return static::$_instance;
    }
    /***
     * @static
     *
     * @param       $id
     * @param array $attr    includes (url,label,size,name,set,focus, nodiv, callback, options
     * @param array $options
     *
     * @return HTML|null
     * url: url to get search results from<br>
     * label: if set becomes the text of a &lt;label&gt; element for the input<br>
     * size: size of the input<br>
     * focus: whether to start with focus<br>
     * nodiv: if true then a div is not included<br>
     * callback: name of the javascript function to be the callback for the results, refaults to the same name as the id with camel case<br>
     * options: Javascript function autocomplete options<br>

     */
    public static function search($id, $attr = array(), $options = array())
    {
      $o   = array(
        'url'      => false, //
        'nodiv'    => false, //
        'label'    => false, //
        'size'     => 30, //
        'name'     => false, //
        'set'      => false, //
        'value'    => false, //
        'focus'    => false, //
        'callback' => false //
      );
      $o   = array_merge($o, $attr);
      $url = ($o['url']) ? $o['url'] : false;
      if (!$o['nodiv']) {
        HTML::div(array('class' => 'ui-widget'));
      }
      if (($o['label'])) {
        HTML::label(null, $o['label'], array('for' => $id), false);
      }
      $input_attr['size'] = $o['size'];
      if (($o['name'])) {
        $input_attr['name'] = $o['name'];
      }
      if (($o['set'])) {
        $input_attr['data-set'] = $o['set'];
      }
      if (($o['value'])) {
        $input_attr['value'] = htmlentities($o['value']);
      }
      if ($o['focus']) {
        $input_attr['autofocus'] = true;
      }
      $input_attr['type'] = 'search';
      HTML::input($id, $input_attr);
      if (!($o['nodiv'])) {
        HTML::div();
      }
      $callback = (($o['callback'])) ? $o['callback'] : strtoupper($id[0]) . strtolower(substr($id, 1));
      JS::autocomplete($id, $callback, $url, $options);

      return static::$_instance;
    }
    /**
     * @static
     *
     * @param        $id
     * @param string $url
     * @param array  $options 'description' => false,<br>
    'disabled' => false,<br>
    'editable' => true,<br>
    'selected' => '',<br>
    'label' => false,<br>
    'cells' => false,<br>
    'inactive' => false,<br>
    'purchase' => false,<br>
    'sale' => false,<br>
    'js' => '',<br>
    'selectjs' => '',<br>
    'submitonselect' => '',<br>
    'sales_type' => 1,<br>
    'no_sale' => false,<br>
    'select' => false,<br>
    'type' => 'local',<br>
    'kits'=>true,<br>
    'where' => '',<br>
    'size'=>'20px'<br>
     *
     * @return HTML|string
     */
    public static function searchLine($id, $url = '#', $options = array())
    {
      $defaults                      = array(
        'description'    => false,
        'disabled'       => false,
        'editable'       => true,
        'selected'       => '',
        'label'          => null,
        'cells'          => false,
        'inactive'       => false,
        'purchase'       => false,
        'sale'           => false,
        'js'             => '',
        'selectjs'       => '',
        'submitonselect' => '',
        'sales_type'     => 1,
        'no_sale'        => false,
        'select'         => false,
        'type'           => 'local',
        'kitsonly'       => false,
        'where'          => '',
        'size'           => '15'
      );
      $o                             = array_merge($defaults, $options);
      $UniqueID                      = md5(serialize($o));
      $_SESSION['search'][$UniqueID] = $o;
      $desc_js                       = $o['js'];
      HTML::setReturn(true);
      if ($o['cells']) {
        HTML::td(null, array('class'=> 'label'));
      }
      if ($o['label']) {
        HTML::label(null, $o['label'], array('for' => $id), false);
      }
      HTML::input($id, array('value' => $o['selected'], 'name' => $id, 'size' => $o['size']));
      if ($o['editable']) {
        HTML::label('lineedit', 'edit', array(
          'for' => $id, 'class' => 'stock button', 'style' => 'display:none'
        ), false);
        $desc_js .= '$("#lineedit").data("stock_id",value.stock_id).show().parent().css("white-space","nowrap"); ';
      }
      if ($o['cells']) {
        HTML::td()->td(true);
      }
      $selectjs = '';
      if ($o['selectjs']) {
        $selectjs = $o['selectjs'];
      } elseif ($o['description'] !== false) {
        HTML::textarea('description', $o['description'], array(
          'name' => 'description', 'rows' => 1, 'class'=> 'width90'
        ), false);
        $desc_js .= "$('#description').css('height','auto').attr('rows',4);";
      } elseif ($o['submitonselect']) {
        $selectjs
          = <<<JS
                $(this).val(value.stock_id);
                $('form').trigger('submit'); return false;
JS;
      } else {
        $selectjs
          = <<<JS
                $(this).val(value.stock_id);return false;
JS;
      }
      if ($o['cells']) {
        HTML::td();
      }
      $js
             = <<<JS
    Adv.o.stock_id = \$$id = $("#$id").catcomplete({
                delay: 0,
                autoFocus: true,
                minLength: 1,
                source: function( request, response ) {
                        if (Adv.lastXhr) Adv.lastXhr.abort();
                        Adv.loader.off();
                        Adv.lastXhr = $.ajax({
                                url: "$url",
                                dataType: "json",
                                data: {UniqueID: '{$UniqueID}',term: request.term},
                                success: function( data,status,xhr ) {
                                if ( xhr === Adv.lastXhr ) {
                                if (!Adv.o.stock_id.data('active')) {
                                var value = data[0];
                            Adv.Events.onFocus("#stock_id",[0,Adv.o.stock_id.position().top]);
                                $.each(value,function(k,v) {Adv.Forms.setFormValue(k,v);});
                                    $desc_js

                                        return false;
                                }
                                        response($.map( data, function( item ) {
                                                return {
                                                        label: item.stock_id+": "+item.item_name,
                                                        value: item,
                                                        category: item.category
                                                }
                                        }));
                                        Adv.loader.on();
                                }}})
                        },
                     select: function( event, ui ) {
 var value = ui.item.value;
 $selectjs
                                Adv.Events.onFocus("#stock_id",[0,$(this).position().top]);
                                $.each(value,function(k,v) {Adv.Forms.setFormValue(k,v);});
                                    $desc_js

                                return false;
                        },
                        focus: function(){return false;},
                        open: function() { $('.ui-autocomplete').unbind('mouseover');}
                        }
                ).blur(function() { $(this).data('active',false)}).focus(function() { $(this).data('active',true)}).on('paste',function() {var \$this=$(this);window.setTimeout(function(){\$this.catcomplete('search', \$this.val())},1)});
JS;
      $clean = "\$$id.catcomplete('destroy');";
      JS::addLive($js, $clean);

      return HTML::setReturn(false);
    }
    /**
     * @static
     *
     * @param $contactType
     *
     * @return mixed
     */
    public static function emailDialogue($contactType)
    {
      static $loaded = false;
      if ($loaded == true) {
        return;
      }
      $emailBox = new Dialog('Select Email Address:', 'emailBox', '');
      $emailBox->addButtons(array('Close' => '$(this).dialog("close");'));
      $emailBox->setOptions(array(
        'modal' => true, 'width' => 500, 'height' => 350, 'resizeable' => false
      ));
      $emailBox->show();
      $action
        = <<<JS
     var emailID= $(this).data('emailid');
     $.post('/contacts/emails.php',{type: $contactType, id: emailID}, function(data) {
     \$emailBox.html(data).dialog('open');

     },'html');

     return false;
JS;
      JS::addLiveEvent('.email-button', 'click', $action, 'wrapper', true);
      $loaded = true;
    }
    public static function lineSortable()
    {
      $js = <<<JS
$('.grid').find('tbody').sortable({
  items:'tr:not(.newline,.editline)',
  stop:function (e, ui) {
    var self = $(this), _this = self.find('tr:not(".newline,.editline")'), lines = {};
    self.sortable('disable');
    $.each(_this, function (k, v) {
      lines[$(this).data('line')] = k;
      if (k == _this.length - 1) {
        $.post('#', {lineMap:lines, _action:'setLineOrder', order_id:$("#order_id").val()},
          function (data) {
            $.each(_this, function (k, v) {
              var that = $(this), curline = that.data('line'), buttons = that.find('#_action');
              that.data('line', lines[curline]);
              if (that.hasClass('editline')) {
                that.find('#LineNo').attr('value', lines[curline]).val(lines[curline])
              }
              $.each(buttons, function () {
                var curbutton = $(this), curvalue = curbutton.val(), newvalue = curvalue.replace(curline, lines[curline]);
                curbutton.attr('value', newvalue).val(newvalue);
              });
            });
            self.sortable('enable');
            console.log(_this)
          }, 'json');
      }
    });
  },
  helper:function (e, ui) {
    ui.children().each(function () {
      $(this).width($(this).width());
    });
    return ui;
  }}).find('tr:not(.newline,.editline)');
$('.grid').find('.newline').droppable({drop:function (event, ui) {
  var infields = $(this).find('td');
  $(ui.draggable).find('td').each(function (k, v) {
    var currfield = infields.eq(k),currvalue=$(v).text();
    currfield.find('input').val(currvalue).end().find('textarea').text(currvalue).attr('rows',4);
    currfield.not(':has(input),:has(textarea),:has(button)').text(currvalue)})}})
JS;
      JS::addLive($js);
    }
  }
