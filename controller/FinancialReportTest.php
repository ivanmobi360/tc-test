<?php

use reports\ReportLib;
class FinancialReportTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    

    //Events
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('First', $seller->id, $this->createLocation()->id, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $this->setEventId( $evt, 'aaa');
    $catA = $this->createCategory('CatA', $evt->id, 10.00, 500, 0, array('tax_inc'=>1, 'cc_fee_id'=>11));
    $catB = $this->createCategory('CatB', $evt->id, 4.00);
    
    
    $evt2 = $this->createEvent('Check THEM', $seller->id, $this->createLocation()->id);
    $this->setEventId( $evt, 'bbb');
    $catX = $this->createCategory('CatA', $evt2->id, 100.00, 300);
    
    
    Utils::clearLog();
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $evt->id,  $catA->id, 5);
    
    
    $foo = $this->createUser('bar');
    $this->buyTickets($foo->id, $evt->id, $catA->id, 1);
    
    $foo = $this->createUser('baz');
    $this->buyTickets($foo->id, $evt->id, $catB->id, 1);
    
    $this->db->beginTransaction();
    for ($i=1; $i<=60; $i++){
      $this->buyTickets($foo->id, $evt2->id, $catX->id, rand(1,5));  
    }
    $this->db->commit();
    

  }
  
  function xtestHST(){
    $this->clearAll();
    
    
    
  }
 
}


