<?php

require_once 'data/AttrColors.php';

$custom = [];
foreach ($attrColors AS $colorArray)
{
    $colorName = $colorArray[1];
    $attrCode  = $colorArray[0];

    $colorTmpArray = explode('/', $colorName);
    if (2 <= count($colorTmpArray))
    {
        $colorTmpArray = array_map(function($word)
        {
            return ucwords(strtolower($word));
        }, $colorTmpArray);

        $colorName = implode(' and ', $colorTmpArray);
    } else {
        $colorName = ucwords(strtolower($colorName));
    }

    $colorTmpArray = explode(' ', $colorName);

    foreach ($colorTmpArray AS &$word)
    {
        if (2 == strlen($word))
        {
            $word = strtoupper($word);
        }

        if (in_array(strtolower($word), ['mlti', 'mlt', 'multi', 'mult']))
        {
            $word = 'Multicolor';
        }

        if (in_array(strtolower($word), ['grn', 'gn']))
        {
            $word = 'Green';
        }

        if ('sge' == strtolower($word))
        {
            $word = 'Sage';
        }

        if ('turq' == strtolower($word))
        {
            $word = 'Turquoise';
        }

        if (in_array(strtolower($word), ['edmnd', 'edmnds']))
        {
            $word = 'Edmonds';
        }
    }

    $colorName = implode(' ', $colorTmpArray);
    $custom[$attrCode] = $colorName;
}

$attrColors = $custom;