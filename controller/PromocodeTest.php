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
      
      //tour testing
      $build = new TourBuilder($this, $seller);
      $build->event_id = 'tourtpl';
      $build->build();
      $cats = $build->categories;
      //$catA = $cats[1]; //the 100.00 one, yep, cheating
      //$catB = $cats[0];
      $this->setEventParams($build->event_id, array('has_ccfee' => 0, 'has_tax'=>0));

      $foo = $this->createUser('foo');
      
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
      
      //fail on negatives
      $id = $this->createAutonomousPromocodeBuilder('xx', $evt->id, $catA->id, 20, 'f', -20, null, 'amount')->build();
      $this->assertFalse($id);
      $id = $this->createAutonomousPromocodeBuilder('xx', $evt->id, $catA->id, 20, 'f', 20, -10, 'amount')->build();
      $this->assertFalse($id);
      $id = $this->createAutonomousPromocodeBuilder('xx', $evt->id, $catA->id, 20, 'f', 20, 10, 'amount')->build(); //range max must be greater
      $this->assertFalse($id);
      
      $id = $this->createAutonomousPromocodeBuilder('xx', $evt->id, $catA->id, 25, 'p', '')->build();
      $this->assertFalse($id);
      
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
  
      Utils::clearLog();
      
      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
      
      //10% after 5 tickets
      $p1 = $this->createAutonomousPromocodeBuilder('10%', $evt->id, $catA->id, 10, 'p', 5)->build();
      $this->assertNotNull($p1);
      //20% after 8 tickets
      $p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8)->build();
      $this->assertNotNull($p2);
  
      Utils::clearLog();
      
      //no discount
      $n = 3;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA, $trans['price_paid'] );
      $this->assertEquals(0, $trans['promocode_id']); //apparently stores 0 when no apply
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=0 "));
      
      
      Utils::clearLog();
      
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
  

      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
      
      //10% after 5 tickets
      $p1 = $this->createAutonomousPromocodeBuilder('10%', $evt->id, $catA->id, 10, 'p', 5, 7)->build();
      $this->assertNotNull($p1);
      //20% after 8 tickets
      $p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8, 10)->build();
      $this->assertNotNull($p2);
  
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
  
  //Verify code is not used if out of date
  function testDateRange(){
  
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
  
      //10% after 5 tickets
      $builder = $this->createAutonomousPromocodeBuilder('10%', $evt->id, $catA->id, 10, 'p', 5, 7);
      $builder->valid_from = $this->dateAt('-10 day');
      $builder->valid_to = $this->dateAt('-5 day');
      $p1 = $builder->build();
      $this->assertNotNull($p1);
  
      Utils::clearLog();
  
      //no discount
      $n = 5;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA, $trans['price_paid'] );
      $this->assertEquals(0, $trans['promocode_id']); //apparently stores 0 when no apply
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=0 "));

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
      
      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
  
      //"$20 discount after $200 purchase would be"
      $p1 = $this->createAutonomousPromocodeBuilder('$20', $evt->id, $catA->id, 20, 'f', 200, null, 'amount')->build();
      $this->assertNotNull($p1);

      Utils::clearLog();
  

      //pass first threshold
      $n = 2;
      $txn_id = $this->buyTickets('foo', $evt->id, $catA->id, $n);
      $trans =  $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      $this->assertEquals($n*$priceA-20, $trans['price_paid'] );
      $this->assertEquals($p1, $trans['promocode_id']);
      $this->assertEquals($n, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1)); //promocode must be set in tickets
      $this->assertEquals(20, \Database::get_one("SELECT SUM(price_promocode) FROM ticket WHERE promocode_id=? ", $p1));//discount is splitted in tickets

  
  }
  
  /**
   * "On the point of the categories, indeed, it has to be cross categories, so 
      if a discount is supposed to be "for 15+ tickets we give you 10% discount", then 
      if a customer buys 10 kid tickets and 5 adult tickets, they should receive their discount."
   */
  function testCrossCategory(){
  
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
  
      $priceA = 100;
      $priceB = 60;
      $priceC = 50;
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Kid', $evt->id, $priceB, 99);
      $catC = $this->createCategory('Pet', $evt->id, $priceC, 99);
  
      $foo = $this->createUser('foo');
  
            
      //fixed one for laughs
      $this->createPromocode('asd', $evt->id, $catA, 25);
      
      //10% after 5 tickets
      $p1 = $this->createAutonomousPromocodeBuilder('10%', $evt->id,  array($catA, $catB)  , 10, 'p', 5, 7)->build();
      $this->assertNotNull($p1);
  
      Utils::clearLog();
      
      
      //Full price if threshodls is not met
      $web = new WebUser($this->db);
      $web->login($foo->username);
      $web->addToCart($evt->id, $catA->id, 2);
      $web->addToCart($evt->id, $catB->id, 2);
      $txn_id = $web->payByCashBtn();
      
      //expect 200 + 120
      $this->assertEquals(2*$priceA + 2*$priceB, $this->db->get_one("SELECT SUM(price_paid) FROM ticket_transaction WHERE txn_id=? ", $txn_id) );
      $this->assertEquals(0, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE promocode_id=? ", $p1));
      $this->assertEquals(0, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1));
      $this->assertEquals(0, $this->db->get_one("SELECT SUM(price_promocode) FROM ticket WHERE promocode_id=? ", $p1) );
      Utils::clearLog();
      
      
      //Full price if purchased category is not part of the promocode 
      $web = new WebUser($this->db);
      $web->login($foo->username);
      $web->addToCart($evt->id, $catA->id, 1);
      $web->addToCart($evt->id, $catC->id, 5);
      $txn_id = $web->payByCashBtn();
      
      //expect 200 + 120
      $this->assertEquals(1*$priceA + 5*$priceC, $this->db->get_one("SELECT SUM(price_paid) FROM ticket_transaction WHERE txn_id=? ", $txn_id) );
      $this->assertEquals(0, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE promocode_id=? ", $p1));
      $this->assertEquals(0, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1));
      $this->assertEquals(0, $this->db->get_one("SELECT SUM(price_promocode) FROM ticket WHERE promocode_id=? ", $p1) );
      Utils::clearLog();
      
      
      //Combined sales should be able to produce the discount (3 adult and 2 fixed)
      $web = new WebUser($this->db);
      $web->login($foo->username);
      $web->addToCart($evt->id, $catA->id, 3);
      $web->addToCart($evt->id, $catB->id, 2);
      $txn_id = $web->payByCashBtn();
      
      //expect 270 + 108
      $this->assertEquals(3*$priceA*.9 + 2*$priceB*.9, $this->db->get_one("SELECT SUM(price_paid) FROM ticket_transaction WHERE txn_id=? ", $txn_id) );
      $this->assertEquals(2, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE promocode_id=? ", $p1));
      $this->assertEquals(5, \Database::get_one("SELECT COUNT(id) FROM ticket WHERE promocode_id=? ", $p1));
      $this->assertEquals(3*$priceA*.1 + 2*$priceB*.1, $this->db->get_one("SELECT SUM(price_promocode) FROM ticket WHERE promocode_id=? ", $p1) );
      
  
  }
  
  function testSix(){
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
      $seller = $this->createUser('seller');
      
      $priceA = 100;
      $priceB = 50;
      
      $evt = $this->createEvent('Six Sigma Training', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      //$this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      //$this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Kid', $evt->id, $priceB, 99);
      
      $foo = $this->createUser('foo');
      
      
      //10% after 5 tickets
      $p1 = $this->createAutonomousPromocodeBuilder('10%', $evt->id,  array($catA, $catB), 15, 'p', 10)->build();
      $this->assertNotNull($p1);
      
      //Utils::clearLog();
      
  }
  

  

 
}