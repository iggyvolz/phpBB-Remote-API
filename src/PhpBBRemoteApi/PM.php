<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
class PM
{
    public $read;
    public $contents;
    public $title;
    public $author;
    public $datetime;
    private $link;
    /**
     * Loads a phpBB PM from its message box element
     *
     * @param phpbbRemoteApi $api Board API object
     * @param DOMElement $pmElement
     */
    public function __construct($api,$pmElement)
    {
        $this->api=$api;
        $xpath=new \DomXPath($pmElement->ownerDocument);
        // Get pm title
        $this->title=$xpath->evaluate("string(.//a[contains(concat(\" \", normalize-space(@class), \" \"), \" topictitle \")]/text())",$pmElement);
        // Check if read
        $this->read=$xpath->evaluate("count(.//dl[contains(concat(\" \", normalize-space(@class), \" \"), \" pm_unread \")])=0",$pmElement);
        // Get pm author
        $this->author=$xpath->evaluate("string(.//a[contains(concat(\" \", normalize-space(@class), \" \"), \" username-coloured \")]/text())",$pmElement);
        // Get pm datetime
        $this->datetime=new \DateTime(trim(str_replace("»","",$xpath->evaluate("string(.//text()[4])",$pmElement))));
        // Get pm link
        $this->link=$xpath->evaluate("string(.//a[contains(concat(\" \", normalize-space(@class), \" \"), \" topictitle \")]/@href)",$pmElement);
    }
    public function open()
    {
        // Load link
        $result=curlRequest(sprintf("%s/%s",$this->api->url,$this->link),null,$httpcode);
        $xpath=new \DomXPath($result);
        $this->contents=$xpath->evaluate("string(//div[contains(concat(\" \", normalize-space(@class), \" \"), \" content \")]/text())");
    }
    
}