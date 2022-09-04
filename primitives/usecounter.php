// name: usecounter
global $counters;
$name = stripgrp(nextgrp($latex));
if (array_key_exists($name,$counters))
  {
    $latex = $counters[$name] . "\0" . $latex;
  }
return;
