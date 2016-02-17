<?php
//require '../db.php';
require_once "./simple_html_dom.php";
$siteMainLink="http://www.baka-tsuki.org";
$siteLink="http://www.baka-tsuki.org/project/index.php";

class BakaTsuki{
    
    private $lnLink;
    private $lnMainHTML;
    private $lnEditHTML;
    
    public function BakaTsuki($title)
    {
        $title=str_replace(" ","_",$title);
        $this->setLN($title);
        
        print_r($this->getVolumeDataForLN());
        
    }
    public function setLN($title)
    {
        global $siteLink;
        $this->lnLink="$siteLink?title=$title";
        $this->lnMainHTML=file_get_html($this->lnLink);
        $editLink=$this->lnLink."&action=edit";
        $html=file_get_contents($editLink);
        $this->lnEditHTML=$this->getEditText($html);
    }
    
    private function getEditText($html)
    {
            $data=preg_split('/<textarea.*>/', $html);
            $data = preg_split( '/<\/textarea>/', $data[1]);  
            return $data[0];
    }
    
    ###################################################
	#########BAKATSUKI CRAWLING FUNCTIONS #############
	###################################################

    #############LN Informations################
    //Get LN Desc
    public function getDescForTitle(){ # Gets description for each LN.
        $html = $this->lnMainHTML; 
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
			//return("NO DESCRIPTION");
			return $this->tryDescForTitle();
		}else{
			return($desc[0]); # First value in array is always right.
		}
		
	}
    
	//Fall back method to try and get Desc if above fails
	private function tryDescForTitle(){
        $html = $this->lnMainHTML;
        $desc = $html->find('html body div div div p',0)->plaintext;
        //$descTest = explode(' ',trim($desc));
        $words = explode (' ',"This project has been");
        if($this->contains_all($desc,$words)){
        $desc = $html->find('html body div div div p',1)->plaintext;
        //echo "DEBUG inact proj";
        }
        if($this->contains_all($desc,explode(' ',"series is also available in the following"))){
        $desc = "NO DESCRIPTION LAN";
        $desc = $html->find('html body div div div p',1)->plaintext;
        }
        if($this->contains_all($desc,explode(' ',"revive this project by joining the translation"))){
        $desc = "NO DESCRIPTION REV";
        $desc = $html->find('html body div div div p',2)->plaintext;
        }
        if($this->contains_all($desc,explode(' ',"Abandonment Policy"))){
        $desc = "NO DESCRIPTION ABAN";
        $desc = $html->find('html body div div div p',2)->plaintext;
        }
        if($this->contains_all($desc,explode(' ',"Light Novel Translation Project."))){
        $desc = "NO DESCRIPTION TRA";
        $desc = $html->find('html body div div div p',1)->plaintext;
        }
        if($this->contains_all($desc,explode(' ',"is available in the following languages:"))){
        $desc = "NO DESCRIPTION LAN2";
        //$desc = $html->find('html body div div div p',1)->plaintext;
        }
        /*if(in_array("This",$descTest)) {
        $desc = $html->find('html body div div div p',1)->plaintext;
        }*/
        return $desc;
	}
    
    
    public function getProjectState()// Project Status Working
    {
        $lnData=$this->lnEditHTML;
        $result=array();
        $data=preg_match("/^(\{\{[A-Za-z]*\|[A-Za-z]*\}\})/",$lnData,$matches);
        $status=substr($matches[0],2,-2);
        return explode("|",$status)[1];
    }

    public function getLNInfo()//LN Info Working
    {
        $lnData=$this->lnEditHTML;
        if(strpos($lnData,"Series Information")!=FALSE)
        {
            $data=preg_split('/(==\s?Series Information\s?==)/',$lnData,2);
            $data=preg_split('/(==.*==)/',$data[1],2)[0];
            $data=preg_split('/(\*)/',$data);

            $infoArr=array();
            foreach($data as $info)
            {
                switch($info)
                {
                    case(strpos($info,"Genre")!=FALSE):
                        $infoArr['genre']=explode(":",$info)[1];
                        break;    
                    case(strpos($info,"Original Title")!=FALSE):
                        $infoArr['orig_title']=explode(":",$info)[1];
                        break;
                    case(strpos($info,"Author")!=FALSE):
                        $infoArr['author']=explode(":",$info)[1];
                        break;
                    case(strpos($info,"Illustrator")!=FALSE):
                        $infoArr['illustrator']=explode(":",$info)[1];
                        break;
                    case(strpos($info,"Published Volume")!=FALSE):
                        $infoArr['publish_vol']=explode(":",$info)[1];
                        break;
                    case(strpos($info,"Series Status")!=FALSE):
                        $infoArr['series_status']=explode(":",$info)[1];
                        break;
                }            
            }
            return $infoArr;
        }else return FALSE;
        
   }
    
    //Get LN synopsis
    public function getSynopsisForTitle(){
        /*$html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=$title&action=edit");
        $data = $html->find('html body div div div textarea',0)->plaintext;
        $data = preg_split( "/(== Story Synopsis ==|==Story Synopsis==|==Synopsis==)/", $data );
        $data = preg_split( "/(==|==)/", $data[1] );
        $data = $data[0];
        $synopsis = strip_tags(html_entity_decode($data));
        $synopsis = str_replace("&amp;mdash;","-",$synopsis);
        if(strpos($synopsis,"http://")){
        $synopsis = preg_replace("/('''\[.*?\]''')|(\[.*?\])|('''.*?]''')/", '', $synopsis);
        }*/

        $html = $this->lnMainHTML;
        $container=$html->find("div#mw-content-text *");
        $synopsis="";

        $nextItemIsDesc=false;
        foreach($container as $content)
        {
            if($nextItemIsDesc)
            {
                $synopsis=$content->plaintext;
                break;
            }
            if($content->tag=="h2" && $content->first_child()->id=="Story_Synopsis")
            {
                $nextItemIsDesc=true;
            }
        }
        return $synopsis;
    }

	//Get all images from LN detail page
	public function getImageForTitle(){
        $images = array();
        $html = $this->lnMainHTML;
        foreach($html->find('html body div div div a.image img') as $element) {
        $imgurl = "http://www.baka-tsuki.org".$element->src;
        if($imgurl!="http://www.baka-tsuki.org/project/images/5/53/Stalled.gif"){
        $images[]=$this->parseThumbnail($imgurl);
        }
        }
        return $images;
	}
    

    ################Volume Informations################
    //Parser.php handles volume and chapter parsing
    ################Chapter Informations#########################
    public function getChapterContentForLink($link)
    {
        $html=file_get_html($link);
        $data=$html->find('html body div#mw-content-text',0);

        $chapConArr=array();
        foreach($data->childNodes() as $element)
        {
            if($element->tag=='h2' || $element->tag=='comment' || $element->tag=='table') continue; //removes extras from the main content 

            if($element->tag=='h3') //For Part Titles
            {
                if($element->children() && $element->firstChild()->tag=='span')
                {
                    $chapConArr[]['h3']=$element->firstChild()->innertext;
                    continue;
                }
                $chapConArr[]['h3']=$element->innertext;
                continue;
            }

            if($element->tag=='p')//The main text of the chapter
            {
                if($element->children())
                {
                    $innerText=str_replace("<br>","\n",$element->innertext);
                    $chapConArr[]['p']=$innerText;
                    continue;
                }

                $chapConArr[]['p']=$element->innertext;
                continue;
            }

            if($element->tag=='div' && $element->class=='thumb tright')//image thunmnail shown in between chapter contents
            {
                $arr=array();
                $thumbCon=$element->firstChild()->firstChild();
                if($thumbCon->tag=='a' && $thumbCon->class='image')
                {
                    $arr['imgLink']=$thumbCon->href;
                    $img=$thumbCon->firstChild();
                    $arr['imgThumbLink']=$img->src;
                    $arr['imgTitle']=$img->alt;
                    $arr['imgSrcSet']=$img->srcset;
                }
                $chapConArr[]['image']=$arr;
            }
        }
        return $chapConArr;
            /*
            Return Type Format: As per the position crawled from the page
            Array(
                Array(
                    'h3'='text'//for title header (Part Titles)
                )
                Array(
                    'p'='text' //for main text
                )
                Array(
                    'image'=Array(
                                'imgLink'='http:link', //main link tag from <a href>attribute
                                'imgThumbLink'='http:link', //image link for the thumb shown in the page
                                'imgTitle'='title', //title of image taken from alt
                                'imgSrcSet'='links' //alternate image links
                            )
                )
            )
            */
    }

    ################Misscelanous###################
    public function getUpdatesForLN()
    {
        $updatesText="";
        
        $data=$this->lnEditHTML;
        $data=preg_split("/(==[\s]?Updates[\s]?==)/",$data);
        $data=preg_split("/(==.*==)/",$data[1])[0];
        
        $text1="Past Updates Can Be Found";
        $text2="All Updates Can Be Found";
        $text3="Older Updates Can Be Found";
        if($this->contains_all($data,explode(" ",$text1)) || $this->contains_all($data,explode(" ",$text2)))
        {
            $html=file_get_contents($this->lnLink.":Updates&action=edit");
            $data=$this->getEditText($html);
            $updatesText=$data;
        }else if($this->contains_all($data,explode(" ",$text3)))
        {
            $html=file_get_contents($this->lnLink.":_Updates&action=edit");
            $data=$this->getEditText($html);
            $updatesText=$data;        
        }else
        {
            $updatesText=$data;
        }
        
        print_r($updatesText);

    }

	########################################
	##### UTILITY FUNCTIONS ################
	########################################  
    private function contains_all($str,array $words) {
        if(!is_string($str))
            { return false; }

        foreach($words as $word) {
            if(!is_string($word) || stripos($str,$word)===false)
                { return false; }
        }
        return true;
    }

    private function parseThumbnail($thumbnail){
        if(!strpos($thumbnail,"/thumb/")){
        return $thumbnail;
        }
        $thumbnail = str_replace("/thumb","",$thumbnail);
        $basename = basename($thumbnail);
        $thumbnail = str_replace("/$basename","",$thumbnail);
        return $thumbnail;
    }

}


function getLN(){
    $titles = array();
    $html = file_get_html("http://www.baka-tsuki.org/project/index.php?title=Category:Light_novel_(English)");
        foreach($html->find('html body div div div table tr td ul li a') as $element) {
        $titles[]['title']=$element->plaintext;
        }
    return $titles;
}
