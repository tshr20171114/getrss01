<?php

$pid = getmypid();

$url = $_GET['u'];

error_log("${pid} ${url}");

$res = file_get_contents($url);

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);

if ($rc != 1) {
  exit;
}

$url2 = $matches[1];

error_log("${pid} ${url2}");

$res = file_get_contents($url2);

/*
$pattern1 = getenv('PATTERN1');

$rc = preg_match('/' . $pattern1 . '/', $res, $matches);
*/

$pattern2[] = explode(',', getenv('LINK_PATTERN2'));

error_log("${pid} " . getenv('LINK_PATTERN2'));
error_log("${pid} " . $pattern2[0]);
error_log("${pid} " . $pattern2[1]);
error_log("${pid} " . $pattern2[2]);

$rc = preg_match('/' . $pattern2[0] . '/', $res, $matches);

if ($rc != 1) {
  exit;
}

error_log("${pid} " . $matches[1]);

$url3 = str_replace($pattern2[1], $matches[1], $pattern2[2]);

error_log("${pid} ${url3}");

header('Content-Type: text/plain');
echo  $url3;

?>
