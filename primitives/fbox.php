// name: fbox
$arg = stripgrp(nextgrp($latex));
$width = getWidthOf('\(' . $arg . '\)');
$height = getHeightOf('\(' . $arg . '\)');
$depth = getDepthOf('\(' . $arg . '\)');

$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" >'
  . "\n"
  . '<rect x="0" y="0" width="'
  . $width
  . 'ex" height="'
  . ($height + $depth)
  . 'ex" stroke-width="1" stroke="black" fill="none" />'
  . "\n"
  . '<foreignObject x="0" y="0" width="'
  . $width
  . 'ex" height="'
  . ($height + $depth)
  . 'ex">'
  . "\n"
  . '<body xmlns="http://www.w3.org/1999/xhtml"><div align="center">'
  . '\('
  . '\rule{0ex}{'
  . $height
  . 'ex}'
  . $arg
  . '\)'
  . '</div></body>'
  . '</foreignObject>'
  . '</svg>';

$latex = $svg . "\0" . $latex;
return;
