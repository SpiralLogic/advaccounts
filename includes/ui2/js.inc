<?php
include(APP_PATH . 'modules/smartoptimizer/minifiers/js.php');
   class JS
   {

      private static $_beforeload = array();
      private static $_onload = array();

      private function __construct()
      {
      }

      public static function autocomplete($id, $callback, $url = false, $options = array())
      {
         $afterSource = (isset($options['afterSource'])) ? $options['afterSource'] : '';
         if (!$url)
            $url = $_SERVER['PHP_SELF'];
         self::$_onload[] = <<< JS
      var autocomplete{$id};
      $('#{$id}').autocomplete({
         autoFocus:true,
	      source: function(request, response) {
		      var lastXhr = $.getJSON('{$url}', request, function(data, status, xhr) {
			   	if (xhr === lastXhr) {
				   	response(data);
				   	autocomplete{$id} = data[0];
				   	{$afterSource}(data,request);
				   }
			   });
		   },
		   select: function(event, ui) {
	   	 if ({$callback}(ui.item)===false) return false;
   	   }
      }).css({'z-index' : '2'});
JS;
      }

      public static function setfocus($id = false)
      {
         if (!$id)
            return;
         self::$_onload[] = <<< JS

$('{$id}').focus();

JS;

      }

      public static function gmap($selector, $address, $title)
      {
         $address = str_replace("\r\t\n\v", ", ", $address);
         self::$_onload[] = <<<JS

	var map = $("<div/>").gMap({
	address:"{$address}",
	markers: [{ address:"{$address}", html: "_address", popup: true}],
	zoom: 10}).appendTo('body').dialog({title: "{$title}", autoOpen: false, show: "slide", hide: "slide", height: 450, width: 1000, modal: true});
$("{$selector}").click(function() {
    map.dialog("open");
    return false; });
    $(".ui-widget-overlay").click(function() {
    map.dialog("close");
    return false; });
JS;

      }

      public static function tabs($id, $options = array())
      {
         $defaults = array('noajax' => false);
         extract(array_merge($defaults, $options));
         $content = '';
         self::$_onload[] = "$('" . $id . "').tabs(" . $content . ");";
      }

      public static function render()
      {
            $content = implode(';', self::$_beforeload);
            $content .= '$(function() {' . implode(';', self::$_onload) . '});';
         echo "\n<script language=\"javascript\"  type=\"text/javascript\"><!--\n" . minify_js($content) . "\n--></script>\n";
      }


      public static function arrayToOptions($options = array())
      {
         $options = (object)$options;
         return json_encode($options);
      } 


      public static function onload($js = false)
      {
         if ($js)
         self::register($js, self::$_onload);
      }

      public static function beforeload($js = false)
      {
         if ($js)
         self::register($js, self::$_beforeload);
      }


      protected static function register($js = false, &$var )
      {
         if (empty($var))
            $var = &self::$_beforeload;
          if (is_array($js)) foreach ($js as $j) self::register($j, $var); else
         array_push($var, str_replace(array('<script>', '</script>'), '', $js));

      }
   }
