<?php
## alex at nyoc dot net
## Feel free to better for your needs

function timeago($referencedate=0, $timepointer='', $measureby='', $autotext=true){    ## Measureby can be: s, m, h, d, or y
    if($timepointer == '') $timepointer = time();
    $Raw = $timepointer-$referencedate;    ## Raw time difference
    $Clean = abs($Raw);
    $calcNum = array(array('s', 60), array('m', 60*60), array('h', 60*60*60), array('d', 60*60*60*24), array('mo', 60*60*60*24*30));    ## Used for calculating
    $calc = array('s' => array(1, 'second'), 'm' => array(60, 'minute'), 'h' => array(60*60, 'hour'), 'd' => array(60*60*24, 'day'), 'mo' => array(60*60*24*30, 'month'));    ## Used for units and determining actual differences per unit (there probably is a more efficient way to do this)

    
    if($measureby == ''){    ## Only use if nothing is referenced in the function parameters
        $usemeasure = 's';    ## Default unit
    
        for($i=0; $i<count($calcNum); $i++){    ## Loop through calcNum until we find a low enough unit
            if($Clean <= $calcNum[$i][1]){        ## Checks to see if the Raw is less than the unit, uses calcNum b/c system is based on seconds being 60
                $usemeasure = $calcNum[$i][0];    ## The if statement okayed the proposed unit, we will use this friendly key to output the time left
                $i = count($calcNum);            ## Skip all other units by maxing out the current loop position
            }        
        }
    }else{
        $usemeasure = $measureby;                ## Used if a unit is provided
    }
    
    $datedifference = floor($Clean/$calc[$usemeasure][0]);    ## Rounded date difference
    
    if($autotext==true && ($timepointer==time())){
        if($Raw < 0){
            $prospect = ' from now';
        }else{
            $prospect = ' ago';
        }
    }
    
    if($referencedate != 0){        ## Check to make sure a date in the past was supplied
        if($datedifference == 1){    ## Checks for grammar (plural/singular)
            return $datedifference . ' ' . $calc[$usemeasure][1] . $prospect;
        }else{
            return $datedifference . ' ' . $calc[$usemeasure][1]  . "s" . $prospect;
        }
    }else{
        return 'No input time referenced.';
    }
}
?>
