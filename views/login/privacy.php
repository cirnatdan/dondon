<?php
include("header.php");
$config = parse_ini_file('../config.ini',true);
?>
<main id="main">
<article id="article">
<h2>Halcyon privacy policy</h2>
<?php
if(file_exists(__DIR__ . "/../../config/imprint.txt")) {
$imprint = file_get_contents(__DIR__ . "/../../config/imprint.txt");
}
else {
$imprint = "Imprint not set!";
}
echo nl2br(str_replace("{{imprint}}",$imprint,file_get_contents(__DIR__ . "/../../config/privacy.txt")));
?>
</article>
</main>
<?php include("footer.php") ?>
