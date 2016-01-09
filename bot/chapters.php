<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<?php

require '../simple_html_dom/simple_html_dom.php';
require '../db.php';

function getList(){
	$titles = array();
	$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=Category:Light_novel_(English)");
	foreach($html->find('html body div div div table tr td ul li a') as $element) {
	 $titles[]=$element->plaintext;
	}
	return $titles;
}

function getVolLinksForTitle($title){
$title = str_replace(" ","_",$title);
$volumes = array();
$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
foreach($html->find('html body div div div dl dd ul li a') as $element) {
if (strpos($element->href,'redlink=1') !== false OR strpos($element->href,'Magazine_') !== false OR strpos($element->href,'User:') !== false OR strpos($element->href,'_(') !== false) {
continue;
}
 $volumes[]=$element->href;
}
return $volumes;
}


function test($title,$volumes){
$title = str_replace(" ","_",$title);
$chps= array();
$i=0;
$decIncre = false;
$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
$source = $html->find('html body div div div dl dd ul');
if(count($source) < 5) {
return false;
}
foreach($source as $element) {
	if($decIncre) {
	if($i>0){
	$i=$i-1;
	}
	$decIncre=false;
	}
	$temp = array();
	//echo "i is $i".PHP_EOL;
	//Chapter Name
	if($volumes[$i] == '' || !$volumes[$i]) {
	//echo "skip $i".$volumes[$i];
	continue;
	}
	foreach($element->find('a') as $link) {
	//echo $link->plaintext.PHP_EOL;
	if($link->plaintext == "Registration Page" || $link->plaintext == "General Format/Style Guideline" || strpos($link->plaintext,"Editing Guidelines" === false)) { $decIncre=true;continue 2; }
	 $temp[]=trim($link->plaintext);
	 $chps[$volumes[$i]]['name'] = $temp;
	}
	//echo "i is $i, chapter name".PHP_EOL;
	//Chapter link
	$temp = array();
	foreach($element->find('a') as $link) {
	if (strpos($link->href,'redlink=1') !== false OR strpos($link->href,'Magazine_') !== false OR strpos($link->href,'User:') !== false OR strpos($link->href,'_(') !== false) {
	$temp[]="";
	$chps[$volumes[$i]]['url'] = $temp;
	continue;
	}
	if(stripos($link->href,"/project/index.php?title=")=== false){
	$temp[]="";
	$chps[$volumes[$i]]['url'] = $temp;
	continue;
	}
	 $temp[]=trim($link->href);
	 $chps[$volumes[$i]]['url'] = $temp;
	 //echo "i is $i, chapter link".PHP_EOL;
	}
	$i++;
}
return $chps;
}

function getVolListForTitle($title){
$title = str_replace(" ","_",$title);
$volumes = array();
$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
foreach($html->find('html body div div div#mw-content-text h3 span.mw-headline') as $element) {
$volumes[]=$element->plaintext;
}
$vol=array();
foreach($volumes as $volume){
if(stripos($volume,"Volume")=== false && stripos($volume,"Part")=== false){
//echo "$volume<br>";
continue;
}
$volume=str_replace("Full Text","",$volume);
$volume=str_replace("MOBI","",$volume);
$volume=str_replace("PDF","",$volume);
$volume=str_replace("-","",$volume);
$volume=str_replace("()","",$volume);
$volume=preg_replace("/\(([^\)]*)\)/","",$volume);
$vol[]=trim($volume);
}
return $vol;
}

$titles = getList();
foreach($titles as $title) {
echo "$title<br>";
print_r(getVolListForTitle($title));
//print_r(getVolLinksForTitle($title));
echo "<br><br>";
print_r(test($title,getVolListForTitle($title)));
echo "<br><br>";
//break;
}

?>