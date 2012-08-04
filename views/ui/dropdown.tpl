<div class="btn-group">
  {{#foreach $menus as $menu}}
  <button class="btn btn-mini btn-inverse dropdown-toggle" data-toggle="dropdown">{{$menu.title}} <span class="caret"></span></button>
  <ul class="dropdown-menu">
    {{#foreach $menu.items as $item}}
    {{#if $item=='divider'}}
    <li class="divider"></li>
    {{#else}}
    <li><a class="{{$item.class}}" href="#" {{#foreach $item.data as $name=>$value}} data-{{$name}}="{{$value}}"{{/foreach}}>{{$item.label}}</a></li>
    {{/if}}
    {{/foreach}}
  </ul>
  </li>
  {{/foreach}}
</div>
