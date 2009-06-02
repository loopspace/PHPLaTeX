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
$matrix = stripgrp(nextgrp($latex));
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
$maxheight = 3.5;

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
	    $inarrow = 1;
	    $upperdisplacement = "";
	    $upperlabel = "";
	    $lowerdisplacement = "";
	    $lowerlabel = "";
	    $middledisplacement = "";
	    $middlelabel = "";
	    $curving = "";
	    $stylevariant = "";
	    $style = "";
	    $displacement = "";
	    $control = "";
	    $swap = 0;
	    $dash = 0;
	    $target = "";

	    while ($inarrow)
	      {
		// ignore spaces
		$matrix = ltrim($matrix," ");
		$nexttok = nexttok($matrix);
		//		print htmlspecialchars($nexttok) . "<br />";
		if ($nexttok == '^')
		  {
		    print "label above<br />";
		    // label above
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    $matrix = ltrim($matrix," ");
		    if (preg_match('/(((< *){0,2}|(> *){0,2})(\([\.0-9]+\)))|!{[^;]+;[^}]+}/',$matrix,$matches))
		      {
			$upperdisplacement = $matches[0];
			$matrix = substr($matrix,strlen($upperdisplacement));
		      }
		    $matrix = ltrim($matrix," ");
		    $upperlabel = nextgrp($matrix);
		  }
		elseif ($nexttok == '_')
		  {
		    print "label below<br />";
		    // label below
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    $matrix = ltrim($matrix," ");
		    if (preg_match('/(((< *){0,2}|(> *){0,2})(\([\.0-9]+\)))|!{[^;]+;[^}]+}/',$matrix,$matches))
		      {
			$lowerdisplacement = $matches[0];
			$matrix = substr($matrix,strlen($upperdisplacement));
		      }
		    $matrix = ltrim($matrix," ");
		    $lowerlabel = nextgrp($matrix);
		  }
		elseif ($nexttok == '|')
		  {
		    print "label middle<br />";
		    // label in middle
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    ltrim($matrix," ");
		    if (preg_match('/(((< *){0,2}|(> *){0,2})(\([\.0-9]+\)))|!{[^;]+;[^}]+}/',$matrix,$matches))
		      {
			$middledisplacement = $matches[0];
			$matrix = substr($matrix,strlen($upperdisplacement));
		      }
		    ltrim($matrix," ");
		    $middlelabel = nextgrp($matrix);
		  }
		elseif ($nexttok == '@')
		  {
		    // lots of possibilities
		    $matrix = ltrim($matrix," ");
		    $nexttok = nexttok($matrix);
		    if ($nexttok == '/')
		      {
			print "curving<br />";
			// curving
			$nexttok = nexttok($matrix);
			while ($nexttok != '/')
			  {
			    $curving .= $nexttok;
			    $nexttok = nexttok($matrix);
			  }
		      }
		    elseif ($nexttok == '(')
		      {
			// also curving
			$nexttok = nexttok($matrix);
			while ($nexttok != ')')
			  {
			    print "curving<br />";
			    $curving .= $nexttok;
			    $nexttok = nexttok($matrix);
			  }
		      }
		    elseif ($nexttok == '{')
		      {
			print "style<br />";
			// style
			$matrix = $nexttok . $matrix;
			$style = nextgrp($matrix);
		      }
		    elseif (($nexttok == '^') or ($nexttok == '_') or ($nexttok == '2') or ($nexttok == '3'))
		      {
			print "style<br />";
			// style again
			$stylevariant = $nexttok;
			$style = nextgrp($matrix);
		      }
		    elseif ($nexttok == '<')
		      {
			print "displacement<br />";
			// displacement
			$nexttok = nexttok($matrix);
			while ($nexttok != '>')
			  {
			    $displacement .= $nexttok;
			    $nexttok = nexttok($matrix);
			  }
		      }
		    elseif ($nexttok == "'")
		      {
			print "control<br />";
			// control points
			$matrix = ltrim($matrix," ");
			$control = nextgrp($matrix);
		      }
		    elseif ($nexttok == "?")
		      {
			print "swap<br />";
			// swap above and belo
			$swap = 1;
		      }
		    elseif ($nexttok == "!")
		      {
			print "dash<br />";
			// dashed stem
			$dash = 1;
		      }
		  }
		elseif ($nexttok == "[")
		  {
		    print "target<br />";
		    // target
		    $nexttok = nexttok($matrix);
		    while ($nexttok != "]")
		      {
			$target .= $nexttok;
			$nexttok = nexttok($matrix);
		      }
		  }
		else
		  {
		    print "end of arrow<br />";
		    $inarrow = 0;
		  }
	      }

	    $matrix = $nexttok . "\0" . $matrix;
	    $arrows[] = array(
			      "row" => $m,
			      "col" => $n,
			      "upperdisplacement" => $upperdisplacement,
			      "upperlabel" => $upperlabel,
			      "lowerdisplacement" => $lowerdisplacement,
			      "lowerlabel" => $lowerlabel,
			      "middledisplacement" => $middledisplacement,
			      "middlelabel" => $middlelabel,
			      "curving" => $curving,
			      "stylevariant" => $stylevariant,
			      "style" => $style,
			      "displacement" => $displacement,
			      "control" => $control,
			      "swap" => $swap,
			      "dash" => $dash,
			      "target" => $target
			      );
	    print "upperdisplacement:" . htmlspecialchars($upperdisplacement) . "<br />";
	    print "upperlabel:" . htmlspecialchars($upperlabel) . "<br />";
	    print "lowerdisplacement:" . htmlspecialchars($lowerdisplacement) . "<br />";
	    print "lowerlabel:" . htmlspecialchars($lowerlabel) . "<br />";
	    print "middledisplacement:" . htmlspecialchars($middledisplacement) . "<br />";
	    print "middlelabel:" . htmlspecialchars($middlelabel) . "<br />";
	    print "curving:" . htmlspecialchars($curving) . "<br />";
	    print "stylevariant:" . htmlspecialchars($stylevariant) . "<br />";
	    print "style:" . htmlspecialchars($style) . "<br />";
	    print "displacement:" . htmlspecialchars($displacement) . "<br />";
	    print "control:" . htmlspecialchars($control) . "<br />";
	    print "swap:" . htmlspecialchars($swap) . "<br />";
	    print "dash:" . htmlspecialchars($dash) . "<br />";
	    print "target:" . htmlspecialchars($target) . "<br />";


	  }
	else
	  {
	    // append to current entry
	    $entry[$m][$n] = $entry[$m][$n] . $nexttok;
	  }
	$nexttok = nexttok($matrix);
      }
    $entry[$m][$n] = '\(' . trim($entry[$m][$n]) . '\)';
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
    $upperdisplacement = $arrows[$i]["upperdisplacement"];
    $upperlabel = $arrows[$i]["upperlabel"];
    $lowerdisplacement = $arrows[$i]["lowerdisplacement"];
    $lowerlabel = $arrows[$i]["lowerlabel"];
    $middledisplacement = $arrows[$i]["middledisplacement"];
    $middlelabel = $arrows[$i]["middlelabel"];
    $curving = $arrows[$i]["curving"];
    $stylevariant = $arrows[$i]["stylevariant"];
    $style = $arrows[$i]["style"];
    $displacement = $arrows[$i]["displacement"];
    $control = $arrows[$i]["control"];
    $swap = $arrows[$i]["swap"];
    $dash = $arrows[$i]["dash"];


    // final entry
    $en = $sn + substr_count(strtolower($target),"r") - substr_count(strtolower($target),"l");
    $em = $sm + substr_count(strtolower($target),"d") - substr_count(strtolower($target),"u");
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

    $nx = round($ox/($ox*$ox + $oy*$oy)*20)/20;
    $ny = round($oy/($ox*$ox + $oy*$oy)*20)/20;
    $lx += 10*$nx;
    $ly += 10*$ny - $fudgeheight;

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
	  . "\(" . $upperlabel . "\)"
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
  }

$svg .= '</svg>' . "\n";

print '<pre>' . htmlspecialchars($svg) . '</pre>';

$latex = $svg . "\0" . $latex;
return;