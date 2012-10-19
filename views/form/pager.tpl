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
  <td>
    {{$.name}}
    {{.}}
  </td>
  {{/$form.rest}}
</tr>
