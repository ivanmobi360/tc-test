<?php
/**
 * tests for the website Venue module
 * @author MASTER
 *
 */
use model\Eventsmanager;
use tool\Date;
class EventsTest extends DatabaseBaseTest{
  
  
  /**
   * "check everywhere we sell tickets to see 
		  if the cancelled are counted in the capacity 
		  (ie, when we check to see if there's still tickets left we can sell, 
      cancelled tickets should not count as sold tickets"
   */
  function testCapacity(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    $this->setUserHomePhone($seller, '111');
    $box = $this->createBoxoffice('xbox', 'seller');//placeholder box for testing in box offices
    
    $evt = $this->createEvent('Return my Capacity!!1', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Adult', $evt->id, 100, 5);
    $catB = $this->createCategory('Kid', $evt->id, 50, 10);
    
    
    $foo = $this->createUser('foo');
    
    return;

    //return;
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('aaa', $catA->id, 5);
    $txn_id = $outlet->payByCash($foo);
    
    try {
      $outlet->addItem('aaa', $catA->id, 1);
      $this->fail("Should have failed");
    } catch (Exception $e) {
      Utils::log("Addition failed properly: " .$e->getMessage());
    }
    
    $this->manualCancel($txn_id);
    
    //return;
    
    try{
      $outlet->addItem('aaa', $catA->id, 5);
      $txn_id = $outlet->payByCash($foo); 
      Utils::log(__METHOD__ . " ################# ALL FINE ############ "); 
    }catch (Exception $e){
      $this->fail( "Addition failed! " .  $e->getMessage() );
      throw($e);
    }
    
    $this->manualCancel($txn_id);
    
  }

 
}