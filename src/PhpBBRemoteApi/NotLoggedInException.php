<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
/**
 * Thrown when an action requires a login, but the user is not logged in
 */
class NotLoggedInException extends PhpBBRemoteApiException
{
  public function __construct()
  {
    parent::__construct("Exception: Not logged in to phpBB", 0);
  }
}