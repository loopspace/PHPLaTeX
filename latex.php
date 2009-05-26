<?php

$defs = array();
$commands = array();
$primitives = array();

function error ($message)
{
  print "<br /><strong>Error:&nbsp;</strong>" . $message . "<br />";
}

function nexttok (&$latex)
{
  // Silently ignore nulls
  do {
    $firstchar = substr($latex,0,1);
    $latex = substr($latex,1);
  } while ($firstchar == "\0");

  if ($firstchar)
    {

      if ($firstchar == "\\")
	{
	  $secondchar = substr($latex,0,1);
	  $latex = substr($latex,1);

	  if (preg_match('/[A-z]/',$secondchar))
	    {
	      preg_match('/^([a-z]*)(.*)/s',$latex, $matches);
	      $latex = $matches[2];

	      return $firstchar . $secondchar . $matches[1];
	    }
	  else
	    {
	      return $firstchar . $secondchar;
	    }
	}
      elseif ($firstchar == '{')
	{
	  $token = $firstchar;
	  $c = 1;
	  while($c > 0)
	    {
	      $token = $token . substr($latex,0,1);
	      $latex = substr($latex,1);
	      $test = str_replace(array('\{','\}'),'',$token);
       
	      $c = substr_count($test, '{') - substr_count($test,'}');

	    }
	  return $token;
	}
      else
	{
	  return $firstchar;
	}
    }
  else
    {
      return '';
    }
}

function expandtok ($token,&$latex)
{
  global $defs;
  global $commands;
  global $primitives;
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
	  if ($pattern)
	    {
	      // explode along hashes (check no more than 9?)
	      $patarray = explode("#",$pattern);
	      // first entry is whatever is before #1
	      ltrim($latex,array_shift ($patarray)); // or error!
	      $num = 0;
	      while ($patarray)
		{
		  $num++;
		  $arg = "";
		  $testpat = "";
		  $nextpat = substr(array_shift($patarray),1);
		  if ($nextpat)
		    {
		      // have a delimiter, slurp in tokens until we match the delimiter
		      do {
			$nexttok = nexttok($latex);

			if (strpos($nextpat,$testpat . $nexttok) === 0)
			  {
			    // matches the start of the delimiter
			    $testpat = $testpat . $nexttok;
			  }
			else
			  {
			    // broke the match, add it all to $arg and start again
			    $arg = $arg . $testpat . $nexttok;
			}
		      } while ($nextpat !== $testpat);
		    }
		  else
		    {
		      // no delimiter, slurp in next token only
		      $arg = nexttok($latex);
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
	  // TODO: handle optional arguments
	  // trim whitespace after command first
	  // NB do this here to get '\)' correct.
	  $latex = ltrim($latex);
	  for ($i = 0;$i < $args;$i++)
	    {
	      if (array_key_exists(($i+1),$opts))
		{
		  // optional argument
		  $nexttok = nexttok($latex);
		  if ($nexttok == "[")
		    {
		      // specified by user, slurp in until closing brace
		      $nexttok = nexttok($latex);
		      $arg = "";
		      while ($nexttok != "]")
			{
			  $arg = $arg . $nexttok;
			  $nexttok = nexttok($latex);
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
		  $arg = nexttok($latex);
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
  elseif ($firstchar == "{")
    {
      // token by virtue of grouping, strip off outermost grouping
      $extoken = preg_replace('/^{(.*)}$/s','$1',$token);
      return array(1,$extoken);
    }
  elseif ($firstchar == "%")
    {
      // slurp in rest until a newline
      do {
	  $nexttok = nexttok($latex);
      } while ($nexttok != "\n");
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
  else
    {
      // no expansion to be done
      // TODO: add support for ^ and _ in math mode
      // any other "special" characters?
      return array(0,$token);
    }
}

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
	if (is_file($file) and is_readable($file))
	  {
	    $primitive = file_get_contents($file);
	    list($name,$function) = explode("\n",$primitive,2);
	    // actual name is last non-whitespace part of first line
	    $name = preg_replace('/^.*\b([A-z]+).*$/','\1',$name);
	    $primitives[$name] = create_function('&$latex',$function);

	  }
      }
  }

?>