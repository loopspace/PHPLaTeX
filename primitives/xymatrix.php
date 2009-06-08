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
$maxheight = 2.5;
$maxdepth = 2;
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
    $height[$m][$n] = $maxheight; // need getHeightOf here, and depth
    $depth[$m][$n] = $maxdepth;

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
$dim["col"] = ($dim["col"] + $maxheight + $maxdepth);
$numrows = count($entry);
$numcols += 1;

$svgwidth = 2*($dim["row"]*$numrows);
$svgheight =  2*($dim["col"]*$numcols);

$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" ';
$svg .= 'width="' 
  . $svgwidth
  . 'ex" height="' 
  . $svgheight
  . 'ex">'
  . "\n";

$svg .= '<defs>
    <marker id="filledArrow" viewBox="0 0 10 10" refX="10" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 10 5 L 0 10 z" />
    </marker>
    <marker id="basicArrow" viewBox="0 0 10 10" refX="10" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 10 5 L 0 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="doubleArrow" viewBox="0 0 15 10" refX="10" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="15" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 10 5 L 0 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 5 0 L 15 5 L 5 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="barArrow" viewBox="0 0 10 10" refX="10" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 10 5 L 0 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 10 0 L 10 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="bardoubleArrow" viewBox="0 0 15 10" refX="10" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="15" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 10 5 L 0 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 5 0 L 15 5 L 5 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 15 0 L 15 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="parenthesisArrow" viewBox="0 0 5 10" refX="5" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="5" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 Q 10 5 L 0 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="reversebasicArrow" viewBox="0 0 10 10" refX="0" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 10 0 L 0 5 L 10 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="reversedoubleArrow" viewBox="0 0 15 10" refX="5" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="15" markerHeight="10"
	    orient="auto">
      <path d="M 10 0 L 0 5 L 10 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 15 0 L 5 5 L 15 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="reversebarArrow" viewBox="0 0 10 10" refX="0" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 10 0 L 0 5 L 10 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 0 0 L 0 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="reversebardoubleArrow" viewBox="0 0 15 10" refX="5" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="15" markerHeight="10"
	    orient="auto">
      <path d="M 10 0 L 0 5 L 10 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 15 0 L 5 5 L 15 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 0 0 L 0 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="reverseparenthesisArrow" viewBox="0 0 5 10" refX="5" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="5" markerHeight="10"
	    orient="auto">
      <path d="M 5 0 Q -5 5 L 5 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="slashArrow" viewBox="0 0 5 10" refX="2.5" refY="5" 
	    markerUnits="strokeWidth"
	    markerWidth="5" markerHeight="10"
	    orient="auto">
      <path d="M 5 0 L 0 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="doubleslashArrow" viewBox="0 0 10 10" refX="2.5" refY="5"
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 5 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 5 0 L 10 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="crossArrow" viewBox="0 0 10 10" refX="5" refY="5"
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 0 0 L 10 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 10 0 L 10 0" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="plusArrow" viewBox="0 0 10 10" refX="5" refY="5"
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 5 0 L 5 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 0 5 L 10 5" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="vbarArrow" viewBox="0 0 5 10" refX="2.5" refY="5"
	    markerUnits="strokeWidth"
	    markerWidth="5" markerHeight="10"
	    orient="auto">
      <path d="M 2.5 0 L 2.5 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="doublevbarArrow" viewBox="0 0 10 10" refX="2.5" refY="5"
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <path d="M 2.5 0 L 2.5 10" fill="none" stroke="black" stroke-width="1" />
      <path d="M 7.5 0 L 7.5 10" fill="none" stroke="black" stroke-width="1" />
    </marker>
    <marker id="circleArrow" viewBox="0 0 10 10" refX="0" refY="5"
	    markerUnits="strokeWidth"
	    markerWidth="10" markerHeight="10"
	    orient="auto">
      <circle cs="5" cy="5" r="5" fill="none" stroke="black" stroke-width="1" />
    </marker>
</defs>' . "\n";

$arrowheads = array(
		    "filledArrow" => "filledArrow",
		    ">" => "basicArrow",
		    ">>" => "doubleArrow",
		    ">|" => "barArrow",
		    "|>>" => "bardoubleArrow",
		    ")" => "parenthesisArrow",
		    "<" => "reversebasicArrow",
		    "<<" => "reversedoubleArrow",
		    "|<" => "reversebarArrow",
		    "|<<" => "reversebardoubleArrow",
		    "(" => "reverseparenthesisArrow",
		    "/" => "slashArrow",
		    "//" => "doubleslashArrow",
		    "x" => "crossArrow",
		    "+" => "plusArrow",
		    "|" => "vbarArrow",
		    "||" => "doublevbarArrow",
		    "o" => "circleArrow"
		    );

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
	  . ($maxheight + $maxdepth)
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml"><div align="center">'
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
     * $lux,$luy x,y-coordinates of upper label
     * $llx,$lly x,y-coordinates of lower label
     * $lmx,$lmy x,y-coordinates of middle label
     * $csx,$csy x,y-coordinates of start of arrow (bezier cubic)
     * $cex,$cey x,y-coordinates of end of arrow (bezier cubic)
     * $ccsx,$ccsx x,y-coordinates of first control pt (bezier cubic)
     * $ccex,$ccyx x,y-coordinates of second control pt (bezier cubic)

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
	$sy = ($sm * $dim["col"]) + $maxheight + $maxdepth - ($maxheight + $maxdepth - $height[$sm][$sn] - $depth[$sm][$sn])/2;
	$ey = ($em * $dim["col"]) + ($maxheight + $maxdepth - $height[$em][$en] - $depth[$em][$en])/2;
      }
    elseif ($em < $sm)
      {
	// target is above source
	$sy = ($sm * $dim["col"]) + ($maxheight + $maxdepth - $height[$sm][$sn] - $depth[$sm][$sn])/2;
	$ey = ($em * $dim["col"]) + $maxheight + $maxdepth - ($maxheight + $maxdepth - $height[$em][$en] - $depth[$em][$en])/2;
      }
    else
      {
	// target is in same row as source
	$sy = ($sm * $dim["col"]) + ($maxheight + $maxdepth)/2;
	$ey = ($sm * $dim["col"]) + ($maxheight + $maxdepth)/2;
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
      . "\n";

    $arrowpath = '<path d="';

    // If curving, define a bezier 
    // we also need to define the (x,y) coordinates of the labels so need to know the displacements
    if ($curving)
      {
	if (preg_match('/\[([udlr ]+)\] *, *\[([udlr ]+)\]/',$curving,$dirs))
	  {
	    // cubic bezier curve
	    LaTeXdebug("$dirs[1] $dirs[2]",1);
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
		$csy = ($sm * $dim["col"]) + $maxheight + $maxdepth - ($maxheight + $maxdepth - $height[$sm][$sn] - $depth[$sm][$sn])/2;
		$ccsy = 1;
	      }
	    elseif (stripos($dirs[1],"u") !== FALSE)
	      {
		// arrow should leave source upwards
		$csy = ($sm * $dim["col"]) + ($maxheight + $maxdepth - $height[$sm][$sn] - $depth[$sm][$sn])/2;
		$ccsy = -1;
	      }
	    else
	      {
		// arrow should leave source in the middle
		$csy = ($sm * $dim["col"]) + ($maxheight + $maxdepth)/2;
		$ccsy = 0;
	      }
	    if (stripos($dirs[2],"d") !== FALSE)
	      {
		// arrow should enter target from below
		$cey = ($em * $dim["col"]) + $maxheight + $maxdepth - ($maxheight + $maxdepth - $height[$em][$en] - $depth[$em][$en])/2;
		$ccey = 1;
	      }
	    elseif (stripos($dirs[2],"u") !== FALSE)
	      {
		// arrow should enter target from above
		$cey = ($em * $dim["col"]) + ($maxheight + $maxdepth - $height[$em][$en] - $depth[$em][$en])/2;
		$ccey = -1;
	      }
	    else
	      {
		// arrow should enter target in the middle
		$cey = ($em * $dim["col"]) + ($maxheight + $maxdepth)/2;
		$ccey = 0;
	      }

	    $csy += $fudgeheight;
	    $cey += $fudgeheight;
	    $cntl = 4;

	    $arrowpath .= 'M '
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
	    
	    // compute the positions of the labels (along the curves, shifting them off the curve is done later)
	    $lux = (1 - $udisp)*(1 - $udisp)*(1 - $udisp)*$csx + 3*(1 - $udisp)*(1 - $udisp)*$udisp*($csx + $ccsx*$cntl) + 3*(1 - $udisp)*$udisp*$udisp*($cex + $ccex*$cntl) + $udisp*$udisp*$udisp*$cex;
	    $luy = (1 - $udisp)*(1 - $udisp)*(1 - $udisp)*$csy + 3*(1 - $udisp)*(1 - $udisp)*$udisp*($csy + $ccsy*$cntl) + 3*(1 - $udisp)*$udisp*$udisp*($cey + $ccey*$cntl) + $udisp*$udisp*$udisp*$cey;
	    $llx = (1 - $ldisp)*(1 - $ldisp)*(1 - $ldisp)*$csx + 3*(1 - $ldisp)*(1 - $ldisp)*$ldisp*($csx + $ccsx*$cntl) + 3*(1 - $ldisp)*$ldisp*$ldisp*($cex + $ccex*$cntl) + $ldisp*$ldisp*$ldisp*$cex;
	    $lly = (1 - $ldisp)*(1 - $ldisp)*(1 - $ldisp)*$csy + 3*(1 - $ldisp)*(1 - $ldisp)*$ldisp*($csy + $ccsy*$cntl) + 3*(1 - $ldisp)*$ldisp*$ldisp*($cey + $ccey*$cntl) + $ldisp*$ldisp*$ldisp*$cey;
	    $lmx = (1 - $mdisp)*(1 - $mdisp)*(1 - $mdisp)*$csx + 3*(1 - $mdisp)*(1 - $mdisp)*$mdisp*($csx + $ccsx*$cntl) + 3*(1 - $mdisp)*$mdisp*$mdisp*($cex + $ccex*$cntl) + $mdisp*$mdisp*$mdisp*$cex;
	    $lmy = (1 - $mdisp)*(1 - $mdisp)*(1 - $mdisp)*$csy + 3*(1 - $mdisp)*(1 - $mdisp)*$mdisp*($csy + $ccsy*$cntl) + 3*(1 - $mdisp)*$mdisp*$mdisp*($cey + $ccey*$cntl) + $mdisp*$mdisp*$mdisp*$cey;
	  }
	else
	  {
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

	    // should we displace the start and finish slightly?
	    $arrowpath .= 'M '
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

	    // compute label positions
	    $lux = (1 - $udisp)*(1 - $udisp)*$sx + 2*(1 - $udisp)*$udisp*($mx + $dir*$scale*$nx) + $udisp*$udisp*$ex;
	    $luy = (1 - $udisp)*(1 - $udisp)*$sy + 2*(1 - $udisp)*$udisp*($my + $dir*$scale*$ny) + $udisp*$udisp*$ey;
	    $llx = (1 - $ldisp)*(1 - $ldisp)*$sx + 2*(1 - $ldisp)*$ldisp*($mx + $dir*$scale*$nx) + $ldisp*$ldisp*$ex;
	    $lly = (1 - $ldisp)*(1 - $ldisp)*$sy + 2*(1 - $ldisp)*$ldisp*($my + $dir*$scale*$ny) + $ldisp*$ldisp*$ey;
	    $lmx = (1 - $mdisp)*(1 - $mdisp)*$sx + 2*(1 - $mdisp)*$mdisp*($mx + $dir*$scale*$nx) + $mdisp*$mdisp*$ex;
	    $lmy = (1 - $mdisp)*(1 - $mdisp)*$sy + 2*(1 - $mdisp)*$mdisp*($my + $dir*$scale*$ny) + $mdisp*$mdisp*$ey;

	  }
      }
    else
      {
	$arrowpath .= 'M '
	  . $sx
	  . ' '
	  . $sy
	  . ' L '
	  . $ex
	  . ' '
	  . $ey;
	// compute label positions
	$lux = $sx + $udisp*($ex - $sx);
	$luy = $sy + $udisp*($ey - $sy);
	$llx = $sx + $ldisp*($ex - $sx);
	$lly = $sy + $ldisp*($ey - $sy);
	$lmx = $sx + $mdisp*($ex - $sx);
	$lmy = $sy + $mdisp*($ey - $sy);
      }

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

    $arrowpath .= '" fill="none" ';

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
	$arrowpath .= 'marker-start="url(#'
	  . $markerstart
	  . ')" ';
      }
    if ($markerend)
      {
	$arrowpath .= 'marker-end="url(#'
	  . $markerend
	  . ')" ';
      }
    $arrowpath .= ' />';

    $svg .= $arrowpath
      . '</svg>'
      . "\n";

    if ($upperlabel)
      {
	$upperlabelwidth = getWidthOf('\(' . $upperlabel . '\)');
	$upperlabelheight = "3.5";

//	$svg .= '<circle cx="' . $lux . 'ex" cy="' . $luy . 'ex" r="1" stroke="black" stroke-width="1" />' . "\n";
	$lux += - $upperlabelwidth/2 + $nx*$swap;
	$luy += - $upperlabelheight/2 - $fudgeheight + ($ny - $fudgeheight)*$swap;

	$svg .= '<foreignObject x="'
	  . $lux
	  . 'ex" y="'
	  . $luy
	  . 'ex" width="'
	  . ($upperlabelwidth + 2)
	  . 'ex" height="'
	  . $upperlabelheight
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml"><div style="width:'
	  . $upperlabelwidth
	  . 'ex,height:'
	  . ($upperlabelheight -1)
	  . 'ex" align="center">'
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
	$lowerlabelheight = "3";
	$llx += - $lowerlabelwidth/2 - $nx*$swap;
	$lly += - ($ny - $fudgeheight)*$swap;

	$svg .= '<foreignObject x="'
	  . $llx
	  . 'ex" y="'
	  . $lly
	  . 'ex" width="'
	  . ($lowerlabelwidth + 1)
	  . 'ex" height="'
	  . $lowerlabelheight
	  . 'ex">'
	  . '<body xmlns="http://www.w3.org/1999/xhtml"><div style="width:'
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