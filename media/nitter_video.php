<?php
if(!isset($_COOKIE["session"]) || $_COOKIE["session"] != "true") {
http_response_code(403);
die('Forbidden');
}
?>
<!DOCTYPE html>
<html lang="en" style="height:100%">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
</head>
<body style="margin:0;height:100%;overflow:hidden">
<video class="video-js vjs-default-skin" controls id="player" poster="<?=htmlspecialchars($_GET["preview"])?>" title="<?=htmlspecialchars($_GET["title"])?>" style="width:100%;height:100%">
Your browser does not support the video tag.
</video>
<script>
    var video = document.getElementById('player');
    var videoSrc = '<?=htmlspecialchars($_GET["url"])?>';
    //
    // First check for native browser HLS support
    //
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = videoSrc;
        //
        // If no native HLS support, check if HLS.js is supported
        //
    } else if (Hls.isSupported()) {
        var hls = new Hls();
        hls.loadSource(videoSrc);
        hls.attachMedia(video);
    }
</script>
</body>
</html>
