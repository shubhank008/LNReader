<?php
require_once("include/bakatsuki.php");
require_once("./simple_html_dom.php");
require_once("./mangaupdates.php");


class LNScrape extends BakaTsuki{
	private $DBHost;
	private $DBUser;
	private $DBHPass;
	private $DBDatabase;
	private $DBTableLNList;
    private $DBTableVolList;
    private $DBTableChapList;
    private $DBTableVolIllusList;
    
	
	Private $ConnectionHandler;
	
	Public function LNScrape(){ # Defines variable values.
		$this->DBHost=("localhost"); #DB Location
		$this->DBUser=("lnReaderUser"); #DB Username
		$this->DBHPass=("lnReaderPass"); #DB Password
		$this->DBDatabase=("db_LNReader"); #Name of database
		$this->DBTableLNList=("tbl_LNList"); #Name of table to put LN's.
        $this->DBTableVolList=("tbl_volumeList");#Table For Volume List
        $this->DBTableChapList=("tbl_chapterList");#Table For Chapter List
        $this->DBTableVolIllusList=("tbl_volIllusList");#Table For Volume Illustrations link
        $this->DBConnect(); # Opens DB connection.

    }
	private function DBConnect(){ # Creates a new connection to DB.
		$this->ConnectionHandler = new PDO("mysql:host=$this->DBHost;dbname=$this->DBDatabase", $this->DBUser, $this->DBHPass);
	}
	private function DBClose(){ # Closes connection to DB.
		$this->ConnectionHandler = null;	
	}
    
	private function Scrape(){ # Main Function.
		header('Cache-Control: no-cache'); # Don't allow caching.
		ini_set('max_execution_time', 300); # Tries to make php timeout longer becuase script takes a while to run.
		$TitleArray = $this->GetLN(); # Gets array of LN names.
		$TitleArrayMaxCount = count($TitleArray); # Counts how many LN's in array.
		$TitleArrayDone = 0;
		foreach($TitleArray as $Title){ # For each LN in array.
			$query = $this->ConnectionHandler->prepare("SELECT * FROM  $this->DBTableLNList WHERE(ln_title='$Title')"); #Tries to find current LN in DB.
			$query->execute(); # Executes DB query.
			$Rows = $query->fetchColumn(); # Fetches the number of rows the query returned.
			$query = null;
			if ($Rows > 0){ # if the num of rows returned is bigger than 0 then the LN is already in DB and doesn't need to be processed again.
				#LN Already In DB
				#echo "Old: " . $Title . "<br>";	
			}else{
				#New LN
				#echo "New: " . $Title . "<br>";
                $columnArr=$this->getColumnsForTable($this->DBTableLNList);
                $columnArr['ln_id']=$this->getID($Title);
                
                $Description = $this->getDescForTitle($Title); # Gets description for current LN.
				$columnArr['ln_desc'] =htmlspecialchars($Description); # Strips any leftover html tags from description.
                //$Images = $this->getImageForTitle($Title,0); # Gets list of imges for current LN.
				//$columnArr['ln_img'] = $this->ConnectionHandler->quote($Images); # Again escapes the images.
                
                $columnArr['ln_title'] =trim($Title); # Escapes the LN title.
                
                $sql='';
                foreach($columnArr as $key=>$val)
                {
                    if(empty($val) || $val=='' || strlen($val)==0) continue;
                    
                    $sql.="$key='$val',";
                }
                $sql=trim($sql,',');

                
				$query = $this->ConnectionHandler->prepare("INSERT INTO $this->DBTableLNList SET $sql"); # MYSQL query.
                $query->execute();# Executes query
				$query = null;
				#sleep(5);
			}
			#Simple counter.
			$TitleArrayDone = $TitleArrayDone + 1;
			echo "Done: " . $TitleArrayDone . "/" . $TitleArrayMaxCount;
			echo "<br>";
		}
	}
	
	
	
	########################################
	##### UTILITY FUNCTIONS ################
	########################################    
    private function getColumnsForTable($table)
    {
        $query=$this->ConnectionHandler->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$this->DBDatabase' AND TABLE_NAME = '$table'");
        $query->execute();
        $rows=$query->fetchAll();
        $columnsArr=array();
        foreach($rows as $row)
        {
            $key=$row['COLUMN_NAME'];
            $columns[$key]='';
        }
        return $columns;
    }
    
    private function getID($title)
    {
        
        if(strlen($title)<=10)
        {
            $title=trim(str_replace(" ","_",$title));
        }else
        {
            $title=str_replace(" ","_",trim(substr($title,0,10)));
        }
        
        return $title."_".date('YmdGis');
    }
}

$help=new LNScrape();

?>
