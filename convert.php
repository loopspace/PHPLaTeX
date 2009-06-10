<?php

include("latex.php");
initialise();

// Main program starts here

if (array_key_exists("file",$_REQUEST))
  {
    $file = dirname($_SERVER["SCRIPT_FILENAME"]) ."/doc/" . basename($_REQUEST["file"]);
    if (is_file($file) and is_readable($file))
      {
	$source = file_get_contents($file);
      }
  }
else
  {
    $source = $_REQUEST["latex"];
  }

// This is to figure out whether or not stripslashes is needed
if (array_key_exists('testslashes',$_REQUEST))
  {
    if ($_REQUEST['testslashes'] != '\test')
      {
	$source = stripslashes($source);
      }
  }

$source = trim($source);

if ($source)
  {
    header("Content-type: application/xhtml+xml");
  }
else
  {
    header("Location: " . dirname($_SERVER["PHP_SELF"]) . "/convert.html");
  }

print processLaTeX ($source);
?>