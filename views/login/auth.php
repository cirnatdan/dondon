<!DOCTYPE HTML>
<html lang='en'>
<head>
<script>
if(
localStorage.getItem('current_id') |
localStorage.getItem('current_instance') |
localStorage.getItem('current_authtoken')
){
location.href = '/logout';
};
</script>
<script src="/assets/js/jquery/jquery.min.js"></script>
<script src="/assets/js/mastodon.js/mastodon.js"></script>
<script src="/assets/js/jquery-cookie/src/jquery.cookie.js"></script>
<?php

use HalcyonSuite\HalcyonForMastodon\Mastodon;
$api = new Mastodon();
if ($_GET['code']) {

echo "
<script>
localStorage.setItem('current_id','$account_id');
localStorage.setItem('current_instance','$domain');
localStorage.setItem('current_authtoken', '$access_token');
localStorage.setItem('current_search_history', '[]');
localStorage.setItem('setting_post_stream', 'auto');
localStorage.setItem('setting_post_privacy', 'public');
localStorage.setItem('setting_local_instance', 'default');
localStorage.setItem('setting_search_filter', 'all');
localStorage.setItem('setting_link_previews', 'true');
localStorage.setItem('setting_desktop_notifications', 'true');
localStorage.setItem('setting_service_worker', 'false');
localStorage.setItem('setting_who_to_follow', 'false');
localStorage.setItem('setting_show_replies', 'true');
localStorage.setItem('setting_show_bots', 'true');
localStorage.setItem('setting_show_content_warning', 'false');
localStorage.setItem('setting_show_nsfw', 'false');
localStorage.setItem('setting_full_height', 'false');
localStorage.setItem('setting_thread_view', 'true');
localStorage.setItem('setting_show_admin','false');
localStorage.setItem('setting_compose_autocomplete', 'true');
localStorage.setItem('setting_play_gif','true');
localStorage.setItem('setting_play_video','true');
localStorage.setItem('setting_play_audio','true');
localStorage.setItem('setting_play_peertube','true');
localStorage.setItem('setting_play_youplay','false');
localStorage.setItem('setting_play_invidious','false');
localStorage.setItem('setting_play_vimeo','false');
localStorage.setItem('setting_post_privacy','".$profile["source"]["privacy"]."');
localStorage.setItem('setting_post_sensitive','".$profile["source"]["sensitive"]."');
localStorage.setItem('setting_redirect_invidious','unset');
localStorage.setItem('setting_redirect_nitter','unset');
localStorage.setItem('setting_redirect_bibliogram','unset');
localStorage.setItem('setting_redirect_nofb','unset');
localStorage.setItem('setting_rewrite_invidious','unset');
localStorage.setItem('setting_rewrite_nitter','unset');
localStorage.setItem('setting_rewrite_bibliogram','unset');
localStorage.setItem('setting_rewrite_nofb','unset');
$.cookie('darktheme','unset',{path:'/',expires:3650});
if(sessionStorage.return && sessionStorage.return == 'share') location.href = '/intent/toot?action=send';
else location.href = '/';
</script>
";
}
else echo "<h1>An error occured</h1><p>There was an error and Halcyon couldn't fetch or validate a access token for this instance</p>";
?>
</head>
<body>
</body>
</html>
