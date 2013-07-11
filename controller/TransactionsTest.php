<?php
/**
 * We'll use this fixture for some testing of the admin360 transactions screens, apparently used to void individual tickets.
 * "All tickets that are cancelled = 1 should not be counted 
 * in any of the current reports the promoter can see in their Administration Reports (not Admin360)."
 */

class TransactionsTest extends DatabaseBaseTest{
  
    function testVoidOne(){
  
      $this->clearAll();
  
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $v1);
      $this->setEventParams($evt->id, array('has_tax'=>0));
      $catA = $this->createCategory('Adult', $evt->id, 100);
  
      $foo = $this->createUser('foo');
       
      $outlet = new OutletModule($this->db, 'outlet1');
      $outlet->addItem('aaa', $catA->id, 2);
      //$outlet->payByCash($foo);
      $outlet->payByCC($foo, $this->getCCData());
  
      //void one
      $this->voidTicket($this->db->get_one("SELECT id FROM ticket LIMIT 1"));
      
  }
  
  function testVoidLine(){
  
      $this->clearAll();
  
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $v1);
      $this->setEventParams($evt->id, array('has_tax'=>0));
      $catA = $this->createCategory('Adult', $evt->id, 100);
      $catB = $this->createCategory('Kid', $evt->id, 50);
  
      $foo = $this->createUser('foo');
       
      $outlet = new OutletModule($this->db, 'outlet1');
      $outlet->addItem('aaa', $catA->id, 2);
      //$outlet->addItem('aaa', $catB->id, 1);
      $txn_id = $outlet->payByCC($foo, $this->getCCData());
  
      //void Line
      $this->voidLine($this->db->get_one("SELECT id FROM ticket_transaction LIMIT 1"));
  
  }
  
  function testVoidTransaction(){
  
      $this->clearAll();
  
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $v1);
      $this->setEventParams($evt->id, array('has_tax'=>0));
      $catA = $this->createCategory('Adult', $evt->id, 100);
      $catB = $this->createCategory('Kid', $evt->id, 50);
  
      $foo = $this->createUser('foo');
       
      $outlet = new OutletModule($this->db, 'outlet1');
      $outlet->addItem('aaa', $catA->id, 1);
      $outlet->addItem('aaa', $catB->id, 1);
      //$outlet->payByCash($foo);
      $txn_id = $outlet->payByCC($foo, $this->getCCData());
  
      //return; 
      
      //void transaction
      $this->voidTransaction($txn_id);
      
      $this->assertEquals(0, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE cancelled=0"));
      $this->assertEquals(0, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE cancelled=0"));
      
      return;
      
      //unvoid 
      $this->unvoidTransaction($txn_id);
      $this->assertEquals(2, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE cancelled=0"));
      $this->assertEquals(2, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE cancelled=0"));
  
  }
  
 
}


