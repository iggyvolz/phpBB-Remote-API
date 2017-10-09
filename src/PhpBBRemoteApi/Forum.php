<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
class Forum
{
  public $api;
  public $f;
  /**
   * List of topics in the forum
   *
   * @var Topic[]
   */
  public $topics=[];
  /**
   * Create a forum object
   *
   * @param phpbbRemoteApi $api Board API object
   * @param int $f Forum ID
   */
  public function __construct($api,$f)
  {
    list($this->api,$this->f)=[$api,$f];
  }
  /**
   * Gets a page of topics
   *
   * @param int $start The index to start with (0 being the newest topic, infinity the oldest).  If higher than the number of topics, displays the last page.
   * @return Topic[] Up to 25 within the forum, beginning at $start
   */
  public function get_page($start=0)
  {
    $httpcode=200;
    $result=curlRequest(sprintf("%s/viewforum.php?f=%s&start=%s",$this->api->url,$this->f,$start),null,$httpcode);
    if($httpcode===404)
    {
      throw new ForumNotFoundException($result->getElementById("message")->getElementsByTagName("p")->item(0)->textContent);
    }
    $xpath=new \DomXPath($result);
    $topics=$xpath->query("//ul[contains(concat(\" \", normalize-space(@class), \" \"), \" topics \")]/li");
    for($i=0;$i<$topics->length;$i++)
    {
      $this->topics[$i]=new Topic($this->api,$topics->item($i));
    }
    return $this->topics;
  }
  public function create_topic()
  {
    /*
      'addbbcode20' => '100',
  'attach_sig' => 'on',
  'creation_time' => '1507323675',
  'form_token' => '21f4bc0fb3f637bf93f3ed7675eea3c45ddb360b',
  'icon' => '0',
  'lastclick' => '1507323674',
  'message' => 'Body',
  'poll_length' => '0',
  'poll_max_options' => '1',
  'poll_option_text' => '',
  'poll_title' => '',
  'post' => 'Submit',
  'show_panel' => 'options-panel',
  'subject' => 'Subject',
  'topic_time_limit' => '0',
  'topic_type' => '0',
  */
    // Get form token and creation time from form
    $result=curlRequest(sprintf("%s/posting.php?mode=post&f=%s",$this->api->url,$this->f));
    $xpath=new \DomXPath($result);
    $creation_time=$xpath->evaluate("string(//input[@name=\"creation_time\"]/@value)");
    $form_token=$xpath->evaluate("string(//input[@name=\"form_token\"]/@value)");
    
  }
  /**
   * Reset local cache of the forum, clearing memory of topics
   *
   * @return void
   */
  public function resetCache()
  {
    $this->topics=[];
  }
}
