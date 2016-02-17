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
    
    private $bannedSeries;
    public function Parser($title) //everything is initiated by parser being the only public function
    {
        $this->bannedSeries=array("Kino no Tabi","White_Album_2_Omake","Maru-MA","Mayo_Chiki!","Persona x Detective Naoto");
        $this->title=$title;
        
        foreach($this->bannedSeries as $series)
        {
            if($series == $title)
            {
                return null;
            }
        }
        $this->initializeDoc();
        
        $this->novel=array();
        
        
        $this->parseNovelChapters();
        return $this->novel;
    }
    
    private function initializeDoc()//initializes document of the ln
    {
        $temptitle=str_replace(" ","_",$this->title);
        $link="http://www.baka-tsuki.org/project/index.php?title=".$temptitle;
        $this->doc=file_get_html($link);
    }
    
    private function parseNovelChapters()//Main Function For Parse
    {
        $html=$this->doc;
        
        $novelOrder=0;
        $books=array();
        $oneBookOnly=FALSE;
        $h2s=$html->find("h1,h2");
        $this->novel['title']=$this->title;
        
        foreach($h2s as $h2)//loops around h1 and h2
        {
            $sp=$h2->find("span");
            if(count($sp)>0)
            {
                
                $containsBy=FALSE;
                foreach($sp as $span) 
                {
                    if($this->validateH2($this->title,$span)) //checks if the pattern of h2 matches the title of the book
                    {
                        $containsBy=TRUE;
                        break;
                    }
                }
                if(!$containsBy) continue;
                $novel=array();
                
                $novel['title']=$this->sanitize($h2->innertext,false);
                $novel['order']=$novelOrder;
                
                $tempBooks=$this->parseBookMethod1($h2);// MEthod one for much of most ln's

                if($tempBooks!=null && count($tempBooks)>0)
                {
                    $novel['bookCollection']=$tempBooks;
                }
                
                
                if((count($tempBooks)==0 && $oneBookOnly && count($books)==0) || empty($tempBooks))//method second for having only single volume as book
                {
                    $tempBooks=$this->parseBookMethod3($h2);
                    if($tempBooks!=null && count($tempBooks)>0)
                    {
                        $oneBookOnly=TRUE;
                        $novel['bookCollection']=$tempBooks;
                    }
                }
                
                $books[]=$novel;
                /*
                if(count($books)==0 ||($oneBookOnly && count($tempBooks)==0))
                {
                    $tempBooks=$this->parseBookMethod3($h2);
                    if($tempBooks!=null & count($tempBooks)>0)
                    {
                        $oneBookOnly=TRUE;
                        $books[]=$tempBooks;
                    }
                }*/
                
            }
        }

        //print_r($books);
        foreach($books as $book)
        {
            $validatedNovels[]=$this->validateNovelBooks($book['bookCollection']);
        }
        $this->novel=$validatedNovels;
    }

    private function parseBookMethod1($h2)// Method 1
    {
        $books=array();
        
        $bookElement=$h2;
        $walkBook=true;
        $bookOrder=0;
        do
        {
            $bookElement=$bookElement->next_sibling(); //iterates through siblings to find chapter list
            if(!$bookElement || $bookElement==null || $bookElement->tag=="h2")
            {
                $walkBook=false;
            }else if($bookElement->tag!="h3")
            {
                $h3s=$bookElement->find("h3");
                if($h3s!=null && $h3s && count($h3s)>0)
                {   
                    foreach($h3s as $h3)
                    {
                        $books[]=$this->processH3($h3,$bookOrder);
                        ++$bookOrder;
                    }
                }
            }
            else if($bookElement->tag=="h3")
            {
                $books[]=$this->processH3($bookElement,$bookOrder);
                ++$bookOrder;
            }

        }while($walkBook);
        
        return $books;
    }
    
        
    private function parseBookMethod2($h2) //Method 2 (Not Used! Left for future use)
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
    private function parseBookMethod3($h2)// Method 3
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
            }else if($bookElement->tag=="ul" || $bookElement->tag=="dl")
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
                $chapters=$bookElement->find("a");//only finds links
                foreach($chapters as $chapter)
                {
                    $pageModel=$this->processA($chapter->innertext,$chapter,$chapterOrder);
                    if($pageModel!=null)
                    {
                        $chapterCollection[]=$pageModel;
                        ++$chapterOrder;
                    }
                }
                
                $book['chapterCollection']=$chapterCollection;
                $books[]=$book;
                ++$bookOrder;
            }
        }while($walkBook);
        return $books;
    }
    
    private function parseChapters($bookElement)//Chapter Parsing
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
                            
                            $alreadyExists=FALSE;
                            if(count($chapterCollection)>0) //checking if chapters already exists in partCollection
                            {   
                                foreach($chapterCollection as $chapters)
                                { 
                                    if(isset($chapters['partCollection']))
                                    {
                                        foreach($chapters['partCollection'] as $parts)
                                        {
                                            if($parts['page']==$p['page'] && $parts['title']==$p['title']) $alreadyExists=TRUE;
                                        }
                                    }
                                    
                                }
                                
                            }
                            
                            if(!$alreadyExists)
                            {
                                $chapterCollection[]=$p;
                                ++$chapterOrder;
                            }
                        }
                    }
                }
            }
        }while($walkChapter);
        //print_r($chapterCollection);
        return $chapterCollection;
    }
    
    private function parseParts($ele,$chapterOrder) //parses sub chapters
    {
        $partCollection=array();
        
        $parts=$ele->find("li");
        
        foreach($parts as $p)
        {
            $partModel=$this->processLI($p,0);
            foreach($partModel as $pa)
            {
                if($pa!=null && !empty($pa))
                {
                    $partCollection[]=$pa;
                }
            }
        }
        
        $parentLink;
        $links=$ele->find("a");
        $partHaveParent=FALSE;
        foreach($links as $link)
        {
            if($link->parent()===$ele)
            {
                $partHaveParent=TRUE;
                $parentLink=$link;
            }
            break;
        }
        
        $parent=array();
        if($partHaveParent)
        {
            $parent=$this->processA($parentLink->innertext,$parentLink,$chapterOrder);
        }else
        {
            $title=preg_match("/<b>.*<\/b>/", $ele->innertext, $result);
            if(!empty($title) && $title!=null)
            {
                $parent['title']=$this->sanitize($result[0],false);
            }else
            {
                $parent['title']=preg_split("/</",$ele->innertext,2)[0];
            }
            $parent['page']="NILL";
            $parent['order']=$chapterOrder;
        }
        $parent['partCollection']=$partCollection;
        
        $partArr=array();
        $partArr[]=$parent;
        
        return $partArr;

    }

    ###########VALIDATING FUNCTIONS############
    private function validateH2($title,$ele) //validates the main h2 for parsing
    {
        $rules = array(str_replace(" ","_",$title),"_by", "Full_Text", "_Series", "_series", "Side_Stor", "Short_Stor", "Parody_Stor");
        foreach($rules as $rule)
        {
            if(strpos($ele->id,$rule)!==FALSE) return TRUE;
        }
        return false;
    }

    private function validateNovelBooks($books)//validates volumes
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
    
    private function validateNovelChapters($book)//validates chapters
    {
        $chapters=$book['chapterCollection'];
        $validatedChapters=array();
        $chapterOrder=0;
        foreach($chapters as $chapter)
        {
            if(strpos($chapter['page'],"User:")!==FALSE || strpos($chapter['page'],"Special:BookSources")!==FALSE || (strpos($chapter['page'],"edit")!==FALSE && strpos($chapter['page'],"redlink")===FALSE))
            {
                continue;
            }else
            {
                if(strpos($chapter['page'],"redlink")!==FALSE)
                {
                    $chapter['page']="n/a";
                }
                $chapter['order']=$chapterOrder;
                $validatedChapters[]=$chapter;
                
                if(isset($chapter['partCollection']))
                {
                    $chapter['partCollection']=$this->validateSubChapters($chapters);
                }
                ++$chapterOrder;
            }
               
        }
        return $validatedChapters;
    }
    
    private function validateSubChapters($chapters)//validates sub chapters
    {
        $parts=$chapters['partCollection'];
        $validatedParts=array();
        $partOrder=0;
        
        foreach($parts as $part)
        {
            if(strpos($part['page'],"User:")!==FALSE || strpos($part['page'],"Special:BookSources")!==FALSE || (strpos($part['page'],"edit")!==FALSE && strpos($part['page'],"redlink")===FALSE))
            {
                continue;
            }else
            {
                if(strpos($part['page'],"redlink")!==FALSE)
                {
                    $chapter['page']="n/a";
                }
                $part['order']=$partOrder;
                $validatedParts[]=$part;
                ++$partOrder;
            }        
        }
        
        return $calidatedParts;
    }
    
    #############HELPER FUNCTIONS#########
    private function sanitize($title,$isAggressive)//removes any unwanted stuff from title
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

    private function processLI($ele,$chapterOrder) //process li node
    {
        $pageModels=array();
        $links=$ele->find("a");
        $parts=$ele->find("li");
        if(count($parts)>0)//if has sub chapters in chapter 
        {
            return $this->parseParts($ele,$chapterOrder);
        }
            
        if($links || $links!=null || count($links)>0)
        {
            
            foreach($links as $link)
            {
                if(strpos($link->href,"User_talk:")!==FALSE || strpos($link->href,"User:")!==FALSE) continue; //user links
                if(stripos($link->innertext,"pdf")!==FALSE || stripos($link->innertext,"epub")!==FALSE) continue; //epub and pdg links
                $linkText=$link->plaintext;
                if($link->parent()!=$ele)
                {
                    $linkText=$ele->plaintext;
                }
                if(strpos($ele->plaintext,"MTL")!==FALSE)//machine translated series
                {
                    $linkText=$this->sanitize($ele->plaintext,false);
                }
                //echo $linkText."<br>";
                $p=$this->processA($linkText,$link,$chapterOrder);
                if($p!=null)
                {
                    $pageModels[]=$p;
                }
            }
                
        }else
        {
            $page['title']=$this->sanitize($ele->plainText,FALSE);
            if(empty($page['title']) || !isset($page['title'])) return $pageModels;
            $page['order']=$chapterOrder;
            $page['external']="FALSE";
            $page['page']="NILL";
            $pageModels[]=$page;
        }
        
        $tempPages=$pageModels;
        if(count($tempPages)>1)
        {
            foreach($tempPages  as $pages)
            {
                if($pages['external']=="TRUE") continue;
                if($pages['external']=="FALSE")
                {
                    unset($pageModels);
                    $pageModels[]=$pages;
                    break;
                }
            }
        }
        return $pageModels;
        
    }
    
    private function processA($title,$link,$chapterOrder) //processes links
    {
        $href=$link->href;
        $p=array();
        $p['title']=$this->sanitize($title,FALSE);
        $p['order']=$chapterOrder;
        
        if(strpos($link->class,"external text")!==FALSE)
        {
            $p['external']="TRUE";
            $p['page']=$href;
        }else
        {
            $p['external']="FALSE";
            $p['page']=$this->normalizeInternalURL($href);
        }
        return $p;
    }
                                               
    private function processH3($ele,$bookOrder)//processes the h3 and parses the chapter list(Chapter Parsing is Initialized Here)
    {
        $book=array();
        strpos($ele->innertext,"href");
        if(strpos($ele->innertext,"href")!==FALSE)
        {
            $book['title']=$this->sanitize($ele->innertext,TRUE);
        }else
        {
            $book['title']=$this->sanitize($ele->innertext,FALSE);
        }
        
        $book['order']=$bookOrder;
        
        $chapterCollection=$this->parseChapters($ele);
        
        if(count($chapterCollection)==0)//in case the format is different
        {
            $bookLinks=$ele->find("a");
            if($bookLinks && $bookLinks!=null)
            {
                foreach($bookLinks as $links)
                {
                    if(strpos($links->href,ROOT_URL)==0)
                    {
                        $p=array();
                        $p=$this->processA($links->innertext,$links,0);
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
    
    private function normalizeInternalURL($url)//only keeps parts after index.php?title:
    {
        $url=str_replace("/project/index.php?title="," ",$url);
        $url=str_replace(ROOT_HTTPS," ",$url);
        $url=str_replace(ROOT_HTTP," ",$url);
        $url=str_replace(ROOT_URL," ",$url);
        return $url;
    }
}

#########################################
/*
    Return Format in Detail:
    
    array(
        $novel['title']=$title,
        $novel['bookCollection']=array(
                                    $book['title']=$title,
                                    $book['chapterCollection']=array(
                                                                    $chapter['title']=$title,
                                                                    $chapter['order']=$order,
                                                                    $chapter['external']=$bool,
                                                                    $chapter['page']=$page,
                                                                    $chapter['partCollection']=array( //if has sub chapters
                                                                                                    $part['title']=$title,
                                                                                                    $part['order']=$order,
                                                                                                    $part['external']=$bool,
                                                                                                    $part['page']=$page,
                                                                                            
                                                                                                )
                                                                )
                                )
    
*/
#########################################
