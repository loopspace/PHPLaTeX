<?php
/*
 * PHPLaTeX parser
 *
 * Converts something approximating LaTeX code into XHTML+MathML
 * Works by parsing a string in the same manner as TeX:
 *  examining and expanding tokens
 */


/*
 * Fundamental pieces of structure:
 *  definitions (defined by \def)
 *  commands (defined by \command)
 *  primitives (can use PHP code)
 *  counters
 */

$defs = array();
$commands = array();
$primitives = array();
$counters = array();
$conditionals = array();

/*
 * Error function: should be more used
 */

function error ($message)
{
  print "<br /><strong>Error:&nbsp;</strong>" . $message . "<br />";
}

/*
 * Gets the next token from the stream
 *  A token is either a command, like \command, or a single character.
 *  At this stage we also ignore nulls, since we use them as separators,
 *  and comments.
 */

function nexttok (&$latex)
{
  // Silently ignore nulls: we use them as separators
  do {
    $firstchar = substr($latex,0,1);
    $latex = substr($latex,1);
  } while ($firstchar === "\0");

  // Do we have a first character (i.e. is there anything left on the stream)?
  if ($firstchar != "")
    {

      // Is it a backslash?
      if ($firstchar == "\\")
	{
	  // get the next character
	  $secondchar = substr($latex,0,1);
	  $latex = substr($latex,1);

	  // is it a letter?
	  if (preg_match('/[A-Za-z]/',$secondchar))
	    {
	      // Yes, slurp in as many letters as possible
	      preg_match('/^[A-Za-z]*/',$latex,$command);
	      $latex = preg_replace('/^([A-Za-z]*)/','',$latex);

	      return $firstchar . $secondchar . $command[0];
	    }
	  else
	    {
	      return $firstchar . $secondchar;
	    }
	}
      elseif ($firstchar == "%")
	{
	  // comment, ignore rest of line and recall ourselves
	  $latex = preg_replace('/^.*/','',$latex);
	  return nexttok($latex);
	}
      elseif ($firstchar == "<")
	{
	  // XHTML tag, get the rest and pass on
	  list($tag,$latex) = explode(">",$latex,2);
	  $tag = "<" . $tag . ">";
	  // is it a basic MathML tag?
	  if (preg_match('/^<m[ino]\b/',$tag))
	    {
	      // yes, read in whole tag and contents as a single token
	      list($contents,$latex) = explode(">",$latex,2);
	      $tag = $tag . $contents . ">";
	    }
	  return $tag;
	}
      elseif ($firstchar == "&")
	{
	  // possible HTML or MathML entity, get the rest
	  if (preg_match('/^([A-Za-z]+|##0-9]+|#x[A-Fa-f0-9]+);/',$latex))
	    {
	      list($entity,$latex) = explode(";",$latex,2);
	      return "&" . $entity . ";";
	    }
	  else
	    {
	      return $firstchar;
	    }
	}
      else
	{
	  return $firstchar;
	}
    }
  else
    {
      return;
    }
}

/*
 * Most commands and so forth actually work on groups, not tokens.
 * This function returns the next group.
 * 
 * Question: do we return the group still grouped or do we remove the
 *  outermost grouping?
 */

function nextgrp (&$latex)
{
  $firstchar = nexttok($latex);
  $group = "";

  if ($firstchar != "")
    {
      if (($firstchar == "{") or ($firstchar == "\\bgroup"))
	{
	  $c = 0;
	  $nexttok = "";
	  while($c >= 0)
	    {
	      $group = $group . $nexttok;
	      $nexttok = nexttok($latex);
	      $test = str_replace(array('\{','\}'),'',$group . $nexttok);
	      $test = preg_replace('/\\bgroup\b/','{',$test);
	      $test = preg_replace('/\\egroup\b/','}',$test);
       
	      $c = substr_count($test, '{') - substr_count($test,'}');
	    }
	}
      else
	{
	  $group = $firstchar;
	}
    }
  return $group;
}

/*
 * Expands a token
 */

function expandtok ($token,&$latex)
{
  global $defs;
  global $commands;
  global $primitives;
  global $conditionals;
  // get first character of the token

  $firstchar = substr($token,0,1);

  if ($firstchar == "\\")
    {
      // command
      $command = substr($token,1);
      if (array_key_exists($command,$primitives))
	{
	  // command is actually a "primitive" which is a PHP function called with the stream as its argument
	  return array(1,$primitives[$command]($latex));
	}
      elseif (array_key_exists($command,$defs))
	{
	  // command is a generic def, need to slurp in tokens to match its pattern
	  $pattern = $defs[$command]["pattern"];
	  $defn = $defs[$command]["defn"];
	  // in an ideal world we'd use a regular expression here but . only mathes characters not tokens
	  if ($pattern != "")
	    {
	      // explode along hashes (check no more than 9?)
	      $patarray = explode("#",$pattern);
	      // first entry is whatever is before #1
	      $latex = ltrim($latex,array_shift ($patarray)); // or error!
	      $num = 0;
	      while ($patarray)
		{
		  $num++;
		  $arg = "";
		  $testpat = "";
		  $nextpat = substr(array_shift($patarray),1);
		  if ($nextpat)
		    {
		      // have a delimiter, slurp in groups until we match the delimiter
		      do {
			$nextgrp = nextgrp($latex);

			if (strpos($nextpat,$testpat . $nextgrp) === 0)
			  {
			    // matches the start of the delimiter
			    $testpat = $testpat . $nextgrp;
			  }
			else
			  {
			    // broke the match, add it all to $arg and start again
			    $arg = $arg . $testpat . $nextgrp;
			}
		      } while ($nextpat !== $testpat);
		    }
		  else
		    {
		      // no delimiter, slurp in next group only
		      $arg = nextgrp($latex);
		    }
		  $defn=str_replace("#" . $num, $arg, $defn);
		}
	    }
	  // return expanded command
	  return array(1,$defn);
	}
      elseif (array_key_exists($command,$commands))
	{
	  // command is known
	  $args = $commands[$command]["args"];
	  $opts = $commands[$command]["opts"];
	  $defn = $commands[$command]["defn"];
	  // slurp in next $args tokens
	  // trim whitespace after command first
	  // NB do this here to get '\)' correct.
	  $latex = ltrim($latex, " ");
	  for ($i = 0;$i < $args;$i++)
	    {
	      if (array_key_exists(($i+1),$opts))
		{
		  // optional argument
		  $nexttok = nexttok($latex);
		  if ($nexttok == "[")
		    {
		      // specified by user, slurp in until closing brace
		      $nextgrp = nextgrp($latex);
		      $arg = "";
		      while ($nextgrp != "]")
			{
			  $arg = $arg . $nextgrp;
			  $nextgrp = nextgrp($latex);
			}
		    }
		  else
		    {
		      // put the token back on the stream, wasn't wanted, and use default instead
		      $latex = $nexttok . $latex;
		      $arg = $opts[($i+1)];
		    }
		}
	      else
		{
		  $arg = nextgrp($latex);
		}
	      $defn=str_replace("#" . ($i+1), $arg, $defn);
	    }
	  // return expanded command
	  return array(1,$defn);
	}
      else
	{
	  // command is not known, just return it unexpanded
	  return array(0,$token);
	}
    }
  /*  elseif ($firstchar == "<")
   *{
   *  // xhtml tag, skip
   *  list($tag,$latex) = explode(">",$latex,2);
   *  // remove nulls just in case any are left hanging around
   *  $tag = str_replace("\0","",$tag);
   *  return array(0,"<" . $tag . ">");
   *}
   */
  elseif ($firstchar == "~")
    {
      // non-breaking space
      return array(0,"&nbsp;");
    }
  elseif ($firstchar == "\n")
    {
      // strip off leading whitespace, if this whitespace contains another newline then return \par, otherwise return a single space
      preg_match('/^(\s*)/s',$latex,$whitespace);
      $latex = preg_replace('/^\s*/s','',$latex);
      if (substr_count($whitespace[1],"\n") > 0)
	{
	  return array(1,"\\par");
	}
      else
	{
	  return array(1," ");
	}
    }
  elseif ($firstchar == "{")
    {
      if ($conditionals{"mmode"})
	{
	  // replace the first character, maybe this test ought to be on the whole token, though we ought to only get firstchar being { if the whole token is {
	  $latex = $firstchar . $latex;
	  $base = nextgrp($latex);
	  $nexttok = nexttok($latex);
	  if ($nexttok == '^')
	    {
	      // superscript, do we have a subscript?
	      $sup = nextgrp($latex);
	      $nexttok = nexttok($latex);
	      if ($nexttok == '_')
		{
		  // also have subscript
		  $sub = nextgrp($latex);
		  $return = '<msubsup><mrow>' . $base . '</mrow><mrow>' . $sub . '</mrow><mrow>' . $sup . '</mrow></msubsup>';
		}
	      else
		{
		  $return = '<msup><mrow>' . $base . '</mrow><mrow>' . $sup . '</mrow></msup>' . $nexttok;
		}
	    }
	  elseif ($nexttok == '_')
	    {
	      $sub = nextgrp($latex);
	      $nexttok = nexttok($latex);
	      if ($nexttok == '^')
		{
		  // also have subscript
		  $sup = nextgrp($latex);
		  $return = '<msubsup><mrow>' . $base . '</mrow><mrow>' . $sub . '</mrow><mrow>' . $sup . '</mrow></msubsup>';
		}
	      else
		{
		  $return = '<msub><mrow>' . $base . '</mrow><mrow>' . $sub . '</mrow></msub>' . $nexttok;
		}
	    }
	  else
	    {
	      // neither sub or sup, replace token
	      $return = '<mrow>' . $nextgrp . '</mrow>' . $nexttok;
	    }
	  return array(1,$return);
	}
      else
	{
	  // out of math mode, no grouping looked for, { does nothing
	  return '';
	}
    }
  elseif ($firstchar == "}")
    {
      // if we get a }, should just ignore it
      return '';
    }
  else
    {
      if ($conditionals{"mmode"})
	{
	  $mod = 1;
	  $nexttok = nexttok($latex);
	  if ($nexttok == '^')
	    {
	      // superscript, do we have a subscript?
	      $sup = nextgrp($latex);
	      $nexttok = nexttok($latex);
	      if ($nexttok == '_')
		{
		  // also have subscript
		  $sub = nextgrp($latex);
		  $return = '<msubsup><mrow>' . $token . '</mrow><mrow>' . $sub . '</mrow><mrow>' . $sup . '</mrow></msubsup>';
		}
	      else
		{
		  $return = '<msup><mrow>' . $token . '</mrow><mrow>' . $sup . '</mrow></msup>' . $nexttok;
		}
	    }
	  elseif ($nexttok == '_')
	    {
	      $sub = nextgrp($latex);
	      $nexttok = nexttok($latex);
	      if ($nexttok == '^')
		{
		  // also have subscript
		  $sup = nextgrp($latex);
		  $return = '<msubsup><mrow>' . $token . '</mrow><mrow>' . $sub . '</mrow><mrow>' . $sup . '</mrow></msubsup>';
		}
	      else
		{
		  $return = '<msub><mrow>' . $token . '</mrow><mrow>' . $sub . '</mrow></msub>' . $nexttok;
		}
	    }
	  else
	    {
	      // neither sub or sup, replace next token back on stream
	      $latex = $nexttok . "\0" . $latex;
	      // enclose character in appropriate tags
	      if (preg_match('/^[A-Za-z]*$/',$token))
		{
		  $return = '\mathchar{' . $token . '}';
		}
	      elseif (preg_match('/^[0-9]*$/',$token))
		{
		  $return = '\mathnum{' . $token . '}';
		}
	      elseif (preg_match('/^\s*$/',$token))
		{
		  $return = "";
		}
	      elseif (preg_match('/^[+=-]*$/',$token))
		{
		  $return = '\mathop{' . $token . '}';
		}
	      else
		{
		  $return = $token;
		  $mod = 0;
		}
	    }
	  return array($mod,$return);

	}
      // no expansion to be done
      // TODO: add support for ^ and _ in math mode
      // any other "special" characters?
      return array(0,$token);
    }
}

/*
 * Expand a string and estimate its width
 */

function getWidthOf ($string)
{
  $expanded = processLaTeX($string);
  $textwidth = 80; // maximum width, global variable?
  $width = 0;
  $expanded = trim($expanded);
  // default is one unit, rules should be more complicated to deal with fracs and newlines
  $rule = array(
		"mi" => 1,
		"mo" => 1.5,
		"mn" => 1
		);
  
  while ($expanded)
    {
      // strip off first char
      $char = substr($expanded,0,1);
      $expanded = substr($expanded,1);
      if ($char == "<")
	{
	  // tag
	  if (preg_match('/^([a-z]+)/',$expanded,$type))
	    {
	      // opening tag
	      $tags[] = $type[1]; // push tag onto stack
	    }
	  elseif (preg_match('/^\/([a-z]+)/',$expanded,$type))
	    {
	      // closing tag, ought to check for balance
	      unset($tags[count($tags)-1]);
	    }
	  else
	    {
	      // errk.  Something that wasn't an opening or closing tag
	    }
	  // delete rest of tag from string
	  list($junk,$expanded) = explode(">",$expanded,2);
	  while (preg_match('/\\$/',$junk))
	    {
	      // make sure the > wasn't escaped (is this the right thing to match?)
	      list($junk,$expanded) = explode(">",$expanded,2);
	    }
	}
      else
	{
	  if ($char == "&")
	    {
	      // entity
	      list($entity,$expanded) = explode(";",$expanded,2);
	    }
	  if (preg_match('/\s/',$char))
	    {
	      // whitespace
	      ltrim($expanded);
	    }
	  if (array_key_exists($tags[count($tags) - 1],$rule))
	    {
	      $width = $width + $rule[$tags[count($tags)-1]];
	    }
	  else
	    {
	      $width++;
	    }
	}
    }
  return min($textwidth,$width);
}


/*
 * Process a string consisting of LaTeX commands
 *  Strip off tokens, expanding them one by one.
 *  If the expansion actually does something, replace them at the front
 *  of the stream (with a null string as a separator so that we don't
 *  inadvertantly create a new command).
 */

function processLaTeX (&$latex)
{
  $processed = "";

  while ($latex)
    {
      // get next token from stream
      $token = nexttok($latex);

      // expand token
      list($mod,$extoken) = expandtok($token,$latex);

      // did that actually do anything?

      if ($mod)
	{
	  // yes, reinsert at front of stream (with a separator) and start again
	  $latex = $extoken . "\0" . $latex;
	}
      else
	{
	  // no, consider as processed and pass on to the next chunk
	  $processed = $processed . $extoken;
	}
    }
  return $processed;
}

// primitives

$primitivedir = dirname($_SERVER["SCRIPT_FILENAME"]) ."/primitives/";

if (is_dir($primitivedir) and is_readable($primitivedir))
  {
    $handle = opendir($primitivedir);
    while (false !== ($file = readdir($handle)))
      {
	$file = $primitivedir . $file;
	if (is_file($file) and is_readable($file) and preg_match('/\.php$/', $file))
	  {
	    $primitive = file_get_contents($file);
	    list($name,$function) = explode("\n",$primitive,2);
	    // actual name is last non-whitespace part of first line
	    // perhaps should have something better than this ...
	    $name = preg_replace('/^.*\s+(\S+)\s*$/','\1',$name);
	    $primitives[$name] = create_function('&$latex',$function);
	  }
      }
  }

?>