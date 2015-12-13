<?php
namespace phpbbRemoteApi;
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
  private function curlrequest($url,$params=NULL,$hidelog=false)
  {
    if($hidelog)
    {
      echo "CURL REQUEST TO $url WITH PARAMS [REDACTED]\n";
    }
    else
    {
      $pparams=json_encode($params);
      echo "CURL REQUEST TO $url WITH PARAMS $pparams\n";
    }
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
  public function get_post($f,$t,$s)
  {
    return new phpBBPost($this->url,$f,$t,$s);
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
    $handle=$this->curlrequest(sprintf("%s/posting.php?mode=reply&f=%u&t=%u",$this->url,$f,$t),["subject"=>$subject,"addbbcode20"=>"100","message"=>$message,"topic_cur_post_id"=>$topic_cur_post_id,"lastclick"=>$lastclick,"post"=>"Submit","attach_sig"=>"on","creation_time"=>$creation_time,"form_token"=>$form_token,"sid"=>$sid,"forum_id"=>$forum_id,"topic_id"=>$topic_id]);
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
  public $f;
  public $t;
  public $s;
  public $author;
  public $time;
  public $conts;
  public function __construct($url,$f,$t,$s)
  {
    list($this->f,$this->t,$this->s)=[$f,$t,$s];
    $handle=$this->curlrequest(sprintf("%s/viewtopic.php?f=%u&t=%u&start=%u",$url,$f,$t,$s));
    $result=curl_exec($handle);
    curl_close($handle);
    //file_put_contents("result.html",$result);
    $dom = new \DOMDocument;
    @$dom->loadHTML($result);
    $nodes = $dom->getElementsByTagName('blockquote');
    while($nodes->item(0))
    {
      $nodes->item(0)->parentNode->removeChild($nodes->item(0));
      $nodes = $dom->getElementsByTagName('blockquote');
    }
    $result=$dom->saveHTML();
    $this->author=explode("<",explode("\">",explode("<strong><a href",explode("<p class=\"author\">",$result)[1])[1])[1])[0];
    $this->time=new \DateTime(explode(" </p>",explode("</strong> » ",explode("<p class=\"author\">",$result)[1])[1])[0]);
    $this->conts=trim(strip_tags(substr(explode("<dl class=\"postprofile\"",explode("<div class=\"content\">",$result)[1])[0],0,-4)));
  }
  private function curlrequest($url,$params=NULL)
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
    //file_put_contents("result.html",$result);
    $result=preg_replace("~<blockquote(.*?)>(.*)</blockquote>~si","",' '.$result.' ',1);
    $this->author=trim(strip_tags(explode("</a>",explode("To:</strong>",explode("<p class=\"author\">",$result)[1])[1])[0]));
    $this->time=new DateTime(trim(explode("<br />",explode("</strong>",explode("<p class=\"author\">",$result)[1])[1])[0]));
    $this->subject=strip_tags(explode("</h3>",explode("<h3 class=\"first\">",$result)[1])[0]);
    $this->conts=strip_tags(explode("</div>",explode("<div class=\"content\">",$result)[1])[0]);
  }
  private function curlrequest($url,$params=NULL,$hidelog=false)
  {
    if($hidelog)
    {
      echo "CURL REQUEST TO $url WITH PARAMS [REDACTED]\n";
    }
    else
    {
      $pparams=json_encode($params);
      echo "CURL REQUEST TO $url WITH PARAMS $pparams\n";
    }
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
