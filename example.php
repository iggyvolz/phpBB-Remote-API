<?php
require "api.php";
require "settings.php";
date_default_timezone_set("America/New_York");
$api=new phpbbRemoteApi(PHPBB_URL,PHPBB_FORUM,PHPBB_TOPIC,PHPBB_USER,PHPBB_PASSWORD);
$api->create_post("Post Title","Post Data");
$api->download_post(5);
echo $api->get_post(5)->author;
