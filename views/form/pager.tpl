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
  <td {{$class}}>
    {{.}}
  </td>
  {{/$form.rest}}
</tr>
