<?php
function normalizeTableLoops($html) {
    if (preg_match_all('/\[loop:([a-zA-Z0-9_]+)\].*?\[\/loop:\1\]/is', $html, $matches, PREG_OFFSET_CAPTURE)) {
        // Process from end to start to avoid offset shifting issues
        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
            $fullMatch = $matches[0][$i][0];
            $startPos = $matches[0][$i][1];
            $loopName = $matches[1][$i][0];
            
            // Check if it crosses cell boundaries
            if (stripos($fullMatch, '</td>') !== false) {
                // Find TR start before the loop
                $trStart = strrpos(substr($html, 0, $startPos), '<tr');
                
                // Find TR end after the loop
                $endPos = $startPos + strlen($fullMatch);
                $trEndPos = stripos($html, '</tr>', $endPos);
                
                if ($trStart !== false && $trEndPos !== false) {
                    $trEnd = $trEndPos + 5; // include </tr>
                    
                    // Extract the whole TR block
                    $trBlock = substr($html, $trStart, $trEnd - $trStart);
                    
                    // Remove the loop tags from INSIDE the TR block
                    $trBlockClean = preg_replace('/\[\/?loop:' . $loopName . '\]/is', '', $trBlock);
                    
                    // Wrap the clean TR block with the loop tags
                    $newBlock = "[loop:$loopName]\n$trBlockClean\n[/loop:$loopName]";
                    
                    // Replace in original HTML
                    $html = substr_replace($html, $newBlock, $trStart, $trEnd - $trStart);
                }
            }
        }
    }
    return $html;
}

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

echo normalizeTableLoops($html);
