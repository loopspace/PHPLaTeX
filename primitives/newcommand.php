// name: newcommand
global $commands;
list($mod,$name) = expandtok(nexttok($latex),$latex); // first argument is name of command, need to strip off brackets
$nexttok = nexttok($latex); // next is either defn or says we have arguments
if ($nexttok == "[")
  {
    // have arguments, how many?
    $num = nexttok($latex);
    nexttok($latex); // ought to be a "]", should test this
    // should test for numeric here also
    $nexttok = nexttok($latex); // either optional first argument or defn
    if ($nexttok == "[")
      {
	// option argument specified
	$nexttok = nexttok($latex);
	$opt = "";
	while ($nexttok != "]")
	  {
	    // slurp everything up to but not including "]"
	    $opt = $opt . $nexttok;
	    $nexttok = nexttok($latex);
	  }
	$optarray = array("1" => $opt);
	$defn = nexttok($latex);
      }
    else
      {
	// no optional arguments, just defn
	$optarray = array();
	$defn = $nexttok;
      }
  }
else
  {
    $num = 0;
    $optarray = array();
    $defn = $nexttok;
  }

// strip off slashes, just in case
// need the four slashes as we are already inside a quoted string
$name=ltrim($name,"\\\\");
$commands[$name] = array(
			 "args" => $num,
			 "opts" => $optarray,
			 "defn" => $defn
			 );
	      return;
