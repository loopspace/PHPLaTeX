// name: newcounter
global $commands;
global $counters;
$name = stripgrp(nextgrp($latex));
$counters[$name] = 0;
$commands["the" . $name] = array(
				 "args" => 0,
				 "opts" => array(),
				 "defn" => '\usecounter{' . $name . '}'
				 );
return;