<?php


class PosTest extends DatabaseBaseTest{
  
  protected function fixture(){
    //let's create some events
    $this->clearAll();
    
    $this->createUser('foo');


    $this->seller = $this->createUser('seller');
    $loc = $this->createLocation();
    //$loc->
    $evt = $this->createEvent('Barcelona vs Real Madrid', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'aaaaaaaa');
    $this->catA = $this->createCategory('Category A', $evt->id, 25.00, 1);
    $this->catB = $this->createCategory('Category B', $evt->id, 10.00);
    $this->catC = $this->createCategory('Category C', $evt->id, 5.00);
    
    
    $loc = $this->createLocation();
    $evt = $this->createEvent('Water March', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'bbbbbbbb');
    $this->createCategory('Zamora Branch', $evt->id, 14.00);
    
    $evt = $this->createEvent('Third Event', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'ccc');
    $this->createCategory('Heaven', $evt->id, 22.50);
    $this->createCategory('Limbo', $evt->id, 22.50);
    
    
    $this->seller = $this->createUser('seller2');
    $loc = $this->createLocation();
    $evt = $this->createEvent('Transformers Con', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'tttttttt');
    $this->createCategory('Autobots', $evt->id, 55.00);
  }
  
  public function testCreate(){
    $this->fixture();
    
    //now let's operate the cart
    Utils::clearLog();
    $merch = new WebUser($this->db);
    $merch->login($this->seller->username);
    
    $this->assertEquals(0, $merch->getCart()->size());
    $merch->posAddItem($this->catB->id);
    $this->assertEquals(1, $merch->getCart()->size());

    
    $merch->posPay();
    
    //one john doe transaction
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE user_id=? AND delivery_method=?", array(JOHN_DOE_ID, \model\DeliveryMethod::POS_CASH  )));
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE user_id=? AND paid=1 AND used=1", array(JOHN_DOE_ID )));
    //$this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket"));
    
    
  }
  
  public function testCartActions(){
    $this->fixture();
    
    //now let's operate the cart
    Utils::clearLog();
    $merch = new WebUser($this->db);
    $merch->login($this->seller->username);
    
    $this->assertFalse($merch->getCart()->getCurrency());
    $this->assertEquals(0, $merch->getCart()->size());
    
    
    $merch->posAddItem($this->catB->id);
    $this->assertEquals(1, $merch->getCart()->size());
    
    $this->assertEquals('CAD', $merch->getCart()->getCurrency());
    
    Utils::clearLog();
    $merch->posAddItem($this->catB->id, -1);
    $this->assertEquals(0, $merch->getCart()->size());
    
    $this->assertEquals(0.00, $merch->getCart()->getTotal());
    $this->assertFalse($merch->getCart()->getCurrency());
    
    //return;
    
    $merch->posAddItem($this->catB->id);
    $this->assertEquals(1, $merch->getCart()->size());
    
    $merch->posAddItem($this->catA->id);
    $this->assertEquals(2, $merch->getCart()->size());
    $this->assertEquals('CAD', $merch->getCart()->getCurrency());
    
    Utils::clearLog();
    $merch->posAddItem($this->catB->id, -1);
    $this->assertEquals(1, $merch->getCart()->size());
    
    Utils::clearLog();
    //add again exhausted cart
    $merch->posAddItem($this->catA->id);
    $this->assertEquals(1, $merch->getCart()->size());
  }
  
  public function testQtyBug(){
    $this->fixture();
    
    //now let's operate the cart
    Utils::clearLog();
    $merch = new WebUser($this->db);
    $merch->login($this->seller->username);
    
    $this->assertEquals(0, $merch->getCart()->size());
    $merch->posAddItem($this->catA->id);
    $this->assertEquals(1, $merch->getCart()->size());

    
    $merch->posPay();
    
    //one transaction
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction"));
    
    // ****** supossing the cart is clear *****
    $this->assertEquals(0, $merch->getCart()->size());
    $merch->posAddItem($this->catA->id);
    $this->assertEquals(0, $merch->getCart()->size());
    //Utils::log(print_r($_SESSION, true));
    //Utils::clearLog();
    //return;
    
    $merch->posAddItem($this->catB->id);
    $this->assertEquals(1, $merch->getCart()->size());
    Utils::clearLog();
    $merch->posPay();
    $this->assertEquals(2, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction"));
    
  }
  
  //category with capacity 1
  //it must allow to add only one row 
  public function test_add_one_max(){
    $this->fixture();
    
    Utils::clearLog();
    $merch = new WebUser($this->db);
    $merch->login($this->seller->username);
    
    $this->assertEquals(0, $merch->getCart()->size());
    $merch->posAddItem($this->catA->id);
    $this->assertEquals(1, $merch->getCart()->size());
    $merch->posAddItem($this->catA->id);
    $this->assertEquals(1, $merch->getCart()->size());
    
    //besides calling ajax/Event, and placing some cart->load(), the fix was make overbooking=0 when creating the category (it was 1) - Ivan
    
  }
  
 
}
