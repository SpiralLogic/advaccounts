<div class='center'>
  <table class='{{$class}}'>
    <thead>
    <tr class='navibar'>
      <td colspan='{{$colspan}}' class='navibar'><span>Records {{$from}}-{{$to}} of {{$all}}{{$inactive?}} <label><input {{$checked}} type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'>Show also
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
    <tr {{$.attrs}}>
      {{#$.cells}}
      <td {{$.attrs}}>{{$.cell}}</td>

      {{/$.cells}}
    </tr>

    {{/$rows}}
    <tfoot>
    <tr class='navibar'>
      <td colspan='{{$colspan}}' class='navibar'><span>Records {{$from}}-{{$to}} of {{$all}}{{$inactive?}} <label><input {{$checked}} type='checkbox' name='_action' value='showInactive' onclick='JsHttpRequest.request(this)'>Show also
        inactive</label>{{/$inactive?}}</span><span class='floatright'>{{#navbuttonsbottom}}{{.}}{{/navbuttonsbottom}}</span></td>
    </tr>
    </tfoot>
  </table>
</div>
