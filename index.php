<?php

include("latex.php");
include("math.php");
initialise();

// Main program starts here

// This is to figure out whether or not stripslashes is needed
if (array_key_exists('latex',$_REQUEST))
    {
	if (array_key_exists('testslashes',$_REQUEST))
	{
	    if ($_REQUEST['testslashes'] == '\test')
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
    }
else
{
    $source = "";
}
$source = trim($source);

?>

<!DOCTYPE html>
<html lab="en" >
    <head>
	<meta charset="utf-8">
	<title>PHPLaTeX Demo Page</title> 
    </head>
    <body>

<form action="<?php print $_SERVER['PHP_SELF'] ?>" method="post">
<p>
<textarea name="latex" rows="20" cols="50"><?php print htmlspecialchars($source) ?></textarea>
</p>
<input type="hidden" name="testslashes" value="\test" />
  <div>View Source:
<input type="checkbox" name="source" value="1" <?php if (array_key_exists('source',$_REQUEST) && $_REQUEST['source'])   print 'checked="checked"' ?> /></div>
<input type="submit" value="send" />
<input type="reset" />
</form>

<a href="<?php print dirname($_SERVER['PHP_SELF'])?>/convert.php?file=PHPLaTeX.tex">Documentation</a>

  (N.B. don't use &#92;begin{document} and &#92;end{document} here)

<h3>Result:</h3>

<p>

<?php
  $source = '\outputtrue' . "\0" . $source;
  $result = processLaTeX ($source);

  print $result;

  if (array_key_exists('source',$_REQUEST) && $_REQUEST["source"])
    {
      print '<pre>';
      print htmlspecialchars($result);
      print '</pre>';
    }
exitGracefully()
?>

</p>

</body>
</html>
