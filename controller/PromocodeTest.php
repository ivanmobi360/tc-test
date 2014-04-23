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
      $this->assertNotEmpty($id);
      
      //accept array of categories
      $id = $this->createPromocode('uniqueA', $evt->id, $catA, 50);
      $this->assertNotEmpty($id);
      
      //15% after 20 tickets
      $id = $this->createAutonomousPromocodeBuilder('15%', $evt->id, $catA->id, 15, 'p', 20)->build();
      $this->assertNotEmpty($id);
      $this->assertNotEmpty(Promocode::get($id));
      
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
      $this->assertNotEmpty($p1);
      //20% after 8 tickets
      $p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8)->build();
      $this->assertNotEmpty($p2);
  
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
      $this->assertNotEmpty($p1);
      //20% after 8 tickets
      $p2 = $this->createAutonomousPromocodeBuilder('20%', $evt->id, $catA->id, 20, 'p', 8, 10)->build();
      $this->assertNotEmpty($p2);
  
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
      $this->assertNotEmpty($p1);
  
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
      $this->assertNotEmpty($p1);

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
      $this->assertNotEmpty($p1);
  
      Utils::clearLog();
      
      
      //Full price if threshodls is not met
      $web = new WebUser($this->db);
      $web->login($foo->username);
      $web->addToCart($evt->id, $catA->id, 2);
      $web->addToCart($evt->id, $catB->id, 2); Utils::clearLog();
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
  
  function testTenMin(){
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
      $this->assertNotEmpty($p1);
      
      //Utils::clearLog();
      
  }
  
  /**
   * If an autonomous discount is applicable, no other discount is possible
   */
  function testOverride(){
      
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
      $seller = $this->createUser('seller');
      
      $this->createBoxoffice('111-xbox', $seller->id);
      $venue_id = $this->createVenue('Pool');
      $this->createReservationUser('tixpro', $venue_id);
      
      $priceA = 100;
      $priceB = 50;
      
      $evt = $this->createEvent('Six Sigma Training', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $venue_id );
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Kid', $evt->id, $priceB, 99);
      ModuleHelper::showEventInAll($this->db, $evt->id);
      $foo = $this->createUser('foo');
      
      
      //10% after 3 tickets
      $p_id = $this->createAutonomousPromocodeBuilder('10%', $evt->id,  array($catA, $catB), 10, 'p', 3)->build();
      $this->assertNotEmpty($p_id);
      
      $this->createPromocode('MITAD', $evt->id, array($catA, $catB),  50);
      
      $web = new WebUser($this->db);
      $web->login($foo->username);
      $web->addToCart($evt->id, $catA->id, 3);
      
      Utils::clearLog();
      
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      $this->assertEquals($p_id, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
      //Utils::log(print_r($_SESSION, true)); return;
      
      
      $web->applyPromoCode($evt->id, 'MITAD');
      Utils::clearLog();
      
      
      
      //expect no change. code is ignored, autonomous prevails
      $res = $web->getCart()->returnItemCart($evt->id); Utils::clearLog();
      Utils::log(print_r($res, true));
      $this->assertEquals($p_id, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
  }
  
  /**
   * if normal promocode is in place
   * and autonomous promocode threshold is breached
   * then drop normal promocode
   * and let autonomous promocode take over
   */
  function testReverseOverride(){
  
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
  
      $this->createBoxoffice('111-xbox', $seller->id);
      $venue_id = $this->createVenue('Pool');
      $this->createReservationUser('tixpro', $venue_id);
  
      $priceA = 100;
      $priceB = 50;
  
      $evt = $this->createEvent('Six Sigma Training', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $venue_id );
      $this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Adult', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Kid', $evt->id, $priceB, 99);
      ModuleHelper::showEventInAll($this->db, $evt->id);
      $foo = $this->createUser('foo');
  
  
      //10% after 3 tickets
      $p_id = $this->createAutonomousPromocodeBuilder('10%', $evt->id,  array($catA, $catB), 10, 'p', 3)->build();
      $this->assertNotEmpty($p_id);
  
      $this->createPromocode('MITAD', $evt->id, array($catA, $catB),  50);
  
      $web = new WebUser($this->db);
      $web->login($foo->username);
      $web->addToCart($evt->id, $catA->id, 2);
      
      $web->applyPromoCode($evt->id, 'MITAD');
      
      //we expect the promocode to be applied
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      $this->assertEquals('MITAD', $res['itemEvent']['_total']['_event']['promocode']);
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode_special']);
      //return;    
    
      
      Utils::clearLog();
      
      //At the time we reach the autonomous code threshold, the manual code should be dropped.
      $web->quantityUpdate($evt->id, $catA->id, 3);
  
      //Threshold breached!
      $res = $web->getCart()->returnItemCart($evt->id); //Utils::clearLog();
      Utils::log(print_r($res, true));
      $this->assertEquals($p_id, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
  
  }
  
  /**
   *  cross-category counted
      single-cart-wide (event-wide?)
      once-per-range, ticket-distributed
      (Functional but backpedaled. This logic might be activated again when new policies/esceptions are enabled)
   */
  function xtestGregCase(){
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
      $seller = $this->createUser('seller');
      
      $priceA = 175;
      $priceB = 175;
      
      $evt = $this->createEvent('Spa Day', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      //$this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      //$this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Spa A', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Spa B', $evt->id, $priceB, 99);
      $this->createCategory('Spa C', $evt->id, $priceB, 99);
      $this->createCategory('Spa D', $evt->id, $priceB, 99);
      $this->createCategory('Spa E', $evt->id, $priceB, 99);
      
      $foo = $this->createUser('foo');
      
      Utils::clearLog();
      //Discount between 2 and 3 
      $p1 = $this->createAutonomousPromocodeBuilder('DOUBLE', $evt->id,  array($catA, $catB), 30, 'f', 2, 3)->build();
      $this->assertNotEmpty($p1);
      
      Utils::clearLog();
      //Discount after 4
      $p2 = $this->createAutonomousPromocodeBuilder('GROUP', $evt->id,  array($catA, $catB), 100, 'f', 4)->build();
      $this->assertNotEmpty($p2);
      
      // --------
      
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 2);
      $web->addToCart($evt->id, $catB->id, 2);
      
      // return;
      Utils::clearLog();
      
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      //return;
      $this->assertEquals($p2, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
      Utils::clearLog();
      $txn_id = $web->payByCashBtn();
      
      //expecte a 100.00 single discount
      $this->assertEquals(100.00, $this->db->get_one("SELECT SUM(discount) FROM ticket_transaction WHERE event_id=?", $evt->id)); //ok with interception in tool\Cart line 731
      
      $this->assertEquals(100.00, $this->getTotalDiscount($evt->id, $txn_id));
      
      
      //*********** 3,1 case
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 3);
      $web->addToCart($evt->id, $catB->id, 1);
      
      // return;
      Utils::clearLog();
      
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      //return;
      $this->assertEquals($p2, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
      Utils::clearLog();
      $txn_id = $web->payByCashBtn();
      
      //expect a 100.00 single discount
      $this->assertEquals(100.00, $this->db->get_one("SELECT SUM(discount) FROM ticket_transaction WHERE event_id=? AND txn_id=?", array($evt->id, $txn_id))); //ok with interception in tool\Cart line 731
      $this->assertEquals(100.00, $this->getTotalDiscount($evt->id, $txn_id) );
      
      
  }
  
  protected function getTotalDiscount($event_id, $txn_id){
      return $this->db->get_one("SELECT SUM(price_promocode) FROM ticket
                                                      INNER JOIN ticket_transaction t ON ticket.transaction_id = t.id
                                                      WHERE t.event_id=? AND txn_id=?", array($event_id, $txn_id));
  }
  
  /**
   * 2013-04-11
   * 
   */
  function testExaminedCases(){
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
  
      $seller = $this->createUser('seller');
      $this->createBoxoffice('111-xbox', $seller->id);
  
      $priceA = 175;
      $priceB = 175;
  
      $evt = $this->createEvent('Spa Day', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      //$this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      //$this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Spa A', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Spa B', $evt->id, $priceB, 99);
      $this->createCategory('Spa C', $evt->id, $priceB, 99);
      $this->createCategory('Spa D', $evt->id, $priceB, 99);
      $this->createCategory('Spa E', $evt->id, $priceB, 99);
      
      ModuleHelper::showEventInAll($this->db, $evt->id);
  
      $foo = $this->createUser('foo');
  
      Utils::clearLog();
      //Discount between 2 and 3
      $p1 = $this->createAutonomousPromocodeBuilder('DOUBLE', $evt->id,  array($catA, $catB), 30, 'f', 2, 3)->build();
      $this->assertNotEmpty($p1);
  
      Utils::clearLog();
      //Discount after 4
      $p2 = $this->createAutonomousPromocodeBuilder('GROUP', $evt->id,  array($catA, $catB), 100, 'f', 4)->build();
      $this->assertNotEmpty($p2);
  
      // ----- --------------------- BEGIN CASES ----------------------------
      /*
      case of cat A x 1 ticket + cat B x 1 ticket
      it goes above min range of discount, but doesn't trigger them because they are from different categories
      */
  
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 1);
      $web->addToCart($evt->id, $catB->id, 1);
  
      // return;
      Utils::clearLog();
  
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      
      //return;
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode_special']);
      //$this->assertEmpty($res['itemEvent']['_total']['_event']['promocode_special']['id']); //NOTHING
      $this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
      //return;
  
      Utils::clearLog();
      $txn_id = $web->payByCashBtn();
  
      
      $this->assertEquals(0.00, $this->db->get_one("SELECT SUM(discount) FROM ticket_transaction WHERE event_id=?", $evt->id)); //ok with interception in tool\Cart line 731
      $this->assertEquals(0.00, $this->getTotalDiscount($evt->id, $txn_id));
  
      //return;
  
      //*********** **************************************
      /*
        case of cat A x2 tickets + cat B x 1 ticket
        cat A goes above min range of discount and triggers it, for that category alone, 
        cat B remains untouched
       */
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 2);
      $web->addToCart($evt->id, $catB->id, 1);
  
      // return;
      Utils::clearLog();
  
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      //return;
      //$this->assertEquals($p2, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      //$this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
  
      Utils::clearLog();
      $txn_id = $web->payByCashBtn();
  
      $this->assertEquals(30.00, $this->db->get_one("SELECT SUM(discount) FROM ticket_transaction WHERE event_id=? AND txn_id=?", array($evt->id, $txn_id))); //ok with interception in tool\Cart line 731
      $this->assertRows(1, 'ticket_transaction', "txn_id=? AND promocode_id=?", array($txn_id, $p1));
      $this->assertEquals(30.00, $this->getTotalDiscount($evt->id, $txn_id) );
      
      // ***********************************************************
      
      /*
       * case of cat A x2 tickets + cat B x 2 tickets
        •	cat A goes above min range and triggers discount  for cat A, and 
        •	cat B goes above min range and triggers discount for cat B, so far, 
        •	all min range have been in the (2-3) discount, 
        •	in this case you might think it triggered the 4+ discount, but it doesn't, 
        •	because as "fixed price" discount, they only work by category, 
        •	so for each category so far, we haven't exceeded 2 tickets

            at this moment, you gain a discount by reaching and staying within a range of (tickets/amount) sold, so 
            for as long as you are in that range, you gain the discount once.
            2 tickets of cat A means $15.00 rebate each ticket.
            3 tickets of cat A means $10.00 rebate each ticket.

       */
      
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 2);
      $web->addToCart($evt->id, $catB->id, 2);
      
      // return;
      Utils::clearLog();
      
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      //return;
      //$this->assertEquals($p2, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      //$this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
      Utils::clearLog();
      $txn_id = $web->payByCashBtn();
      
      $this->assertEquals(60.00, $this->db->get_one("SELECT SUM(discount) FROM ticket_transaction WHERE event_id=? AND txn_id=?", array($evt->id, $txn_id))); //ok with interception in tool\Cart line 731
      $this->assertRows(2, 'ticket_transaction', "txn_id=? AND promocode_id=?", array($txn_id, $p1));
      $this->assertEquals(60.00, $this->getTotalDiscount($evt->id, $txn_id) );
  
  
      /**
       * 3 tickets of cat A means $10.00 rebate each ticket.
       */
      
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 3);
      $web->addToCart($evt->id, $catB->id, 2);
      
      // return;
      Utils::clearLog();
      
      //expect cart to have autonomous discount
      $res = $web->getCart()->returnItemCart($evt->id);
      Utils::log(print_r($res, true));
      //return;
      //$this->assertEquals($p2, $res['itemEvent']['_total']['_event']['promocode_special']['id']);
      //$this->assertEquals('', $res['itemEvent']['_total']['_event']['promocode']);
      
      Utils::clearLog();
      $txn_id = $web->payByCashBtn();
      
      $this->assertEquals(60.00, $this->db->get_one("SELECT SUM(discount) FROM ticket_transaction WHERE event_id=? AND txn_id=?", array($evt->id, $txn_id))); //ok with interception in tool\Cart line 731
      $this->assertRows(2, 'ticket_transaction', "txn_id=? AND promocode_id=?", array($txn_id, $p1));
      $this->assertEquals(60.00, $this->getTotalDiscount($evt->id, $txn_id) );
      
      
  }
  
  /**
   * 2014-0415
   */
  function test_cc_and_discount(){
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
  
      $seller = $this->createUser('seller');
      $this->createBoxoffice('111-xbox', $seller->id);
  
      $priceA = 175;
      $priceB = 175;
  
      $evt = $this->createEvent('Spa Day', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      //$this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      //$this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Spa A', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Spa B', $evt->id, $priceB, 99);
      $this->createCategory('Spa C', $evt->id, $priceB, 99);
      $this->createCategory('Spa D', $evt->id, $priceB, 99);
      $this->createCategory('Spa E', $evt->id, $priceB, 99);
  
      ModuleHelper::showEventInAll($this->db, $evt->id);
  
      $foo = $this->createUser('foo');
  
      Utils::clearLog();
      //Discount between 2 and 3
      $p1 = $this->createAutonomousPromocodeBuilder('DOUBLE', $evt->id,  array($catA, $catB), 30, 'f', 2, 3)->build();
      $this->assertNotEmpty($p1);
  
      Utils::clearLog();
      //Discount after 4
      $p2 = $this->createAutonomousPromocodeBuilder('GROUP', $evt->id,  array($catA, $catB), 100, 'f', 4)->build();
      $this->assertNotEmpty($p2);
  
      // ----- --------------------- BEGIN CASES ----------------------------
      //1a ticket
      $web = new WebUser($this->db);
      $web->login($foo->username);Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 1);
      ///$web->addToCart($evt->id, $catB->id, 1);
  
      $fee_cc = $web->getOnlineFees();
  
      Utils::clearLog();
      $txn_id = $web->payWithCreditCard();
      
      $this->assertEquals($fee_cc, $this->db->get_one("SELECT SUM(fee_cc) FROM ticket_transaction WHERE txn_id=?", $txn_id), null, 0.001);
      
      // ********************************************
      // 1a, 1b ticket 
      $web = new WebUser($this->db);
      $web->login($foo->username);//Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 1);
      $web->addToCart($evt->id, $catB->id, 1);
      
      $fee_cc = $web->getOnlineFees();
      
      Utils::clearLog();
      $txn_id = $web->payWithCreditCard();
      
      $this->assertEquals($fee_cc, $this->db->get_one("SELECT SUM(fee_cc) FROM ticket_transaction WHERE txn_id=?", $txn_id), null, 0.001);
  
      // ***********************************************
      
      // 2a, 1b ticket
      $web = new WebUser($this->db);
      $web->login($foo->username);//Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 2);
      $web->addToCart($evt->id, $catB->id, 1);
      
      $fee_cc = $web->getOnlineFees();
      
      Utils::clearLog();
      $txn_id = $web->payWithCreditCard();
      
      $this->assertEquals($fee_cc, $this->db->get_one("SELECT SUM(fee_cc) FROM ticket_transaction WHERE txn_id=?", $txn_id), null, 0.001);
  
  }
  
  function testBoxoffice(){
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
  
      $seller = $this->createUser('seller');
      $bo_id = $this->createBoxoffice('111-xbox', $seller->id);
  
      $priceA = 175;
      $priceB = 175;
  
      $evt = $this->createEvent('Spa Day', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      //$this->setEventParams($evt->id, array('has_tax'=>0)); //for easy calculations
      //$this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('Spa A', $evt->id, $priceA, 99);
      $catB = $this->createCategory('Spa B', $evt->id, $priceB, 99);
      $this->createCategory('Spa C', $evt->id, $priceB, 99);
      $this->createCategory('Spa D', $evt->id, $priceB, 99);
      $this->createCategory('Spa E', $evt->id, $priceB, 99);
  
      ModuleHelper::showEventInAll($this->db, $evt->id);
  
      $foo = $this->createUser('foo');
  
      Utils::clearLog();
      //Discount between 2 and 3
      $p1 = $this->createAutonomousPromocodeBuilder('DOUBLE', $evt->id,  array($catA, $catB), 30, 'f', 2, 3)->build();
      $this->assertNotEmpty($p1);
  
      Utils::clearLog();
      //Discount after 4
      $p2 = $this->createAutonomousPromocodeBuilder('GROUP', $evt->id,  array($catA, $catB), 100, 'f', 4)->build();
      $this->assertNotEmpty($p2);
  
      // ----- --------------------- BEGIN CASES ----------------------------
      $box = new BoxOfficeModule($this);
      $box->login('111-xbox');
      $this->assertEquals($bo_id, $box->getId());
      
      $box->addItem($evt->id, $catA->id, 1); Utils::clearLog();
      $txn_id = $box->payWithCC();
      
      $this->assertRows(1, 'ticket');
      
      $trans = $this->db->auto_array("SELECT * FROM ticket_transaction WHERE txn_id=?", $txn_id);
      
      $this->assertEquals($bo_id, $trans['bo_id']);
      
      /*
      // 1a, 1b ticket
      $web = new WebUser($this->db);
      $web->login($foo->username);//Utils::clearLog();
      $web->addToCart($evt->id, $catA->id, 1);
      $web->addToCart($evt->id, $catB->id, 1);
  
      $fee_cc = $web->getOnlineFees();
  
      Utils::clearLog();
      $txn_id = $web->payWithCreditCard();
  
      $this->assertEquals($fee_cc, $this->db->get_one("SELECT SUM(fee_cc) FROM ticket_transaction WHERE txn_id=?", $txn_id), null, 0.001);
  
      */
  
  }
  

  

 
}