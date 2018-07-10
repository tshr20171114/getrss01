<?php

function f_md5($image_url_) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $image_url_);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $contents = curl_exec($ch);
  curl_close($ch);
  
  $original_image = imagecreatefromstring($contents);
  $w = imagesx($original_image);
  $h = imagesy($original_image);
  $new_image = imagecreatetruecolor(48, 48);
  imagecopyresampled($new_image, $original_image, 0, 0, 0, 0, 48, 48, $w, $h);
  imagedestroy($original_image);
  imagefilter($new_image, IMG_FILTER_GRAYSCALE);
  ob_start();
  imagejpeg($new_image);
  $data = ob_get_clean();
  imagedestroy($new_image);
  return md5($data);
}

function f_parse($html_, $host_, $page_) {
 
  $pid = getmypid();
 
  $connection_info = parse_url(getenv('DATABASE_URL'));
  $pdo = new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']);
    
  $sql = <<< __HEREDOC__
SELECT M1.type
      ,M1.word
  FROM m_words M1
 WHERE M1.type > 200
__HEREDOC__;
  
  $words = array();
  foreach ($pdo->query($sql) as $row) {
    $words[(string)$row['type']] = $row['word'];
  }
  
  $buf = $html_;
  $buf = explode($words['201'], $buf, 2)[1];
  $buf = explode($words['202'], $buf, 2)[0];

  $sql = <<< __HEREDOC__
INSERT INTO t_contents2
( uri, title, thumbnail, thumbnail_hash, time, page ) VALUES ( :b_uri, :b_title, :b_thumbnail, :b_thumbnail_hash, :b_time, :b_page )
__HEREDOC__;
  $statement = $pdo->prepare($sql);

  $sql = <<<  __HEREDOC__
SELECT thumbnail_hash
  FROM t_file_hash
 WHERE thumbnail = :b_thumbnail
__HEREDOC__;
  $statement_select = $pdo->prepare($sql);

  $sql = <<< __HEREDOC__
INSERT INTO t_file_hash
( thumbnail, thumbnail_hash ) VALUES ( :b_thumbnail, :b_thumbnail_hash )
__HEREDOC__;
  $statement_insert = $pdo->prepare($sql);

  foreach(explode($words['203'], $buf) as $one_record) {

    /*
    if (strpos($one_record, $words['204']) === false) {
      continue;
    }

    if (strpos($one_record, $words['207']) === false) {
      continue;
    }

    if (strpos($one_record, $words['206']) !== false) {
      continue;
    }
    */    
    switch (true) {
      case strpos($one_record, $words['204']) === false:
      case strpos($one_record, $words['207']) === false:
      case strpos($one_record, $words['206']) !== false:
        continue 2;
    }   
   
    if (preg_match($words['205'], $one_record, $matches) == 0) {
      continue;
    }
    $time = $matches[1];
    
    if (preg_match('/^([1-4]\d|\d)$/', $time, $matches) == 1) {
      continue;
    }
    
    /*
    if (preg_match('/<a href="(.+?)"/', $one_record, $matches) == 0) {
      continue;
    }
    $href = $matches[1];
    
    if (preg_match('/<img src="(.+?)"/', $one_record, $matches) == 0) {
      continue;
    }
    $thumbnail = 'https:' . $matches[1];
    
    if (preg_match($words['208'], $one_record, $matches) == 0) {
      continue;
    }
    $title = $matches[1];
   
    if (preg_match('/<a .+? title="(.+?)"/', $one_record, $matches) == 0) {
      continue;
    }
    $title .= ' ' . htmlspecialchars($matches[1]);
    */
    
    switch (true) {
      case preg_match('/<a href="(.+?)"/', $one_record, $matches1) == 0:
      case preg_match('/<img src="(.+?)"/', $one_record, $matches2) == 0:
      case preg_match($words['208'], $one_record, $matches3) == 0:
      case preg_match('/<a .+? title="(.+?)"/', $one_record, $matches4) == 0:
        continue 2;
    }
    $href = $matches1[1];
    $thumbnail = 'https:' . $matches2[1];
    // $title = $matches3[1] . ' ' . htmlspecialchars($matches4[1]);
    $title = $matches3[1] . ' ' . $matches4[1];
   
    $statement_select->execute(
      [
        ':b_thumbnail' => $thumbnail,
      ]);
    $result = $statement_select->fetch();
    
    if ($result === FALSE) {
      // $thumbnail_hash = md5_file($thumbnail);
      $thumbnail_hash = f_md5($thumbnail);
      $statement_insert->execute(
        [
          ':b_thumbnail' => $thumbnail,
          ':b_thumbnail_hash' => $thumbnail_hash,
        ]);          
    } else {
      error_log("${pid} hash hit");
      $thumbnail_hash = $result['thumbnail_hash'];
    }
    
    //error_log("${time} ${title} ${href} ${thumbnail} ${page_}");
    error_log("${pid} ${href} ${title}");
   
    $statement->execute(
      [
        ':b_uri' => $href,
        ':b_title' => $title,
        ':b_thumbnail' => $thumbnail,
        ':b_thumbnail_hash' => $thumbnail_hash,
        ':b_time' => $time,
        ':b_page' => $page_
      ]);
  }
  
  $pdo = null;
  
  return;
}

$start_time = time();

$max_count = getenv('MAX_COUNT_2');
$per_count = getenv('PER_COUNT_2');

$pid = getmypid();
$count = $_GET['c'];
$url = $_GET['u'];

error_log("${pid} START ${count}");

$count++;

$target_url = getenv('SEARCH_URL_2');

if ($count > $max_count) {
  error_log("${pid} FINISH ${count}");
  return;
}

if ($count === 1) {
  $connection_info = parse_url(getenv('DATABASE_URL'));
  $pdo = new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']);

  $pdo->exec('TRUNCATE TABLE t_contents2');

  $pdo = null;
}

$urls = array();
for($i = 0; $i < $per_count; $i++) {
  $urls[] = $target_url . (($count - 1) * $per_count + $i + 1);
}
$urls[] = $url . '?c=' . $count . '&u=' . $url;

$mh = curl_multi_init();
curl_multi_setopt($mh, CURLMOPT_PIPELINING, 3);

foreach($urls as $url) {
  error_log("${pid} ${url}");
  $ch = curl_init();
  curl_setopt_array($ch, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/61.0'
    ));
  curl_multi_add_handle($mh, $ch);
}

do {
  $stat = curl_multi_exec($mh, $running);
} while ($stat === CURLM_CALL_MULTI_PERFORM);

do switch (curl_multi_select($mh, 10)) {
  case -1:
    usleep(10);
    do {
      $stat = curl_multi_exec($mh, $running);
    } while ($stat === CURLM_CALL_MULTI_PERFORM);
    continue 2;
  case 0:
    continue 2;
  default:
    do {
      $stat = curl_multi_exec($mh, $running);
    } while ($stat === CURLM_CALL_MULTI_PERFORM);
    
    do if ($raised = curl_multi_info_read($mh, $remains)) {
      $info = curl_getinfo($raised['handle']);
      $page = explode('=', parse_url($info['url'], PHP_URL_QUERY), 4)[3];
      $host = parse_url($info['url'], PHP_URL_HOST);
      $response = curl_multi_getcontent($raised['handle']);
      //error_log("${pid} ${query_string} " . strlen($response));
      //error_log($response);
      f_parse($response, $host, $page);
      curl_multi_remove_handle($mh, $raised['handle']);
      curl_close($raised['handle']);
    } while ($remains);
} while ($running);

curl_multi_close($mh);

$sql = <<< __HEREDOC__
SELECT T1.uri
      ,T1.title
      ,T1.thumbnail
      ,T1.thumbnail_hash
      ,T1.time
  FROM t_contents2 T1
 ORDER BY CAST(T1.page AS integer)
         ,T1.create_time
__HEREDOC__;

$xml_root_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>rss2</title>
    <link>http://www.yahoo.co.jp</link>
    <description>none</description>
    <language>ja</language>
    __ITEMS__
  </channel>
</rss>
__HEREDOC__;

$base_url = getenv('BASE_URL');
if ($count === 1) {  
  $connection_info = parse_url(getenv('DATABASE_URL'));
  $pdo = new PDO(
    "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
    $connection_info['user'],
    $connection_info['pass']);

  $items = array();
  foreach ($pdo->query($sql) as $row) {
    $uri = $row['uri'];
    $title = $row['title'];
    $thumbnail = $row['thumbnail'];
    $time = $row['time'];
    // $tag = explode('/', $uri)[4];
    $thumbnail_hash = $row['thumbnail_hash'];
    $items[] = "<item><title>${time}min ${title}</title><link>${base_url}${uri}</link><description>&lt;img src='${thumbnail}'&gt;${thumbnail_hash}</description><pubDate></pubDate><category>${thumbnail_hash}</category></item>";
    error_log($thumbnail_hash . ' ' . $thumbnail);
  }

  header('Content-Type: application/xml; charset=UTF-8');
  echo str_replace('__ITEMS__', implode("\r\n", $items), $xml_root_text);
  $pdo = null;
  
  error_log('items : ' . count($items) . ' ' . $_SERVER['REQUEST_URI']);
} else {
  echo '<HTML><HEAD><TITLE>' . ($start_time - time()) . '</TITLE></HEAD><BODY>' . time() . '</BODY></HTML>';
}

error_log("${pid} FINISH ${count}");
?>
