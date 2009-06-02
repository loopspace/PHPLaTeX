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
// remove nulls
$name = preg_replace('/\0/','',$name);
//list($mod,$extoken) = expandtok("\\" . $name,$latex);
$latex = "\\" . $name . "\0" . $latex;
return;

/*
 * \csname command\endcsname
 * Produces \command
 */