// name: if
global $conditionals;
$if = stripgrp(nextgrp($latex));
if (array_key_exists($if,$conditionals))
  {
    $then = "";
    $else = "";
    $nextgrp = nextgrp($latex);
    while (($nextgrp != '\\else') and ($nextgrp != '\\fi'))
      {
	$then .= $nextgrp;
	// ifs have to group so if we have a new if, slurp in everything until the fi
	// should an '\ifsth .. \fi' be considered as a group?  test this
	if (preg_match('/^\\\\if/',$nextgrp))
	  {
	    while ($nextgrp != '\\fi')
	      {
		$nextgrp = nextgrp($latex);
		$then .= $nextgrp;
	      }
	  }
	$nextgrp = nextgrp($latex);
      }
    if ($nextgrp == '\\else')
      {
	$nextgrp = nextgrp($latex);
	while ($nextgrp != '\\fi')
	  {
	    $else = $else . $nextgrp;
	    if (preg_match('/^\\\\if/',$nextgrp))
	      {
		while ($nextgrp != '\\fi')
		  {
		    $nextgrp = nextgrp($latex);
		    $else .= $nextgrp;
		  }
	      }
	    $nextgrp = nextgrp($latex);
	  }
      }
    if($conditionals[$if])
      {
	$latex = $then . "\0" . $latex;
      }
    else
      {
	$latex = $else . "\0" . $latex;
      }
  }
return;