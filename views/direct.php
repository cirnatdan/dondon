<?php include (__DIR__ . '/header.php'); ?>
<main id="main" class="home">
<div class="article_wrap">
<aside class="left_column">
<?php include dirname(__FILE__).('/widgets/side_current_user.php'); ?>
</aside>
<article class="center_column">
<header class="timeline_header">
<ul class="header_items">
<li class="item toots view">
<a href="#">
<?=_('Direct messages')?>
</a>
</li>
</ul>
</header>
<div id="js-stream_update">
<button>
View <span></span> new Toots
</button>
</div>
<ul id="js-timeline" class="timeline">
</ul>
<footer id="js-timeline_footer" class="timeline_footer">
<i class="fa fa-spin fa-circle-o-notch" aria-hidden="true"></i>
</footer>
</article>
<aside class="right_column">
<section class="side_widgets_wrap">
<?php include dirname(__FILE__).('/widgets/side_who_to_follow.php'); ?>
<?php include dirname(__FILE__).('/widgets/side_trending.php'); ?>
<?php include dirname(__FILE__).('/widgets/side_firefox_addon.php'); ?>
</section>
<?php include dirname(__FILE__).('/widgets/side_footer.php'); ?>
</aside>
</div>
</main>
<script>
current_file = location.pathname;
setDirectTimeline();
$('title').text('Halcyon / Direct');
</script>
<?php include('footer.php'); ?>
