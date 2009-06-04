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

// build a matrix of entries
$m = 0;
$n = 0;
$maxwidth = 1;
$maxheight = 3.5;
$numcols = 0;

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
	    $swap = 1;
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
		    if (preg_match('/!{[^;]+;[^}]+}|(?:> *>?|< *<?)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
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
		    if (preg_match('/!{[^;]+;[^}]+}|(?:> *>?|< *<?)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
		      {
			$lowerdisplacement = $matches[0];
			$matrix = substr($matrix,strlen($lowerdisplacement));
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
		    if (preg_match('/!{[^;]+;[^}]+}|(?:> *>?|< *<?)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
		      {
			$middledisplacement = $matches[0];
			$matrix = substr($matrix,strlen($middledisplacement));
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
		    elseif (preg_match('/^</',$nexttok))
		      {
			// nexttok thinks that (x)html tags are a single token so actually gets all of this in one go
			print "displacement<br />";
			// displacement
			$displacement = substr($nexttok,1,strlen($nexttok)-2);
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
			$swap = -1;
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
	    print "source: $m $n<br />";
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
	if ($n > $numcols)
	  $numcols = $n;
	$n = 0;
      }
  }
$dim["row"] = ($dim["row"] + $maxwidth);
$dim["col"] = ($dim["col"] + $maxheight);
$numrows = count($matrix);

$svgwidth = 2*($dim["row"]*$numrows);
$svgheight =  2*($dim["col"]*$numcols);

$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ';
$svg .= 'width="' 
  . $svgwidth
  . 'ex" height="' 
  . $svgheight
  . 'ex">'
  . "\n";

$svg .= '<defs><marker id="arrow" viewBox="0 0 10 10" refX="10" refY="5" 
      markerUnits="strokeWidth"
      markerWidth="10" markerHeight="10"
      orient="auto">
      <path d="M 0 0 L 10 5 L 0 10 z" />
    </marker></defs>' . "\n";

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
    $target = $arrows[$i]["target"];

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
	$ey = ($em * $dim["col"]) + ($maxheight - $height[$em][$en])/2;
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

    // direction of arrow
    $bx = $ex - $sx;
    $by = $ey - $sy;
    $ax = round($bx/sqrt($bx*$bx + $by*$by)*20)/20;
    $ay = round($by/sqrt($bx*$bx + $by*$by)*20)/20;

    // orthogonal direction
    $ox = $ey - $sy;
    $oy = $sx - $ex;
    $nx = round($ox/sqrt($ox*$ox + $oy*$oy)*20)/20;
    $ny = round($oy/sqrt($ox*$ox + $oy*$oy)*20)/20;

    $sx += $nx * MakeEx($displacement);
    $sy += $ny * MakeEx($displacement);
    $ex += $nx * MakeEx($displacement);
    $ey += $ny * MakeEx($displacement);

    // midpoints of lines
    $mx = ($sx + $ex)/2;
    $my = ($sy + $ey)/2;

    // draw arrow
    // This is a bit of a cludge to rescale the arrow so that '1px' becomes '1ex'.  This is needed because the 'path' element only takes bare units.
    $svg .= '<svg width="'
      . $svgwidth
      . 'ex" height="'
      . $svgheight
      . 'ex" viewBox="0 0 '
      . $svgwidth
      . ' '
      . $svgheight
      . '">'
      . "\n"
      . '<path d="';

    // If curving, define a quadratic bezier with control point defined by offsetting the midpoint by the normal vector
    if ($curving)
      {
	if (preg_match('/\[([udlr ]+)\] *, *\[([udlr ]+)\]/',$curving,$dirs))
	  {
	    print "$dirs[1] $dirs[2]";
	    // Need to recompute the anchors
	    // horizontally
	    if (stripos($dirs[1],"r") !== FALSE)
	      {
		// arrow should leave source to the right
		$csx = ($sn * $dim["row"]) + $maxwidth - ($maxwidth - $width[$sm][$sn])/2;
		$ccsx = 1;
	      }
	    elseif (stripos($dirs[1],"l") !== FALSE)
	      {
		// arrow should leave source to the left
		$csx = ($sn * $dim["row"]) + ($maxwidth - $width[$sm][$sn])/2;
		$ccsx = -1;
	      }
	    else
	      {
		// arrow leaves in the middle
		$csx = ($sn * $dim["row"]) + $maxwidth/2;
		$ccsx = 0;
	      }
	    if (stripos($dirs[2],"r") !== FALSE)
	      {
		// arrow should enter target from the right
		$cex = ($en * $dim["row"]) + $maxwidth - ($maxwidth - $width[$em][$en])/2;
		$ccex = -1;
	      }
	    elseif (stripos($dirs[2],"l") !== FALSE)
	      {
		// arrow should enter target from the left
		$cex = ($en * $dim["row"]) + ($maxwidth - $width[$em][$en])/2;
		$ccex = 1;
	      }
	    else
	      {
		// arrow arrives in the middle
		$cex = ($en * $dim["row"]) + $maxwidth/2;
		$ccex = 0;
	      }
	    // vertically
	    if (stripos($dirs[1],"d") !== FALSE)
	      {
		// arrow should leave source downwards
		$csy = ($sm * $dim["col"]) + $maxheight - ($maxheight - $height[$sm][$sn])/2;
		$ccsy = 1;
	      }
	    elseif (stripos($dirs[1],"u") !== FALSE)
	      {
		// arrow should leave source upwards
		$csy = ($sm * $dim["col"]) + ($maxheight - $height[$sm][$sn])/2;
		$ccsy = -1;
	      }
	    else
	      {
		// arrow should leave source in the middle
		$csy = ($sm * $dim["col"]) + $maxheight/2;
		$ccsy = 0;
	      }
	    if (stripos($dirs[2],"d") !== FALSE)
	      {
		// arrow should enter target from below
		$cey = ($em * $dim["col"]) + $maxheight - ($maxheight - $height[$em][$en])/2;
		$ccey = 1;
	      }
	    elseif (stripos($dirs[2],"u") !== FALSE)
	      {
		// arrow should enter target from above
		$cey = ($em * $dim["col"]) + ($maxheight - $height[$em][$en])/2;
		$ccey = -1;
	      }
	    else
	      {
		// arrow should enter target in the middle
		$cey = ($em * $dim["col"]) + $maxheight/2;
		$ccey = 0;
	      }

	    $csy += $fudgeheight;
	    $cey += $fudgeheight;
	    $cntl = 4;

	    $svg .= 'M '
	      . $csx
	      . ' '
	      . $csy
	      . ' C '
	      . ($csx + $ccsx*$cntl)
	      . ' '
	      . ($csy + $ccsy*$cntl)
	      . ' '
	      . ($cex + $ccex*$cntl)
	      . ' '
	      . ($cey + $ccey*$cntl)
	      . ' '
	      . $cex
	      . ' '
	      . $cey;
	    
	  }
	else
	  {
	    $type = substr($curving,0,1);
	    if ($type == "_")
	      {
	    $dir = -1;
	      }
	    else
	      {
		$dir = 1;
	      }
	    $length = substr($curving,1);
	    $length = trim($length);
	    if ($length)
	      {
		$scale = MakeEx($length);
	      }
	    else
	      {
		$scale = 1;
	      }

	    // should we displace the start and finish slightly?
	    $svg .= 'M '
	      . $sx
	      . ' '
	      . $sy
	      . ' Q '
	      . ($mx + $dir*$scale*$nx)
	      . ' '
	      . ($my + $dir*$scale*$ny)
	      . ' '
	      . $ex
	      . ' '
	      . $ey;
	  }
      }
    else
      {
	$svg .= 'M '
	  . $sx
	  . ' '
	  . $sy
	  . ' L '
	  . $ex
	  . ' '
	  . $ey;
      }

    $svg .= '" fill="none" stroke="black" stroke-width=".1" marker-end="url(#arrow)" />'
      . '</svg>'
      . "\n";

    // position label
    $labelwidth="10";
    $labelheight="3";
    $xoffset="1";
    $yoffset="2";

    if ($upperlabel)
      {
	$ux = $mx - $labelwidth/2 + $nx*$swap + $ax*MakeEx($upperdisplacement);
	$uy = $my - $labelheight/2 - $fudgeheight + ($ny - $fudgeheight)*$swap + $ax*MakeEx($upperdisplacement);

	$svg .= '<foreignObject x="'
	  . $ux
	  . 'ex" y="'
	  . $uy
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
    if ($lowerlabel)
      {
	$lx = $mx - $labelwidth/2 - $nx*$swap;
	$ly = $my - ($ny - $fudgeheight)*$swap;

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
	  . "\(" . $lowerlabel . "\)"
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
      }
  }

$svg .= '</svg>' . "\n";

print '<pre>' . htmlspecialchars($svg) . '</pre>';

$latex = $svg . "\0" . $latex;
return;