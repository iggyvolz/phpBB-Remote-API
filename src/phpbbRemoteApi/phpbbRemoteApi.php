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
    $handle=$this->curlrequest(sprintf("%s/ucp.php?mode=login",$this->url),["username"=>$this->user,"password"=>$this->pass,"redirect"=>"./ucp.php","mode"=>"login","login"=>"Login"],true);
    $result=curl_exec($handle);
    curl_close($handle);
    return $result;
  }
  public function curlrequest($url,$params=NULL)
  {
    echo "CURL REQUEST TO $url WITH PARAMS ".json_encode($params)."\n";
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
    return $handle;
  }
  public function get_page($f,$t,$s)
  {
    $handle=$this->curlrequest(sprintf("%s/viewtopic.php?f=%u&t=%u&start=%u",$this->url,$f,$t,$s));
    $result=curl_exec($handle);
    preg_match_all("/<h3(.+)?>((.+)<ul class=\"profile-icons\">(.+)<\/ul>)/Us", $result, $matches);
    // Walk over array, matching the entire post
    $matches=$matches[0];
    curl_close($handle);
    $return=[];
    foreach($matches as $i=>$post)
    {
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
    $handle=$this->curlrequest(sprintf("%s/viewtopic.php?f=%u&t=%u",$this->url,$f,$t));
    $result=curl_exec($handle);
    curl_close($handle);
    $nresult=explode(" posts",explode("</div>",explode("<div class=\"pagination\">",$result)[1])[0])[0];
    if(count(explode("<a",$nresult))>1)
    {
      $nresult=explode("&bull;",$nresult)[1]; // Get rid of Unread Posts if any
    }
    $this->num_posts=trim($nresult);
    return $this->num_posts+0;
  }
  public function get_unread_pm()
  {
    $handle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&folder=inbox",$this->url));
    $result=curl_exec($handle);
    curl_close($handle);
    $nresult=@explode("\"",explode("<a href=\"./ucp.php?i=pm&amp;mode=view&amp;f=0&amp;p=",explode("<ul class=\"topiclist cplist pmlist\">",$result)[1])[1])[0];
    if(!$nresult) { return null; }
    return $nresult+0;
  }
  public function delete_pm($p)
  {
    $ihandle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose&action=delete&f=0&p=%u",$this->url,$p));
    $iresult=curl_exec($ihandle);
    curl_close($ihandle);
    $confirm_key=explode("\"",explode("<form id=\"confirm\" action=\"./ucp.php?i=pm&amp;mode=compose&amp;action=delete&amp;f=0&amp;p=$p&amp;confirm_key=",$iresult)[1])[0];
    $confirm_uid=explode("\"",explode("<input type=\"hidden\" name=\"confirm_uid\" value=\"",$iresult)[1])[0];
    $sess=explode("\"",explode("<input type=\"hidden\" name=\"sess\" value=\"",$iresult)[1])[0];
    $handle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose&action=delete&f=0&p=$p&confirm_key=$confirm_key",$this->url),["confirm_uid"=>$confirm_uid,"p"=>$p,"f"=>"0","action"=>"delete","sess"=>$sess,"sid"=>$sess,"confirm"=>"Yes"],true);
    $result=curl_exec($handle);
    curl_close($handle);
    return $result;
  }
  public function lock_thread($f,$t)
  {
    $ihandle=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1",$this->url,$f,$t),["action"=>"lock"]);
    $iresult=curl_exec($ihandle);
    curl_close($ihandle);
    echo $iresult;
    $confirm_key=explode("\"",explode("<form id=\"confirm\" action=\"./mcp.php?f=$f&amp;t=$t&amp;quickmod=1&amp;confirm_key=",$iresult)[1])[0];
    $confirm_uid=explode("\"",explode("<input type=\"hidden\" name=\"confirm_uid\" value=\"",$iresult)[1])[0];
    $sess=explode("\"",explode("<input type=\"hidden\" name=\"sess\" value=\"",$iresult)[1])[0];
    $handle=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1&confirm_key=%s",$this->url,$f,$t,$confirm_key),["topic_id_list[0]"=>$t,"action"=>"lock","confirm_uid"=>$confirm_uid,"sess"=>$sess,"sid"=>$sess,"confirm"=>"Yes"]);
    $result=curl_exec($handle);
    curl_close($handle);
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
    $handle=$this->curlrequest(sprintf("%s/mcp.php?f=%u&t=%u&quickmod=1&confirm_key=%s",$this->url,$f,$t,$confirm_key),["topic_id_list[0]"=>$t,"action"=>"unlock","confirm_uid"=>$confirm_uid,"sess"=>$sess,"sid"=>$sess,"confirm"=>"Yes"]);
    $result=curl_exec($handle);
    curl_close($handle);
    return $result;
  }
  public function create_post($f,$t,$subject,$message)
  {
    $ihandle=$this->curlrequest(sprintf("%s/posting.php?mode=reply&f=%u&t=%u",$this->url,$f,$t));
    $iresult=curl_exec($ihandle);
    curl_close($ihandle);
    $topic_cur_post_id=explode("\"",explode("<input type=\"hidden\" name=\"topic_cur_post_id\" value=\"",$iresult)[1])[0];
    $lastclick=explode("\"",explode("<input type=\"hidden\" name=\"lastclick\" value=\"",$iresult)[1])[0];
    $creation_time=explode("\"",explode("<input type=\"hidden\" name=\"creation_time\" value=\"",$iresult)[1])[0];
    $form_token=explode("\"",explode("<input type=\"hidden\" name=\"form_token\" value=\"",$iresult)[1])[0];
    $sid=explode("\"",explode("<input type=\"hidden\" name=\"sid\" value=\"",$iresult)[1])[0];
    $forum_id=explode("\"",explode("<input type=\"hidden\" name=\"forum_id\" value=\"",$iresult)[1])[0];
    $topic_id=explode("\"",explode("<input type=\"hidden\" name=\"topic_id\" value=\"",$iresult)[1])[0];
    $locked=(strpos($iresult,"<input type=\"checkbox\" name=\"lock_topic\" id=\"lock_topic\" checked")===FALSE?false:true);
    $handle=$this->curlrequest(sprintf("%s/posting.php?mode=reply&f=%u&t=%u",$this->url,$f,$t),["subject"=>$subject,"addbbcode20"=>"100","message"=>$message,"topic_cur_post_id"=>$topic_cur_post_id,"lastclick"=>$lastclick,"post"=>"Submit","attach_sig"=>"on","creation_time"=>$creation_time,"form_token"=>$form_token,"sid"=>$sid,"forum_id"=>$forum_id,"topic_id"=>$topic_id,"lock_topic".($locked?"":"_off")=>"lock_topic"]);
    $result=curl_exec($handle);
    curl_close($handle);
    return $result;
  }
  public function create_pm($subject,$message,$to,$bcc=NULL)
  {
    $ihandle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose",$this->url));
    $iresult=curl_exec($ihandle);
    curl_close($ihandle);
    $lastclick=explode("\"",explode("<input type=\"hidden\" name=\"lastclick\" value=\"",$iresult)[1])[0];
    $status_switch=explode("\"",explode("<input type=\"hidden\" name=\"status_switch\" value=\"",$iresult)[1])[0];
    $form_token=explode("\"",explode("<input type=\"hidden\" name=\"form_token\" value=\"",$iresult)[1])[0];
    $creation_time=explode("\"",explode("<input type=\"hidden\" name=\"creation_time\" value=\"",$iresult)[1])[0];
    $data=["subject"=>$subject,"message"=>$message,"lastclick"=>$lastclick,"status_switch"=>$status_switch,"form_token"=>$form_token,"creation_time"=>$creation_time,"post"=>"submit"];
    if($to)
    {
      foreach($to as $r)
      {
        $rid=$this->get_id_from_user($r);
        $data["address_list[u][$rid]"]="to";
      }
    }
    if($bcc)
    {
      foreach($bcc as $r)
      {
        $rid=$this->get_id_from_user($r);
        $data["address_list[u][$rid]"]="bcc";
      }
    }
    $handle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose",$this->url),$data,true);
    $result=curl_exec($handle);
    curl_close($handle);
    return $result;
  }
  public function get_id_from_user($u)
  {
    $ihandle=$this->curlrequest(sprintf("%s/memberlist.php?mode=searchuser",$this->url),["username"=>$u]);
    $iresult=curl_exec($ihandle);
    curl_close($ihandle);
    $id=explode("\"",explode("<a href=\"./memberlist.php?mode=viewprofile&amp;u=",$iresult)[1])[0]+0;
    return $id;
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
  public function __construct($url,$f,$t,$s,$result=null)
  {
    list($this->url,$this->f,$this->t,$this->s)=[$url,$f,$t,$s];
    if(!$result)
    {
      $handle=$this->curlrequest(sprintf("%s/viewtopic.php?f=%u&t=%u&start=%u",$url,$f,$t,$s));
      $result=curl_exec($handle);
      curl_close($handle);
    }
    $authorarr=[];
    preg_match("/by <strong><a href=\".\/memberlist.php\?mode=viewprofile&amp;u=[0-9]+\"(.+)?>(.+)<\/a><\/strong> &raquo; (.+)<\/p>/Us", $result, $authorarr);
    $this->author=$authorarr[2];
    $this->time=new \DateTime($authorarr[3]);
    $parr=[];
    preg_match("/<a href=\"#p([0-9]+)\">/Us", $result, $parr);
    $this->p=(int)$parr[1];
    $contsarr=[];
    preg_match("/<div class=\"content\">(.+)<\/div>(\s+)(<div class=\"notice\">)?(<div id=\"sig[0-9]+\" class=\"signature\")?(<dl class=\"postprofile\" id=\"profile[0-9]\">)?/Us", $result, $contsarr);
    $this->htmlconts=/*trim*/($contsarr[1]);
    $this->rawconts=trim(strip_tags($this->htmlconts));
    if(strpos($result, '<img src="./styles/prosilver/imageset/icon_post_target_unread.gif" width="11" height="9" alt="Unread post" title="Unread post" />')!==FALSE)
    {
      $this->read=true;
    }
    else
    {
      $this->read=false;
    }
    $dom = new \DOMDocument;
    @$dom->loadHTML($this->htmlconts);
    $nodes = $dom->getElementsByTagName('blockquote');
    while($nodes->item(0))
    {
      $nodes->item(0)->parentNode->removeChild($nodes->item(0));
      $nodes = $dom->getElementsByTagName('blockquote');
    }
    $result=$dom->saveHTML();
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
    $handle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=compose&action=quotepost&p=%u",$this->url,$this->p));
    $result=curl_exec($handle);
    curl_close($handle);
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
  public function curlrequest($url,$params=NULL)
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
    return $handle;
  }
}
class phpBBPM
{
  public $p;
  public $time;
  public $subject;
  public $conts;
  public $author;
  public function __construct($url,$p)
  {
    $this->p=$p;
    $handle=$this->curlrequest(sprintf("%s/ucp.php?i=pm&mode=view&f=0&p=%u",$url,$p));
    $result=curl_exec($handle);
    curl_close($handle);
    file_put_contents("result.html",$result);
    $result=preg_replace("~<blockquote(.*?)>(.*)</blockquote>~si","",' '.$result.' ',1);
    $authorarr=[];
    preg_match("/<strong>From:<\/strong> <a href=\".\/memberlist.php\?mode=viewprofile&amp;u=[0-9]+\"(.+)?>(.+)<\/a>/Us", $result, $authorarr);
    $this->author=$authorarr[2];
    $timearr=[];
    preg_match("/<strong>Sent:<\/strong> (.++)/Us", $result, $timearr);
    $this->time=new \DateTime($timearr[1]);
    $subjectarr=[];
    preg_match("/<h3 class=\"first\">(.+)<\/h3>/Us", $result, $subjectarr);
    $this->subject=$subjectarr[1];
    $contsarr=[];
    preg_match("/<div class=\"content\">(.+)<\/div>/Us", $result, $contsarr);
    $this->conts=$contsarr[1];
  }
  public function curlrequest($url,$params=NULL)
  {
    echo "CURL REQUEST TO $url WITH PARAMS ".json_encode($params)."\n";
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
    return $handle;
  }
}
