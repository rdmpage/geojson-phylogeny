<?php

/*

Barth, W., Mutzel, P., & Jünger, M. (2004). Simple and Efficient Bilayer Cross Counting. 
Journal of Graph Algorithms and Applications, 8(2), 179-194.
doi:10.7155/jgaa.00088

*/
// Layer is an array of edges, where each edge is a pair of integers. 
function count_crossings($layer)
{
	// 1. Sort layer lexically
	asort($layer);
	
	// 2. Get positions of "southern nodes"	
	$southsequence = array();
	foreach ($layer as $edge)
	{
		$southsequence[] = $edge[1];
	}
	
	// 3. count crossings
	
	$firstindex = 1;
	$q = count(array_unique($southsequence));
	while ($firstindex < $q)
	{
		$firstindex *= 2;
	}
	$treesize = 2 *$firstindex - 1;
	$firstindex--;
	
	$tree = array_fill(0, $treesize, 0);
	
	$crosscount = 0;
	$r = count($layer); // number of edges
	for ($k = 0; $k < $r; $k++) // insert edge k
	{
		$index = $southsequence[$k] + $firstindex;
		$tree[$index]++;
		while ($index > 0)
		{
			if ($index % 2 != 0)
			{
				$crosscount += $tree[$index + 1];
			}
			$index = floor(($index - 1)/2);
			$tree[$index]++;
		}
	}

	return $crosscount;
}

/*

$layer = array(
	array(0,1),
	array(1,2),
	array(1,0),
	array(0,2)
	);
	

$layer = array(
	array(0,0),
	array(1,1),
	array(1,2),
	array(2,0),
	array(2,3),
	array(2,4),
	array(3,0),
	array(3,2),
	array(4,3),
	array(5,2),
	array(5,4)
	);	


$crossings = count_crossings($layer);
echo "Number of crossings: $crossings\n";
*/	
?>