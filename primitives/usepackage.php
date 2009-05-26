// name: usepackage
$package = nextgrp($latex); // get package name
// check that $package is safe!
if (preg_match("/^\\w+$/s",$package))
{
// TODO: should check that we have not loaded it already
$filename = dirname($_SERVER["SCRIPT_FILENAME"]) . "/packages/" . $package . ".sty";
if (file_exists($filename) and is_readable($filename))
  {
    $handle = fopen($filename,"r");
    $preamble = fread($handle, filesize($filename));
    fclose($handle);
    processLaTeX($preamble);
  }
}
return;
