// name: true
global $conditionals;
$name = nextgrp($latex);
if (array_key_exists($name,$conditionals))
  {
    $conditionals[$name] = 1;
  }
return;