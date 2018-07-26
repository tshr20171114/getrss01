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

$url3 = 'none';
for ($i = 0; $i < 2; $i++) {
  if ($i == 0) {
     $pattern = explode(',', getenv('LINK_PATTERN1'));
  } else {
     $pattern = explode(',', getenv('LINK_PATTERN2'));
  }
  $rc = preg_match('/' . $pattern[0] . '/', $res, $matches);

  if ($rc != 1) {
    continue;
  }

  error_log("${pid} " . $matches[1]);

  $url3 = str_replace($pattern[1], $matches[1], $pattern[2]);

  error_log("${pid} ${url3}");
  error_log("${pid} " . urlencode($url3));
  error_log("${pid} " . sha1($url3));
}

header('Content-Type: text/plain');
echo  $url3;

?>
