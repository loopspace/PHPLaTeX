// name: if
global $conditionals;
$if = nextgrp($latex);
if (array_key_exists($if,$conditionals))
  {
    $then = "";
    $else = "";
    $nextgrp = nextgrp($latex);
    while (($nextgrp != '\\else') and ($nextgrp != '\\fi'))
      {
	$then = $then . $nextgrp;
	$nextgrp = nextgrp($latex);
      }
    if ($nextgrp == '\\else')
      {
	$nextgrp = nextgrp($latex);
	while ($nextgrp != '\\fi')
	  {
	    $else = $else . $nextgrp;
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