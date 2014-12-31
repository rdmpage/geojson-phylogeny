<?php

// http://docstore.mik.ua/orelly/webprog/pcook/ch04_26.htm
function pc_next_permutation($p) {
	$size = count($p) - 1;


    // slide down the array looking for where we're smaller than the next guy
    for ($i = $size - 1; $p[$i] >= $p[$i+1]; --$i) { }

    // if this doesn't occur, we've finished our permutations
    // the array is reversed: (1, 2, 3, 4) => (4, 3, 2, 1)
    if ($i == -1) { return false; }

    // slide down the array looking for a bigger number than what we found before
    for ($j = $size; $p[$j] <= $p[$i]; --$j) { }

    // swap them
    $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;

    // now reverse the elements in between by swapping the ends
    for (++$i, $j = $size; $i < $j; ++$i, --$j) {
         $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
    }

    return $p;
}

/*
$p = range(0,4);

while ($p)
{
	echo '[' . join(',', $p) . ']' . "\n";
	$p = pc_next_permutation($p);
}
*/

?>