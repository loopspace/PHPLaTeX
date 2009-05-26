// name: def
global $defs;
list($mod,$name) = expandtok(nexttok($latex),$latex); // first argument is name of command, need to strip off brackets
$nexttok = nexttok($latex);
$pattern = "";
while(strlen($nexttok) == 1)
  {
    $pattern .= $nexttok;
    $nexttok = nexttok($latex);
  }
$defn = $nexttok;
// strip off slashes if needed
// need the four slashes as we are already inside a quoted string
$name = ltrim($name,"\\\\");
$defs[$name] = array(
                     "pattern" => $pattern,
                     "defn" => $defn
		     );
return;
