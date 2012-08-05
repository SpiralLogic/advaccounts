<table class='width100'>
  <tr>
    <td class='menu_group top'>
      <table class='width100'>
        <tr>
          <td class='menu_group' colspan={{colspan}}>
            {{name}}
          </td>
        </tr>
        <tr>
          <td class='width50 menu_group_items'>
            <ul>
              {{#lmods}}
              {{^label}}
              <li class='empty'>&nbsp;</li>
              {{/label}}
              {{#label?}}
              <li class='empty'{{label}}</li>
              {{/label?}}
              {{#access?}}
              <li>{{link}} </li>
              {{/access?}}
              {{^access}}
              <li><span class='inactive'>{{anchor}} </span></li>
              {{/access}}
              {{/lmods}}
            </ul>
          </td>
          {{#rmods?}}
          <td class='width50 menu_group_items'>
            <ul>
              {{#rmods}}
              {{^label}}
              <li class='empty'>&nbsp;</li>
              {{/label}}
              {{#access?}}
              <li>{{link}} </li>
              {{/access?}}
              {{^access}}
              <li><span class='inactive'>{{anchor}} </span></li>
              {{/access}}
              {{/rmods}}
            </ul>
          </td>
          {{/rmods?}}
        </tr>
      </table>
    </td>
  </tr>
</table>
