<div class='ajaxmark'>
  <img alt='Ajax Loading' width='25' height='25' id='ajaxmark' src='/themes/<?= $theme ?>/images/ajax_loader3.gif'>

  <div id='top'><p><?= $company ?> | <?= $server_name ?> | <?= $username ?></p>
    <ul>
      <li><a href='<?= BASE_URL ?>system/display_prefs.php?'>Preferences</a></li>
      <li><a href='<?= BASE_URL ?>system/change_current_user_password.php?selected_id=<?= $username ?>'>Change password</a></li>
      <li><a target='_blank' class='openWindow' href='<?= $help_url ?>'>Help</a></li>
      <li><a href='<?= BASE_URL ?>access/logout.php?'>Logout</a></li>
    </ul>
  </div>
</div>
<div id='logo'><h1><?= APP_TITLE ?><br><span class='slogan'><?= VERSION ?></span></h1></div>
      <div id='_tabs2'>
        <ul class="menu" id="topmenu">
          <% foreach ($menu as $m): %>
          <li <?= $m['class'] ? "class='{$m['class']}'" : '' ?>>
            <a href='<?=$m['href']?>'<?=$m['acc1']?>><?=$m['acc0']?></a></li>
          <% endforeach; %></ul>
