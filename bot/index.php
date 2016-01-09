<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

<?php

require '../simple_html_dom/simple_html_dom.php';
require './mangaupdates.php';
require '../db.php';

function contains_all($str,array $words) {
    if(!is_string($str))
        { return false; }

    foreach($words as $word) {
        if(!is_string($word) || stripos($str,$word)===false)
            { return false; }
    }
    return true;
}
function parseThumbnail($thumbnail){
if(!strpos($thumbnail,"/thumb/")){
return $thumbnail;
}
$thumbnail = str_replace("/thumb","",$thumbnail);
$basename = basename($thumbnail);
$thumbnail = str_replace("/$basename","",$thumbnail);
return $thumbnail;
}
function getLastUpdatedTime($title){
$cmd = "select * from `titles` where `title`=\"$title\";";
//echo $cmd;
$result = mysql_query($cmd);
if (!$result) exit("The query did not succeded ".mysql_error());
else {
    while ($row = mysql_fetch_array($result)) {
    	//print_r($row);
    	//echo 'last updated time '. $row['updated'];
        return $row['updated'];
    }
}
}

//Get LN list
function getList(){
	$titles = array();
	$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=Category:Light_novel_(English)");
	foreach($html->find('html body div div div table tr td ul li a') as $element) {
	 $titles[]=$element->plaintext;
	}
	return $titles;
}
//Get LN Desc
function getDescForTitle($title){
	$title = str_replace(" ","_",$title);
	$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
	$desc = $html->find('html body div div div p',0)->plaintext;
	//$descTest = explode(' ',trim($desc));
	$words = explode (' ',"This project has been");
	if(contains_all($desc,$words)){
	$desc = $html->find('html body div div div p',1)->plaintext;
	}
	if(contains_all($desc,explode(' ',"series is also available in the following"))){
	$desc = "";
	}
	if(contains_all($desc,explode(' ',"revive this project by joining the translation"))){
	$desc = "";
	}
	if(contains_all($desc,explode(' ',"Light Novel Translation Project."))){
	$desc = "";
	}
	if(contains_all($desc,explode(' ',"is available in the following languages:"))){
	$desc = "";
	}
	/*if(in_array("This",$descTest)) {
	$desc = $html->find('html body div div div p',1)->plaintext;
	}*/
	return $desc;
}
//Get LN image
function getImageForTitle($title){
	$title = str_replace(" ","_",$title);
	$images = array();
	$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
	foreach($html->find('html body div div div a.image img') as $element) {
	$images[]=parseThumbnail("http://www.baka-tsuki.org".$element->src);
	}
	return $images;
}
//Get LN synopsis
function getSynopsisForTitle($title){
	$title = str_replace(" ","_",$title);
	$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title&action=edit");
	$data = $html->find('html body div div div textarea',0)->plaintext;
	$data = preg_split( "/(== Story Synopsis ==|==Story Synopsis==|==Synopsis==)/", $data );
	$data = preg_split( "/(==|==)/", $data[1] );
	$data = $data[0];
	$synopsis = strip_tags(html_entity_decode($data));
	$synopsis = str_replace("&amp;mdash;","-",$synopsis);
	if(strpos($synopsis,"http://")){
	$synopsis = preg_replace("/('''\[.*?\]''')|(\[.*?\])|('''.*?]''')/", '', $synopsis);
	}
	return $synopsis;
}

$titles = getList();



foreach($titles as $title){
mysql_query("SET NAMES utf8");
$lastTime = getLastUpdatedTime($title);
if(strlen($lastTime) > 4){
//echo 'updated time';
	$curDate = date('Y-m-d h:i:s', time());
	$diff = abs(strtotime($curDate) - strtotime($lastTime));
	$years = floor($diff / (365*60*60*24));
	$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
	$days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
	if($days < 14) {
	echo "$title was updated less than 14 days ago, skipping<br>";
	continue;
	}
}
$desc = $synopsis = str_replace("\"","'",getDescForTitle($title));
$images = getImageForTitle($title);
$synopsis = str_replace("\"","'",getMUSynopsisForTitle($title));
$altTitles = getAltForTitle($title);
$genres = strip_tags(getGenreForTitle($title));
$authDat = getAuthForTitle($title);
$author = $authDat[0];
$illus = $authDat[1];
$date = getDateForTitle($title);
$curDate = date('Y-m-d h:i:s', time());
//print_r($images);
$cmd = "INSERT INTO `titles` VALUES('',\"$title\",\"$altTitles\",\"$author\",\"$illus\",\"$genres\",\"$desc\",\"$synopsis\",\"$date\",'','baka-tsuki','','',\"$curDate\",null);";
$cmd = preg_replace( "/\r|\n/" ,"", $cmd);
$cmd = str_replace("<br>","",$cmd);
$cmd = str_replace("<br />"," , ",$cmd);
$cmd = str_replace("&nbsp;",",",$cmd);
//echo $cmd;
$sql = mysql_query($cmd);
		if (!$sql) {
				error_log($cmd." - ".mysql_error() . "\n",3,"./bakabot_error.txt");
				$data = array("result"=>"failed","error"=>mysql_error());
				exit(mysql_error());
				}
		else {
		}
		
//Insert Images
foreach($images as $image) {
$cmd = "REPLACE INTO `images` VALUES('',\"$title\",\"$image\",null);";
$sql = mysql_query($cmd);
		if (!$sql) {
				error_log($cmd." - ".mysql_error() . "\n",3,"./bakabotImages_error.txt");
				$data = array("result"=>"failed","error"=>mysql_error());
				//exit(mysql_error());
				}
		else {	
		}
}
echo "$title added to database.<br>";
//break;
}

?>