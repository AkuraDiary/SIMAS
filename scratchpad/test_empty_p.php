<?php
$html = '<tr><td><p>&nbsp; [loop:X] &nbsp;</p><p>&nbsp; Data &nbsp;</p></td><td><p>Data 2</p><p>&nbsp; [/loop:X] &nbsp;</p></td></tr>';
$loopName = 'X';

// 1. Remove paragraph wrappers that ONLY contain the loop tag (and spaces/nbsp)
$trBlockClean = preg_replace('/<p>(?:\s|&nbsp;|<br>)*\[\/?loop:' . $loopName . '\](?:\s|&nbsp;|<br>)*<\/p>/is', '', $html);

// 2. Remove the loop tag and swallow surrounding spaces/nbsp if they are inside a paragraph or text
$trBlockClean = preg_replace('/(?:\s|&nbsp;)*\[\/?loop:' . $loopName . '\](?:\s|&nbsp;)*/is', '', $trBlockClean);

echo "Clean:\n$trBlockClean\n\n";

