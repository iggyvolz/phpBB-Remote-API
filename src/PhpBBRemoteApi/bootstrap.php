<?php
namespace PhpBBRemoteApi;
date_default_timezone_set("UTC");
$scan=scandir(__DIR__);
require_once "PhpbbRemoteApiException.php";
foreach($scan as $file)
{
    if($file[0] == ".") continue;
    require_once $file;
}