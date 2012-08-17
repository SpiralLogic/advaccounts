<table class='width100'>
  <tr>
    <td class='menu_group top'>
      <table class='width100'>
        <tr>
          <td class='menu_group' colspan={{$colspan}}>
            {{$name}}
          </td>
        </tr>
        <tr>
          <td class='width50 menu_group_items'>
            <ul>
              {{#$lmods}}
              {{#if !$.label}}
              <li class='empty'>&nbsp;</li>
              {{#elseif $.access}}
              <li>{{$.link}} </li>
              {{#else}}
              <li><span class='inactive'>{{$.anchor}} </span></li>
              {{/if}}
              {{/$lmods}}
            </ul>
          </td>
          {{#if $rmods}}
          <td class='width50 menu_group_items'>
            <ul>
              {{#$rmods}}
              {{#if !$.label}}
              <li class='empty'>&nbsp;</li>
              {{#elseif $.access}}
              <li>{{$.link}} </li>
              {{#else}}
              <li><span class='inactive'>{{$.anchor}} </span></li>
              {{/if}}
              {{/$rmods}}
            </ul>
          </td>
          {{/if}}
        </tr>
      </table>
    </td>
  </tr>
</table>
