<?php

$dictionary = 'swf_dictionary.txt';

if (! is_file($dictionary))
    die("Dictionary file not found: $dictionary\n");

$starttime = microtime(true);

// board
$board = <<<END
NHDE
CMLF
TAIE
ANRS
END;

// board size
define('BOARD_W', 4);
define('BOARD_H', 4);

// normalize board
$board = preg_replace('/[^A-Z]/', '', strtoupper($board));
print implode("\n", str_split($board, 4))."\n";

// build lookup tables
$letters = array();
$neighbors = array();
foreach (str_split($board) as $position=>$letter)
{
    $letters[$letter][] = $position;
    $neighbors[$position] = neighbors($position);
}

// do search
$words_found = array();
foreach (file($dictionary) as $word)
{
    list($word) = explode("\t", strtoupper(trim($word)));

    // TODO optimize if first 2-3 char not found continue

    foreach ((array)@$letters[$word{0}] as $start)
    {
        $path = array($start=>$word[0]);
        $path = search(substr($word,1), $start, $path);
        if ($path)
        {
            print "$word " . implode(",",array_keys($path)) . "\n";
            $words_found[$word] = $path;
            continue 2;
        }
    }
}


printf("WORDS FOUND: %d\nEXECUTION TIME: %s\n", count($words_found), microtime(true)-$starttime);

/**
 * @param $remainder string
 * @param $position int
 * @param $path array
 * @return array|void
 */
function search($remainder, $position, $path)
{
    global $neighbors;

    $next = @substr($remainder,0,1);
    $remainder = @substr($remainder,1);

    // next letter is not a neighbor, path is invalid
    if (! isset($neighbors[$position][$next]))
    {
        return array();
    }

    foreach ((array)$neighbors[$position][$next] as $nposition)
    {
        // next letter is already in path, can reuse
        if (isset($path[$nposition]))
            continue;

        // search neighbor tree for remainder
        $tpath = $path;
        $tpath[$nposition] = $next;
        if (! $remainder)
            return $tpath;
        $tpath = search($remainder, $nposition, $tpath);
        if ($tpath)
            return $tpath;
    }

    return array();
}

/**
 * @param $position
 * @return array
 */
function neighbors($position)
{
    // converty position to xy
    $y = floor($position/BOARD_W);
    $x = $position - (BOARD_W*$y);
    $neighbors = array();

    // circle around (skipping self and record neighboring letters and their positions)
    for ($cy=max(0,$y-1); $cy<=min($y+1,BOARD_H-1); ++$cy)
    {
        for ($cx=max(0,$x-1); $cx<=min($x+1,BOARD_W-1); ++$cx)
        {
            $loc = $cx + ($cy*BOARD_W);

            if ($loc==$position)
                continue;

            $neighbors[$GLOBALS['board']{$loc}][] = $loc;
        }
    }

    return $neighbors;
}

