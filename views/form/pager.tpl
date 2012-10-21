<tr>

  <td>
    {{#$form.hidden}}
    {{.}}
    {{/$form.hidden}}
    {{#$form.first}}
    {{.}}
    {{/$form.first}}
  </td>

  {{#$form.rest}}
  <td {{$.tdclass}}>
    {{.}}
  </td>
  {{/$form.rest}}
</tr>
