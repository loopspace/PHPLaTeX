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

  $firstchar = substr($latex,0,1);
  $latex = substr($latex,1);

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
	      // TODO: do this!
	    }
	  else
	    {
	      $latex = $defn . $latex;
	    }
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
	      $arg = nexttok($latex);
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
	  // yes, reinsert at front of stream and start again
	  $latex = $extoken . $latex;
	}
      else
	{
	  // no, consider as processed and pass on to the next chunk
	  $processed = $processed . $extoken;
	}
    }
  return $processed;
}

// commands

$primitives["newcommand"] = 
  create_function ('& $latex','
global $commands;
list($mod,$name) = expandtok(nexttok($latex),$latex); // first argument is name of command, need to strip off brackets
$nexttok = nexttok($latex); // next is either defn or says we have arguments
if ($nexttok == "[")
  {
    // have arguments, how many?
    $num = nexttok($latex);
    nexttok($latex); // ought to be a "]", should test this
    // should test for numeric here also
    $nexttok = nexttok($latex); // either optional first argument or defn
    if ($nexttok == "[")
      {
	// option argument specified
	$nexttok = nexttok($latex);
	$opt = "";
	while ($nexttok != "]")
	  {
	    // slurp everything up to but not including "]"
	    $opt = $opt . $nexttok;
	    $nexttok = nexttok($latex);
	  }
	$optarray = array("1" => $opt);
	$defn = nexttok($latex);
      }
    else
      {
	// no optional arguments, just defn
	$optarray = array();
	$defn = $nexttok;
      }
  }
else
  {
    $num = 0;
    $optarray = array();
    $defn = $nexttok;
  }

// strip off slashes, just in case
// need the four slashes as we are already inside a quoted string
$name=ltrim($name,"\\\\");
$commands[$name] = array(
			 "args" => $num,
			 "opts" => $optarray,
			 "defn" => $defn
			 );
	      return;
');

$primitives["usepackage"] =
  create_function ('&$latex','
list($mod,$package) = expandtok(nexttok($latex),$latex); // get package name and strip off braces
// check that $package is safe!
if (preg_match("/^\\w+$/s",$package))
{
// TODO: should check that we have not loaded it already
$filename = dirname($_SERVER["SCRIPT_FILENAME"]) . "/" . $package . ".sty";
if (file_exists($filename) and is_readable($filename))
  {
    $handle = fopen($filename,"r");
    $preamble = fread($handle, filesize($filename));
    fclose($handle);
    processLaTeX($preamble);
  }
}
return;
');

// Define \( and \) as primitives to get round spacing issues
// TODO: check for math mode to ensure well-formed syntax

$primitives["("] =
  create_function ('&$latex','
$latex = "<math xmlns=\"&mathml;\">" . $latex;
return;
');

$primitives[")"] =
  create_function ('&$latex','
$latex = "</math>" . $latex;
return;
');

$primitives["def"] = 
  create_function ('&$latex','
// slurp in stuff until we get a non-trivial token
global $defs;
list($mod,$name) = expandtok(nexttok($latex),$latex); // first argument is name of command, need to strip off brackets
$nexttok = nexttok($latex);
$pattern = "";
while(strlen($nexttok) == 1)
  {
    $pattern .= $nexttok;
    $nexttok = nexttok($latex);
  }
$defn = $nexttok;
// strip off slashes if needed
// need the four slashes as we are already inside a quoted string
$name = ltrim($name,"\\\\");
$defs[$name] = array(
                     "pattern" => $pattern,
                     "defn" => $defn
                    );
return;
');

// Main program starts here

// This is to figure out whether or not stripslashes is needed
if (array_key_exists('testslashes',$_REQUEST))
  {
    if ($_REQUEST['testslashes'] == "\test")
      {
	$source = $_REQUEST["latex"];
      }
    else
      {
	$source = stripslashes($_REQUEST["latex"]);
      }
  }
else
  {
    $source = $_REQUEST["latex"];
  }

$source = trim($source);

header("Content-type: application/xhtml+xml");

// Must be a better way of generating these lines ...
print '<?xml version="1.0"?>
<?xml-stylesheet type="text/xsl" href="http://www.w3.org/Math/XSL/mathml.xsl"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0//EN" 
               "http://www.w3.org/TR/MathML2/dtd/xhtml-math11-f.dtd" [
  <!ENTITY mathml "http://www.w3.org/1998/Math/MathML">
]>
<html xmlns="http://www.w3.org/1999/xhtml">  
  <head>
    <title>PHPLaTeX Demo Page</title> 
  </head>
  <body>

<form action="' . $_SERVER['PHP_SELF'] .'" method="post">
<p>
<textarea name="latex" rows="20" cols="50">' . $source . '</textarea>
</p>
<input type="hidden" name="testslashes" value="\test" />
<input type="submit" value="send" />
<input type="reset" />
</form>

<h3>Result:</h3>

<p>';

print processLaTeX ($source);

print '
</p>

</body>
</html>';