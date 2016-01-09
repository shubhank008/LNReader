<pre><?php
require_once("./functions.php");
$ABC = new LNScrape();
$out=$ABC->GetLN();
$i=0;
foreach($out as $LN) {
//$out = $ABC->getDescForTitle($LN);
//$out = str_replace("\"","'",getMUSynopsisForTitle($LN));
//$out = $ABC->getImageForTitle($LN);
print_r($out);
$i++;
if($i==30){break;}
}

//print_r($out);
?></pre>