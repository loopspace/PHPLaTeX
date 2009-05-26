// name: csname
global $commands;
$latex = ltrim($latex," ");
$nexttok = nexttok($latex);
$name = "";
while ($nexttok != "\\endcsname")
  {
    $name = $name . $nexttok;
    $nexttok = nexttok($latex);
  }
list($mod,$extoken) = expandtok("\\" . $name,$latex);
$latex = $extoken . "\0" . $latex;
return;