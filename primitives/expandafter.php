// name: expandafter
$argone = nexttok($latex);
$argtwo = nexttok($latex);
list($mod,$exargtwo) = expandtok($argtwo,$latex);
$latex = $argone . "\0" . $exargtwo . "\0". $latex;
return;
