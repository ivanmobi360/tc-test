<?php


 
use reports\ReportLib;
class TourBuilderTest extends \DatabaseBaseTest{
  
  function testOutletSetup(){
    $this->clearAll();
    $seller = $this->createUser('seller');
    
    $this->assertEquals(0, bindec('0001') & bindec('0010') );
    $this->assertEquals(0, 1 & bindec('0010') );
    $this->assertEquals(2, 2 & bindec('0010') );
    $this->assertEquals(0, 4 & bindec('0010') );
    
    //move this to a tour builder test
    $builder = new TourBuilder($this, $seller);
    $builder->outlets = '0001';
    $res = $builder->getOutletSetup();
    $this->assertEquals('on', $res['outlet_1']);
    $this->assertFalse(isset($res['outlet_2']));
    $this->assertFalse(isset($res['outlet_4']));
    $this->assertFalse(isset($res['outlet_8']));
    
    
    $builder->outlets = '1110';
    $res = $builder->getOutletSetup();
    $this->assertFalse(isset($res['outlet_1']));
    $this->assertEquals('on', $res['outlet_2']);
    $this->assertEquals('on', $res['outlet_4']);
    $this->assertEquals('on', $res['outlet_8']);
    
    
    
    
  }
  
  
  
  
  
 
  
 

  
}