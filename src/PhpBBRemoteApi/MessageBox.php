<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
class MessageBox
{
    public $api;
    public $pms=[];
    /**
     * Loads a phpBB message box by id
     *
     * @param phpbbRemoteApi $api Board API object
     * @param string $id
     */
    public function __construct($api,$id)
    {
        $this->api=$api;
        $this->id=$id;
    }
    /**
     * Gets a page of pms
     *
     * @param int $start The index to start with (0 being the newest topic, infinity the oldest).  If higher than the number of topics, displays the last page.
     * @return Topic[] Up to 25 within the pmbox, beginning at $start
     */
    public function get_page($start=0)
    {
        $httpcode=200;
        $result=curlRequest(sprintf("%s/ucp.php?i=pm&folder=%s",$this->api->url,$this->id),null,$httpcode);
        // Cannot check for errors - defaults to inbox
        $xpath=new \DomXPath($result);
        $pms=$xpath->query("//ul[contains(concat(\" \", normalize-space(@class), \" \"), \" pmlist \")]/li");
        for($i=0;$i<$pms->length;$i++)
        {
            $this->pms[$i]=new PM($this->api,$pms->item($i));
        }
        return $this->pms;
    }
}