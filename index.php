<?php

include("latex.php");
initialise();

// Main program starts here

// This is to figure out whether or not stripslashes is needed
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

$source = trim($source);

header("Content-type: application/xhtml+xml");

// Must be a better way of generating these lines ...
print '<?xml version="1.0"?>
<?xml-stylesheet type="text/xsl" href="http://www.w3.org/Math/XSL/mathml.xsl"?>'
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN" "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg-flat.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml">  
  <head>
    <title>PHPLaTeX Demo Page</title> 
  </head>
  <body>

<form action="<?php print $_SERVER['PHP_SELF'] ?>" method="post">
<p>
<textarea name="latex" rows="20" cols="50"><?php print htmlspecialchars($source) ?></textarea>
</p>
<input type="hidden" name="testslashes" value="\test" />
<input type="submit" value="send" />
<input type="reset" />
</form>

<h3>Result:</h3>

<p>

<?php print processLaTeX ($source) ?>


</p>

</body>
</html>