<?php


/*
 * Expand a string and estimate its width, height, and depth
 */

$sizeFunctions = array(
		       "horizSum", create_function('$a,$b','$c["width"] = $a["width"] + $b["width"];$c["height"] = max($a["height"], $b["height"]);$c["depth"] = max($a["depth"], $b["depth"]); return $c;'),
		       "vertSum", create_function('$a,$b','$c["width"] = max($a["width"],$b["width"]);$c["height"] = $a["height"] + $b["height"]; $c["depth"] = $a["depth"] + $b["depth"]; return $c;')
		       );

$charSizes = array(
		   "A" => array(
				"width" => 1.5,
				"height" => 1.8,
				"depth" => 0
				),
		   "a" => array(
				"width" => 1,
				"height" => 1.1,
				"depth" => 0
				)
		   );

function charSize ($char)
{
  global $charSizes;
  // gets a string of characters or entities and returns their (approximate) size
  // morally, $size = new charSize();
  $size = array(
		"width" => 0,
		"height" => 0,
		"depth" => 0
		);

  while ($char)
    {
      if (preg_match('/^(&(?:[A-Za-z]+|#[0-9]+|#x[A-Fa-f0-9]+);)(.*)/',$char,$matches))
	{
	  // entity
	  $char = $matches[2];
	  if (array_key_exists($matches[1],$charSizes))
	    {
	      $size = $sizeFunctions["horizSum"]($size, $charSizes[$matches[1]]);
	    }
	  else
	    {
	      if (preg_match('/^(&[A-Z](opf|scr|frk);)/',$matches[1]))
		{
		  $size = $sizeFunctions["horizSum"]($size, $charSizes["A"]);
		}
	      else
		{
		  $size = $sizeFunctions["horizSum"]($size, $charSizes["a"]);
		}
	    }
	}
      else
	{
	  $firstchar = substr($char,0,1);
	  $char = substr($char,1);
	  if (array_key_exists($firstchar,$charWidths))
	    {
	      $size = $sizeFunctions["horizSum"]($size, $charSizes[$firstchar]);
	    }
	  else
	    {
	      if (strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ",$firstchar) !== FALSE)
		{
		  $size = $sizeFunctions["horizSum"]($size, $charSizes["A"]);
		}
	      else
		{
		  $size = $sizeFunctions["horizSum"]($size, $charSizes["a"]);
		}
	    }
	}
    }
  return $size;
}


/*
 * Need these to be global so that we can refer to them in a
 * function-within-a-function (limited scoping rules of PHP)
 */

$sizeRule = array(
		"mi" => create_function(
					'$contents',
					'return charSize($contents);'
					),
		"mo" => create_function(
					'$contents',
					'return $sizeFunctions["horizSum"](charSize($contents),$charSizes["a"]);'
					),
		"mn" => create_function(
					'$contents',
					'return charSize($contents);'
					),
		"mrow" => create_function(
					  '$contents',
					  'return array_sum(explode(" ",$contents));'
					  ),
		"mfrac" => create_function(
					   '$contents',
					   'return max(explode(" ",$contents));'
					   ),
		"msup" => create_function(
					  '$contents',
					  '$conts = explode(" ",$contents); return (array_shift($conts) + .8*array_sum($conts));'),
		"msub" => create_function(
					  '$contents',
					  '$conts = explode(" ",$contents); return array_shift($conts);'),
		"msubsup" => create_function(
					  '$contents',
					  '$conts = explode(" ",$contents); return (array_shift($conts) + .8*array_sum($conts));'),
		"mtext" => create_function(
					   '$contents',
					   'return array_sum(explode(" ",$contents));'
					   ),
		);


function getSizeOf ($string)
{
  $expanded = processLaTeX($string);
  return _getSizeOf($expanded);
}

function _getEnclosure($tag,$string)
{
  
}

function _getSizeOf($string)
{
  
  // Need to go through and compute lengths

  $string = trim($string);

  if (preg_match('#^<([a-z]+)([^>]*)(/?)>(.*)#s',$string,$matches))
    {
      // tag
      if ($matches[3])
	{
	  // non-enclosure tag
	  if (array_key_exists($matches[1],$sizeRule))
	    {
	      return $sizeFunctions["horizSum"]($sizeRule[$matches[1]]($matches[2]),_getSizeOf($matches[4]));
	    }
	  else
	    {
	      return _getSizeOf($matches[4]);
	    }
	}
      else
	{
	  // enclosure tag, find enclosure
	  $tag = $matches[1];
	  
	  $enclosure = "";
	  if (array_key_exists($matches[1],$sizeRule))
	    {
	    }
	  else
	    {
	    }
	}
    }
  else
    {
      // not a tag
      preg_match('/([^<]*)(.*)/',$string,$matches);
      return $sizeFunctions["horizSum"]($charSize($matches[1]),_getSizeOf($matches[2]));
    }
}


/*
 * WIDTH
 */

/*
 * Array to override automatic width calculation
 */

$charWidths = array();

function charWidth ($char)
{
  global $charWidths;
  // gets a string of characters or entities and returns their (approximate) length
  $length = 0;
  while ($char)
    {
      if (preg_match('/^(&(?:[A-Za-z]+|#[0-9]+|#x[A-Fa-f0-9]+);)(.*)/',$char,$matches))
	{
	  // entity
	  $char = $matches[2];
	  if (array_key_exists($matches[1],$charWidths))
	    {
	      $length += $charWidths[$matches[1]];
	    }
	  else
	    {
	      if (preg_match('/^(&[A-Z](opf|scr|frk);)/',$matches[1]))
		{
		  $length += 1.5;
		}
	      else
		{
		  $length += 1;
		}
	    }
	}
      else
	{
	  $firstchar = substr($char,0,1);
	  $char = substr($char,1);
	  if (array_key_exists($firstchar,$charWidths))
	    {
	      $length += $charWidths[$char];
	    }
	  else
	    {
	      if (strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ",$firstchar) !== FALSE)
		{
		  $length += 1.5;
		}
	      else
		{
		  $length += 1;
		}
	    }
	}
    }
  return $length;
}

/*
 * Need these to be global so that we can refer to them in a
 * function-within-a-function (limited scoping rules of PHP)
 */

$widthRule = array(
		"mi" => create_function(
					'$contents',
					'return charWidth($contents);'
					),
		"mo" => create_function(
					'$contents',
					'return (charWidth($contents) + 1);'
					),
		"mn" => create_function(
					'$contents',
					'return charWidth($contents);'
					),
		"mrow" => create_function(
					  '$contents',
					  'return array_sum(explode(" ",$contents));'
					  ),
		"mfrac" => create_function(
					   '$contents',
					   'return max(explode(" ",$contents));'
					   ),
		"msup" => create_function(
					  '$contents',
					  '$conts = explode(" ",$contents); return (array_shift($conts) + .8*array_sum($conts));'),
		"msub" => create_function(
					  '$contents',
					  '$conts = explode(" ",$contents); return array_shift($conts);'),
		"msubsup" => create_function(
					  '$contents',
					  '$conts = explode(" ",$contents); return (array_shift($conts) + .8*array_sum($conts));'),
		"mtext" => create_function(
					   '$contents',
					   'return array_sum(explode(" ",$contents));'
					   ),
		);


function getWidthOf ($string)
{
  $expanded = processLaTeX($string);
  $textwidth = 80; // maximum width, global variable?
  $width = 0;
  
  // Need to go through and compute lengths

  $a = trim($expanded);  
  $b = "";

  // first replace single tags
  while ($a != $b)
    {
      $b = $a;
      $a = preg_replace_callback(
				 '/<([a-z]+)([^>]*)\/>/',
				 create_function(
						 '$matches',
						 '
global $widthRule;
if (array_key_exists($matches[1], $widthRule))
{
return " " . $widthRule[$matches[1]]($matches[2]) . " ";
}
else
{
return " 0 ";
}
'),
			$b);
    }

  $b = "";

  while ($a != $b)
    {
      $b = $a;
      $a = preg_replace_callback(
			'/<([a-z]+)[^>]*>([^<]*)<\/([a-z]+)>/',
			create_function(
					'$matches',
					'
global $widthRule;
if (array_key_exists($matches[1], $widthRule))
{
return " " . $widthRule[$matches[1]]($matches[2]) . " ";
}
else
{
return $matches[2];
}
'),
			$b);
    }

  return trim(min($textwidth,$a));
}


/*
 * HEIGHT
 */

/*
 * Array to override automatic height calculation
 */

$charHeights = array();

function charHeight ($char)
{
  global $charHeights;
  // gets a string of characters or entities and returns their (approximate) length
  $height = 0;
  while ($char)
    {
      if (preg_match('/^(&(?:[A-Za-z]+|#[0-9]+|#x[A-Fa-f0-9]+);)(.*)/',$char,$matches))
	{
	  // entity
	  $char = $matches[2];
	  if (array_key_exists($matches[1],$charHeights))
	    {
	      $height = max($height, $charHeights[$matches[1]]);
	    }
	  else
	    {
	      if (preg_match('/^(&[A-Z](opf|scr|frk);)/',$matches[1]))
		{
		  $height = max($height, 1.8);
		}
	      else
		{
		  $height = max($height,1.8);
		}
	    }
	}
      else
	{
	  $firstchar = substr($char,0,1);
	  $char = substr($char,1);
	  if (array_key_exists($firstchar,$charHeights))
	    {
	      $height = max($height,$charHeights[$char]);
	    }
	  else
	    {
	      if (strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZbdfhijklt",$firstchar) !== FALSE)
		{
		  $height = max($height,1.8);
		}
	      else
		{
		  $height = max($height,1.2);
		}
	    }
	}
    }
  return $height;
}

/*
 * Need these to be global so that we can refer to them in a
 * function-within-a-function (limited scoping rules of PHP)
 */

$heightRule = array(
		"mi" => create_function(
					'$contents',
					'return charHeight($contents);'
					),
		"mo" => create_function(
					'$contents',
					'return (charHeight($contents) + 1);'
					),
		"mn" => create_function(
					'$contents',
					'return charHeight($contents);'
					),
		"mrow" => create_function(
					  '$contents',
					  'return max(explode(" ",$contents));'
					  ),
		"mfrac" => create_function(
					   '$contents',
					   'return array_sum(explode(" ",$contents));'
					   ),
		"mtext" => create_function(
					   '$contents',
					   'return max(explode(" ",$contents));'
					   ),
		);


function getHeightOf ($string)
{
  $expanded = processLaTeX($string);
  $textheight = 80; // maximum width, global variable?
  $height = 0;
  
  // Need to go through and compute lengths

  $a = trim($expanded);  
  $b = "";

  // first replace single tags
  while ($a != $b)
    {
      $b = $a;
      $a = preg_replace_callback(
				 '/<([a-z]+)([^>]*)\/>/',
				 create_function(
						 '$matches',
						 '
global $heightRule;
if (array_key_exists($matches[1], $heightRule))
{
return " " . $heightRule[$matches[1]]($matches[2]) . " ";
}
else
{
return " 0 ";
}
'),
			$b);
    }

  $b = "";

  while ($a != $b)
    {
      $b = $a;
      $a = preg_replace_callback(
			'/<([a-z]+)[^>]*>([^<]*)<\/([a-z]+)>/',
			create_function(
					'$matches',
					'
global $heightRule;
if (array_key_exists($matches[1], $heightRule))
{
return " " . $heightRule[$matches[1]]($matches[2]) . " ";
}
else
{
return $matches[2];
}
'),
			$b);
    }

  return trim(min($textheight,$a));
}


/*
 * DEPTH
 */

/*
 * Array to override automatic depth calculation
 */

$charDepths = array();

function charDepth ($char)
{
  global $charDepths;
  // gets a string of characters or entities and returns their (approximate) length
  $depth = 0;
  while ($char)
    {
      if (preg_match('/^(&(?:[A-Za-z]+|#[0-9]+|#x[A-Fa-f0-9]+);)(.*)/',$char,$matches))
	{
	  // entity
	  $char = $matches[2];
	  if (array_key_exists($matches[1],$charDepths))
	    {
	      $depth = max($depth, $charDepths[$matches[1]]);
	    }
	  else
	    {
	      if (preg_match('/^(&[A-Z](opf|scr|frk);)/',$matches[1]))
		{
		  $depth = max($depth,0);
		}
	      else
		{
		  $depth = max($depth,0);
		}
	    }
	}
      else
	{
	  $firstchar = substr($char,0,1);
	  $char = substr($char,1);
	  if (array_key_exists($firstchar,$charDepths))
	    {
	      $depth = max($depth,$charDepths[$char]);
	    }
	  else
	    {
	      if (strpos("ABCDEFGHIJKLMNOPQRSTUVWXYZ",$firstchar) !== FALSE)
		{
		  $depth = max($depth,0);
		}
	      else
		{
		  $depth = max($depth,0);
		}
	    }
	}
    }
  return $depth;
}

/*
 * Need these to be global so that we can refer to them in a
 * function-within-a-function (limited scoping rules of PHP)
 */

$depthRule = array(
		"mi" => create_function(
					'$contents',
					'return charDepth($contents);'
					),
		"mo" => create_function(
					'$contents',
					'return (charDepth($contents) + 1);'
					),
		"mn" => create_function(
					'$contents',
					'return charDepth($contents);'
					),
		"mrow" => create_function(
					  '$contents',
					  'return max(explode(" ",$contents));'
					  ),
		"mfrac" => create_function(
					   '$contents',
					   'return array_sum(explode(" ",$contents));'
					   ),
		"mtext" => create_function(
					   '$contents',
					   'return max(explode(" ",$contents));'
					   ),
		);


function getDepthOf ($string)
{
  $expanded = processLaTeX($string);
  $textdepth = 80; // maximum width, global variable?
  $depth = 0;
  
  // Need to go through and compute lengths

  $a = trim($expanded);  
  $b = "";

  // first replace single tags
  while ($a != $b)
    {

      $b = $a;
      $a = preg_replace_callback(
				 '/<([a-z]+)([^>]*)\/>/',
				 create_function(
						 '$matches',
						 '
global $depthRule;
if (array_key_exists($matches[1], $depthRule))
{
return " " . $depthRule[$matches[1]]($matches[2]) . " ";
}
else
{
return " 0 ";
}
'),
			$b);
    }

  $b = "";

  while ($a != $b)
    {

      $b = $a;
      $a = preg_replace_callback(
			'/<([a-z]+)[^>]*>([^<]*)<\/([a-z]+)>/',
			create_function(
					'$matches',
					'
global $depthRule;
if (array_key_exists($matches[1], $depthRule))
{
return " " . $depthRule[$matches[1]]($matches[2]) . " ";
}
else
{
return $matches[2];
}
'),
			$b);
    }

  return trim(min($textdepth,$a));
}


/*
 * Convert lengths into multiples of 'ex'
 * These are interpreted as TeX lengths, not CSS lengths
 * Basically, we view 'ex' as being the same in both systems
 */

function MakeEx($length)
{
  global $fontsize;
  preg_match('/(-?\d*)(\D*)/',$length,$matches);
  $dist = $matches[1];
  $type = $matches[2];
  if ($type == 'ex')
    {
      return $dist;
    }
  elseif ($type == 'em')
    {
      return ($dist*151/90);
    }
  elseif ($type == 'pt')
    {
      return ($dist*151/(90*$fontsize));
    }
  elseif ($type == 'px')
    {
      return ($dist*151/(90*$fontsize));
    }

  return $dist;
}

// The following all use "vectors": arrays with "x" and "y".
// Should probably make this OO for use with TikZ coordinate systems.

function cubicBezier($t,$s,$a,$b,$e)
{
  foreach (array("x","y") as $c)
    {
      $p[$c] = (1 - $t)*(1 - $t)*(1 - $t)*$s[$c] + 3*(1 - $t)*(1 - $t)*$t*$a[$c] + 3*(1 - $t)*$t*$t*$b[$c] + $t*$t*$t*$e[$c];
      $d[$c] = -(1 - $t)*(1 - $t)*$s[$c] + (1 - $t)*(1 - 3*$t)*$a[$c] + $t*(2 - 3*$t)*$b[$c] + $t*$t*$e[$c];
    }
  return array($p,$d);
}

function quadraticBezier($t,$s,$a,$e)
{
  foreach (array("x","y") as $c)
    {
      $p[$c] = (1 - $t)*(1 - $t)*$s[$c] + 2*(1 - $t)*$t*$a[$c] + $t*$t*$e[$c];
      $d[$c] = -(1 - $t)*$s[$c] + (1 - 2*$t)*$a[$c] + $t*$e[$c];
    }
  return array($p,$d);
}

function linear($t,$s,$e)
{
  foreach (array("x","y") as $c)
    {
      $p[$c] = (1 - $t)*$s[$c] + $t*$e[$c];
      $d[$c] = $e[$c] - $s[$c];
    }
  return array($p,$d);
}

function vecSum($a,$b)
{
  foreach (array("x","y") as $c)
    {
      $r[$c] = $a[$c] + $b[$c];
    }
  return $r;
}

// scale a vector, either by a single scaler or a vector of scalers
function vecScale($l,$a)
{
  if (is_array($l))
    {
      foreach (array("x","y") as $c)
	{
	  $r[$c] = $l[$c]*$a[$c];
	}
    }
  else
    {
      foreach (array("x","y") as $c)
	{
	  $r[$c] = $l*$a[$c];
	}
    }
  return $r;
}

function vecMinus($a,$b)
{
  foreach (array("x","y") as $c)
    {
      $r[$c] = $a[$c] - $b[$c];
    }
  return $r;
}

function vecOrth($a)
{
  LaTeXdebug("vecOrth got x: " . $a["x"] . " and y: " .  $a["y"],1);
  $r["x"] = $a["y"];
  $r["y"] = - $a["x"];
  return $r;
}

function vecSqNorm($a)
{
  LaTeXdebug("vecNorm got x: " . $a["x"] . " and y: " .  $a["y"],1);
  return sqrt($a["x"]*$a["x"] + $a["y"]*$a["y"]);
}

function vecReSqNorm($a)
{
  if (($a["x"] == 0) and ($a["y"] == 0))
    {
      return 0;
    }
  else
    {
      return vecScale(1/vecSqNorm($a),$a);
    }
}

// Sup norm with $ur and $dl as upper-right and lower-left corners of box
function vecSupNorm($a,$ur,$dl)
{
  if (($ur["x"] > 0) and ($ur["y"] > 0) and ($dl["x"] <0) and ($dl["y"] < 0))
    {
      return max($a["x"]/$ur["x"], $a["x"]/$dl["x"], $a["y"]/$ur["y"], $a["y"]/$dl["y"]);
    }
  else
    {
      return 0;
    }
}

function vecReSupNorm($a,$ur,$dl)
{
  $n = vecSupNorm($a,$ur,$dl);
  if ($n == 0)
    {
      return 0;
    }
  else
    {
      return vecScale(1/$n,$a);
    }
}


function vecSign($a)
{
  // replace each entry by its sign
  foreach (array("x","y") as $c)
    {
      if ($a[$c] == 0)
	{
	  $r[$c] = 0;
	}
      else
	{
	  $r[$c] = $a[$c]/abs($a[$c]);
	}
    }
  return $r;
}

function udrlVect($str)
{
  // turn a udrl string into a vector
  $r["x"] = substr_count(strtolower($str),"r") - substr_count(strtolower($str),"l");
  $r["y"] = substr_count(strtolower($str),"d") - substr_count(strtolower($str),"u");
  return $r;
}

// return the vector in string form, rounded as usually this is before insertion into SVG
function vecXY($a,$b = "",$u = "")
{
  if ($b == "x")
    {
      return 'x="' . (round($a["x"]*20)/20) . $u . '" y="' . (round($a["y"]*20)/20) . $u . '"';
    }
  elseif ($b == "w")
    {
      return 'width="' . abs(round($a["x"]*20)/20) . $u . '" height="' . abs(round($a["y"]*20)/20) . $u . '"';
    }
  else
    {
      return (round($a["x"]*20)/20) . $u . ' ' . (round($a["y"]*20)/20) . $u;
    }
}

function vecMake($x,$y)
{
  $r["x"] = $x;
  $r["y"] = $y;
  return $r;
}

?>