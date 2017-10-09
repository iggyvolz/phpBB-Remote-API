<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
/**
 * A phpBB user
 */
class User
{
  public $username;
  // TODO
  public function __construct($username)
  {
    $this->username=$username;
  }
}
