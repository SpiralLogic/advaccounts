<div class="btn-group  {{$auto}}">
  {{#$menus}}
  {{#if $.split}}
  <button class="btn btn-mini btn-primary btn-split" >{{$.title}} </button><button class='btn btn-mini btn-primary dropdown-toggle {{$.auto}}' data-toggle="dropdown"><span class="caret"></span></button>
{{#else}}
  <button class="btn btn-mini btn-primary dropdown-toggle {{$.auto}}" data-toggle="dropdown">{{$.title}}<span class="caret"></span></button>
{{/if}}
  <ul class="dropdown-menu">
    {{#$.items}}
    {{#if $.divider}}
    <li class="divider"></li>
    {{#else}}
    <li>
      <a class="{{$.class}}" href="{{$.href?:'#'}}" {{#$item.data}} data-{{!}}="{{.}}"{{/$item.data}}>{{$.label}}</a></li>
    {{/if}}
    {{/$.items}}
  </ul>

  {{/$menus}}
</div>
