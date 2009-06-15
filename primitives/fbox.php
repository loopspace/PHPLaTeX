// name: fbox
$arg = stripgrp(nextgrp($latex));
$width = getWidthOf('\(' . $arg . '\)');
$height = getHeightOf('\(' . $arg . '\)');
$depth = getDepthOf('\(' . $arg . '\)');

$boxheight = max($height,2);

LaTeXdebug('Width: ' . $width . ' Height: ' . $height . ' Depth: ' . $depth,1);

$svgwidth = ($width + 1);
$svgheight = ($height + 1);

$svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="'
  . $svgwidth
  . 'ex" height="'
  . $svgheight
  . 'ex">'
  . "\n"
  . '<rect x="0" y="0" width="'
  . $width
  . 'ex" height="'
  . ($boxheight + $depth)
  . 'ex" stroke-width="1" stroke="black" fill="none" />'
  . "\n"
  . '<foreignObject x="0" y="0" width="'
  . $width
  . 'ex" height="'
  . ($boxheight + $depth)
  . 'ex">'
  . "\n"
  . '<body xmlns="http://www.w3.org/1999/xhtml" style="border-width: 0pt; margin: 0pt; padding: 0pt;">'
  . '<div align="center">'
  . '\('
  . '\rule{.2ex}{'
  . $height
  . 'ex}'
  . $arg
  . '\)'
  . '</div>'
  . '</body>'
  . '</foreignObject>'
  . '</svg>';

$latex = $svg . "\0" . $latex;
return;
