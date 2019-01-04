<?php

$url = getenv('URL_010');

$res = file_get_contents($url);

error_log($res);


