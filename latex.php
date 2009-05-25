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



newcommand("emph",1,array(),"<i>#1</i>");
newcommand("textbf",1,array(),"<strong>#1</strong>");
newcommand("\"",1,array(),"&#1uml;");
newcommand('`',1, array(),"&#1grave;");
newcommand("'",1, array(),"&#1acute;");
newcommand('^',1, array(),"&#1circ;");
newcommand('~',1, array(),"&#1tilde;");
newcommand('c',1, array(),"&#1cedil;");
newcommand('(',0, array(),"<span class=\"math\">");
newcommand(')',0, array(),"</span>");
newcommand("mathbb",1, array(), "&#1opf;");

newcommand("ae",0,array(),"&aelig;");
newcommand("AE",0,array(),"&AElig;");
newcommand("oe",0,array(),"&oelig;");
newcommand("OE",0,array(),"&OElig;");
newcommand("aa",0,array(),"&aring;");
newcommand("AA",0,array(),"&Aring;");
newcommand("o",0,array(),"&oslash;");
newcommand("O",0,array(),"&Oslash;");
newcommand("ss",0,array(),"&szlig;");
newcommand("dag",0,array(),"&dagger;");
newcommand("ddag",0,array(),"&Dagger;");
newcommand("S",0,array(),"&sect;");
newcommand("P",0,array(),"&para;");
newcommand("copyright",0,array(),"&copy;");
newcommand("pounds",0,array(),"&pound;");
newcommand("aleph",0,array(), "&alefsym;");
// newcommand("hbar",0,array(), "&;");
// newcommand("imath",0,array(), "&;");
// newcommand("jmath",0,array(), "&;");
// newcommand("ell",0,array(), "&;");
newcommand("wp",0,array(), "&weierp;");
newcommand("Re",0,array(), "&real;");
newcommand("Im",0,array(), "&image;");
newcommand("surd",0,array(), "&radic;");
newcommand("angle",0,array(), "&ang;");
// newcommand("backslash",0,array(), "&;");
newcommand("partial",0,array(), "&part;");
newcommand("infty",0,array(), "&infin;");
// newcommand("triangle",0,array(), "&;");
// newcommand("Box",0,array(), "&;");
// newcommand("Diamond",0,array(), "&;");
// newcommand("flat",0,array(), "&;");
// newcommand("natural",0,array(), "&;");
// newcommand("sharp",0,array(), "&;");
newcommand("clubsuit",0,array(), "&clubs;");
newcommand("diamondsuit",0,array(), "&diams;");
newcommand("heartsuit",0,array(), "&hearts;");
newcommand("spadesuit",0,array(), "&spades;");
newcommand("cdot",0,array(), "&sdot;");
newcommand("vartheta",0,array(), "&thetasym;");
newcommand("varpi",0,array(), "&piv;");
newcommand("dots",0,array(), "&hellip;");
newcommand("in",0,array(), "&isin;");
newcommand("to",0,array(),"&rarr;");
newcommand("approx",0,array(),"&asymp;");
newcommand("propto",0,array(),"&prop;");
newcommand("neq",0,array(),"&ne;");
newcommand("neg",0,array(),"&not;");
newcommand("wedge",0,array(),"&and;");
newcommand("vee",0,array(),"&or;");
newcommand("supset",0,array(),"&sup;");
newcommand("subset",0,array(),"&sub;");
newcommand("emptyset",0,array(),"&empty;");
newcommand("pm",0,array(),"&plusm;");
newcommand("implies",0,array(),"&rArr;");

newcommand("prime",0,array(),"&prime;");
newcommand("nabla",0,array(),"&nabla;");
newcommand("forall",0,array(),"&forall;");
newcommand("exists",0,array(),"&exists;");
newcommand("times",0,array(),"&times;");
newcommand("notin",0,array(),"&notin;");
newcommand("ni",0,array(),"&ni;");
newcommand("prod",0,array(),"&prod;");
newcommand("sum",0,array(),"&sum;");
newcommand("ast",0,array(),"&ast;");
newcommand("equiv",0,array(),"&equiv;");
newcommand("sim",0,array(),"&sim;");
newcommand("oplus",0,array(),"&oplus;");
newcommand("cap",0,array(),"&cap;");
newcommand("cup",0,array(),"&cup;");
newcommand("rfloor",0,array(),"&rfloor;");
newcommand("euro",0,array(),"&euro;");
newcommand("int",0,array(),"&int;");
newcommand("cong",0,array(),"&cong;");
newcommand("ne",0,array(),"&ne;");
newcommand("le",0,array(),"&le;");
newcommand("ge",0,array(),"&ge;");
newcommand("otimes",0,array(),"&otimes;");
newcommand("perp",0,array(),"&perp;");
newcommand("alpha",0,array(),"&alpha;");
newcommand("beta",0,array(),"&beta;");
newcommand("gamma",0,array(),"&gamma;");
newcommand("delta",0,array(),"&delta;");
newcommand("epsilon",0,array(),"&epsilon;");
newcommand("zeta",0,array(),"&zeta;");
newcommand("eta",0,array(),"&eta;");
newcommand("theta",0,array(),"&theta;");
newcommand("iota",0,array(),"&iota;");
newcommand("kappa",0,array(),"&kappa;");
newcommand("lambda",0,array(),"&lambda;");
newcommand("mu",0,array(),"&mu;");
newcommand("nu",0,array(),"&nu;");
newcommand("xi",0,array(),"&xi;");
newcommand("omicron",0,array(),"&omicron;");
newcommand("pi",0,array(),"&pi;");
newcommand("rho",0,array(),"&rho;");
newcommand("sigma",0,array(),"&sigma;");
newcommand("tau",0,array(),"&tau;");
newcommand("upsilon",0,array(),"&upsilon;");
newcommand("phi",0,array(),"&phi;");
newcommand("chi",0,array(),"&chi;");
newcommand("psi",0,array(),"&psi;");
newcommand("omega",0,array(),"&omega;");

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


