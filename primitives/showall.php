// name: showall
global $primitives;
global $defs;
global $commands;
$thelot = '<pre>';
$thelot .= 'Primitives:' . "\n";
foreach ($primitives as $name => $defn)
{
  $thelot .= htmlspecialchars($name) . "\n" . htmlspecialchars($defn) . "\n";
}
$thelot .= 'Defs:' . "\n";
foreach ($defs as $name => $def)
{
  $thelot .= htmlspecialchars($name)
    . "\n"
    . htmlspecialchars($def["pattern"])
    . "\n"
    . htmlspecialchars($def["defn"])
    . "\n";
}
$thelot .= 'Commands:' . "\n";
foreach ($commands as $name => $cmd)
{
  $thelot .= htmlspecialchars($name)
    . "\n"
    . htmlspecialchars($cmd["args"])
    . "\n"
    . htmlspecialchars(join("\n",$cmd["opts"]))
    . "\n"
    . htmlspecialchars($cnd["defn"]);
}
$thelot .= '</pre>';
$latex = $thelot . "\0" . $latex;
return;
