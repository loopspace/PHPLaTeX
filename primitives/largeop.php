// name: MakeLargeOp
global $isLargeOp;
$name = stripgrp(nextgrp($latex));
$isLargeOp[$name] = 1;
$isLargeOp[$entity] = 1;
$entity = stripgrp(nextgrp($latex));
LaTeXdebug($name,1);
$latex = '\newcommand{' . $name . '}{\mathop[movablelimits="true"]{' . $entity . '}}' . "\0" . $latex;
return;