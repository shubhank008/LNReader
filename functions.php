<?php
class LNScrape{
	private $DBHost;
	private $DBUser;
	private $DBHPass;
	private $DBDatabase;
	private $DBTableLN;
	
	Private $ConnectionHandler;
	
	Public function LNScrape(){ # Defines variable values.
		require_once("./simple_html_dom.php");
		$this->DBHost=("localhost"); #DB Location
		$this->DBUser=("TestUser"); #DB Username
		$this->DBHPass=("test"); #DB Password
		$this->DBDatabase=("LNScrape"); #Name of database
		$this->DBTableLN=("LN"); #Name of table to put LN's.
	}
	Private function DBConnect(){ # Creates a new connection to DB.
		$this->ConnectionHandler = new PDO("mysql:host=$this->DBHost;dbname=$this->DBDatabase", $this->DBUser, $this->DBHPass);
	}
	Private function DBClose(){ # Closes connection to DB.
		$this->ConnectionHandler = null;	
	}
	Private function GetLN(){ # Gets a list of LN's an returns in array.
		require_once("./simple_html_dom.php");
		$titles = array();
		$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=Category:Light_novel_(English)");
		foreach($html->find('html body div div div table tr td ul li a') as $element) {
			$titles[]=$element->plaintext;
		}
		return $titles;
	}
	private function getDescForTitle($title){ # Gets description for each LN.
        $title = str_replace(" ","_",$title);
        $html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title"); 
		$html = $html->getElementById('mw-content-text'); # Gets all html code inside mw-content-text div.  
		preg_match("/<\/span><\/h2>(.*?)<\/p><p>(.*?)<\/p>/", $html, $desc); # Tries to get description with this regex.
		if (empty($abc)){ # If the above failes it tries again.
			preg_match("/<\/span><\/span><\/h2>(.*?)<\/p>/", $html, $desc);
		}
		if (empty($abc)){# If the above failes it tries again.
			preg_match("/<\/span><\/h2>(.*?)<\/p>/", $html, $desc);
		}
		if (empty($abc)){ # If all the above failes it will return "NO DESCRIPTION"
			#return($html);
			return("NO DESCRIPTION");
		}else{
			return($desc[0]); # First value in array is always right.
		}
		
	}
	private function getImageForTitle($title, $Array=1){ # Gets a list of img links for LN, as default it will output an array but you can tell it to output a large string where each links is seperated with a new line.
			$title = str_replace(" ","_",$title);
			$images = array();
			$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title");
			foreach($html->find('html body div div div a.image img') as $element) {
				$ImgUrl = "http://www.baka-tsuki.org" . $element->src;
				if($ImgUrl == "http://www.baka-tsuki.org/project/images/5/53/Stalled.gif"){ #This is one of the status images that we dont need.
					# Unwanted Img
				}else{
					# Wanted Img.
					$ImageArray[]=$ImgUrl;
				}
			}
			if($Array == 1){
				return $ImageArray;
			}else{
				foreach($ImageArray as $Img){
					$ImgString = $ImgString . $Img;	
					$ImgString = $ImgString . "\n"; # Inserts newline.
				}
				return $ImgString;	
			}
	}
	Public function Scrape(){ # Main Function.
		header('Cache-Control: no-cache'); # Don't allow caching.
		ini_set('max_execution_time', 300); # Tries to make php timeout longer becuase script takes a while to run.
		$TitleArray = $this->GetLN(); # Gets array of LN names.
		$TitleArrayMaxCount = count($TitleArray); # Counts how many LN's in array.
		$TitleArrayDone = 0;
		foreach($TitleArray as $Title){ # For each LN in array.
			$this->DBConnect(); # Opens DB connection.
			$query = $this->ConnectionHandler->prepare("SELECT * FROM  $this->DBTableLN WHERE(LNName='$Title')"); #Tries to find current LN in DB.
			$query->execute(); # Executes DB query.
			$Rows = $query->fetchColumn(); # Fetches the number of rows the query returned.
			$query = null;
			if ($Rows > 0){ # if the num of rows returned is bigger than 0 then the LN is already in DB and doesn't need to be processed again.
				#LN Already In DB
				#echo "Old: " . $Title . "<br>";	
			}else{
				#New LN
				#echo "New: " . $Title . "<br>";	
				$Description = $this->getDescForTitle($Title); # Gets description for current LN.
				$Description = $this->ConnectionHandler->quote($Description); # Escapes the description incase it contains quotation marks.
				$Description = strip_tags($Description); # Strips any leftover html tags from description.
				$Images = $this->getImageForTitle($Title,0); # Gets list of imges for current LN.
				$Images = $this->ConnectionHandler->quote($Images); # Again escapes the images.
				$Title = $this->ConnectionHandler->quote($Title); # Escapes the LN title.
				$query = $this->ConnectionHandler->prepare("INSERT INTO $this->DBTableLN (`LNName`, `LNDescription`, `LNImages`) VALUES ($Title, $Description, $Images);"); # MYSQL query.
				$query->execute(); # Executes query.
				$query = null;
				#sleep(5);
			}
			#Simple counter.
			$TitleArrayDone = $TitleArrayDone + 1;
			echo "Done: " . $TitleArrayDone . "/" . $TitleArrayMaxCount;
			echo "<br>";
		}
	}
}
?>