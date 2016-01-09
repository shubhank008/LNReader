<?php

//require '../simple_html_dom/simple_html_dom.php';

$url="";
$html="";

function getMUSynopsisForTitle($title) {
global $url,$html;
$url = getUrlForSearch($title);
//echo $url;
if(!strpos($url,"https://www.mangaupdates.com/series.html?id=")){
$url = getUrlForSearch(str_replace("'","",$title));
}
if($url){
//Saved to mu_url.html file atleast
$html = str_get_html(getHtmlForUrl($url));
$data = $html->find('html body td.text div div.sContainer div.sMember div.sContent',0)->plaintext;
$data = strip_tags($data,"<br>");
return $data;}
else {
return "";
}
}

function getAltForTitle($title) {
global $url,$html;
$data = $html->find('html body td.text div div.sContainer div.sMember div.sContent',3)->innertext;
$data = strip_tags($data,"<br>");
return $data;
}

function getAuthForTitle($title) {
global $url,$html;
$data = $html->find('html body td.text div div.sContainer div.sMember div.sContent',18)->innertext;
$author = strip_tags($data,"<br>");
$data = $html->find('html body td.text div div.sContainer div.sMember div.sContent',19)->innertext;
$illus = strip_tags($data,"<br>");
return array($author,$illus);
}

function getGenreForTitle($title) {
global $url,$html;
$data = $html->find('html body td.text div div.sContainer div.sMember div.sContent',14)->innertext;
$data = strip_tags($data,"<br>");
$data = str_replace("Search for series of same genre(s)","",$data);
return $data;
}

function getDateForTitle($title) {
global $url,$html;
$data = $html->find('html body td.text div div.sContainer div.sMember div.sContent',20)->innertext;
$data = strip_tags($data,"<br>");
return $data;
}

function getUrlForSearch($title){
$html = searchTitle($title);
$html = str_get_html($html);
$data = $html->find('html body div table.text tbody tr td.text a',0)->plaintext;
/*if(strtoupper($title) == strtoupper($data) || strtoupper($title." (NOVEL)") == strtoupper($data)){
$data = $html->find('html body div table.text tbody tr td.text a',0)->href;
return $data;
} else {
return false;
}*/
$data = $html->find('html body div table.text tbody tr td.text a',0)->href;
return $data;
}

function searchTitle($title){
$url = "https://www.mangaupdates.com/series.html";
$fields = array(
						'act' => "series",
						'stype' => "title",
						'search' => $title,
						'x' => '26',
						'y' => '15',
						'session' => ''
				);
//url-ify the data for the POST
foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
rtrim($fields_string, '&');
//open connection
$ch = curl_init();
//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch,CURLOPT_POST, count($fields));
curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//execute post
$result = curl_exec($ch);
//close connection
curl_close($ch);
file_put_contents("mu_result.html",$result);
return $result;
}

function getHtmlForUrl($url){
$ch = curl_init();
//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//execute post
$result = curl_exec($ch);
//close connection
curl_close($ch);
file_put_contents("mu_url.html",$result);
return $result;
}

//getMUSynopsisForTitle("Zhan Long");
?>