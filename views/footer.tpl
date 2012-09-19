{{$backlink?}}
<div class='center margin20'>
    <a class='button' href='javascript:(window.history.length === 1) ? window.close() : window.history.go(-1);'>{{ $backlink }}</a>
</div>
{{/$backlink?}}
<!-- end page body div -->
{{$page_body}}
<!-- end wrapper div-->
</div>
{{#if $footer}}

<div id='footer'>
{{#if $user}}
    <span class='power'><i class='icon-share'> </i><a target='_blank' href='{{POWERED_URL}}'>{{POWERED_BY}}</a></span>
    <span class='date'>{{$today}} | {{$now}}</span>
    <span> </span>| <span>mem/peak: {{$mem}} </span><span>|</span><span> load time: {{\ADV\App\Dates::_getReadableTime(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) }}</span>
{{/if}}
    <!-- end footer div-->
</div>
{{/if}}
{{>help_modal}}
<!-- end content div-->
</div>
{{>sidemenu}}
{{$messages}}
{{#if !REQUEST_AJAX}}
{{/if}}
<script>{{$beforescripts}}
</script>  {#$JS->render()#}
{{#if !REQUEST_AJAX}}
</body>
</html>
{{/if}}
