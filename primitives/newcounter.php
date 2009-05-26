// name: newcounter
global $primitives;
global $counters;
$name = nextgrp($latex);
$counters[$name] = 0;
$primitives["the" . $name] = create_function('&$latex','
global $counters;
$latex = $counters["' . $name . '"] . "\0" . $latex;
return;');
return;