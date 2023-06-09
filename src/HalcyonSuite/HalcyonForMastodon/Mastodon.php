<?php
namespace HalcyonSuite\HalcyonForMastodon;

class Mastodon extends \Mastodon_api {
function __construct(){
$appSettings = parse_ini_file(__DIR__. '/../../../config/config.ini',true);
$this->datadir = __DIR__ . "/../../../data/instances";
$this->clientName = $appSettings["App"]["api_client_name"];
$this->clientRedirectUris = $appSettings["App"]["api_client_website"].'/auth';
$this->clientWebsite = $appSettings["App"]["api_client_website"];
$this->clientScopes = array('read','write','follow');
$this->instances = array();
$this->readInstances();
}
private function newInstance($domain) {
$res = $this->create_app($this->clientName,$this->clientScopes,$this->clientRedirectUris."?&host=".substr($domain,8),$this->clientWebsite);
if(isset($res['html']['client_id'])) {
$this->instances[$domain] = $res['html'];
file_put_contents($this->datadir."/".substr($domain,8).".txt",json_encode(array("client_id" => $res['html']['client_id'],"client_secret" => $res['html']['client_secret'])));
}
else {
header("Location: /login/?cause=domain");
die();
}
}
public function selectInstance($domain) {
$this->set_url($domain);
if(!$this->instanceExists($domain)) {
$this->newInstance($domain);
}
$this->set_client($this->instances[$domain]['client_id'],$this->instances[$domain]['client_secret']);
}
public function getInstance($domain) {
if($domain == base64_decode("aHR0cHM6Ly9nYWIuY29t") || $domain == base64_decode("aHR0cHM6Ly9nYWIuYWk=")) {
header("Location: /login/?cause=domain");
die();
}
$this->set_url($domain);
if (!$this->instanceExists($domain)) {
$this->newInstance($domain);
}
return array('client_id' => $this->instances[$domain]['client_id'],'client_secret' => $this->instances[$domain]['client_secret']);
}
public function instanceExists($domain) {
return isset($this->instances[$domain]);
}
private function readInstances() {
$instlist = array_diff(scandir($this->datadir),array("..",".",".htaccess"));
foreach($instlist as $index => $item) {
    if (is_dir($this->datadir."/".$item)) {
        continue;
    }
$itemname = "https://".substr($item,0,-4);
$this->instances[$itemname] = json_decode(file_get_contents($this->datadir."/".$item),true);
}
}
}
?>
