<?php
namespace PhpBBRemoteApi;

/**
 * Thrown when a login is unsuccessful
 */
class UnsuccessfulLoginException extends PhpBBRemoteApiException
{
  public function __construct($message)
  {
    parent::__construct("Unable to log in: $message", 0);
  }
}