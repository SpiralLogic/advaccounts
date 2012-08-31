<div class="center">
  <div class="formbox formdiv">
    <h3>Sales Person Detail:</h3><br>
    {{$form->start()}}
    {{#$form}}
    {{.}}
    {{/$form}}
    <br>
    {{#$form.buttons}}
    {{.}}
    {{/$form.buttons}}
    {{$form->end()}}
  </div>
</div>
