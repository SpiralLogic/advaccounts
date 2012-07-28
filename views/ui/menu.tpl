<div class='width90 center ui-tabs ui-widget ui-widget-content ui-corner-all tabs' id='tabs{{$menuCount}}'>
  <ul class='ui-tabs-nav
            ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>
    {{#foreach $items as $key => $item}}
    <li class='ui-state-default ui-corner-top'>
      {{#if $item->link != "#"}}
      <a title='{{$item->label}}' href='{{$item->link}}' {{$item->param_element?}}data-paramel='{{$item->param_element}}'{{/$item->param_element?}} {{$item->target?}}data-target='{{$item->target}}'{{/$item->target?}}><span>{{$item->label}}</span></a>
      {{#else}}
      <a title='{{$item->label}}' href='#tabs{{$menuCount}}-{{$key}}'><span>{{$item->label}}</span></a>
      {{/if}}</li>
    {{/foreach}}
  </ul>
</div>
