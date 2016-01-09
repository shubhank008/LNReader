<?php
class LNScrape{
	private $DBHost;
	private $DBUser;
	private $DBHPass;
	private $DBDatabase;
	private $DBTableLN;
	
	Private $ConnectionHandler;
	
	Public function LNScrape(){
		$this->DBHost=("localhost");
		$this->DBUser=("TestUser");
		$this->DBHPass=("test");
		$this->DBDatabase=("LNScrape");
		$this->DBTableLN=("LN");	
	}
	Private function DBConnect(){
		$this->ConnectionHandler = new PDO("mysql:host=$this->DBHost;dbname=$this->DBDatabase", $this->DBUser, $this->DBHPass);
	}
	Private function DBClose(){
		$this->ConnectionHandler = null;	
	}
	Private function GetLN(){
		require_once("./simple_html_dom.php");
		$titles = array();
		$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=Category:Light_novel_(English)");
		foreach($html->find('html body div div div table tr td ul li a') as $element) {
			$titles[]=$element->plaintext;
		}
		return $titles;
	}
	private function contains_all($str,array $words) {
		if(!is_string($str))
			{ return false; }
	
		foreach($words as $word) {
			if(!is_string($word) || stripos($str,$word)===false)
				{ return false; }
		}
		return true;
	}
	private function getDescForTitle($title){ 
        $title = str_replace(" ","_",$title);
        $html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title"); 
		$html = $html->getElementById('mw-content-text');      
		preg_match("/<\/span><\/h2>(.*?)<\/p><p>(.*?)<\/p>/", $html, $abc);
		if (empty($abc)){
			preg_match("/<\/span><\/span><\/h2>(.*?)<\/p>/", $html, $abc);
		}
		if (empty($abc)){
			preg_match("/<\/span><\/h2>(.*?)<\/p>/", $html, $abc);
		}
		if (empty($abc)){
			#return($html);
			return("NO DESCRIPTION");
		}else{
			return($abc[0]);	
		}
		
	}
	private function parseThumbnail($thumbnail){
		if(!strpos($thumbnail,"/thumb/")){
			return $thumbnail;
		}
	}

	private function getImageForTitle($title, $Array=1){
			$title = str_replace(" ","_",$title);
			$images = array();
			$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
			foreach($html->find('html body div div div a.image img') as $element) {
				$ImgUrl = "http://www.baka-tsuki.org" . $element->src;
				if($ImgUrl == "http://www.baka-tsuki.org/project/images/5/53/Stalled.gif"){
					# Unwanted Img
				}else{
					$ImageArray[]=$ImgUrl;
				}
			}
			if($Array == 1){
				return $ImageArray;
			}else{
				foreach($ImageArray as $Img){
					$ImgString = $ImgString . $Img;	
					$ImgString = $ImgString . "\n";
				}
				return $ImgString;	
			}
	}

	public function test(){
		require_once("./simple_html_dom.php");
		$Title = "A Simple Survey";
		$Description = $this->getImageForTitle($Title,0);
		echo "Title: " . $Title . "<br>";
		echo $Description;
	}
	Public function Scrape(){
		header('Cache-Control: no-cache');
		ini_set('max_execution_time', 300);
		$TitleArray = $this->GetLN();
		$TitleArrayMaxCount = count($TitleArray);
		$TitleArrayDone = 0;
		foreach($TitleArray as $Title){
			$this->DBConnect();
			$query = $this->ConnectionHandler->prepare("SELECT * FROM  $this->DBTableLN WHERE(LNName='$Title')");
			$query->execute();
			$Rows = $query->fetchColumn();
			$query = null;
			if ($Rows > 0){
				#LN Already In DB
				echo "Old: " . $Title . "<br>";	
			}else{
				#New LN
				echo "New: " . $Title . "<br>";	
				$Description = $this->getDescForTitle($Title);
				$Description = $this->ConnectionHandler->quote($Description);
				$Description = strip_tags($Description);
				$Images = $this->getImageForTitle($Title,0);
				$Images = $this->ConnectionHandler->quote($Images);
				$Title = $this->ConnectionHandler->quote($Title);
				$query = $this->ConnectionHandler->prepare("INSERT INTO $this->DBTableLN (`LNName`, `LNDescription`, `LNImages`) VALUES ($Title, $Description, $Images);");
				$query->execute();
				$query = null;
				#sleep(5);
			}
			$TitleArrayDone = $TitleArrayDone + 1;
			echo "Done: " . $TitleArrayDone . "/" . $TitleArrayMaxCount;
			echo "<br>";
		}
	}
}
?>