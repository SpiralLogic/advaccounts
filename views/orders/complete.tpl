<div class="center">
  {{$viewtrans}}<br><br>
  {{$printtrans}}<br><br>
  {{$modifytrans}}<br>
  <br>
  {{$emailtrans}}<br><br>

  {{#$buttons}}
  <a class="button" href="{{$.href}}" accesskey="{{$.accesskey}}">
    {{$.label}}</a>
  <br><br>
{{/$buttons}}
</div>
