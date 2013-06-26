<?php


use model\DeliveryMethod;
use reports\ReportLib;
class GateCheckInReportTest extends DatabaseBaseTest{
  
  //fixture to create activity for report
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();

    //Events
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    $evt = $this->createEvent('First', $seller->id, $loc->id, '2012-01-01', '9:00', '2012-01-05', '18:00' );
    $catA = $this->createCategory('VIP', $evt->id, 4.50, 500);
    $catB = $this->createCategory('General Admission', $evt->id, 14.99);
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Second', $seller->id, $loc->id, '2012-02-01', '9:00', '2012-02-05', '18:00' );
    $cat2 = $this->createCategory('Ferrari', $evt->id, 15.00);
    
    $seller = $this->createUser('seller3');
    $evt = $this->createEvent('Third', $seller->id, $loc->id, '2012-03-01', '9:00', '2012-03-05', '18:00' );
    $cat3 = $this->createCategory('Uva', $evt->id, 20.00, 500);
    
    
    $foo = $this->createUser('foo');
    $this->buyTickets($foo->id, $catA->id, 10);
    
    //Flag last 5 as used
    $this->flagTickets(5);
    
    //$this->assertEquals(5, $this->db->get_one("SELECT COUNT(id) FROM ticket WHERE used=1"));
    
    /*$client = new WebUser($this->db);
    $client->login($foo->username);*/

    $this->buyTickets($foo->id, $catB->id, 7);
    $this->flagTickets(3);
    
    
    
    
    //some other seller ticket activity
    $this->buyTickets($foo->id, $cat2->id, 4);
    $this->flagTickets(3);
    
    //flag last transaction as PayAtdoor
    $id = $this->db->get_one("SELECT id FROM ticket_transaction ORDER BY id DESC");
    $this->db->update('ticket_transaction', array('delivery_method' => DeliveryMethod::PAY_AT_THE_DOOR) , "id=?", $id);
     //$this->flagTickets(3, array('delivery_method' => DeliveryMethod::PAY_AT_THE_DOOR));
    
    
  }
  
  protected function flagTickets($n, $data=false){
    $data = $data ?: array( 'used'=>1  ); //deatuls to 'used'
    $id = $this->db->get_one("SELECT id FROM ticket ORDER BY id DESC");
    for ($i=$id; $i>$id-$n; $i--){
      $this->db->update('ticket', $data , "id=?", $i);
      
    }
  }
  
  function testTour(){
    $this->clearAll();
    
    $venue_id = $this->createVenue('Kignston Oval');
    $out1 = $this->createOutlet('outlet 1', '0001');
    
    
    $seller = $this->createUser('seller');
    $seller2 = $this->createUser('seller2');
    $foo = $this->createUser('foo');
    
    
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, cheating
    $catB = $cats[0];
    //return;
    
    
    
    //return; //fixture. create event template and tour dates from here as seller.
    
    $evt = $this->createEvent("Feria Biess", $seller2->id, $this->createLocation()->id);
    $this->setEventId($evt, 'n0rm41');
    $this->setEventGroupId($evt, '0001');
    $cat = $this->createCategory('Adult', $evt->id, 10.00);
    
    
    //normal event purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $cat->id, 1);
    $txn_id = $outlet->payByCash($foo);
 
    
    //tour1 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 3);
    $txn_id = $outlet->payByCash($foo);
    /*
    //tour2 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour2', $catA->id, 2);
    $txn_id = $outlet->payByCash($foo);
    
    
    //a purchase of multiple items
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 3);
    $outlet->addItem('tour1', $catB->id, 1);
    $outlet->addItem('tour2', $catB->id, 1);
    $txn_id = $outlet->payByCash($foo);*/
  }
 
}


