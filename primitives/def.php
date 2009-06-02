// name: def
global $defs;
$name = stripgrp(nextgrp($latex)); // first argument is name of command
$nexttok = nexttok($latex);
$pattern = "";
while($nexttok != "{")
  {
    $pattern .= $nexttok;
    $nexttok = nexttok($latex);
  }
$latex = $nexttok . $latex;
$defn = stripgrp(nextgrp($latex));
// strip off slashes if needed
// need the four slashes as we are already inside a quoted string
$name = ltrim($name,"\\\\");
$defs[$name] = array(
                     "pattern" => $pattern,
                     "defn" => $defn
		     );
return;
