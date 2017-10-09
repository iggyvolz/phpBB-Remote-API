<?php
namespace PhpBBRemoteApi;
/**
 * Thrown when a topic was not found
 */
class TopicNotFoundException extends PhpBBRemoteApiException
{
  public function __construct($message)
  {
    parent::__construct("Topic not found: $message", 0);
  }
}