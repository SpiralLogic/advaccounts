<div class='ajaxmark'>
    <img alt='Ajax Loading' id='ajaxmark' src='/themes/{{$theme}}/images/progressbar.gif'>

    <div id='top'><p>{{$company}} | {{$server_name}} </p>
    </div>
</div>
<div id='logo'><h1>{{APP_TITLE}}<span class='slogan'>{{VERSION}}</span></h1></div>
<div id='tabs'>
    <ul class="menu" id="topmenu">
    {{#$menu}}
        <li class='{{$.class}}'>
            <a href='{{$.href}}' >{{$.name}}</a>
            <ul>
           {{#$.extra}}
            <li class='title'>{{$.title}}</li>
            {{#$.modules}}
            <li><a href="{{$.href}}">{{$.label}}</a></li>
            {{/$.modules}}
            {{/$.extra}}
            </ul>
        </li>
    {{/$menu}}
    </ul>
    <ul class="menu" id="rightmenu">
<li><a>{{$name}}</a>
<ul>
        <li><a href='{{ROOT_URL}}system/display_prefs.php?'>Preferences</a></li>
        <li><a href='{{ROOT_URL}}system/change_current_user_password.php?selected_id={{e($username)}}'>Change&nbsp;password</a>
        </li>
        <li><a href='{{ROOT_URL}}access/logout.php?'>Logout</a></li>
    </ul></li>
    </ul>
</div>

