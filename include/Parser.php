<?php
require_once("./simple_html_dom.php");
define("ROOT_URL","//www.baka-tsuki.org");
define("ROOT_HTTPS","https:");
define("ROOT_HTTP","http:");
class Parser
{
    private $novel;
    
    private $doc;
    private $title;
    
    public function Parser($title) //everything is initiated by parser being the only public function
    {
        $this->title=$title;
        $this->initializeDoc();
        
        $this->bookModel=array();
        $this->novel=array();
        $this->bookCollectionModel=array();
        
        
        $this->parseNovelChapters();
        print_r($this->novel);
    }
    
    private function initializeDoc()//initializes document of the ln
    {
        $temptitle=str_replace(" ","_",$this->title);
        $link="http://www.baka-tsuki.org/project/index.php?title=".$temptitle;
        $this->doc=file_get_html($link);
    }
    
    private function parseNovelChapters()
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
                $tempBooks=$this->parseBookMethod1($h2);// MEthod one for much of most ln's

                if($tempBooks!=null && count($tempBooks)>0)
                {
                    $books[]=$tempBooks;
                }
     
                if(count($books)==0 || (count($tempBooks)==0 && $oneBookOnly))
                {
                    $tempBooks=$this->parseBookMethod2($h2);
                    if($tempBooks!=null && count($tempBooks)>0)
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
        $this->novel['title']=$this->title;
        foreach($books as $book)
        {
            $this->novel['bookCollection']=$this->validateNovelBooks($book);
        }
    }

    private function parseBookMethod1($h2)// Method 1
    {
        $books=array();
        
        $bookElement=$h2;
        $walkBook=true;
        $bookOrder=0;
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
                        echo $h3."<br>";
                        $books[]=$this->processH3($h3,$bookOrder);
                        ++$bookOrder;
                    }
                }
            }else if($bookElement->tag=="h3")
            {
                $books[]=$this->processH3($bookElement,$bookOrder);
                ++$bookOrder;
            }

        }while($walkBook);
        return $books;
    }
    
        
    private function parseBookMethod2($h2)
    {
        $books=array();
        
        $bookElement=$h2;
        $walkbook=true;
        $bookOrder=0;
        do
        {
            $bookElement=$bookElement->next_sibling();
            if(!$bookElement || $bookElement==null||$bookElement->tag=="h2")
            {
                $walkbook=FALSE;
            }else if($bookElement->tag=="p")
            {
                $book=array();
                
                if(strpos($bookElement->innertext,"href")!==FALSE)
                {
                    $book['title']=$this->sanitize($bookElement->innertext,true);
                }else
                {
                    $book['title']=$this->sanitize($bookElement->innertext,false);
                }
                
                $book['order']=$bookOrder;
                
                $chapterCollection=array();
                
                $walkChapter=true;
                $chapterOrder=0;
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
                        $chapters=$chapterElement->find("li");
                        foreach($chapters as $chapter)
                        {
                            $pageModels=$this->processLI($chapter,$chapterOrder);
                            foreach($pageModels as $p)
                            {
                                if($p!=null)
                                {
                                    $chapterCollection[]=$p;
                                    ++$chapterOrder;
                                }
                            }
                        }
                    }
                    
                    //no subchapter
                    if(count($chapterCollection)==0)
                    {
                        $links=$bookElement->find("a");
                        if(count($links)>0)
                        {
                            $link=$links[0];
                            $p=$this->processA($link->innertext,$link,$chapterOrder);
                            if(!empty($p) && $p!=null)
                            {
                                $chapterCollection[]=$p;
                                ++$chapterOrder;
                            }
                        }
                    }
                    $book['chapterCollection']=$chapterCollection;
                }while($walkChapter);
                $books[]=$book;
                ++$bookOrder;
            }
        }while($walkbook);
        return $books;
    }
    
    //Only have 1 book, chapter list is nested in ul/dl
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
                if(strpos($h2->innertext,"href")!==FALSE)
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
                    $pageModels=$this->processLI($chapter,$chapterOrder);
                    foreach($pageModels as $p)
                    {
                        if($p!=null)
                        {
                            $chapterCollection[]=$p;
                            ++$chapterOrder;
                        }    
                    }
                }
                
                $book['chapterCollection']=$chapterCollection;
                $books[]=$book;
                ++$bookOrder;
            }
        }while($walkBook);
        return $books;
    }
    
    private function parseChapters($bookElement)
    {
        $chapterCollection=array();
        
        $walkChapter=true;
        $chapterOrder=0;
        $chapterElement=$bookElement;
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
                    $pageModels=$this->processLI($chapter,$chapterOrder);
                    foreach($pageModels as $p)
                    {
                        if($p!=null && !empty($p))
                        {
                            $chapterCollection[]=$p;
                            ++$chapterOrder;
                        }
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

    private function validateNovelBooks($books)
    {
        $validatedBooks=array();
        
        $bookOrder=0;
        foreach($books as $book)
        {
            $validatedBook=array();
            $validatedChapters=$this->validateNovelChapters($book);
            
            if(count($validatedChapters)>0)
            {
                $validatedBook=$book;
                $validatedBook['chapterCollection']=$validatedChapters;
                $validatedBook['order']=$bookOrder;
                $validatedBooks[]=$validatedBook;
                ++$bookOrder;
            }
        }
        
        return $validatedBooks;
    }
    
    private function validateNovelChapters($book)
    {
        $chapters=$book['chapterCollection'];
        $validatedChapters=array();
        $chapterOrder=0;
        foreach($chapters as $chapter)
        {
            if(strpos($chapter['page'],"User:")!==FALSE || strpos($chapter['page'],"Special:BookSources")!==FALSE)
            {
                continue;
            }else
            {
                $chapter['order']=$chapterOrder;
                $validatedChapters[]=$chapter;
                ++$chapterOrder;
            }
               
        }
        return $validatedChapters;
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

    private function processLI($ele,$chapterOrder)
    {
        $pageModels=array();
        
        $links=$ele->find("a");

        if($links || $links!=null || count($links)>0)
        {
            
            foreach($links as $link)
            {
                if(strpos($link->href,"User_talk:")!==FALSE || strpos($link->href,"User:")!==FALSE) continue;
                
                $linkText=$link->plaintext;
                if($link->parent()!=$ele)
                {
                    $linkText=$ele->plaintext;
                }
                //echo $linkText."<br>";
                $p=$this->processA($linkText,$link,$chapterOrder);
                if($p!=null)
                {
                    $pageModels[]=$p;
                }
            }
                
        }
        return $pageModels;
    }
    
    private function processA($title,$link,$chapterOrder)
    {
        $href=$link->href;
        $p=array();
        $p['title']=$this->sanitize($title,FALSE);
        $p['order']=$chapterOrder;
        print_r($href);
        $pos=strpos($link->href,"&redlink=1");
        echo $pos."<br>";
        if(!empty($pos))
        {
            $p['page']="NULL";
            return $p;
        }
        
        if(strpos($link->class,"external text")!==FALSE)
        {
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
                                               
    private function processH3($ele,$bookOrder)//processes the h3 and parses the chapter list
    {
        $book=array();
        
        if(strpos($ele->innertext,"href")!==FALSE)
        {
            $book['title']=$this->sanitize($ele->innertext,TRUE);
        }else
        {
            $book['title']=$this->sanitize($ele->innertext,FALSE);
        }
        
        $book['order']=$bookOrder;
        
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
                        $p=array();
                        $p=$this->processA($link->innertext,$link,0);
                        if($p!=null)
                        {
                            $chapterCollection[]=$p;
                            break;
                        }
                    }
                }
            }
        }
        $book['chapterCollection']=$chapterCollection;
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
            if(strpos($url,"#")!==FALSE)
            {   
                $url=substr(0,strpos($url,"#"));
            }
        }
        
        if(strpos($url,".blogspot.")!==FALSE)
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

new Parser("Absolute Duo");
echo strpos("/project/index.php?title=Absolute_Duo:Volume_7_Epilogue&action=edit&redlink=1","&redlink=1");