<div class='width90 center ui-tabs ui-widget ui-widget-content ui-corner-all tabs' id='tabs{{$menuCount}}'>
  <ul class='ui-tabs-nav
            ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>
    {{#foreach $items as $key => $item}}
    <li class='ui-state-default ui-corner-top'>
      <a {{HTML::attr($item->attr)}}><span>{{$item->label}}</span></a></li>
    {{/foreach}}
  </ul>
  {{#$tabs}}
  <div id="{{$.id}}" {{$.class?}}class="{{$.class}}"{{/$.class?}}
       {{$.style?}}style="{{$.style}}"{{/$.style?}}>
    {{$.contents}}
  </div>
  {{/$tabs}}
</div>
