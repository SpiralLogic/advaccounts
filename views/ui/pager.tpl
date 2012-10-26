<div class='center'>
  {{$form._start}}

  <table class='{{$class}}'>
    <thead>
    <tr class='navibar'>
      <td colspan='{{$colspan}}' class='navibar'>
        <span>{{$records}} {{$inactive?}}<label><input {{$checked}} type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'>Show also
        inactive</label>{{/$inactive?}}</span><span class='floatright'>{{#navbuttons}}{{.}}{{/navbuttons}}</span></td>
    </tr>
    <tr class="naviheader">{{#$headers}}
      <th>{{.}}</th>
      {{/$headers}}</tr>
    </thead>
    {{#$rows}}
    {{$.group?}}
    <tr class='navigroup'>
      <th colspan={{$.colspan}}>{{$.group}}</th>
    </tr>
    {{/$.group?}}
    {{$.edit?}}
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
    {{/$.edit?}}
    {{#if !$.edit}}
    <tr {{$.attrs}}>
      {{#$.cells}}
      <td {{$.attrs}}>{{$.cell}}</td>

      {{/$.cells}}
    </tr>
    {{/if}}
    {{/$rows}}
    <tfoot>
    <tr class='navibar'>
      <td colspan='{{$colspan}}' class='navibar'><span>Records {{$from}}-{{$to}} of {{$all}}{{$inactive?}} <label><input {{$checked}} type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'>Show also
        inactive</label>{{/$inactive?}}</span><span class='floatright'>{{#navbuttonsbottom}}{{.}}{{/navbuttonsbottom}}</span></td>
    </tr>
    </tfoot>
  </table>
  {{$form._end}}
</div>
