<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
/**
 * Thrown when a forum was not found
 */
class forumNotFoundException extends PhpBBRemoteApiException
{
  public function __construct($message)
  {
    parent::__construct("Forum not found: $message", 0);
  }
}