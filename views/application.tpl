<div class='menu_group title'>
  {{$name}}
</div>
<div class="menu_group">
  <ul>
    {{#$lmods}}
    {{#if $.access}}
    <li>{{$.link}} </li>
    {{#else}}
    <li><span class='inactive'>{{$.anchor}} </span></li>
    {{/if}}
    {{/$lmods}}
  </ul>
  {{#if $rmods}}
  <ul>
    {{#$rmods}}
    {{#if $.access}}
    <li>{{$.link}} </li>
    {{#else}}
    <li><span class='inactive'>{{$.anchor}} </span></li>
    {{/if}}
    {{/$rmods}}
  </ul>
  {{/if}}
</div>
