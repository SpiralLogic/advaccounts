<div class='width90 center ui-tabs ui-widget ui-widget-content ui-corner-all tabs' id='tabs{{menuCount}}'>
  <ul class='ui-tabs-nav
            ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>
    {{#items}}
    <li class='ui-state-default ui-corner-top'>
      {{#link?}}
      <a title='{{label}}' href='{{link}}' {{#param_element?}}data-paramel='{{param_element}}'{{/param_element?}} {{#target?}}data-target='{{target}}'{{/target?}}><span>{{label}}</span></a>
      {{/link?}}
      {{/link?}}
      <a title='{{label}}' href='#tabs{{menuCount}}-{{key}}'><span>{{label}}</span></a>
      {{/if}}</li>
    {{/foreach}}
  </ul>
  {{#foreach $tabs as $tab}}
  <div id="{{tab.id}}" {{tab.class?}}class="{{tab.class}}"{{/$tab.class?}}
       {{$tab.style?}}style="{{$tab.style}}"{{/$tab.style?}}>
    {{$tab.contents}}
  </div>
  {{/foreach}}
</div>
