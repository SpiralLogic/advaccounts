<div class='ajaxmark'>
    <img alt='Ajax Loading' id='ajaxmark' src='/themes/{{$theme}}/images/progressbar.gif'>

    <div id='top'><p>{{$company}} | {{$server_name}} | {{$name}}</p>

    </div>
</div>
<div id='logo'><h1>{{APP_TITLE}}<span class='slogan'>{{VERSION}}</span></h1></div>
<div id='_tabs2'>
    <ul class="menu" id="topmenu">
    {{#$menu}}
        <li class='{{$.class}}'>
            <a href='{{$.href}}' {{$.acc1}}>{{$.acc0}}</a>
        </li>
    {{/$menu}}
    </ul><ul class="menu">
                     <li><a href='{{ROOT_URL}}system/display_prefs.php?'>Preferences</a></li>
                     <li><a href='{{ROOT_URL}}system/change_current_user_password.php?selected_id={{e($username)}}'>Change password</a>
                     </li>
                     <li><a target='_blank' class='openWindow' href='{{$help_url}}'>Help</a></li>
                     <li><a href='{{ROOT_URL}}access/logout.php?'>Logout</a></li>
                 </ul>
</div>

