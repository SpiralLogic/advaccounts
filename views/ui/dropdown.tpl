<div class="btn-group  auto">
  {{#foreach $menus as $menu}}
  <button class="btn btn-mini btn-primary dropdown-toggle auto" data-toggle="dropdown">{{$menu.title}} <span class="caret"></span></button>
  <ul class="dropdown-menu">
    {{#foreach $menu.items as $item}}
    {{#if $item=='divider'}}
    <li class="divider"></li>
    {{#else}}
    <li>
      <a class="{{$item.class}}" href="{{$item.href?:'#'}}" {{$item.data?}}{{#foreach $item.data as $name=>$value}} data-{{$name}}="{{$value}}"{{/foreach}}{{/$item.data?}}>{{$item.label}}</a></li>
    {{/if}}
    {{/foreach}}
  </ul>

  {{/foreach}}
</div>
