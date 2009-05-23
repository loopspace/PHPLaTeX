<?php

function nexttok ($latex)
{

   if (preg_match('/^(.)(.*)/s', $latex, $matches))
   {

   $firstchar = $matches[1];
   $rest = $matches[2];

   if ($firstchar == "\\")
   {
   preg_match('/^(.)(.*)/s',$rest,$matches);

   $secondchar = $matches[1];
   $rest = $matches[2];

     if (preg_match('/[A-z]/',$secondchar))
     {
       preg_match('/^([a-z]*)(.*)/s',$rest, $matches);

       return array($firstchar . $secondchar . $matches[1], $matches[2]);
     }
     else
     {
       return array($firstchar . $secondchar, $rest);
     }
   }
   elseif ($firstchar == '{')
   {
     $token = $firstchar;
     $c = 1;
     while($c > 0)
     {
       preg_match('/^(.)(.*)/s',$rest,$matches);

       $token = $token . $matches[1];
       $rest = $matches[2];
       $test = str_replace(array('\{','\}'),'',$token);
       
       $c = substr_count($test, '{') - substr_count($test,'}');

     }
     return array($token, $rest);
   }
   else
   {
     return array($firstchar, $rest);
   }
}
else
{
return array('','');
}
}

?>

<form action="http://localhost/~astacey/latex.php" method="post">
<p>
<textarea name="latex" rows="20" cols="50">
<?php print stripslashes($_REQUEST["latex"]) ?>
</textarea>
</p>
<input type="submit" value="send" />
<input type="reset" />
</form>

<?php

$source = stripslashes($_REQUEST["latex"]);

while ($source)
   {
   $next = nexttok($source);
   print $next[0] . ":";
   $source = $next[1];
}

?>
