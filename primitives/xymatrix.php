// name: xymatrix
$args = "";
$dim = array(
	     "row" => "4",
	     "col" => "6"
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
    // TODO: actually process this stuff
  }
// TODO: convert lengths (should be global function) 
$latex = $nexttok . $latex;
$matrix = nextgrp($latex);
$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1">' . "\n";

$svg .= '<defs><marker id="arrow" viewBox="0 0 10 10" refX="0" refY="5" 
      markerUnits="strokeWidth"
      markerWidth="10" markerHeight="10"
      orient="auto">
      <path d="M 0 0 L 10 5 L 0 10 z" />
    </marker></defs>' . "\n";

// build a matrix of entries
$m = 0;
$n = 0;
$maxwidth = 1;
$maxheight = 3;

// due to not vertically centering our entries, need a vertical fudge
$fudgeheight = 1/2;

while ($matrix)
  {
    $entry[$m][$n] = "";
    $nexttok = nexttok($matrix);
    while ($nexttok and ($nexttok != '&') and ($nexttok != '\\\\'))
      {
	if ($nexttok == '\\ar')
	  {
	    // got an arrow
	    $arrow = "";
	    $label = "";
	    $nexttok = nexttok($matrix);
	    while ($nexttok != '{')
	      {
		$arrow .= $nexttok;
	    	$nexttok = nexttok($matrix);
	      }
	    $matrix = $nexttok . "\0" . $matrix;
	    $label =  nextgrp($matrix);
	    $arrows[] = array(
			      "row" => $m,
			      "col" => $n,
			      "arrow" => $arrow,
			      "label" => '\(' . $label . '\)'
			      );
	  }
	else
	  {
	    //	    print $nexttok . ":" . htmlspecialchars($matrix) . "<br />";

	    // append to current entry
	    $entry[$m][$n] = $entry[$m][$n] . $nexttok;
	    $nexttok = nexttok($matrix);
	  }
      }
    $entry[$m][$n] = trim($entry[$m][$n]);
    $width[$m][$n] = (getWidthOf($entry[$m][$n]) + 2); // margin of error
    $height[$m][$n] = $maxheight; // need getHeightOf here

    if ($width[$m][$n] > $maxwidth)
      $maxwidth = $width[$m][$n];
    // should do same for height
    if ($nexttok == '&')
      {
	$n++;
      }
    elseif ($nexttok == '\\\\')
      {
	$m++;
	$n = 0;
      }
  }
$dim["row"] = ($dim["row"] + $maxwidth);
$dim["col"] = ($dim["col"] + $maxheight);


for($m = 0;$m < count($entry);$m++)
  {
    for($n = 0;$n < count($entry[$m]); $n++)
      {
	$svg .= '<foreignObject x="'
	  . ($n * $dim["row"])
	  . 'ex" y="'
	  . ($m * $dim["col"])
	  . 'ex" width="'
	  . $maxwidth
	  . 'ex" height="'
	  . $maxheight
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml"><div style="width:'
	  . $maxwidth
	  . 'ex,height:'
	  . ($maxheight -1)
	  . 'ex" align="center">'
	  . $entry[$m][$n]
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
      }
  }

for($i = 0; $i < count($arrows);$i++)
  {
    // extract significant parts from arrow
    // starting entry
    $sm = $arrows[$i]["row"];
    $sn = $arrows[$i]["col"];
    // displacement
    preg_match('/\[([^\]]*)\]/',$arrows[$i]["arrow"],$disp);
    // final entry
    $en = $sn + substr_count(strtolower($disp[1]),"r") - substr_count(strtolower($disp[1]),"l");
    $em = $sm + substr_count(strtolower($disp[1]),"d") - substr_count(strtolower($disp[1]),"u");
    // need to compute "anchors" for source and target
    // horizontally
    if ($en > $sn)
      {
	// target is to right of source
	$sx = ($sn * $dim["row"]) + $maxwidth - ($maxwidth - $width[$sm][$sn])/2;
	$ex = ($en * $dim["row"]) + ($maxwidth - $width[$em][$en])/2;
      }
    elseif ($en < $sn)
      {
	// target is to left of source
	$sx = ($sn * $dim["row"]) + ($maxwidth - $width[$sm][$sn])/2;
	$ex = ($en * $dim["row"]) + $maxwidth - ($maxwidth - $width[$em][$en])/2;
      }
    else
      {
	// target is in same column as source
	$sx = ($sn * $dim["row"]) + $maxwidth/2;
	$ex = ($sn * $dim["row"]) + $maxwidth/2;
      }
    // vertically
    if ($em > $sm)
      {
	// target is below source
	$sy = ($sm * $dim["col"]) + $maxheight - ($maxheight - $height[$sm][$sn])/2;
	$ey = ($em * $dim["col"]) + ($maxheigh - $height[$em][$en])/2;
      }
    elseif ($em < $sm)
      {
	// target is above source
	$sy = ($sm * $dim["col"]) + ($maxheight - $height[$sm][$sn])/2;
	$ey = ($em * $dim["col"]) + $maxheight - ($maxheight - $height[$em][$en])/2;
      }
    else
      {
	// target is in same row as source
	$sy = ($sm * $dim["col"]) + $maxheight/2;
	$ey = ($sm * $dim["col"]) + $maxheight/2;
      }

    $sy += $fudgeheight;
    $ey += $fudgeheight;

    // draw arrow
    $svg .= '<line x1="'
      . $sx
      . 'ex" y1="'
      . $sy
      . 'ex" x2="'
      . $ex
      . 'ex" y2="'
      . $ey
      . 'ex" stroke="black" stroke-width="1" marker-end="url(#arrow)" />'
      . "\n";

    // position label
    $labelwidth="10";
    $labelheight="3";
    $xoffset="1";
    $yoffset="2";

    // midpoints of lines
    $lx = (($sx + $ex)/2 - $labelwidth/2);
    $ly = (($sy + $ey)/2 - $labelheight/2 - $fudgeheight);
    // orthogonal direction
    $ox = $ey - $sy;
    $oy = $sx - $ex;
    // scale so that $ox >= $xoffset and $oy >= $yoffset

    $nx = round($xoffset*$ox/($ox*$ox + $oy*$oy)*20)/20;
    $ny = round($xoffset*$oy/($ox*$ox + $oy*$oy)*20)/20;
    $lx += $nx;
    $ly += $ny;

	$svg .= '<foreignObject x="'
	  . $lx
	  . 'ex" y="'
	  . $ly
	  . 'ex" width="'
	  . $labelwidth
	  . 'ex" height="'
	  . $labelheight
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml"><div style="width:'
	  . $labelwidth
	  . 'ex,height:'
	  . ($labelheight -1)
	  . 'ex" align="center">'
	  . $arrows[$i]["label"]
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
  }

$svg .= '</svg>' . "\n";

print '<pre>' . htmlspecialchars($svg) . '</pre>';

$latex = $svg . "\0" . $latex;
return;