// name: newenvironment
global $commands;
$name = nextgrp($latex); // first argument is name of command
$nextgrp = nextgrp($latex); // next is either defn or says we have arguments
if ($nextgrp == "[")
  {
    // have arguments, how many?
    $num = nextgrp($latex);
    nextgrp($latex); // ought to be a "]", should test this
    // should test for numeric here also
    $nextgrp = nextgrp($latex); // either optional first argument or defn
    if ($nextgrp == "[")
      {
	// option argument specified
	$nextgrp = nextgrp($latex);
	$opt = "";
	while ($nextgrp != "]")
	  {
	    // slurp everything up to but not including "]"
	    $opt = $opt . $nextgrp;
	    $nextgrp = nextgrp($latex);
	  }
	$optarray = array("1" => $opt);
	$startdefn = nextgrp($latex);
      }
    else
      {
	// no optional arguments, just defn
	$optarray = array();
	$startdefn = $nextgrp;
      }
  }
else
  {
    $num = 0;
    $optarray = array();
    $startdefn = $nextgrp;
  }

$enddefn = nextgrp($latex);

// strip off slashes, just in case
// need the four slashes as we are already inside a quoted string
$name=ltrim($name,"\\\\");
$commands[$name] = array(
			 "args" => $num,
			 "opts" => $optarray,
			 "defn" => $startdefn
			 );
$commands["end" . $name] = array(
				 "args" => 0,
				 "opts" => array(),
				 "defn" => $enddefn
				 );
	      return;
