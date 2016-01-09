<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<pre>
<?php

require '../simple_html_dom/simple_html_dom.php';
require '../db.php';


$html = file_get_contents("http://www.baka-tsuki.org/project/index.php?title=Zero_no_Tsukaima&action=edit");
	$data = preg_split('/<textarea readonly="" accesskey="," id="wpTextbox1" cols="80" rows="25" style="" lang="en" dir="ltr" name="wpTextbox1">/', $html);
	$data = preg_split( '/<\/textarea/', $data[1] );
	$data = preg_split( '/(==.* by .*==)/', $data[0] ); //print_r($data);
	$data = preg_split( '/(==.*Project Staff.*==|==.*Translators.*==)/', $data[1] ); //print_r($data);
	$data = trim($data[0]);

//print_r($data);

$volumes = preg_split('/(\n|\r\n?){2,}/', $data);
//print_r($volumes);
$out = array();
$i=0;
foreach($volumes as $volume){
//print_r($volume);
$res = preg_match('/(==.*Volume.*\(\[)|(.*==.*Volume.*==)/', trim($volume), $title);
	if($res){
	$vol_title = $title[0];
	$vol_title = str_replace('=','',$vol_title);
	$vol_title = trim(str_replace('([','',$vol_title));
	//echo $vol_title.PHP_EOL;
	$out[$i]['title']=$vol_title;
	} else {
	continue;
	}
$res = preg_match_all('/(::\*.*)/', trim($volume), $chps);
	if($res){
	foreach($chps as $chap){
	$chaps = preg_replace('/(::\*|]]|\[\[)/','',$chap);
	$chapters = array();
	foreach($chaps as $chap) {
	$chap = explode('|',$chap); 
	if(count($chap)>1){
		$chap_title = $chap[1];
		$chap_url = $chap[0];
		$chap_url= "http://www.baka-tsuki.org/project/index.php?title=".str_replace(' ','_',$chap_url);
	$chapters[]=array('title'=>$chap_title,'url'=>$chap_url);	
	}
	$out[$i]['chapters'] = $chapters;
	}
	}
	}
$i++;
}
$output = array('count'=>count($out),'result'=>$out);
echo json_encode($output,128);
//print_r($out);
?>
</pre>