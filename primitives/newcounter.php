// name: newcounter
global $primitives;
global $counters;
$name = nextgrp($latex);
$counters[$name] = 0;
$commands["the" . $name] = array(
				 "args" => 1,
				 "opts" => array(),
				 "defn" => '\usecounter{' . $name . '}'
				 );
return;