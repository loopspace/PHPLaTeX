// name: show
global $primitives;
global $defs;
global $commands;
$name = nextgrp($latex);
$name = ltrim($name,'\\');
print '<pre>' . htmlspecialchars($name);
if (array_key_exists($name,$primitives))
  {
    print  " is a primitive.\n";
  }
elseif (array_key_exists($name,$defs))
  {
    print " is a def:\n";
    print "pattern:    " . htmlspecialchars($defs[$name]["pattern"]);
    print "\ndefinition: " . htmlspecialchars($defs[$name]["defn"]);
  }
elseif (array_key_exists($name,$commands))
  {
    print " is a command:\n";
    print "arguments:  " . htmlspecialchars($commands[$name]["args"]);
    print "\noptionals:  " . htmlspecialchars(join("\n",$commands[$name]["opts"]));
    print "\ndefinition: " . htmlspecialchars($commands[$name]["defn"]);
  }
else
  {
    print  "is unknown";
  }
print '</pre>';
return;
