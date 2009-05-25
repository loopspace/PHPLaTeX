<?php

$commands = array();
$primitives = array();

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
  else
    {
      // no expansion to be done
      // TODO: add support for ^ and _ in math mode
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

function newcommand ($name,$args,$opts,$defn)
{
  global $commands;
  // strip off slashes, just in case
  $name=ltrim($name,"\\");
  $commands[$name] = array(
			   "args" => $args,
			   "opts" => $opts,
			   "defn" => $defn
			   );
}

// commands

$primitives["newcommand"] = 
  create_function ('& $latex','
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
	$optarray = array($opt);
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
newcommand($name,$num,$optarray,$defn);
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

// Main program starts here

// TODO: Is there a way to figure out whether or not stripslashes is needed?
// $source = stripslashes($_REQUEST["latex"]);
$source = $_REQUEST["latex"];

?>

<form action="<?php print $_SERVER['PHP_SELF'] ?>" method="post">
<p>
<textarea name="latex" rows="20" cols="50">
<?php print $source ?>
</textarea>
</p>
<input type="submit" value="send" />
<input type="reset" />
</form>

<?php

  print processLaTeX ($source);

?>


