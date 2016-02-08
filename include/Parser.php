<?php
require_once("./simple_html_dom.php");
define("ROOT_URL","//www.baka-tsuki.org");
define("ROOT_HTTPS","https:");
define("ROOT_HTTP","http:");
class Parser
{
    private $bookModel;
    private $novel;
    private $bookCollectionModel;
    
    private $doc;
    private $title;
    
    public function Parser($title)
    {
        $this->title=$title;
        $this->initializeDoc();
        
        $this->bookModel=array();
        $this->novelModel=array();
        $this->bookCollectionModel=array();
        
        
        $this->parseNovelChapters();
    }
    
    private function initializeDoc()
    {
        $temptitle=str_replace(" ","_",$this->title);
        $link="http://www.baka-tsuki.org/project/index.php?title=".$temptitle;
        $this->doc=file_get_html($link);
    }
    
    public function parseNovelChapters()
    {
        $html=$this->doc;
        $books=array();
        $oneBookOnly=FALSE;

        $h2s=$html->find("h1,h2");
        foreach($h2s as $h2)
        {
            $sp=$h2->find("span");
            if(count($sp)>0)
            {
                $containsBy=FALSE;
                foreach($sp as $span) 
                {
                    if($this->validateH2($this->title,$span))
                    {
                        $containsBy=TRUE;
                        break;
                    }
                }
                if(!$containsBy) continue;

                $tempBooks=$this->parseBookMethod1($h2);
                
                if(!$tempBooks!=null && count($tempBooks)>0)
                {
                    $books[]=$tempBooks;
                }
                
                if(count($books)==0 || (count($tempBooks)==0 && $oneBookOnly))
                {
                    $tempBooks=$this->parseBookMethod2($h2);
                    if(!$tempBooks!=null && count($tempBooks)>0)
                    {
                        $oneBookOnly=TRUE;
                        $books[]=$tempBooks;
                    }
                }
                
                if(count($books)==0 ||($oneBookOnly && count($tempBooks)==0))
                {
                    $tempBooks=$this->parseBooksMethod3($h2);
                    if($tempBooks!=null & count($tempBooks)>0)
                    {
                        $oneBookOnly=TRUE;
                        $books[]=$tempBooks;
                    }
                }

            }
        }
    }

    private function parseBookMethod1($h2)
    {
        $books=array();
        
        $bookElement=$h2;
        $walkBook=true;
        do
        {
            $bookElement=$bookElement->next_sibling();
            if(!$bookElement || $bookElement==null || $bookElement->tag=="h2")
            {
                $walkBook=false;
            }else if($bookElement->tag!="h3")
            {
                $h3s=$bookElement->find("h3");
                if($h3s!=null && $h3 && count($h3s)>0)
                {   
                    foreach($h3s as $h3)
                    {
                        $books[]=$this->processH3($h3);
                    }
                }
            }else if($bookElement->tag=="h3")
            {
                $books[]=$this->processH3($bookElement);
            }
        }while($walkBook);
        return $books;
    }
    
        
    private function parseBookMethod2($h2)
    {
        $books=array();
        
        $bookElement=$h2;
        $walkbook=true;
        $bookorder=0;
        do
        {
            $bookElement=$bookElement->next_sibling();
            if(!$bookElement || $bookElement==null||$bookElement->tag=="h2")
            {
                $walkbook=FALSE;
            }else if($bookElement->tag=="p")
            {
                $book=array();
                
                if(strpos($bookElement->innertext,"href")!=FALSE)
                {
                    $book['title']=$this->sanitize($bookElement->innertext,true);
                }else
                {
                    $book['title']=$this->sanitize($bookElement->innertext,false);
                }
            
                $chapterCollection=array();
                
                $walkChapter=true;
                $chapterElement=$bookElement;

                
                do
                {
                    $chapterElement=$chapterElement->next_sibling();
                    if(!$chapterElement || $chapterElement==null)
                    {
                        $walkChapter=FALSE;
                    }else if($chapterElement->tag=="p")
                    {
                        $walkChapter=FALSE;
                    }else if($chapterElement->tag=="dl" || $chapterElement->tag=="ul" || $chapterElement->tag=="div")
                    {
                        $array=array();
                        $chapters=$chapterElement->find("li");
                        foreach($chapters as $chapter)
                        {
                            $array=array();
                            $array=$this->processLI($chapter);
                            foreach($array as $p)
                            {
                                $chapterCollection[]=$p;
                            }
                        }
                    }
                    
                    if(count($chapterCollection)==0)
                    {
                        $links=$bookElement->find("a");
                        if(count($links)>0)
                        {
                            $link=$links[0];
                            $p=$this->processA($link->innertext,$link);
                            if(!empty($p) || $p!=null)
                            {
                                $chapterCollection[]=$p;
                            }
                        }
                    }
                    $book['chapterCollection']=$chapterCollection;
                }while($walkChapter);
                $books[]=$book;
            }
        }while($walkbook);
        return $books;
    }
    
    private function parseBooksMethod3($h2)
    {
        $books=array();
        
        $bookElement=$h2;
        $walkBook=true;
        $bookOrder=0;
        
        do
        {
            $bookElement=$bookElement->next_sibling();
            if($bookElement == null || empty($bookElement) || $bookElement->tag=="h2")
            {
                $walkBook=false;
            }else if($bookElement->tag=="ul" || $bookElement->tagName=="dl")
            {
                $book=array();
                if(strpos($h2->innertext,"href")!=FALSE)
                {
                    $book['title']=$this->sanitize($h2->innertext,true);
                }else
                {
                    $book['title']=$this->sanitize($h2->innertext,false);
                }
                
                $book['order']=$bookOrder;
                
                $chapterCollection=array();
                //parse the chapters
                $chapterOrder=0;
                $chapters=$bookElement->find("li");
                foreach($chapters as $chapter)
                {
                    $pages=$this->processLI($chapter);
                    foreach($pages as $page)
                    {
                        $chapterCollection[]=$page;
                        ++$chapterOrder;
                    }
                }
                
                $book['chapterCollection']=$chapterCollection;
                $books[]=$book;
                ++$bookOrder;
            }
        }while($walkBook);
        print_r($books);
        return $books;
    }
    
    private function parseChapters($bookElement)
    {
        $walkChapter=true;
        $chapterOrder=0;
        $chapterElement=$bookElement;
        $chapterCollection=array();
        do
        {
            $chapterElement=$chapterElement->next_sibling();
            if($chapterElement==null
                ||$chapterElement->tag=="h2"
                ||$chapterElement->tag=="h3"
                ||$chapterElement->tag=="h4")
            {
                $walkChapter=FALSE;
            }else
            {
                $chapters=$chapterElement->find("li");
                foreach($chapters as $chapter)
                {
                    $tempArr=$this->processLI($chapter);
                    if($tempArr && count($tempArr)>0)
                    {
                        $chapterCollection[]=$tempArr;
                    }
                }
            }
        }while($walkChapter);
        return $chapterCollection;
    }
    ##############################
    ##############################
    ######Helper Functions########
    ##############################
    private function validateH2($title,$ele) 
    {
        $rules = array($title,"_by", "Full_Text", "_Series", "_series", "Side_Stor", "Short_Stor", "Parody_Stor");
        foreach($rules as $rule)
        {
            if(strpos($ele->id,$rule)!==FALSE) return TRUE;
        }
        return false;
    }

    private function sanitize($title,$isAggressive)
    {
        //echo "Before: ".$title."<br>";
        $title=preg_replace("/<.+?>/","",$title);
        $title=preg_replace("/\\[.+?\\]/","",$title);
        $title=preg_replace("/- PDF/","",$title);
        $title=preg_replace("/\\(PDF\\)/","",$title);
        $title=preg_replace("/- (Full Text)/","",$title);
        $title=preg_replace("/- \\(.*Full Text.*\\)/","",$title);
        $title=preg_replace("/\\(.*Full Text.*\\)/","",$title);
        //echo "After: ".$title."<br>";
        if($isAggressive)
        {
            $title=preg_replace("/^(.+?)[(\\[].*$/","$1",$title);
            //echo "Afrer Aggressive: ".$title."<br>";
        }
        return trim($title);

    }

    private function processLI($ele)
    {
        $links=$ele->find("a");

        $page=array();
        if($links || $links!=null || count($links)>0)
        {
            
            foreach($links as $link)
            {
                if(strpos($link->href,"User_talk:")!=FALSE || strpos($link->href,"User:")!=FALSE) continue;
                
                $linkText=$link->plaintext;
                if($link->parent()!=$ele)
                {
                    $linkText=$ele->plaintext;
                }
                //echo $linkText."<br>";
                $page[]=$this->processA($linkText,$link);
            }
                
        }
        return $page;
    }
    
    private function processA($title,$link)
    {
        $href=$link->href;
        if(strpos($href,"&redlink=1")) return null;
        
        $p=array();
        $p['title']=$this->sanitize($title,FALSE);
        
        if(strpos($link->class,"external text")!=FALSE)
        {
            echo "TRUE";
            $p['external']="TRUE";
            $p['page']=$this->sanitizeBaseURL($href,false);
        }else
        {
            $p['external']="FALSE";
            $temppage=$this->normalizeInternalURL($href);
            $p['page']=$temppage;
        }
        return $p;
    }
                                               
    private function processH3($ele)
    {
        $book=array();
        
        if(strpos($ele->innertext,"href")!=FALSE)
        {
            $book['title']=$this->sanitize($ele->innertext,TRUE);
        }else
        {
            $book['title']=$this->sanitize($ele->innertext,FALSE);
        }
        
        $chapterCollection=$this->parseChapters($ele);
        if(count($chapterCollection)==0)
        {
            $bookLinks=$ele->find("a");
            if($bookLinks && $bookLinks!=null)
            {
                foreach($bookLinks as $links)
                {
                    if(strpos($links->has_attribute("href"),ROOT_URL)==0)
                    {
                        $p=$this->processA($link->innertext,$link);
                        $chapterCollection[]=$p;
                    }
                }
            }
        }
        $book['chapterCollecton']=$chapterCollection;
        
        return $book;
    }
    
    private function normalizeInternalURL($url)
    {
        $url=str_replace("/project/index.php?title="," ",$url);
        $url=str_replace(ROOT_HTTPS," ",$url);
        $url=str_replace(ROOT_HTTP," ",$url);
        $url=str_replace(ROOT_URL," ",$url);
        return $url;
    }
    private function sanitizeBaseURL($url,$stripAnchor)
    {
        if($stripAnchor)
        {
            if(strpos($url,"#")!=FALSE)
            {   
                $url=substr(0,strpos($url,"#"));
            }
        }
        
        if(strpos($url,".blogspot.")!=FALSE)
        {
            $url=str_replace("?m=1","");
            if(strpos($url,".blogspot.com/")==FALSE)
            {
                $url=preg_replace("/blogspot.[a-z]+\//","blogspot.com/",1);
            }
        }
        return $url;
    }
}

new Parser("Fate/Apocrypha");