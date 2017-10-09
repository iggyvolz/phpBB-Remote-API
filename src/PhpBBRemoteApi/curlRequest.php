<?php
namespace PhpBBRemoteApi;
require_once "bootstrap.php";
/**
 * Makes a curl request to a forum
 *
 * @param string $url URL to request
 * @param array|null $params Parameters to pass via POST (GET used if null is passed)
 * @param int &$httpcode Outputs last HTTP response code
 * @return DOMDOcument Document of loaded page
 */
function curlRequest($url,$params=NULL, &$httpcode=null)//:DOMDocument
{
  $pparams=json_encode($params);
  echo "CURL REQUEST TO $url WITH PARAMS $pparams\n";
  //sleep(3);
  $handle=curl_init($url);
  curl_setopt($handle, CURLOPT_COOKIEFILE, phpbbRemoteApi::COOKIE_FILE);
  curl_setopt($handle, CURLOPT_COOKIEJAR,   phpbbRemoteApi::COOKIE_FILE);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($handle, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)");
  curl_setopt($handle, CURLOPT_TIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, round(phpbbRemoteApi::TIMEOUT,0));
  if($params)
  {
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($params));
  }
  $result=curl_exec($handle);
  // Get HTTP response code
  if($httpcode !== NULL)
  {
    $httpcode=curl_getinfo($handle,CURLINFO_HTTP_CODE);
  }
  curl_close($handle);
  $dom=new \DOMDocument();
  // Ignore id errors
  $previous_value = libxml_use_internal_errors(TRUE);
  $dom->loadHTML($result);
  libxml_clear_errors();
  libxml_use_internal_errors($previous_value);
  return $dom;
}