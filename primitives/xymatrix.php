// name: xymatrix
$args = "";
$sep = vecMake(8,8); // separation of entries
$margin = vecMake(4,4); // margin around diagram
$padding = vecMake(1,1); // padding around nodes
global $clip; // first clip path label
if (!$clip)
  $clip = "A";
$arrowfile = "arrows.def";
$accuracy = 20; // rounding for computations
$maxwidth = 1;
$maxheight = 1;
$maxdepth = 0;

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
    foreach ($argarray as $arg)
      {
	if (preg_match('/(.*)(=|+|+=|-|-=)(.*)/',$arg,$matches))
	  {
	    $length = MakeEx($matches[3]);
	    // not sure what += and -= mean!
	    switch($matches[1]) {
	    case "":
	      $sep = vecMake($length,$length);
	      break;
	    case "R":
	      $sep["y"] = $length;
	      break;
	    case "C":
	      $sep["x"] = $length;
	      break;
	    case "M":
	      $padding = vecMake($length,$length);
	      break;
	    case "W":
	      $maxwidth = $length;
	      break;
	    case "H":
	      $maxheight = $length;
	      break;
	    case "L":
	      $labelpadding = vecMake($length,$length);
	      break;
	    default:
	      LaTeXdebug("Unknown dimension for xymatrix: $arg",1);
	    }
	  }
	elseif (preg_match('/!(.)/',$arg,$matches))
	  {
	    switch($matches[1]) {
	    case "":
	      // uniform spacing
	      break;
	    case "0":
	      // uniform spacing, ignore entry sizes
	      break;
	    case "R":
	      // equal row spacing
	      break;
	    case "C":
	      // equal column spacing
	      break;
	    default:
	      LaTeXdebug("Unknown spacing for xymatrix: $arg",1);
	    }
	  }
	elseif (preg_match('/\[[uUdDrRlL]+\]/',$arg))
	  {
	    $rotate = $arg;
	  }
	else
	  {
	    LaTeXdebug("Unknown argument for xymatrix: $arg",1);
	  }
      } 
  }
// TODO: convert lengths (should be global function) 
$latex = $nexttok . $latex;
$matrix = stripgrp(nextgrp($latex));

// build a matrix of entries
$m = 0;
$n = 0;
$numcols = 0;

// due to not vertically centering our entries, need a vertical fudge
$fudgeheight = 0;

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
	    $labels = array();
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
		// ignore spaces and nulls
		$matrix = ltrim($matrix," \0");
		$nexttok = nexttok($matrix);
		//		LaTeXdebug($nexttok,1);
		if (($nexttok == '^') or ($nexttok == '_') or ($nexttok == '|'))
		  {
		    LaTeXdebug("label",1);
		    // label 
		    if ($nexttok == '^')
		      {
			$position = 1;
		      }
		    elseif ($nexttok == '_')
		      {
			$position = -1;
		      }
		    else
		      {
			$position = 0;
		      }
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    $labeldisplacement = .5;
		    $matrix = ltrim($matrix," ");
		    if (preg_match('/!{[^;]+;[^}]+}|(?:(?:[<>])[<> ]*)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
		      {
			$labeldisplacement = $matches[0];
			$matrix = substr($matrix,strlen($labeldisplacement));
		      }
		    $matrix = ltrim($matrix," ");
		    $text = nextgrp($matrix);
		    $labels[] = array(
				      "position" => $position,
				      "displacement" => $labeldisplacement,
				      "text" => $text
				      );
		  }
		elseif ($nexttok == '@')
		  {
		    // lots of possibilities
		    $matrix = ltrim($matrix," ");
		    $nexttok = nexttok($matrix);
		    if ($nexttok == '/')
		      {
			LaTeXdebug("curving",1);
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
			LaTeXdebug("curving",1);
			$nexttok = nexttok($matrix);
			while ($nexttok != ')')
			  {
			    $curving .= $nexttok;
			    $nexttok = nexttok($matrix);
			  }
		      }
		    elseif ($nexttok == '{')
		      {
			LaTeXdebug("style",1);
			// style
			$matrix = $nexttok . $matrix;
			$style = nextgrp($matrix);
		      }
		    elseif (($nexttok == '^') or ($nexttok == '_') or ($nexttok == '2') or ($nexttok == '3'))
		      {
			LaTeXdebug("style",1);
			// style again
			$stylevariant = $nexttok;
			$style = nextgrp($matrix);
		      }
		    elseif (preg_match('/^</',$nexttok))
		      {
			// nexttok thinks that (x)html tags are a single token so actually gets all of this in one go
			LaTeXdebug("displacement",1);
			// displacement
			$displacement = substr($nexttok,1,strlen($nexttok)-2);
		      }
		    elseif ($nexttok == "'")
		      {
			LaTeXdebug("control",1);
			// control points
			$matrix = ltrim($matrix," ");
			$control = nextgrp($matrix);
		      }
		    elseif ($nexttok == "?")
		      {
			LaTeXdebug("swap",1);
			// swap above and belo
			$swap = -1;
		      }
		    elseif ($nexttok == "!")
		      {
			LaTeXdebug("dash",1);
			// dashed stem
			$dash = 1;
		      }
		  }
		elseif ($nexttok == "[")
		  {
		    LaTeXdebug("target",1);
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
		    LaTeXdebug("end of arrow",1);
		    $inarrow = 0;
		  }
	      }

	    $matrix = $nexttok . "\0" . $matrix;
	    $arrows[] = array(
			      "y" => $m,
			      "x" => $n,
			      "labels" => $labels,
			      "curving" => $curving,
			      "stylevariant" => $stylevariant,
			      "style" => $style,
			      "displacement" => $displacement,
			      "control" => $control,
			      "swap" => $swap,
			      "dash" => $dash,
			      "target" => $target
			      );
	  }
	else
	  {
	    // append to current entry
	    $entry[$m][$n] = $entry[$m][$n] . $nexttok;
	  }
	$nexttok = nexttok($matrix);
      }
    $entry[$m][$n] = trim($entry[$m][$n]);
    $width[$m][$n] = getWidthOf('\(' . $entry[$m][$n] . '\)');
    $height[$m][$n] = getHeightOf('\(' . $entry[$m][$n] . '\)');
    $depth[$m][$n] = getDepthOf('\(' . $entry[$m][$n] . '\)');

    if ($width[$m][$n] > $maxwidth)
      $maxwidth = $width[$m][$n];
    if ($height[$m][$n] > $maxheigh)
      $maxheight = $height[$m][$n];
    if ($depth[$m][$n] > $maxdepth)
      $maxdepth = $depth[$m][$n];

    if ($nexttok == '&')
      {
	$n++;
      }
    elseif ($nexttok == '\\\\')
      {
	$m++;
	$numcols=max($n,$numcols);
	$n = 0;
      }
  }

$numcols=max($n,$numcols);

// maximum node size as seen from outside (i.e. plus padding)
$maxNodeSize = vecSum($padding,vecMake($maxwidth, $maxheight + $maxdepth));
// node separation vector
$nodeCoords = vecSum($sep,$maxNodeSize);
// size of matrix of entries
$matrixSize = vecMake($numcols,count($entry) - 1); // start counting at 0,0
// total size of diagram
$svgSize = vecSum(vecSum(vecScale(2,$margin),vecScale($matrixSize,$nodeCoords)),$maxNodeSize);

$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ';
$svg .= vecXY($svgSize,"w","ex");
$svg .= '>';

// should only do this once per page, maybe now it's not necessary ...
$svg .= file_get_contents($arrowfile);

$arrowheads = array(
		    "filledArrow" => "#filledArrow",
		    ">" => "#basicArrow",
		    ">>" => "#doubleArrow",
		    ">|" => "#barArrow",
		    "|>>" => "#bardoubleArrow",
		    ")" => "#parenthesisArrow",
		    "<" => "#reversebasicArrow",
		    "<<" => "#reversedoubleArrow",
		    "|<" => "#reversebarArrow",
		    "|<<" => "#reversebardoubleArrow",
		    "(" => "#reverseparenthesisArrow",
		    "/" => "#slashArrow",
		    "//" => "#doubleslashArrow",
		    "x" => "#crossArrow",
		    "+" => "#plusArrow",
		    "|" => "#vbarArrow",
		    "||" => "#doublevbarArrow",
		    "o" => "#circleArrow"
		    );

for($m = 0;$m < count($entry);$m++)
  {
    for($n = 0;$n < count($entry[$m]); $n++)
      {
	$svg .= '<foreignObject '
	  . vecXY(vecSum($margin,vecScale(vecMake($n,$m),$nodeCoords)),"x","ex")
	  . ' '
	  . vecXY($maxNodeSize,"w","ex")
	  . '>'
	  . '<body xmlns="http://www.w3.org/1999/xhtml" style="border-width: 0pt; margin: 0pt; padding: 0pt;">'
	  . '<div align="center">'
	  . '\('
	  . '\rule{0ex}{'
	  . $maxheight
	  . 'ex}'
	  . $entry[$m][$n]
	  . '\)'
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
      }
  }


if ($arrows)
  {
    $svgArrows = "";
    $svgArrowLabels = "";

    foreach($arrows as $arrow)
      {
	// extract significant parts from arrow
	// starting entry
	$s = vecMake($arrow["x"],$arrow["y"]);
	$labels = $arrow["labels"];
	$curving = $arrow["curving"];
	$stylevariant = $arrow["stylevariant"];
	$style = $arrow["style"];
	$displacement = $arrow["displacement"];
	$control = $arrow["control"];
	$swap = $arrow["swap"];
	$dash = $arrow["dash"];
	$target = $arrow["target"];

	$clipLabels = array();
	$arrowpath = "";
	$arrowstyle = "";
	$arrowmask = "";
	$svgArrow = "";
	$svgArrowMask = "";
	
	/*
	 * Coordinate summary:
	 *
	 * $s coordinates of starting entry (matrix co-ords)
	 * $e coordinates of final entry (matrix co-ords)
	 * $sc coordinates of centre of starting entry
	 * $ec coordinates of centre of final entry
	 * $ds vector of direction of arrow as it leaves start
	 * $de vector of direction back along arrow as it enters target
	 * $sa coordinates of start of arrow
	 * $ea coordinates of end of arrow
	 *
	 * $sn,$sm matrix coordinates of starting entry
	 * $en,$em matrix coordinates of final entry
	 * $sx,$sy x,y-coordinates of start of arrow
	 * $ex,$ey x,y-coordinates of end of arrow
	 * $bx,$by arrow vector
	 * $ax,$ay normalised arrow vector
	 * $ox,$oy orthogonal arrow vector
	 * $nx,$ny normalised orthogonal arrow vector
	 * $mx,$my x,y-coordinates of midpoint of arrow
	 * $dx,$dy tangent vector at midpoint
	 * $lux,$luy x,y-coordinates of upper label
	 * $llx,$lly x,y-coordinates of lower label
	 * $lmx,$lmy x,y-coordinates of middle label
	 * $csx,$csy x,y-coordinates of start of arrow (bezier cubic)
	 * $cex,$cey x,y-coordinates of end of arrow (bezier cubic)
	 * $ccsx,$ccsx x,y-coordinates of first control pt (bezier cubic)
	 * $ccex,$ccyx x,y-coordinates of second control pt (bezier cubic)
	 * $qmx,$qmy x,y-coordinates of control point (bezier quadratic)
	 *
	 * $scx,$scy x,y-coordinates of centres of entries
	 * $ecx,$ecy -"-
	 * $dsx,$dsy direction of arrow as it leaves start
	 * $dex,$dxy reverse of direction of arrow as it arrives at target
	 */

	// convert label displacements into distances
	// for simplicity, we vary xymatrix's displacements a little 
	// 1. the label positions are always relative to the curve, not the
	//    midpoints of the entries
	// 2. the offsets (> and <) are relative to the length of the
	//    curve, not absolute distances.

	// final entry
	$e = vecSum($s,udrlVect($target));

	// centres of entries
	$sc = vecSum(vecSum($margin,vecScale($s,$nodeCoords)),vecScale(.5,$maxNodeSize));
	$ec = vecSum(vecSum($margin,vecScale($e,$nodeCoords)),vecScale(.5,$maxNodeSize));

	// direction of arrows

	if ($curving)
	  {
	    if (preg_match('/\[([udlr ]+)\] *, *\[([udlr ]+)\]/',$curving,$dirs))
	      {
		$cntl = 4;
		$arrowtype="C";
		// cubic bezier curve
		LaTeXdebug("$dirs[1] $dirs[2]",1);
		// Need to recompute the anchors
		// horizontally
		$ds = vecScale($cntl,vecSign(udrlVect($dirs[1])));
		$de = vecScale($cntl,vecSign(udrlVect($dirs[2])));
	      }
	    else
	      {
		$arrowtype="Q";
		// symmetric quadratic bezier curve
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

		// mid point of a quadratic bezier is on the tangents at both extremal points
		// so tangents are in the directions of the line between these points
		$o = vecOrth(vecMinus($ec,$sc));
		$n = vecReSqNorm($o);
		$ds = vecSum(vecScale(.5,vecMinus($ec,$sc)),vecScale($dir*$scale,$n));
		$de = vecSum(vecScale(.5,vecMinus($sc,$ec)),vecScale($dir*$scale,$n));
	      }
	  }
	else
	  {
	    $arrowtype="";
	    $ds = vecMinus($ec,$sc);
	    $de = vecScale(-1,$ds);
	  }

	// Now compute anchors as place where vector out of centre leaves box

	$sBoxur = vecSum($padding,vecMake($width[$s["y"]][$s["x"]]/2,($height[$s["y"]][$s["x"]]+$depth[$s["y"]][$s["x"]])/2));
	$sBoxdl = vecScale(-1,$sBoxur);
	$sa = vecSum($sc,vecReSupNorm($ds,$sBoxur,$sBoxdl));

	LaTeXdebug("Renormalising: " . vecXY($sBoxur) . ' ' . vecXY($sBoxdl),1);

	$eBoxur = vecSum($padding,vecMake($width[$e["y"]][$e["x"]]/2,($height[$s["y"]][$s["x"]]+$depth[$e["y"]][$e["x"]])/2));
	$eBoxdl = vecScale(-1,$eBoxur);
	$ea = vecSum($ec,vecReSupNorm($de,$eBoxur,$eBoxdl));

	//    $svg .= '<circle cx="' . $sc["x"] . 'ex" cy="' . $sc["y"] . 'ex" r="1" stroke="green" stroke-width="1" />' . "\n";
	//    $svg .= '<circle cx="' . $sa["x"] . 'ex" cy="' . $sa["y"] . 'ex" r="1" stroke="red" stroke-width="1" />' . "\n";
	//    $svg .= '<circle cx="' . $ea["x"] . 'ex" cy="' . $ea["y"] . 'ex" r="1" stroke="blue" stroke-width="1" />' . "\n";

	// Do the labels first incase there are any holes to punch

	while ($labels)
	  {
	    $label = array_shift($labels);
	    $text = $label["text"];
	    $labeldisplacement = $label["displacement"];
	    $pos = $label["position"];

	    $jot = .1;
	    if ($labeldisplacement)
	      {
		// The first > or < is to anchor it at the relevant end
		$in = (substr_count($labeldisplacement,"<") - 1)*$jot;
		$out = 1 - (substr_count($labeldisplacement,">") - 1)*$jot;
		if (preg_match('/\((\.[0-9]+)\)/',$labeldisplacement,$matches))
		  {
		    $disp = $in + ($out - $in)*$matches[1];
		  }
		elseif (substr($labeldisplacement,0,1) == "<")
		  {
		    $disp = $in;
		  }
		else
		  {
		    $disp = $out;
		  }
	      }
	    else
	      {
		$disp = .5;
	      }

	    $labelSize = vecMake(getWidthOf('\(' . $text . '\)'), getHeightOf('\(' . $text . '\)') + getDepthOf('\(' . $text . '\)'));

	    if ($arrowtype == "C")
	      {
		list($lu,$lud) = cubicBezier($disp,$sa,vecSum($sa,$ds),vecSum($ea,$de),$ea);
	      }
	    elseif ($arrowtype == "Q")
	      {
		list($lu,$lud) = quadraticBezier($disp,$sa,vecSum($sc,$ds),$ea);
	      }
	    else
	      {
		list($lu,$lud) = linear($disp,$sa,$ea);
	      }

	    //	$svg .= '<circle cx="' . $lu["x"] . 'ex" cy="' . $lu["y"] . 'ex" r="1" stroke="red" stroke-width="1" />' . "\n";

	    $lu = vecSum($lu,vecSum(vecScale($labelSize,vecOrth(vecSign(vecScale($pos,$lud)))),vecMake(-1,-1)));

	    //	$svg .= '<circle cx="' . $lu["x"] . 'ex" cy="' . $lu["y"] . 'ex" r="1" stroke="green" stroke-width="1" />' . "\n";
	    //	$lu["x"] += - $upperlabelwidth/2 + $n["x"]*$swap;
	    //	$lu["y"] += - $upperlabelheight/2 - $fudgeheight + ($n["y"] - $fudgeheight)*$swap;

	    if (!$pos)
	      {
		$svgArrowMask .= '<rect '
		  . vecXY($lu,"x")
		  . ' '
		  . vecXY(vecSum($padding,$labelSize),"w")
		  . ' fill="black"/>';
	      }

	    $svgArrowLabels .= '<foreignObject '
	      . vecXY($lu,"x","ex")
	      . ' '
	      . vecXY(vecSum($padding,$labelSize),"w","ex")
	      . '>'
	      . '<body xmlns="http://www.w3.org/1999/xhtml" style="border-width: 0pt; margin: 0pt; padding: 0pt;">'
	      . '<div align="center">'
	      . '\('
	      . $text
	      . '\)'
	      . '</div></body>'
	      . '</foreignObject>'
	      . "\n";
	  }


	$arrowpath = '<path d="'
	  . 'M '
	  . vecXY($sa)
	  . ' ';

	if ($arrowtype == "Q")
	  {
	    $arrowpath .= 'Q '
	      . vecXY(vecSum($sc,$ds))
	      . ' ';
	  }
	elseif ($arrowtype == "C")
	  {
	    $arrowpath .= 'C '
	      . vecXY(vecSum($sa,$ds))
	      . ' '
	      . vecXY(vecSum($ea,$de))
	      . ' ';
	  }

	$arrowpath .= vecXY($ea)
	  . '" ';


	// assemble style
	if ($style)
	  {
	    $vars = array();
	    $types = array();
	    $style = stripgrp($style);
	    while ($style)
	      {
		// format is [^_]?token
		$firstchar = substr($style,0,1);
		if (($firstchar == '^') or ($firstchar == '_'))
		  {
		    $vars[] = $firstchar;
		    $style = substr($style,1);
		  }
		else
		  {
		    $vars[] = $stylevariant;
		  }
		$types[] = stripgrp(nextgrp($style));
	      }
	    if (count($types) == 1)
	      {
		$headvar = $vars[0];
		$stemvar = $tailvar = $stylevariant;
		$head = $types[0];
		$stem = '-';
		$tail = '';
	      }
	    else
	      {
		list($tail,$stem,$head) = $types;
		list($tailvar,$stemvar,$headvar) = $vars;
	      }

	  }
	else
	  {
	    $tailvar = $stemvar = $headvar = "";
	    $tail = "";
	    $stem = "-";
	    $head = ">";
	  }
    
	if (array_key_exists($head,$arrowheads))
	  {
	    $markerend = $arrowheads[$head];
	  }
	else
	  {
	    $markerend = "";
	  }
	if (array_key_exists($tail,$arrowheads))
	  {
	    $markerstart = $arrowheads[$tail];
	  }
	else
	  {
	    $markerstart = "";
	  }

	$arrowpath .= ' fill="none" ';

	if (($markerstart) or ($markerend))
	  {
	    $svgArrowMask .= $arrowpath
	      . ' stroke-width=".1" ';

	    if ($markerstart)
	      {
		$svgArrowMask .= 'marker-start="url('
		  . $markerstart
		  . 'Mask)" ';
	      }
	    if ($markerend)
	      {
		$svgArrowMask .= 'marker-end="url('
		  . $markerend
		  . 'Mask)" ';
	      }
	    $svgArrowMask .= '/>';
	  }

	if (preg_match('/^([^{]*){([^}]*)}\1$/',$stem,$matches))
	  {
	    $stem = $matches[1];
	    $mid = $matches[2];
	  }
	else
	  {
	    $mid = "";
	  }

	if (($stem == '--') or ($stem == '=='))
	  {
	    $arrowstyle = 'stroke-dasharray="1,1" ';
	  }
	elseif (($stem == '.') or ($stem == ':'))
	  {
	    $arrowstyle = 'stroke-dasharray=".5,1.5" ';
	  }
	$arrowmask = '';

	// If we have a doubled or tripled stem, draw these first
	if (($stem == '=') or ($stem == '==') or ($stem == ':') or ($stylevariant == 2))
	  {
	    $arrowmask = '<mask id="Clip'
	      . ++$clip
	      . '" maskUnits="userSpaceOnUse" maskContentUnits="userSpaceOnUse" '
	      . vecXY(vecMake(0,0),"x")
	      . ' '
	      . vecXY($svgSize,"w")
	      . '>'
	      . '<rect '
	      . vecXY(vecMake(0,0),"x")
	      . ' '
	      . vecXY($svgSize,"w")
	      . ' fill="white"/>'
	      . $arrowpath
	      .  ' stroke="black" stroke-width=".3" />'
	      . $svgArrowMask
	      . '</mask>';

	    $svgArrow = $arrowpath
	      . ' '
	      . $arrowstyle
	      . ' '
	      . ' stroke="black" stroke-width=".5" mask="url(#Clip'
	      . $clip
	      . ')" />';
	  }
	elseif ($stylevariant == 3)
	  {
	    $arrowmask = '<mask id="Clip'
	      . ++$clip
	      . '" maskUnits="userSpaceOnUse" maskContentUnits="userSpaceOnUse" '
	      . vecXY(vecMake(0,0),"x")
	      . ' '
	      . vecXY($svgSize,"w")
	      . '>'
	      . '<rect '
	      . vecXY(vecMake(0,0),"x")
	      . ' '
	      . vecXY($svgSize,"w")
	      . ' fill="white"/>'
	      . $svgArrowMask
	      . $arrowpath
	      .  ' stroke="black" stroke-width=".5" />'
	      . '</mask>';

	    $svgArrow = $arrowpath
	      . ' '
	      . $arrowstyle
	      . ' stroke="black" stroke-width=".7" mask="url(#Clip'
	      . $clip
	      . ')" />';
	  }

	// Now we draw the main arrow.  Even with a double stem we need to "draw" this for the arrowheads.

	$svgArrow .= $arrowpath
	  . ' '
	  . $arrowstyle;

	// this is when we draw the actual arrow
	if ((($stem == '-') or ($stem == '--') or ($stem == '.')) and ($stylevariant != 2))
	  {
	    $svgArrow .= ' stroke="black" ';
	  }

	$svgArrow .= ' stroke-width=".1" ';

	if ($markerstart)
	  {
	    $svgArrow .= 'marker-start="url('
	      . $markerstart
	      . ')" ';
	  }
	if ($markerend)
	  {
	    $svgArrow .= 'marker-end="url('
	      . $markerend
	      . ')" ';
	  }


	if ($svgArrowMask)
	  {
	    $arrowmask .= '<mask id="Clip'
		  . ++$clip
		  . '" maskUnits="userSpaceOnUse" maskContentUnits="userSpaceOnUse" '
		  . vecXY(vecMake(0,0),"x")
		  . ' '
		  . vecXY($svgSize,"w")
		  . '>'
		  . '<rect '
		  . vecXY(vecMake(0,0),"x")
		  . ' '
		  . vecXY($svgSize,"w")
		  . ' fill="white"/>'
	      . $svgArrowMask
	      . '</mask>';
	    
	    $svgArrow .= 'mask="url(#Clip'
	      . $clip
	      . ')" ';
	  }


	$svgArrow .= ' />';

	//    LaTeXdebug("midpoints: $m['x'] $m['y'] $d['x'] $d['y']",1);

	if (array_key_exists($mid,$arrowheads))
	  {
	    LaTeXdebug("Got middle of arrow",1);
	    if ($arrowtype == "C")
	      {
		list($m,$d) = cubicBezier(.5,$sa,vecSum($sa,$ds),vecSum($ea,$de),$ea);
	      }
	    elseif ($arrowtype == "Q")
	      {
		list($m,$d) = quadraticBezier(.5,$sa,vecSum($sc,$ds),$ea);
	      }
	    else
	      {
		list($m,$d) = linear(.5,$sa,$ea);
	      }

	    $svgArrow .= '<path d="M '
	      . vecXY(vecMinus($m,$d))
	      . ' L '
	      . vecXY($m)
	      . '" stroke-width=".1" fill="none" marker-end="url('
	      . $arrowheads[$mid]
	      . ')" />';
	  }

	$svgArrows .= $arrowmask . $svgArrow;
      }

    $svg .= $svgArrowLabels
      . '<svg '
      . vecXY($svgSize,"w","ex")
      . ' viewBox="0 0 '
      . vecXY($svgSize)
      . '">'
      . $svgArrows
      . '</svg>';

  }


$svg .= '</svg>';

$latex = $svg . "\0" . $latex;
return;
