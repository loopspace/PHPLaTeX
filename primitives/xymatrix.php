// name: xymatrix
$args = "";
$dim = array(
	     "row" => "20",
	     "col" => "20"
	     );
// morally, \ifnextchar{@} ...
$nexttok = nexttok($latex);
while ($nexttok != "{")
  {
    print "and here";
    $args = $args . $nexttok;
    $nexttok = nexttok($latex);
  }
// process dimensions
if ($args)
  {
    $argarray = explode("@",$args);
    
  }
$latex = $nexttok . $latex;
$matrix = nextgrp($latex);
$svg = '<svg xmlns="http://www.w3.org/2000/svg">' . "\n";
while ($matrix)
  {
    $entry = "";
    $nexttok = nexttok($matrix);
    while ($nexttok and ($nexttok != '&') and ($nexttok != '\\\\'))
      {
	$entry = $entry . $nexttok;
	$nexttok = nexttok($matrix);
      }
    $svg .= '<foreignObject x="' . ($x * $dim["row"]) . '" y="' . ($y * $dim["col"]) . '">' . $entry . '</foreignObject>' . "\n";
    if ($nexttok == '&')
      {
	$x++;
      }
    elseif ($nexttok == '\\\\')
      {
	$y++;
	$x = 0;
      }
  }
$svg = $svg . '</svg>';
print '<pre>' . htmlspecialchars($svg) . '</pre>';
return;