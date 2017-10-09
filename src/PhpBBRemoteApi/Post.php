<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
class Post
{
    public $api;
    /**
     * Whether or not the post is read
     *
     * @var bool
     */
    public $read;
    /**
     * Title of the post
     *
     * @var string
     */
    public $title;
    /**
     * Author of the post
     *
     * @var User
     */
    public $author;
    /**
     * ID of the post
     *
     * @var int
     */
    public $postid;
    /**
     * Date/time of post
     *
     * @var DateTime
     */
    public $datetime;
    /**
     * Text content of post
     *
     * @var string
     */
    public $text;
    /**
     * Loads a phpBB post from its forum element
     *
     * @param phpbbRemoteApi $api Board API object
     * @param DOMElement $postElement
     */
    public function __construct($api,$postElement)
    {
        $this->api=$api;
        $xpath=new \DomXPath($postElement->ownerDocument);
        // Get author name
        $this->author=new User($xpath->evaluate("string(.//*[contains(concat(\" \", normalize-space(@class), \" \"), \" author \")]//a[contains(concat(\" \", normalize-space(@class), \" \"), \" username-coloured \")]/text())",$postElement));
        // Get post time
        $this->datetime=new \DateTime(trim($xpath->evaluate("string(.//*[contains(concat(\" \", normalize-space(@class), \" \"), \" author \")]//text()[3])",$postElement)));
        // Get read or not
        // If a.unread does not contains a lightgray icon, then it is unread
        $this->read=!$xpath->evaluate("count(.//a[contains(concat(\" \", normalize-space(@class), \" \"), \" unread \")]/i[contains(concat(\" \", normalize-space(@class), \" \"), \" icon-lightgray \")])=0",$postElement);
        // Get title
        $this->title=$xpath->evaluate("string(.//div[contains(concat(\" \", normalize-space(@class), \" \"), \" postbody \")]//a/text())",$postelement);
        // Get text content
        $this->text=$xpath->evaluate("string(.//div[contains(concat(\" \", normalize-space(@class), \" \"), \" content \")])",$postElement);
        // Get post id
        $this->postid=(int)substr($postElement->getAttribute("id"),1);
    }
}