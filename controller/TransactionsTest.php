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
  
  //We'll use this test to inspect the search process
  function testSearch(){
      $this->clearAll();
      
      $this->db->beginTransaction();
      //create buyer
      $user = $this->createUser('foo');
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      $bo_id = $this->createBoxoffice('xbox', $seller->id);
      $rsv1 = $this->createReservationUser('tixpro', $v1);
      
      
      //Tour has_ccfee=0
      $build = new TourBuilder( $this, $seller);
      $build->name = $build->name . ' (No ccfees)';
      $build->event_id = 'pizza';
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      $this->setEventParams($build->event_id, array('has_ccfee' => 0));

      
      //Event no ccfee
      $evt = $this->createEvent('Swiming competition (No ccfees)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catX = $this->createCategory('RAGE ON', $evt->id, 100);
      
      $txn_id = $this->buyTickets('foo', 'aaa', $catX->id, 2); //buy a normal event
      $tid = $this->db->get_one("SELECT id FROM ticket_transaction WHERE txn_id=? LIMIT 1", $txn_id);
      $code = $this->db->get_one("SELECT code FROM ticket LIMIT 1");
      $this->db->commit();
      
      //let's run a search
      $this->assertFound(1, 'name', 'foo');
      $this->assertFound(1, 'name', 'foo', 'aaa');
      $this->assertFound(0, 'name', 'foo', 'pizza');
      
      $this->assertFound(1, 'txn', $tid);
      $this->assertFound(1, 'txn', $tid, 'aaa');
      $this->assertFound(0, 'txn', $tid, 'pizza');
      
      $this->assertFound(1, 'code', $code);
      $this->assertFound(1, 'code', $code, 'aaa');
      $this->assertFound(0, 'code', $code, 'pizza');
      
      
  }
  
  function assertFound($total, $selector, $search, $event_id=false){
      //let's run a search
      \Utils::clearLog();
      $res = \model\Transactions::search($selector, $search, $event_id);
      \Utils::log(__METHOD__ . " ". print_r($res, true));
      $this->assertEquals($total, count($res));
  }
  
 
}


