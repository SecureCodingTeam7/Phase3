<?php

class Random
{
	private $seed = 0;
	
	function _construct($seed){
		$this->seed = ($seed ^ 0x5DEECE66DL) & ((1L << 48) - 1);
	}
	

public function  nextBytes($bytes) {
   for ($i = 0; $i < count($bytes);$i++ )
     for ( $rnd = $this->nextInt(); $n = min(bytes.length - i, 4); $n-- > 0; $rnd >>= 8)
       $bytes[$i++] = $rnd;
 }
 
 public function nextInt(int n) {
   if (n <= 0) 
	die("invalid");
    

   if ((n & -n) == n)  // i.e., n is a power of 2
     return (int)((n * $this->next(31)) >> 31);

    $bits=0; 
    $val=0;
   do {
       bits = $this->next(31);
       val = bits % n;
   } while (bits - val + (n-1) < 0);
   return val;
 }
 
 public function next($bits) {
	 $this->seed = (seed * 0x5DEECE66DL + 0xB) & ((1 << 48) - 1);
	 return (seed >>> (48 - bits));

}

?>

