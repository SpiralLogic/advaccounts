<div class='menu_group title'>
  {{$name}}
</div>
<div class="menu_group">
  <ul>
    {{#$lmods}}
    {{#if $.access}}
    <li>{{$.href}} </li>
    {{#else}}
    <li><span class='inactive'>{{$.anchor}} </span></li>
    {{/if}}
    {{/$lmods}}
  </ul>
  {{#if $rmods}}
  <ul>
    {{#$rmods}}
    {{#if $.access}}
    <li>{{$.href}} </li>
    {{#else}}
    <li><span class='inactive'>{{$.anchor}} </span></li>
    {{/if}}
    {{/$rmods}}
  </ul>
  {{/if}}
</div>
