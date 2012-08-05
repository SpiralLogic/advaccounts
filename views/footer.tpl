{{#backlink?}}
<div class='center margin20'>
  <a class='button' href='javascript:(window.history.length === 1) ? window.close() : window.history.go(-1);'>{{backlink}}</a>
</div>
{{/backlink?}}
<!-- end page body div -->
{{page_body}}
<!-- end wrapper div-->
</div>
{{#footer?}}
<div id='footer'>
  {{#user?}}
  <span class='power'><a target='_blank' href='{{POWERED_URL}}'>{{POWERED_BY}}</a></span>
  <span class='date'>{{today}} | {{now}}</span>
  <span> </span>| <span>mem/peak: {{mem}} </span><span>|</span><span> load time: {{load_time}}</span>
  {{/user?}}
  <!-- end footer div-->
</div>
{{/footer?}}
<!-- end content div-->
</div>
{{sidemenu}}
{{messages}}
{{#AJAX_REFERRER?}}
{{/AJAX_REFERRER?}}
<script>{{beforescripts}}
</script>  {{js}}
{{^AJAX_REFERRER}}
</body>
</html>
{{/AJAX_REFERRER}}
