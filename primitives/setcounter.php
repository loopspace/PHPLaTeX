// name: setcounter
global $counters;
$name = stripgrp(nextgrp($latex));
$amount = stripgrp(nextgrp($latex));
$counters[$name] = $amount;
return;