<?php
$html = '
<table>
    <tbody>
        <tr>
            <td><p>[loop:daftar] {{ nama }}</p></td>
            <td><p>{{ divisi }} [/loop:daftar]</p></td>
        </tr>
    </tbody>
</table>
<p>[loop:other] {{ item }} [/loop:other]</p>
';

// Find loops that cross cell boundaries
if (preg_match_all('/\[loop:([a-zA-Z0-9_]+)\](.*?)\[\/loop:\1\]/is', $html, $matches, PREG_OFFSET_CAPTURE)) {
    foreach ($matches[0] as $idx => $matchData) {
        $fullMatch = $matchData[0];
        $startPos = $matchData[1];
        $innerContent = $matches[2][$idx][0];
        $loopName = $matches[1][$idx][0];
        
        // Check if the loop crosses cell boundaries
        if (stripos($innerContent, '</td>') !== false) {
            echo "Loop $loopName crosses cells!\n";
            // Find the start of the TR containing [loop:X]
            $trStart = strrpos(substr($html, 0, $startPos), '<tr');
            // Find the end of the TR containing [/loop:X]
            $endPos = $startPos + strlen($fullMatch);
            $trEnd = stripos($html, '</tr>', $endPos);
            
            if ($trStart !== false && $trEnd !== false) {
                // We need to move the loop tags outside the TR.
                // But wait, the original tags are still inside! We need to strip them from inside,
                // and wrap the TR with them.
                echo "TR Start: $trStart, TR End: $trEnd\n";
            }
        }
    }
}
