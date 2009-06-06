// name: entity
$name = stripgrp(nextgrp($latex));
$name = preg_replace('/\0/',"",$name);
$latex = '&' . $name . ';' . "\0" . $latex;
return;