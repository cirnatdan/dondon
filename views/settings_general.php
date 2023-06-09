<?php include (__DIR__ . '/header.php'); ?>
<main id="main">
<?php include dirname(__FILE__).('/widgets/settings_header.php'); ?>
<div class="article_wrap">
<aside class="left_column">
<?php include dirname(__FILE__).('/widgets/side_current_user.php'); ?>
<?php include dirname(__FILE__).('/widgets/side_footer.php'); ?>
</aside>
<article class="center_column">
<header class="timeline_header">
<ul class="header_items">
<li class="item toots view"><?=_('General settings')?></li>
</ul>
</header>
<div class="timeline">
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Language')?></h3>
</div>
<div class="language_wrap" style="float:left;width:50%">
<select name="language" class="selectbox">
<?php
$languages = scandir("locale");
for($i=0;$i<count($languages);$i++) {
if($languages[$i] != "." && $languages[$i] != "..") {
$selected = "";
if($languages[$i] == $locale) {
$selected = " selected";
}
echo "<option value='".$languages[$i]."'".$selected.">"._('Language_'.$languages[$i])."</option>";
}
}
?>
</select>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Default post privacy')?></h3>
</div>
<div class="post_privacy_wrap" style="float:left;width:50%;margin-top:8px;margin-bottom:-8px">
<div class="radiobox">
<input id="privacy-1" name="post_privacy" type="radio" value="public">
<label for="privacy-1" class="radiotext"><?=_('Public')?></label>
</div>
<div class="radiobox">
<input id="privacy-2" name="post_privacy" type="radio" value="unlisted">
<label for="privacy-2" class="radiotext"><?=_('Unlisted')?></label>
</div>
<div class="radiobox">
<input id="privacy-3" name="post_privacy" type="radio" value="private">
<label for="privacy-3" class="radiotext"><?=_('Followers-only')?></label>
</div>
<div class="radiobox">
<input id="privacy-4" name="post_privacy" type="radio" value="direct">
<label for="privacy-4" class="radiotext"><?=_('Direct')?></label>
</div>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Mark as NSFW by default')?></h3>
</div>
<div class="post_sensitive_wrap" style="float:left;width:50%">
<div class="switch">
<input type="checkbox" id="setting_post_sensitive">
<div class="switch-btn">
<span></span>
</div>
</div>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Autocomplete in compose field')?></h3>
</div>
<div class="compose_autocomplete_wrap" style="float:left;width:50%">
<div class="switch">
<input type="checkbox" id="setting_compose_autocomplete">
<div class="switch-btn">
<span></span>
</div>
</div>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Rewrite YouTube to Invidious at compose')?></h3>
</div>
<div class="rewrite_invidious_wrap" style="float:left;width:50%">
<div class="switch" style="float:left">
<input type="checkbox" id="setting_rewrite_invidious">
<div class="switch-btn">
<span></span>
</div>
</div>
<a href="javascript:void(0)" id="setting_rewrite_invidious_reset" style="float:left;display:none">
<i class="fa fa-2x fa-times" style="margin-top:8px"></i>
</a>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Rewrite Twitter to Nitter at compose')?></h3>
</div>
<div class="rewrite_nitter_wrap" style="float:left;width:50%">
<div class="switch" style="float:left">
<input type="checkbox" id="setting_rewrite_nitter">
<div class="switch-btn">
<span></span>
</div>
</div>
<a href="javascript:void(0)" id="setting_rewrite_nitter_reset" style="float:left;display:none">
<i class="fa fa-2x fa-times" style="margin-top:8px"></i>
</a>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Rewrite Instagram to Bibliogram at compose')?></h3>
</div>
<div class="rewrite_bibliogram_wrap" style="float:left;width:50%">
<div class="switch" style="float:left">
<input type="checkbox" id="setting_rewrite_bibliogram">
<div class="switch-btn">
<span></span>
</div>
</div>
<a href="javascript:void(0)" id="setting_rewrite_bibliogram_reset" style="float:left;display:none">
<i class="fa fa-2x fa-times" style="margin-top:8px"></i>
</a>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Rewrite Facebook to NoFB at compose')?></h3>
</div>
<div class="rewrite_nofb_wrap" style="float:left;width:50%">
<div class="switch" style="float:left">
<input type="checkbox" id="setting_rewrite_nofb">
<div class="switch-btn">
<span></span>
</div>
</div>
<a href="javascript:void(0)" id="setting_rewrite_nofb_reset" style="float:left;display:none">
<i class="fa fa-2x fa-times" style="margin-top:8px"></i>
</a>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Local instance')?></h3>
</div>
<div class="local_instance_wrap" style="float:left;width:50%">
<input name="local_instance" placeholder="default" type="text" class="disallow_enter textfield" id="setting_local_instance">
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Hashtag search filter')?></h3>
</div>
<div class="search_filter_wrap" style="float:left;width:50%;margin-top:8px;margin-bottom:-8px">
<div class="radiobox">
<input id="locinstance-1" name="search_filter" type="radio" value="all">
<label for="locinstance-1" class="radiotext"><?=_('All instances')?></label>
</div>
<div class="radiobox">
<input id="locinstance-2" name="search_filter" type="radio" value="local">
<label for="locinstance-2" class="radiotext"><?=_('Local only')?></label>
</div>
</div>
<div style="float:left;width:50%;text-align:right;margin-top:16px">
<h3><?=_('Who to follow')?></h3>
</div>
<div class="who_to_follow_wrap" style="float:left;width:50%">
<div class="switch">
<input type="checkbox" id="setting_who_to_follow">
<div class="switch-btn">
<span></span>
</div>
</div>
</div>
<span style="visibility:hidden">-</span>
</div>
</article>
</div>
</main>
<script src="/assets/js/halcyon/halcyonSettings.js"></script>
<?php include('footer.php'); ?>
