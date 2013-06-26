<?php


 
use reports\ReportLib;
class RecentOrdersTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    
    //let's create some events
    $this->clearAll();
    
    $this->db->beginTransaction();
    
    $n = 200;
    
    $seller = $this->createUser('seller');
    
    for ($i = 1; $i<=$n; $i++){
      $user = $this->createUser('foo' . $i);
      $evt = $this->createEvent('Event ' . $i, $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
      $cat = $this->createCategory('Cat' . $i, $evt->id, /*100.00*/rand(45,125));
      
      $client = new WebUser($this->db);
      $client->login($user->username);
      $client->addToCart($evt->id, $cat->id, rand(1, 10));
      $this->completeTransaction($client->placeOrder());
      }
      
    $this->db->commit();
    $this->assertEquals($n, $this->db->get_one("SELECT COUNT(id)  FROM  ticket_transaction"));
    
    $lib = new ReportLib();
    $this->assertEquals($n, $this->db->num_rows($this->db->Query($lib->getRecentOrdersSql()))  );
    
    
    $serial =  'TX-aaaa';
    //Apparently there can be multiple rows with the same txn_id;
    $user_id = $this->addUser('wolf');
    $evt = $this->createEvent('Worldcup' , $user_id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Tribuna', $evt->id, 100.00);
    $this->createTransaction($cat->id, $user_id, 200.00, 2, $serial, 1);
    $cat = $this->createCategory('General', $evt->id, 50.00);
    $this->createTransaction($cat->id, $user_id, 150.00, 3, $serial, 1);
    $cat = $this->createCategory('Palco', $evt->id, 150.00);
    $this->createTransaction($cat->id, $user_id, 150.00, 1, $serial, 1);
    
    //Flag a test to alert of schema changes
    $lib = new ReportLib();
    $rows = $this->db->getIterator($lib->getOrderLinesSql(), $serial);
    $this->assertEquals(3, count($rows)); 
    
  }
  
  public function test_several_events_in_one_transaction(){
    
    $this->clearAll();

    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $evt = $this->createEvent('Event A' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Room 1', $evt->id, 100.00);
    $this->buyTickets($foo->id, $cat->id, 2);
    $cat = $this->createCategory('Room 2', $evt->id, 20.00);
    $this->buyTickets($foo->id, $cat->id, 3);
    
    $evt = $this->createEvent('Event B' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Hall 1', $evt->id, 45.00);
    $this->buyTickets($foo->id, $cat->id);
    
    $evt = $this->createEvent('Event C' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Floor Alpha', $evt->id, 25.00);
    $this->buyTickets($foo->id, $cat->id, 2);
    //merely a fixture for http://jira.mobination.net:8080/browse/MME-22
  }
  
  function testSingleTour(){
    
    //simple fixture
    $this->clearAll();

    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $this->createVenue('Pool');
    
    $outlet = $this->createOutlet('outlet1', '0001');
    
    $bits = '1101';
    //move this to a tour builder test
    $build = new TourBuilder($this, $seller);
    $build->outlets = $bits;
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];

    //Data is stored correctly but phpmyadmin apparently has display problems. 
    //Data is shown correctly in other clients, like HeidiSql.
    $this->assertEquals(bindec($bits), $this->db->get_one("SELECT group_id FROM event WHERE id=?", 'aaa')); 
    
    //le'ts do a tour purchase
    $out = new OutletModule($this->db, 'outlet1');
    $out->addItem('tour1', $catX->id, 1);
    $out->payByCash($foo);
    
    
    
  }
  
  
  
  
  
 
  
 

  
}