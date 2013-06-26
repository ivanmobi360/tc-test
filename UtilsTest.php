<?php

class UtilsTest extends BaseTest{
  
  
  public function testWalk(){
    $x = 'A';
    
    for($i = 'A'; $i!='ZZ'; $i++ ){
      //echo $i . "\n"; //simple incrementor confirmed to work
    }
    
    $col = 'C';
    //only pre increments worked
    $this->assertEquals('D', ++$col);
    $this->assertEquals('E', ++$col);
    
    //all these failed
    $col = 'C';
    //$this->assertEquals('D', $col+1);
    //$this->assertEquals('D', ($col+1));
    //$this->assertEquals('B', --$col);
    
  }
  

  
  
}


