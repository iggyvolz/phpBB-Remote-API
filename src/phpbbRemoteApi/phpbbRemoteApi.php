<?php
namespace phpbbRemoteApi;
class notLoggedInException extends \Exception
{
  public function __construct()
  {
    parent::__construct("Exception: Not logged in to phpBB", 0);
  }
}
class phpbbRemoteApi
{
  const COOKIE_FILE="cookies.txt";
  const TIMEOUT=50;
  public $url;
  public $f;
  public $user;
  private $pass;
  public $num_posts;
  private $handle;
  public function __construct($url,$user=NULL,$pass=NULL)
  {
    list($this->url,$this->user,$this->pass)=[$url,$user,$pass];
    if($user&&$pass)
    {
      $this->login();
    }
  }
  public function login()
  {
    $result=$this->curlrequest(sprintf("%s/ucp.php?mode=login",$this->url),["username"=>$this->user,"password"=>$this->pass,"redirect"=>"./ucp.php","mode"=>"login","login"=>"Login"],true);
    return $result;
  }
  public function rawcurlrequest($url,$params=NULL)//:string
  {
    $pparams=json_encode($params);
    echo "CURL REQUEST TO $url WITH PARAMS $pparams\n";
    sleep(3);
    $handle=curl_init($url);
    curl_setopt($handle, CURLOPT_COOKIEFILE, phpbbRemoteApi::COOKIE_FILE);
    curl_setopt($handle, CURLOPT_COOKIEJAR,   phpbbRemoteApi::COOKIE_FILE);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)");
    curl_setopt($handle, CURLOPT_TIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
    if($params)
    {
      curl_setopt($handle, CURLOPT_POST, true);
      curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $result=curl_exec($handle);
    curl_close($handle);
    return $result;
  }
  public function curlrequest($url,$params=NULL)//:\DOMDocument
  {
    $result=$this->rawcurlrequest($url,$params);
    $dom=new \DOMDocument();
    // Ignore id errors
    $previous_value = libxml_use_internal_errors(TRUE);
    $dom->loadHTML($result);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_value);
    return $dom;
  }
  public function get_page($f,$t,$s)
  {
    $result=$this->curlrequest(sprintf("%s/viewtopic.php?f=%u&t=%u&start=%u",$this->url,$f,$t,$s));
    $posts=array_values(array_filter(iterator_to_array($result->getElementsByTagName("div")),function($el)
    {
      return in_array("post",explode(" ",$el->getAttribute("class")));
    }));
    $i=0;
    foreach($posts as $post)
    {
      $i++;
      $return[]=new phpBBPost($this->url,$f,$t,$s+$i,$post);
    }
    return $return;
  }
  public function get_post($f,$t,$s)
  {
    return $this->get_page($f,$t,$s)[0];
  }
  public function download_pm($p)
  {
    return new phpBBPM($this->url,$p);
  }
  public function num_posts($f,$t)
  {
    $result=$this->curlrequest(sprintf("%s/viewtopic.php?f=%u&t=%u",$this->url,$f,$t));
    $divs=array_values(array_filter(iterator_to_array($result->getElementsByTagName("div")),function($el)
    {
      return in_array("pagination",explode(" ",$el->getAttribute("class")));
    }));
    preg_match("/([0-9]+) posts?/", $divs[0]->textContent, $matches);
    return (int)$matches[1];
  }
  public function get_unread_pm()
  {
    $result=$this->curlrequest(sprintf("%s/ucp.php?i=pm&folder=inbox",$this->url));
    $pms=array_values(array_filter(iterator_to_array($result->getElementsByTagName("a")),function($el)
    {
      return in_array("topictitle",explode(" ",$el->getAttribute("class")));
    }));
    if(count($pms)===0)
    {
      return null;
    }
    preg_match("/&p=([0-9]+)/", $pms[0]->getAttribute("href"), $matches);
    return (int)$matches[1];
  }
  public function delete_pm($p)
  {
    $result=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose&action=delete&f=0&p=%u",$this->url,$p));
    $form=$result->getElementById("confirm");
    $inputs=$this->get_inputs($form);
    $action=$form->getAttribute("action");
    // Don't click cancel
    unset($inputs["cancel"]);
    $result=$this->curlrequest($this->url."/".$action,$inputs);
    return $result;
  }
  /*public function lock_thread($f,$t)
  {
    $result=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1",$this->url,$f,$t),["action"=>"lock"]);
    $confirm_key=explode("\"",explode("<form id=\"confirm\" action=\"./mcp.php?f=$f&amp;t=$t&amp;quickmod=1&amp;confirm_key=",$iresult)[1])[0];
    $confirm_uid=explode("\"",explode("<input type=\"hidden\" name=\"confirm_uid\" value=\"",$iresult)[1])[0];
    $sess=explode("\"",explode("<input type=\"hidden\" name=\"sess\" value=\"",$iresult)[1])[0];
    $result=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1&confirm_key=%s",$this->url,$f,$t,$confirm_key),["topic_id_list[0]"=>$t,"action"=>"lock","confirm_uid"=>$confirm_uid,"sess"=>$sess,"sid"=>$sess,"confirm"=>"Yes"]);
    return $result;
  }
  public function unlock_thread($f,$t)
  {
    $ihandle=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1",$this->url,$f,$t),["action"=>"unlock"]);
    $iresult=curl_exec($ihandle);
    curl_close($ihandle);
    echo $iresult;
    $confirm_key=explode("\"",explode("<form id=\"confirm\" action=\"./mcp.php?f=$f&amp;t=$t&amp;quickmod=1&amp;confirm_key=",$iresult)[1])[0];
    $confirm_uid=explode("\"",explode("<input type=\"hidden\" name=\"confirm_uid\" value=\"",$iresult)[1])[0];
    $sess=explode("\"",explode("<input type=\"hidden\" name=\"sess\" value=\"",$iresult)[1])[0];
    $result=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1&confirm_key=%s",$this->url,$f,$t,$confirm_key),["topic_id_list[0]"=>$t,"action"=>"unlock","confirm_uid"=>$confirm_uid,"sess"=>$sess,"sid"=>$sess,"confirm"=>"Yes"]);
    return $result;
  }*/
  public function create_post($f,$t,$message,$subject=null)
  {
    // Attempt to create post three times - this is known to fail occasionally
    for($i=0;$i<3;$i++)
    {
      $result=$this->curlrequest(sprintf("%s/posting.php?mode=reply&f=%u&t=%u",$this->url,$f,$t));
      $form=$result->getElementById("postform");
      // Got form successfully, break out
      if($form) break;
    }
    $inputs=$this->get_inputs($form);
    foreach($inputs as $key=>$val)
    {
      if(strpos($key, "add")===0 || strpos($key, "disable_")===0)
      {
        unset($inputs[$key]);
      }
    }
    $inputs["icon"]="0";
    unset($inputs["bbpalette"]);
    // Don't preview or save draft
    unset($inputs["save"]);
    unset($inputs["preview"]);
    $inputs["message"]=$message;
    if($subject)
    {
      $inputs["subject"]=$subject;
    }
    $action=$form->getAttribute("action");
    $result=$this->curlrequest($this->url."/".$action,$inputs);
    return $result;
  }
  public function create_pm($subject,$message,$to,$bcc=NULL)
  {
    $result=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose",$this->url));
    $form=$result->getElementById("postform");
    $action=$form->getAttribute("action");
    $inputs=$this->get_inputs($form);
    $inputs["subject"]=$subject;
    $inputs["message"]=$message;
    foreach($inputs as $key=>$val)
    {
      if(strpos($key, "add")===0 || strpos($key, "disable_")===0)
      {
        unset($inputs[$key]);
      }
    }
    $inputs["icon"]="0";
    unset($inputs["bbpalette"]);
    if(!is_array($to)) // Handle to as string
    {
      $to=[$to];
    }
    foreach($to as $r)
    {
      $rid=$this->get_id_from_user($r);
      $inputs["address_list[u][$rid]"]="to";
    }
    if($bcc)
    {
      if(!is_array($bcc))
      {
        $bcc=[$bcc];
      }
      foreach($bcc as $r)
      {
        $rid=$this->get_id_from_user($r);
        $inputs["address_list[u][$rid]"]="bcc";
      }
    }
    unset($inputs["preview"]);
    unset($inputs["save"]);
    $result=$this->curlrequest($this->url."/".$action,$inputs,true);
    return $result;
  }
  public function get_id_from_user($u)
  {
    $result=$this->curlrequest(sprintf("%s/memberlist.php?mode=searchuser",$this->url),["username"=>$u]);
    $rows=array_values(array_filter(iterator_to_array($result->getElementsByTagName("tr")),function($el)
    {
      return in_array("bg1",explode(" ",$el->getAttribute("class")));
    }));
    if(empty($rows))
    {
      return null;
    }
    $a=$rows[0]->getElementsByTagName("a")[0];
    preg_match("/\.\/memberlist.php\?mode=viewprofile&u=([0-9]+)/", $a->getAttribute("href"),$matches);
    if($matches[1])
    {
      return (int)$matches[1];
    }
    else
    {
      return null;
    }
  }
  public function get_inputs(\DOMElement $dom)//:Array
  {
    $ret=[];
    $list=$dom->getElementsByTagName("input");
    foreach($list as $element)
    {
      $ret[$element->getAttribute("name")]=$element->getAttribute("value");
    }
    return $ret;
  }
}
class phpBBPost
{
  public $url;
  public $f;
  public $t;
  public $s;
  public $p;
  public $author;
  public $time;
  public $rawconts;
  public $rawcontsnoquotes;
  public $htmlconts;
  public $read;
  private $bbcconts; // Handled by phpbbRemoteApiNotLoggedInException if null
  public function __construct($url,$f,$t,$s,$result)
  {
    list($this->url,$this->f,$this->t,$this->s)=[$url,$f,$t,$s];
    $authorblock=array_values(array_filter(iterator_to_array($result->getElementsByTagName("p")),function($el)
    {
      return in_array("author",explode(" ",$el->getAttribute("class")));
    }))[0];
    preg_match("/\.\/viewtopic.php\?p=([0-9]+)/", $authorblock->getElementsByTagName("a")[0]->getAttribute("href"), $arr);
    $this->p=(int)$arr[1];
    $this->author=$authorblock->getElementsByTagName("a")[1]->textContent;
    preg_match("/» (.+) /",$authorblock->textContent,$arr);
    $this->time=new \DateTime($arr[1]);
    $content=array_values(array_filter(iterator_to_array($result->getElementsByTagName("div")),function($el)
    {
      return in_array("content",explode(" ",$el->getAttribute("class")));
    }))[0];
    $this->htmlconts=$content->ownerDocument->saveXML($content);
    $this->rawconts=trim(strip_tags($this->htmlconts));
    /*if(strpos($result, '<img src="./styles/prosilver/imageset/icon_post_target_unread.gif" width="11" height="9" alt="Unread post" title="Unread post" />')!==FALSE)
    {
      $this->read=true;
    }
    else
    {
      $this->read=false;
    }*/
    foreach($content->getElementsByTagName("blockquote") as $node)
    {
      $node->parentNode->removeChild($node);
    }
    $result=$content->ownerDocument->saveHTML($content);
    $this->rawcontsnoquotes=trim(strip_tags($result));
  }
  public function __get($a)
  {
    if($a=="bbconts")
    {
      if($this->bbconts or $this->bbconts=$this->bbconts())
      {
        return $this->bbconts;
      }
      else
      {
        throw new NotLoggedInException();
      }
    }
  }
  private function bbconts()
  {
    $result=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose&action=quotepost&p=%u",$this->url,$this->p));
    $tmp=@explode("\n",trim(explode("</textarea>",explode('class="inputbox">',$result)[1])[0]));
    if($tmp)
    {
      array_shift($tmp);
      array_shift($tmp);
      $tmp2=implode("\n",$tmp);
      $tmp3=explode("]",$tmp2);
      array_shift($tmp3);
      $tmp4=implode("]",$tmp3);
      return substr($tmp4,0,-8);
    }
    else
    {
      return null;
    }
  }
  public function curlrequest($url,$params=NULL)//:\DOMDocument
  {
    $pparams=json_encode($params);
    echo "CURL REQUEST TO $url WITH PARAMS $pparams\n";
    sleep(3);
    $handle=curl_init($url);
    curl_setopt($handle, CURLOPT_COOKIEFILE, phpbbRemoteApi::COOKIE_FILE);
    curl_setopt($handle, CURLOPT_COOKIEJAR,   phpbbRemoteApi::COOKIE_FILE);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)");
    curl_setopt($handle, CURLOPT_TIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
    if($params)
    {
      curl_setopt($handle, CURLOPT_POST, true);
      curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $result=curl_exec($handle);
    curl_close($handle);
    $dom=new \DOMDocument();
    // Ignore id errors
    $previous_value = libxml_use_internal_errors(TRUE);
    $dom->loadHTML($result);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_value);
    return $dom;
  }
}
class phpBBPM
{
  public $time;
  public $subject;
  public $conts;
  public $author;
  public $rawconts;
  public $rawcontsnoquotes;
  public $deleted;
  public function __construct($url,$p)
  {
    $this->p=$p;
    $result=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=view&f=0&p=%u",$url,$p));
    $authorblock=array_values(array_filter(iterator_to_array($result->getElementsByTagName("p")),function($el)
    {
      return in_array("author",explode(" ",$el->getAttribute("class")));
    }))[0];
    if(!$authorblock)
    {
      // Check to see if the PM has been deleted
      if(iterator_to_array($result->getElementsByTagName("p"))[2]->nodeValue==="You are not able to read this message because it was removed by the author.Return to previous folder")
      {
        // Delete the PM from our inbox
        $this->api->delete_pm($p);
        $this->deleted=true;
        return;
      }
      return;
    }
    $this->author=$authorblock->getElementsByTagName("a")[0]->textContent;
    preg_match("/Sent: (.+)/",$authorblock->textContent,$arr);
    $this->time=new \DateTime($arr[1]);
    $content=array_values(array_filter(iterator_to_array($result->getElementsByTagName("div")),function($el)
    {
      return in_array("content",explode(" ",$el->getAttribute("class")));
    }))[0];
    $this->htmlconts=$content->ownerDocument->saveXML($content);
    $this->rawconts=trim(strip_tags($this->htmlconts));
    /*if(strpos($result, '<img src="./styles/prosilver/imageset/icon_post_target_unread.gif" width="11" height="9" alt="Unread post" title="Unread post" />')!==FALSE)
    {
      $this->read=true;
    }
    else
    {
      $this->read=false;
    }*/
    foreach($content->getElementsByTagName("blockquote") as $node)
    {
      $node->parentNode->removeChild($node);
    }
    $result=$content->ownerDocument->saveHTML($content);
    $this->rawcontsnoquotes=trim(strip_tags($result));

  }
  public function curlrequest($url,$params=NULL)//:\DOMDocument
  {
    $pparams=json_encode($params);
    echo "CURL REQUEST TO $url WITH PARAMS $pparams\n";
    sleep(5);
    $handle=curl_init($url);
    curl_setopt($handle, CURLOPT_COOKIEFILE, phpbbRemoteApi::COOKIE_FILE);
    curl_setopt($handle, CURLOPT_COOKIEJAR,   phpbbRemoteApi::COOKIE_FILE);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)");
    curl_setopt($handle, CURLOPT_TIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
    if($params)
    {
      curl_setopt($handle, CURLOPT_POST, true);
      curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $result=curl_exec($handle);
    curl_close($handle);
    $dom=new \DOMDocument();
    // Ignore id errors
    $previous_value = libxml_use_internal_errors(TRUE);
    $dom->loadHTML($result);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_value);
    return $dom;
  }
}
