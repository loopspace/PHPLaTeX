// name: xymatrix
$args = "";
$dim = array(
	     "row" => "2",
	     "col" => "2"
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
// TODO: convert lengths (should be global function) 
$latex = $nexttok . $latex;
$matrix = nextgrp($latex);
$svg = '<svg xmlns="http://www.w3.org/2000/svg">' . "\n";
// build a matrix of entries
$m = 0;
$n = 0;
$maxwidth = 1;
$maxheight = 1.5;
while ($matrix)
  {
    $entry[$m][$n] = "";
    $nexttok = nexttok($matrix);
    while ($nexttok and ($nexttok != '&') and ($nexttok != '\\\\'))
      {
	$entry[$m][$n] = $entry[$m][$n] . $nexttok;
	$nexttok = nexttok($matrix);
      }
    if ($nexttok == '&')
      {
	$n++;
      }
    elseif ($nexttok == '\\\\')
      {
	$m++;
	$n = 0;
      }
    $width = strlen($entry[$m][$n]); // TODO: replace by something better
    if ($width > $maxwidth)
      $maxwidth = $width;
    // same for height
  }
$dim["row"] = $dim["row"] + $maxwidth;
$dim["col"] = $dim["col"] + $maxheight;


for($m = 0;$m < count($entry);$m++)
  {
    for($n = 0;$n < count($entry[$m]); $n++)
      {
	$svg = $svg
	  . '<foreignObject x="'
	  . ($n * $dim["row"])
	  . 'em" y="'
	  . ($m * $dim["col"])
	  . 'em" width="'
	  . $maxwidth
	  . 'em" height="'
	  . ($maxheight + 1)
	  . 'em">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml"><div style="width:'
	  . $maxwidth
	  . 'em,height:'
	  . $maxheight
	  . 'em" align="center">'
	  . $entry[$m][$n]
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
      }
  }
$svg = $svg . '</svg>';

print '<pre>' . htmlspecialchars($svg) . '</pre>';

$latex = $svg . "\0" . $latex;
return;