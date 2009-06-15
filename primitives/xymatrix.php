// name: xymatrix
$args = "";
$dim = array(
	     "x" => "4", // row
	     "y" => "6"  // col
	     );
$arrowfile = "arrows.def";
$accuracy = 20; // rounding for computations
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
$maxheight = 2.5;
$maxdepth = 2;
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
		// ignore spaces and nulls
		$matrix = ltrim($matrix," \0");
		$nexttok = nexttok($matrix);
		//		LaTeXdebug($nexttok,1);
		if ($nexttok == '^')
		  {
		    LaTeXdebug("label above",1);
		    // label above
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    $matrix = ltrim($matrix," ");
		    if (preg_match('/!{[^;]+;[^}]+}|(?:(?:[<>])[<> ]*)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
		      {
			$upperdisplacement = $matches[0];
			$matrix = substr($matrix,strlen($upperdisplacement));
		      }
		    $matrix = ltrim($matrix," ");
		    $upperlabel = nextgrp($matrix);
		  }
		elseif ($nexttok == '_')
		  {
		    LaTeXdebug("label below",1);
		    // label below
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    $matrix = ltrim($matrix," ");
		    if (preg_match('/!{[^;]+;[^}]+}|(?:(?:[<>])[<> ]*)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
		      {
			$lowerdisplacement = $matches[0];
			$matrix = substr($matrix,strlen($lowerdisplacement));
		      }
		    $matrix = ltrim($matrix," ");
		    $lowerlabel = nextgrp($matrix);
		  }
		elseif ($nexttok == '|')
		  {
		    LaTeXdebug("label middle",1);
		    // label in middle
		    // displacement syntax: (< *){0,2}|(> *){0,2}
		    ltrim($matrix," ");
		    if (preg_match('/!{[^;]+;[^}]+}|(?:(?:[<>])[<> ]*)?(?:\([\.0-9]+\))?|/',$matrix,$matches))
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
			$nexttok = nexttok($matrix);
			while ($nexttok != ')')
			  {
			    LaTeXdebug("curving",1);
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
	    LaTeXdebug("source: $m $n",1);
	    LaTeXdebug("upperdisplacement: " . $upperdisplacement,1);
	    LaTeXdebug("upperlabel: " . $upperlabel,1);
	    LaTeXdebug("lowerdisplacement: " . $lowerdisplacement,1);
	    LaTeXdebug("lowerlabel: " . $lowerlabel,1);
	    LaTeXdebug("middledisplacement: " . $middledisplacement,1);
	    LaTeXdebug("middlelabel: " . $middlelabel,1);
	    LaTeXdebug("curving: " . $curving,1);
	    LaTeXdebug("stylevariant: " . $stylevariant,1);
	    LaTeXdebug("style: " . $style,1);
	    LaTeXdebug("displacement: " . $displacement,1);
	    LaTeXdebug("control: " . $control,1);
	    LaTeXdebug("swap: " . $swap,1);
	    LaTeXdebug("dash: " . $dash,1);
	    LaTeXdebug("target: " . $target,1);


	  }
	else
	  {
	    // append to current entry
	    $entry[$m][$n] = $entry[$m][$n] . $nexttok;
	  }
	$nexttok = nexttok($matrix);
      }
    $entry[$m][$n] = trim($entry[$m][$n]);
    $width[$m][$n] = (getWidthOf('\(' . $entry[$m][$n] . '\)') + 2); // margin of error
    $height[$m][$n] =  (getHeightOf('\(' . $entry[$m][$n] . '\)') + 2); // margin of error
    $depth[$m][$n] =  (getDepthOf('\(' . $entry[$m][$n] . '\)') + 2); // margin of error

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
$maxNodeSize = array(
		     "x" => $maxwidth,
		     "y" => $maxheight + $maxdepth
		     );

$dim = vecSum($dim,$maxNodeSize);

$numrows = count($entry);
$numcols += 1;

$svgwidth = $dim["x"]*($numrows + 1);
$svgheight = $dim["y"]*($numcols);

$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ';
$svg .= 'width="' 
  . $svgwidth
  . 'ex" height="' 
  . $svgheight
  . 'ex">'
  . "\n";

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
	$svg .= '<foreignObject x="'
	  . ($n * $dim["x"])
	  . 'ex" y="'
	  . ($m * $dim["y"])
	  . 'ex" width="'
	  . $maxwidth
	  . 'ex" height="'
	  . ($maxheight + $maxdepth)
	  . 'ex">'
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

for($i = 0; $i < count($arrows);$i++)
  {
    // extract significant parts from arrow
    // starting entry
    $s["y"] = $arrows[$i]["y"];
    $s["x"] = $arrows[$i]["x"];
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


    /*
     * Coordinate summary:
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
    $jot = .1;
    if ($upperdisplacement)
      {
	// The first > or < is to anchor it at the relevant end
	$uin = (substr_count($upperdisplacement,"<") - 1)*$jot;
	$uout = 1 - (substr_count($upperdisplacement,">") - 1)*$jot;
	if (preg_match('/\((\.[0-9]+)\)/',$upperdisplacement,$matches))
	  {
	    $udisp = $uin + ($uout - $uin)*$matches[1];
	  }
	elseif (substr($upperdisplacement,0,1) == "<")
	  {
	    $udisp = $uin;
	  }
	else
	  {
	    $udisp = $uout;
	  }
      }
    else
      {
	$udisp = .5;
      }
    if ($lowerdisplacement)
      {
	// The first > or < is to anchor it at the relevant end
	$lin = (substr_count($lowerdisplacement,"<") - 1)*$jot;
	$lout = 1 - (substr_count($lowerdisplacement,">") - 1)*$jot;
	if (preg_match('/\((\.[0-9]+)\)/',$lowerdisplacement,$matches))
	  {
	    $ldisp = $lin + ($lout - $lin)*$matches[1];
	  }
	elseif (substr($lowerdisplacement,0,1) == "<")
	  {
	    $ldisp = $lin;
	  }
	else
	  {
	    $ldisp = $lout;
	  }
      }
    else
      {
	$ldisp = .5;
      }
    if ($middledisplacement)
      {
	// The first > or < is to anchor it at the relevant end
	$min = (substr_count($middledisplacement,"<") - 1)*$jot;
	$mout = 1 - (substr_count($middledisplacement,">") - 1)*$jot;
	if (preg_match('/\((\.[0-9]+)\)/',$middledisplacement,$matches))
	  {
	    $mdisp = $min + ($mout - $min)*$matches[1];
	  }
	elseif (substr($middledisplacement,0,1) == "<")
	  {
	    $mdisp = $min;
	  }
	else
	  {
	    $mdisp = $mout;
	  }
      }
    else
      {
	$mdisp = .5;
      }

    // final entry
    $e = vecSum($s,udrlVect($target));

    // centres of entries
    $sc["x"] = ($s["x"] * $dim["x"]) + $maxwidth/2;
    $sc["y"] = ($s["y"] * $dim["y"]) + $maxheight -1; // don't vertically centre?
    $ec["x"] = ($e["x"] * $dim["x"]) + $maxwidth/2;
    $ec["y"] = ($e["y"] * $dim["y"]) + $maxheight -1;

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
	    $n = vecNorm($o);
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
    if ($ds["x"] == 0)
      {
	$ts = abs($height[$s["y"]][$s["x"]]/(2*$ds["y"]));
      }
    elseif ($ds["y"] == 0)
      {
	$ts = abs($width[$s["y"]][$s["x"]]/(2*$ds["x"]));
      }
    else
      {
	$ts = min(abs($height[$s["y"]][$s["x"]]/(2*$ds["y"])),abs($width[$s["y"]][$s["x"]]/(2*$ds["x"])));
      }
    $s = vecSum($sc,vecScale($ts,$ds));

    if ($de["x"] == 0)
      {
	$te = abs($height[$e["y"]][$e["x"]]/(2*$de["y"]));
      }
    elseif ($de["y"] == 0)
      {
	$te = abs($width[$e["y"]][$e["x"]]/(2*$de["x"]));
      }
    else
      {
	$te = min(abs($height[$e["y"]][$e["x"]]/(2*$de["y"])),abs($width[$e["y"]][$e["x"]]/(2*$de["x"])));
      }
    $e = vecSum($ec,vecScale($te,$de));

//    $narrowpath = '<circle cx="' . $s["x"] . 'ex" cy="' . $s["y"] . 'ex" r="1" stroke="red" stroke-width="1" />' . "\n";
//    $narrowpath .= '<circle cx="' . $e["x"] . 'ex" cy="' . $e["y"] . 'ex" r="1" stroke="blue" stroke-width="1" />' . "\n";

    $arrowpath = '<svg width="'
      . $svgwidth
      . 'ex" height="'
      . $svgheight
      . 'ex" viewBox="0 0 '
      . $svgwidth
      . ' '
      . $svgheight
      . '">'
      . "\n";

    $arrowpath .= '<path d="'
      . 'M '
      . vecXY($s)
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
	  . vecXY(vecSum($s,$ds))
	  . ' '
	  . vecXY(vecSum($e,$de))
	  . ' ';
      }

    $arrowpath .= vecXY($e)
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

    LaTeXdebug($stem,1);

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
	$arrowpath .= ' stroke-dasharray="1,1" ';
      }
    elseif (($stem == '.') or ($stem == ':'))
      {
	$arrowpath .= ' stroke-dasharray=".5,1.5" ';
      }

    if (($stem == '=') or ($stem == '==') or ($stem == ':') or ($stylevariant == 2))
      {
	$basicarrow = $arrowpath;
	$arrowpath .= ' stroke="black" stroke-width=".5" />' . "\n";
	$arrowpath .= $basicarrow;
	$arrowpath .= ' stroke="white" stroke-width=".3" />' . "\n";
	$arrowpath .= $basicarrow;
      }
    elseif ($stylevariant == 3)
      {
	$basicarrow = $arrowpath;
	$arrowpath .= ' stroke="black" stroke-width=".7" />' . "\n";
	$arrowpath .= $basicarrow;
	$arrowpath .= ' stroke="white" stroke-width=".5" />' . "\n";
	$arrowpath .= $basicarrow;
	$arrowpath .= ' stroke="black" stroke-width=".1" />' . "\n";
	$arrowpath .= $basicarrow;
      }
    elseif (($stem == '-') or ($stem == '--') or ($stem == '.'))
      {
	$arrowpath .= ' stroke="black" ';
      }

    $arrowpath .= ' stroke-width=".1" ';

    if ($markerstart)
      {
	$arrowpath .= 'marker-start="url('
	  . $markerstart
	  . ')" ';
      }
    if ($markerend)
      {
	$arrowpath .= 'marker-end="url('
	  . $markerend
	  . ')" ';
      }
    $arrowpath .= ' />';

    //    LaTeXdebug("midpoints: $m['x'] $m['y'] $d['x'] $d['y']",1);

    if (array_key_exists($mid,$arrowheads))
      {
	LaTeXdebug("Got middle of arrow",1);
	if ($arrowtype == "C")
	  {
	    list($m,$d) = cubicBezier(.5,$s,vecSum($s,$ds),vecSum($e,$de),$e);
	  }
	elseif ($arrowtype == "Q")
	  {
	    list($m,$d) = quadraticBezier(.5,$s,vecSum($sc,$ds),$e);
	  }
	else
	  {
	    list($m,$d) = linear(.5,$s,$e);
	  }

	$arrowpath .= '<path d="M '
	  . vecXY(vecMinus($m,$d))
	  . ' L '
	  . vecXY($m)
	  . '" stroke-width=".1" fill="none" marker-end="url('
	  . $arrowheads[$mid]
	  . ')" />';
      }

    $svg .= $arrowpath
      . '</svg>'
      . "\n";

    if ($upperlabel)
      {
	$upperlabelwidth = getWidthOf('\(' . $upperlabel . '\)');
	$upperlabelheight = getHeightOf('\(' . $upperlabel . '\)') + getDepthOf('\(' . $upperlabel . '\)');

	if ($arrowtype == "C")
	  {
	    list($lu,$lud) = cubicBezier($udisp,$s,vecSum($s,$ds),vecSum($e,$de),$e);
	  }
	elseif ($arrowtype == "Q")
	  {
	    list($lu,$lud) = quadraticBezier($udisp,$s,vecSum($sc,$ds),$e);
	  }
	else
	  {
	    list($lu,$lud) = linear($udisp,$s,$e);
	  }

	LaTeXdebug("tangent is: " . vecXY($lud),1);
	$svg .= '<circle cx="' . $lu["x"] . 'ex" cy="' . $lu["y"] . 'ex" r="1" stroke="red" stroke-width="1" />' . "\n";

	$lu = vecSum($lu,vecScale(vecMake(.5*$upperlabelwidth,.5*$upperlabelheigh),vecSum(vecOrth(vecSign($lud)),vecMake(1,1))));

	$svg .= '<circle cx="' . $lu["x"] . 'ex" cy="' . $lu["y"] . 'ex" r="1" stroke="red" stroke-width="1" />' . "\n";
//	$lu["x"] += - $upperlabelwidth/2 + $n["x"]*$swap;
//	$lu["y"] += - $upperlabelheight/2 - $fudgeheight + ($n["y"] - $fudgeheight)*$swap;

	$svg .= '<foreignObject '
	  . vecXY($lu,1)
	  . ' width="'
	  . ($upperlabelwidth + 2)
	  . 'ex" height="'
	  . $upperlabelheight
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml" style="border-width: 0pt; margin: 0pt; padding: 0pt;">'
	  . '<div align="center">'
	  . '\('
	  . $upperlabel
	  . '\)'
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
      }
    if ($lowerlabel)
      {
	$lowerlabelwidth = getWidthOf('\(' . $lowerlabel . '\)');
	$lowerlabelheight = getHeightOf('\(' . $lowerlabel . '\)') + getDepthOf('\(' . $lowerlabel . '\)');
	$ll["x"] += - $lowerlabelwidth/2 - $n["x"]*$swap;
	$ll["y"] += - ($n["y"] - $fudgeheight)*$swap;

	$svg .= '<foreignObject x="'
	  . $ll["x"]
	  . 'ex" y="'
	  . $ll["y"]
	  . 'ex" width="'
	  . ($lowerlabelwidth + 1)
	  . 'ex" height="'
	  . $lowerlabelheight
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml" style="border-width: 0pt; margin: 0pt; padding: 0pt;">'
	  . '<div style="width:'
	  . $lowerlabelwidth
	  . 'ex,height:'
	  . ($lowerlabelheight -1)
	  . 'ex" align="center">'
	  . '\('
	  . $lowerlabel
	  . '\)'
	  . '</div></body>'
	  . '</foreignObject>'
	  . "\n";
      }
  }

$svg .= '</svg>' . "\n";

$latex = $svg . "\0" . $latex;
return;
