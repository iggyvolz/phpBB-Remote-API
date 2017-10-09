<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
/**
 * A phpBB topic
 */
class Topic
{
  /**
   * Cache of all topics that have been downloaded this session
   * Indexed by phpBBRemoteApi=>[topic num=>topic]
   *
   * @var array
   */
  public static $topics=[];
  /**
   * Whether or not all posts in the topics are read
   *
   * @var bool
   */
  public $read;
  /**
   * Title of the topic
   *
   * @var string
   */
  public $title;
  /**
   * Author of the topic
   *
   * @var User
   */
  public $author;
  public $topicid;
  public $forumid;
  public $datetime;
  public $replies;
  public $views;
  public $lastauthor;
  public $lastdatetime;
  /**
   * Loads a phpBB topic from its forum element
   *
   * @param phpbbRemoteApi $api Board API object
   * @param DOMElement $topicElement
   */
  public function __construct($api,$topicElement)
  {
    $this->api=$api;
    $xpath=new \DomXPath($topicElement->ownerDocument);
    // Get topic title
    $this->title=$xpath->evaluate("string(.//*[contains(@class, 'topictitle')]/text())",$topicElement);
    // Get read/unread
    $this->read=$xpath->evaluate("count(.//*[contains(@class, 'topic_unread')])=0",$topicElement);
    // Get author name
    $this->author=new User($xpath->evaluate("string(.//*[contains(@class, 'topic-poster')]/a/text())",$topicElement));
    // Get post time
    $this->datetime=new \DateTime(trim(str_replace("»","",$xpath->evaluate("string(.//*[contains(@class, 'topic-poster')]/text()[2])",$topicElement))));
    // Get number of replies
    $this->replies=(int)$xpath->evaluate("number(.//*[contains(@class, 'posts')]/text())",$topicElement);
    // Get number of views
    $this->views=(int)$xpath->evaluate("number(.//*[contains(@class, 'views')]/text())",$topicElement);
    // Get last post author
    $this->lastauthor=new User($xpath->evaluate("string(.//*[contains(@class, 'lastpost')]/span/a[1]/text())",$topicElement));
    // Get last post date
    $this->lastdatetime=new \DateTime(trim($xpath->evaluate("string(.//*[contains(@class, 'lastpost')]/span/text()[4])",$topicElement)));
    // Get topic ID & forum ID
    $topiclink=$xpath->evaluate("string(.//*[contains(@class, 'topictitle')]/@href)",$topicElement);
    preg_match("/\.\/viewtopic.php\?f=([0-9]+)&t=([0-9]+)/",$topiclink,$matches);
    $this->forumid=(int)$matches[1];
    $this->topicid=(int)$matches[2];
  }

  /**
   * Gets a page of posts
   *
   * @param int $start The index to start with (0 being the newest topic, infinity the oldest).  If higher than the number of topics, displays the last page.
   * @return Topic[] Up to 25 within the forum, beginning at $start
   */
  public function get_page($start=0)
  {
    $httpcode=200;
    $result=curlRequest(sprintf("%s/viewtopic.php?f=%u&t=%u&start=%u",$this->api->url,$this->forumid,$this->topicid,$start),null,$httpcode);
    if($httpcode===404)
    {
      throw new TopicNotFoundException($result->getElementById("message")->getElementsByTagName("p")->item(0)->textContent);
    }
    $xpath=new \DomXPath($result);
    $posts=$xpath->query("//div[contains(concat(\" \", normalize-space(@class), \" \"), \" post \")]");
    
    for($i=0;$i<$posts->length;$i++)
    {
      $this->posts[$i]=new Post($this->api,$posts->item($i));
    }
    return $this->posts;
  }
  
  /**
   * Reset local cache of the topic, clearing memory of posts
   *
   * @return void
   */
  public function resetCache()
  {
    $this->posts=[];
    // Get page 0
    $this->get_page();
  }
}