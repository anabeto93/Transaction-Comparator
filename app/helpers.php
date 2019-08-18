<?php

if (!function_exists('diff_in_arrays')) {
    /**
     * Paul's Simple Diff Algorithm v 0.1
     * (C) Paul Butler 2007 <http://www.paulbutler.org/>
     * No need reinventing the wheel here, slight modifications to work here
    */
    function diff_in_arrays($old, $new){
        $matrix = array();
        $maxlen = 0;
        foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex){
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if($matrix[$oindex][$nindex] > $maxlen){
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
        return array_merge(
            diff_in_arrays(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            diff_in_arrays(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
    }
}