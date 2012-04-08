<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
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
    static function button($id = FALSE, $content = FALSE, $attr = array()) {
      if ($id) {
        $attr['id'] = $id;
      }
      if (empty($attr['name']) && ($id)) {
        $attr['name'] = $id;
      }
      if (!isset($attr['class'])) {
        $attr['class'] = 'button';
      }
      HTML::button($id, $content, $attr, FALSE);
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
    static function select($id = FALSE, $options = array(), $params = array()) {
      HTML::setReturn(TRUE)->select($id, $params);
      foreach ((array) $options as $label => $option) {
        if (is_array($option)) {
          HTML::optgroup(array('label' => $label));
          foreach ($option as $data) {
            HTML::option(NULL, $data[0] . ' (' . $data[1] . ')', array('value' => $data[1]), FALSE);
          }
          HTML::optgroup();
        }
        else {
          HTML::option(NULL, $option, array('value' => $label), FALSE);
        }
      }
      echo HTML::_select()->setReturn(FALSE);
      return static::$_instance;
    }

    /***
     * @static
     *
     * @param       $id
     * @param array $attr  includes (url,label,size,name,set,focus, nodiv, callback, options
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
    static function search($id, $attr = array(), $options = array()) {
      $o = array(
        'url' => FALSE, //
        'nodiv' => FALSE, //
        'label' => FALSE, //
        'size' => 30, //
        'name' => FALSE, //
        'set' => FALSE, //
        'value' => FALSE, //
        'focus' => FALSE, //
        'callback' => FALSE //
      );
      $o = array_merge($o, $attr);
      $url = ($o['url']) ? $o['url'] : FALSE;
      if (!$o['nodiv']) {
        HTML::div(array('class' => 'ui-widget'));
      }
      if (($o['label'])) {
        HTML::label(NULL, $o['label'], array('for' => $id), FALSE);
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
        $input_attr['autofocus'] = TRUE;
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
    static function searchLine($id, $url = '#', $options = array()) {
      $defaults = array(
        'description' => FALSE,
        'disabled' => FALSE,
        'editable' => TRUE,
        'selected' => '',
        'label' => NULL,
        'cells' => FALSE,
        'inactive' => FALSE,
        'purchase' => FALSE,
        'sale' => FALSE,
        'js' => '',
        'selectjs' => '',
        'submitonselect' => '',
        'sales_type' => 1,
        'no_sale' => FALSE,
        'select' => FALSE,
        'type' => 'local',
        'kitsonly' => FALSE,
        'where' => '',
        'size' => '15'
      );
      $o = array_merge($defaults, $options);
      $UniqueID = md5(serialize($o));
      $_SESSION['search'][$UniqueID] = $o;
      $desc_js = $o['js'];
      HTML::setReturn(TRUE);
      if ($o['cells']) {
        HTML::td(TRUE);
      }
      if ($o['label']) {
        HTML::label(NULL, $o['label'], array('for' => $id), FALSE);
      }
      HTML::input($id, array('value' => $o['selected'], 'name' => $id, 'size' => $o['size']));
      if ($o['editable']) {
        HTML::label('lineedit', 'edit', array(
          'for' => $id, 'class' => 'stock button', 'style' => 'display:none'
        ), FALSE);
        $desc_js .= '$("#lineedit").data("stock_id",value.stock_id).show().parent().css("white-space","nowrap"); ';
      }
      if ($o['cells']) {
        HTML::td()->td(TRUE);
      }
      $selectjs = '';
      if ($o['selectjs']) {
        $selectjs = $o['selectjs'];
      }
      elseif ($o['description'] !== FALSE) {
        HTML::textarea('description', $o['description'], array(
          'name' => 'description', 'rows' => 1, 'cols' => 35
        ), FALSE);
        $desc_js .= "$('#description').css('height','auto').attr('rows',4);";
      }
      elseif ($o['submitonselect']) {
        $selectjs
          = <<<JS
				$(this).val(value.stock_id);
				$('form').trigger('submit'); return false;
JS;
      }
      else {
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
				).blur(function() { $(this).data('active',false)}).focus(function() { $(this).data('active',true)});
JS;
      $clean = "\$$id.catcomplete('destroy');";
      JS::addLive($js, $clean);
      return HTML::setReturn(FALSE);
    }
    /**
     * @static
     *
     * @param $contactType
     *
     * @return mixed
     */
    static public function emailDialogue($contactType) {
      static $loaded = FALSE;
      if ($loaded == TRUE) {
        return;
      }
      $emailBox = new Dialog('Select Email Address:', 'emailBox', '');
      $emailBox->addButtons(array('Close' => '$(this).dialog("close");'));
      $emailBox->setOptions(array(
        'modal' => TRUE, 'width' => 500, 'height' => 350, 'resizeable' => FALSE
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
      JS::addLiveEvent('.email-button', 'click', $action, 'wrapper', TRUE);
      $loaded = TRUE;
    }
  }
