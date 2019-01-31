<?php
function random($len){
    if (@is_readable('/dev/urandom')) { 
        $f=fopen('/dev/urandom', 'r'); 
        $urandom=fread($f, $len); 
        fclose($f); 
    } 

    $return=''; 
    for ($i=0;$i<$len;++$i) { 
        if (!isset($urandom)) { 
            if ($i%2==0) mt_srand(time()%2147 * 1000000 + (double)microtime() * 1000000); 
            $rand=48+mt_rand()%64; 
        } else $rand=48+ord($urandom[$i])%64; 

        if ($rand>57) 
            $rand+=7; 
        if ($rand>90) 
            $rand+=6; 

        if ($rand==123) $rand=45; 
        if ($rand==124) $rand=46; 
        $return.=chr($rand); 
    }
    return $return; 
}