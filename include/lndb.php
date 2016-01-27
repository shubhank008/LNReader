<?php
require_once "./simple_html_dom.php";

class LNDB
{
    public $lnInfoArr;
    private $lnDBLink;
    private $lnMainHTML;
    
    public function LNDB($lnTitle)
    {
        $lnTitle=str_replace(" ","_",$lnTitle);
        $this->lnDBLink="http://www.lndb.info/light_novel/";
        $this->setDOMForLN($lnTitle);
        $this->getLNInfo();
    }
    
    public function setDOMForLN($lnTitle)
    {
        $lnTitle=str_replace(" ","_",$lnTitle);
        $link=$this->lnDBLink.$lnTitle;
        $dom=new DOMDocument();
        $dom->loadHTMLFile($link);
        $this->lnMainHTML=$dom->saveHTML();
        
    }
    
    public function getLNInfo()
    {
        echo $this->lnMainHTML;
        //$html=$this->lnMainHTML;
        //echo $html;
        /*$infoField=$html->find("html body div.lightnovelcontent *");
        foreach($infoField as $ele)
        {
            echo $ele->tag."<br>";
        }*/
    }
}

$help=new LNDB("Absolute Duo");
?>