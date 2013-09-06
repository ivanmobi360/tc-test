<?php
use model\Promocode;

use model\Module;

/**
 * tests for the website Venue module
 * @author MASTER
 *
 */
use model\Eventsmanager;
use tool\Date;
class PromocodeTest extends DatabaseBaseTest{
  
  
  function testAutonomous(){
  
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
      
      $priceA = 100;
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $catA = $this->createCategory('Adult', $evt->id, $priceA);
      $catB = $this->createCategory('Kid', $evt->id, 150);

      $foo = $this->createUser('foo');
      
      //seller login
      $web = new WebUser($this->db);
      $web->login($seller->username);
      
      
      Utils::clearLog();
      
      //create normal promocode
      $id = $this->createPromocode('NORMAL', $evt->id, array($catA, $catB), 10);
      $this->assertNotNull($id);
      
      //accept array of categories
      $id = $this->createPromocode('uniqueA', $evt->id, $catA, 50);
      $this->assertNotNull($id);
      
      //15% after 20 tickets
      $id = $this->createAutonomousPromocodeBuilder('15%', $evt->id, $catA->id, 15, 'p', 20)->build();
      $this->assertNotNull($id);
      $this->assertNotNull(Promocode::get($id));
      
      //25% after  50 tickets   
      $id = $this->createAutonomousPromocodeBuilder('25%', $evt->id, $catA->id, 25, 'p', 50)->build();
      
      //"$20 discount after $200 purchase would be"
      $id = $this->createAutonomousPromocodeBuilder('$20', $evt->id, $catA->id, 20, 'f', 200, null, 'amount')->build();
      
      $web->logout();
      
  }
  
  function testDiscount(){
  
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
  
      $priceA = 100;
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA);
      $catB = $this->createCategory('Kid', $evt->id, 150);
  
      $foo = $this->createUser('foo');
  
      //seller login
      $web = new WebUser($this->db);
      $web->login($seller->username);Utils::clearLog();
      
      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
      
      //10% after 5 tickets
      $p1 = $this->createAutonomousPromocodeBuilder('10%', $evt->id, $catA->id, 10, 'p', 5)->build();
      $this->assertNotNull($p1);
      //20% after 8 tickets
      $p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8)->build();
      $this->assertNotNull($p2);
      $web->logout();
  
      Utils::clearLog();
      
      //no discount
      $n = 3;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA, $trans['price_paid'] );
      $this->assertEquals(0, $trans['promocode_id']); //apparently stores 0 when no apply
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=0 "));
      
      //pass first threshold
      $n = 5;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA*(1-10/100), $trans['price_paid'] );
      $this->assertEquals($p1, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1)); //promocode must be set in tickets
      
      Utils::clearLog();
      //pass second threshold
      $n = 8;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA*(1-20/100), $trans['price_paid'] );
      $this->assertEquals($p2, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p2)); //promocode must be set in tickets
  
  }
  
  //Here we check if the correct promocode is chosen according to the selected transaction
    function testRanged(){
  
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
  
      $priceA = 100;
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Kid', $evt->id, 150);
  
      $foo = $this->createUser('foo');
  
      //seller login
      $web = new WebUser($this->db);
      $web->login($seller->username);Utils::clearLog();
      
      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
      
      //10% after 5 tickets
      $p1 = $this->createAutonomousPromocodeBuilder('10%', $evt->id, $catA->id, 10, 'p', 5, 7)->build();
      $this->assertNotNull($p1);
      //20% after 8 tickets
      $p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8, 10)->build();
      $this->assertNotNull($p2);
      $web->logout();
  
      Utils::clearLog();
      
      //no discount
      $n = 3;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA, $trans['price_paid'] );
      $this->assertEquals(0, $trans['promocode_id']); //apparently stores 0 when no apply
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=0 "));
      
      //pass first threshold
      $n = 5;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA*(1-10/100), $trans['price_paid'] );
      $this->assertEquals($p1, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1)); //promocode must be set in tickets
      
      Utils::clearLog();
      //pass second threshold
      $n = 8;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA*(1-20/100), $trans['price_paid'] );
      $this->assertEquals($p2, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p2)); //promocode must be set in tickets
      
      //no discount after max limit
      Utils::clearLog();
      $n = 11;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA, $trans['price_paid'] );
      $this->assertEquals(0, $trans['promocode_id']); //apparently stores 0 when no apply
      $this->assertEquals($n + 3, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=0 "));
      
  
  }
  
  function testAmount(){
  
      $this->clearAll();
      
      $priceA = 100;
      
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA);
      $catB = $this->createCategory('Kid', $evt->id, 150);
  
      $foo = $this->createUser('foo');
  
      //seller login
      $web = new WebUser($this->db);
      $web->login($seller->username);Utils::clearLog();
  
      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
  
      //"$20 discount after $200 purchase would be"
      $p1 = $this->createAutonomousPromocodeBuilder('$20', $evt->id, $catA->id, 20, 'f', 200, null, 'amount')->build();
      $this->assertNotNull($p1);
      //20% after 8 tickets
      //$p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8)->build();
      //$this->assertNotNull($p2);
      $web->logout();
  
      Utils::clearLog();
  
      //no discount
      /*
      $n = 1;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA, $trans['price_paid'] );
      $this->assertEquals(0, $trans['promocode_id']); //apparently stores 0 when no apply
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=0 "));
      */
      //pass first threshold
      $n = 2;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA-20, $trans['price_paid'] );
      $this->assertEquals($p1, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1)); //promocode must be set in tickets
      /*
      Utils::clearLog();
      //pass second threshold
      $n = 8;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA*(1-20/100), $trans['price_paid'] );
      $this->assertEquals($p2, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p2)); //promocode must be set in tickets*/
  
  }
  

  

 
}