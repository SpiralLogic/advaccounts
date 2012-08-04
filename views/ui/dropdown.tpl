<ul class="nav nav-pills">
  {{#foreach $menus as $menu}}
  <li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
      {{$menu.title}}
      <b class="caret"></b>
    </a>
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
</ul>
