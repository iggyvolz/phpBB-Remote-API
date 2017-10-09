<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
/**
 * Class for accessing phpBB remotely using data scraping
 */
class phpbbRemoteApi
{
  const COOKIE_FILE="cookies.txt";
  const TIMEOUT=50;
  public $url;
  public $user;
  private $pass;
  /**
   * Creates an instance of phpbbRemoteApi
   *
   * @param string $url URL of the base of the forum (example: example.com/forum)
   * @param string $user The username of the logged in user, if you want to log in (ignored if $pass is not passed)
   * @param string $pass The password of the logged in user, if you want to log in
   * 
   * @throws UnsuccessfulLoginException if the phpBB login failed
   */
  public function __construct($url,$user=NULL,$pass=NULL)
  {
    list($this->url,$this->user,$this->pass)=[$url,$user,$pass];
    // Log in user
    if($user&&$pass)
    {
      $httpcode=200;
      $result=curlRequest(sprintf("%s/ucp.php?mode=login",$this->url),["username"=>$this->user,"password"=>$this->pass,"redirect"=>"./ucp.php","mode"=>"login","login"=>"Login"],$httpcode);
      // Detect login state
      // Check if div class="error" exists
      // Xpath selector from https://stackoverflow.com/a/6366390
      $finder = new \DomXPath($result);
      $errors=$finder->query("//*[contains(@class, 'error')]");
      if($errors->length>0)
      {
        // Get the error text
        $error=$errors->item(0)->textContent;
        throw new UnsuccessfulLoginException($error);
      }
    }
  }
  /**
   * Gets a forum object
   *
   * @param int $f The ID of the forum to get
   * @return Forum The forum object
   * @throws ForumNotFoundException if the forum was not found
   */
  public function get_forum($f)
  {
    return new Forum($this,$f);
  }
  /**
   * Gets a message box object
   *
   * @param int $id The ID of the message box to get
   * @return MessageBox The message box
   * @throws MessagBoxNotFoundException if the message box was not found
   */
  public function get_message_box($id)
  {
    return new MessageBox($this,$id);
  }
}