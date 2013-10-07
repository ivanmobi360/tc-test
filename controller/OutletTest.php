<?php
use model\Module;

/**
 * tests for the website Venue module
 * @author MASTER
 *
 */
use model\Eventsmanager;
use tool\Date;
class OutletTest extends DatabaseBaseTest{
  
  function testTodayFixture(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $seller = $this->createUser('seller');
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("-1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    $evt = $this->createEvent('Tacos Time', 'seller', $this->createLocation()->id, $this->dateAt("-3 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('Cuates', $evt->id, 100);
    
    //create a promo code?
    $this->createPromocode('DERP', $catA, 50);
    
    //create buyer
    $this->createUser('foo');
  }
  
  //fixture for Outlet Remittance report
  function testReport(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $seller = $this->createUser('seller');
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, '2012-10-07', '09:00', '2012-10-24' );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    $evt = $this->createEvent('Tacos Time', 'seller', $this->createLocation()->id, '2012-10-14', '15:00', '2012-10-14', '17:00' );
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('Cuates', $evt->id, 100);
    
    
    $evt = $this->createEvent('Otro Evento', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0100');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('Cat X', $evt->id, 50);
    $catY = $this->createCategory('Cat Y', $evt->id, 20);
    
    
    
    
    $foo = $this->createUser('foo');
    $baz = $this->createUser('baz');
    $paz = $this->createUser('paz');
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $this->assertEquals($out1, $outlet->getId());
    $outlet->date = '2012-08-07';
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    //outlet id was written
    $this->assertTrue(0 != $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE outlet_id=?", $out1 ));
    
    $outlet->date = '2012-08-09';
    $outlet->addItem('aaa', $catB->id, 1);
    $outlet->payByCash($baz);
    
    $outlet->date = '2012-08-13';
    $outlet->addItem('aaa', $catB->id, 1);
    $outlet->payByCash($paz);
    
    $outlet->date = '2012-08-14';
    $outlet->addItem('tacos', $catQ->id, 1);
    $outlet->payByCash($paz);
    
    //Third week purchases (fixture for cumulated per week check)
    $outlet->date = '2012-08-20';
    $outlet->addItem('aaa', $catB->id, 4);
    $outlet->payByCash($foo);
    
    
    
    
    
    $bar = $this->createUser('bar');
    $outlet = new OutletModule($this->db, 'outlet2');
    $outlet->addItem('bbb', $catX->id, 1);
    $outlet->payByCash($bar);
    
    
    return; //if not commented out, next code won't let you see the events when you log in
    
    //let's change the event group association for laughs. I should still be able to see the reports
    $this->setEventGroupId( new \model\Events('aaa'), '1000');
    $this->setEventGroupId( new \model\Events('bbb'), '1000');
    $this->setEventGroupId( new \model\Events('tacos'), '1000');
    
  }
  
  function testWeeks(){
    $date_start ='2012-08-07';
    $date_end = '2012-08-14';
    
    $this->assertEquals('2012-08-06', Date::mondayOfDate($date_start));
    $this->assertEquals('2012-08-06', Date::mondayOfDate('2012-08-06'));
    
    
    
    
    $weeks = Date::getWeeksBetweenDates($date_start, $date_end);
    $this->assertEquals(2, count($weeks));
    
    $this->assertEquals('2012-08-06', $weeks[0]['start']);
    $this->assertEquals('2012-08-12', $weeks[0]['end']);
    
    $this->assertEquals('2012-08-13', $weeks[1]['start']);
    $this->assertEquals('2012-08-19', $weeks[1]['end']);
    
    
    // *******************
    $date_start ='2012-08-06';
    $date_end = '2012-08-06';
    
    $weeks = Date::getWeeksBetweenDates($date_start, $date_end);
    $this->assertEquals(1, count($weeks));
    
    $this->assertEquals('2012-08-06', $weeks[0]['start']);
    $this->assertEquals('2012-08-12', $weeks[0]['end']);
    
    // *******************
    $date_start ='2012-08-12';
    $date_end = '2012-08-12';
    
    $weeks = Date::getWeeksBetweenDates($date_start, $date_end);
    $this->assertEquals(1, count($weeks));
    
    $this->assertEquals('2012-08-06', $weeks[0]['start']);
    $this->assertEquals('2012-08-12', $weeks[0]['end']);
    
    
    // *******************
    $date_start ='2012-08-06';
    $date_end = '2012-08-07';
    
    $weeks = Date::getWeeksBetweenDates($date_start, $date_end);
    $this->assertEquals(1, count($weeks));
    
    $this->assertEquals('2012-08-06', $weeks[0]['start']);
    $this->assertEquals('2012-08-12', $weeks[0]['end']);
    
    // ******************* monday to monday
    
    $date_start ='2012-08-06';
    $date_end = '2012-08-13';
    
    Utils::clearLog();
    $weeks = Date::getWeeksBetweenDates($date_start, $date_end);
    Utils::log(print_r($weeks, true));
    $this->assertEquals(2, count($weeks));
    
    $this->assertEquals('2012-08-06', $weeks[0]['start']);
    $this->assertEquals('2012-08-12', $weeks[0]['end']);
    
    $this->assertEquals('2012-08-13', $weeks[1]['start']);
    $this->assertEquals('2012-08-19', $weeks[1]['end']);
    
    
    // ******************* sunday to sunday
    
    $date_start ='2012-08-12';
    $date_end = '2012-08-19';
    
    Utils::clearLog();
    $weeks = Date::getWeeksBetweenDates($date_start, $date_end);
    Utils::log(print_r($weeks, true));
    $this->assertEquals(2, count($weeks));
    
    $this->assertEquals('2012-08-06', $weeks[0]['start']);
    $this->assertEquals('2012-08-12', $weeks[0]['end']);
    
    $this->assertEquals('2012-08-13', $weeks[1]['start']);
    $this->assertEquals('2012-08-19', $weeks[1]['end']);
    
    
    
  }
  
  function testPastEventsAreNotShown(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $seller = $this->createUser('seller');
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    //not shown
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("-10 day"), '09:00', $this->dateAt("-5 day") );
    $this->setEventGroupId($evt, '0010');
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    
    //shown
    $evt = $this->createEvent('Tacos Time', 'seller', $this->createLocation()->id, $this->dateAt("-3 day"), '15:00', $this->dateAt("+2 day"), '17:00' );
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('Cuates', $evt->id, 100);
    
    $evt = $this->createEvent('Cofee', 'seller', $this->createLocation()->id, $this->dateAt("+6 day"), '15:00');
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('cofee', $evt->id, 100);
    
    $evt = $this->createEvent('Today', 'seller', $this->createLocation()->id);
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('like now', $evt->id, 100);
    
    $evt = $this->createEvent('Up to Today', 'seller', $this->createLocation()->id, $this->dateAt("-5 day"), '09:00', date('Y-m-d'));
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('Lasting now', $evt->id, 100);
    
  }
  
  function testWeekSpanDateOfSales(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, '2012-08-30', '09:00', '2012-09-05' );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    
    
    $foo = $this->createUser('foo');
    $baz = $this->createUser('baz');
    $paz = $this->createUser('paz');
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->date = '2012-08-20'; //It should show this week
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    $this->assertEquals('2012-08-20', Eventsmanager::getDateOfFirstSale($evt->id));
    $this->assertEquals('2012-08-20', Eventsmanager::getDateOfLastSale($evt->id));
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->date = '2012-08-21'; //It should show this week
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    $this->assertEquals('2012-08-20', Eventsmanager::getDateOfFirstSale($evt->id));
    $this->assertEquals('2012-08-21', Eventsmanager::getDateOfLastSale($evt->id));
    
  }
  
  function testTour(){
    $this->clearAll();
    
    $venue_id = $this->createVenue('Kignston Oval');
    $out1 = $this->createOutlet('outlet 1', '0001');
    
    
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, cheating
    $catB = $cats[0];
    //return;
    
    
    
    //return; //fixture. create event template and tour dates from here as seller.
    
    $evt = $this->createEvent("Feria Biess", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'n0rm41');
    $this->setEventGroupId($evt, '0001');
    $cat = $this->createCategory('Adult', $evt->id, 10.00);
    
    
    //normal event purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $cat->id, 1);
    $txn_id = $outlet->payByCash($foo);
 
    
    //tour1 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 1);
    $txn_id = $outlet->payByCash($foo);
    
    //tour2 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour2', $catA->id, 2);
    $txn_id = $outlet->payByCash($foo);
    
    
    //a purchase of multiple items
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 3);
    $outlet->addItem('tour1', $catB->id, 1);
    $outlet->addItem('tour2', $catB->id, 1);
    $txn_id = $outlet->payByCash($foo);
    
  }
  
  function test_event_blocked_from_outlet(){
    $this->clearAll();
    
    $venue_id = $this->createVenue('Kignston Oval');
    $out1 = $this->createOutlet('outlet 1', '0001');
    $out2 = $this->createOutlet('outlet 2', '0010');
    $out3 = $this->createOutlet('outlet 3', '0100');
    $out4 = $this->createOutlet('outlet 4', '0100');
    
    
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    

    $evt = $this->createEvent("BoxStation Launch", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'zzz');
    $this->setEventGroupId($evt, '0011');
    $cat = $this->createCategory('Adult', $evt->id, 10.00);
    
    $evt = $this->createEvent("PlayCube Launch", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'playQb');
    $this->setEventGroupId($evt, '0111');
    $cat = $this->createCategory('Adult', $evt->id, 10.00);
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, cheating
    $catB = $cats[0];
    
    //return; //it should appear in both outlets
    
    //exclude zzz from outlet 1, should appear in outlet 2
    $this->db->insert("event_outlet_exclusion", array('event_id'=>'zzz', 'outlet_id'=>$out1));
    
    $this->db->insert("event_outlet_exclusion", array('event_id'=>'aaa', 'outlet_id'=>$out3)); //aaa not visible in out 3
    $this->db->insert("event_outlet_exclusion", array('event_id'=>'aaa', 'outlet_id'=>$out4)); 
          
    //normal event purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $cat->id, 1);
    $txn_id = $outlet->payByCash($foo);
    
  }
  
  /**
   * "The remittance report is showing sales made from other outlets.
		 This report is supposed to show only information from the current logged in outlet, please fix that."
		 http://jira.mobination.net:8080/browse/TIXCAR-202
   */
  function test_remittance_dont_show_sales_from_other_outlets(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 150);
    
    $evt = $this->createEvent('Apple Launch', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'apple');
    $this->setEventGroupId($evt, '0100');
    $catQ = $this->createCategory('Hipsters', $evt->id, 66.66);
    
    $foo = $this->createUser('foo');
     
    $outlet = new OutletModule($this->db, 'outlet1');
    $this->assertEquals($out1, $outlet->getId());
    $outlet->date = '2012-08-07';
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->addItem('apple', $catQ->id, 2);
    $outlet->payByCash($foo); //should be able to do and see this sale in reports
    
    
    $bar = $this->createUser('bar');
    $outlet = new OutletModule($this->db, 'outlet2');
    $this->assertEquals($out2, $outlet->getId());
    $outlet->addItem('apple', $catQ->id, 1);
    $outlet->payByCash($bar);    
  }
  
  //only show today's sales report (check in site)
  function testZReport(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 150);
    
    $foo = $this->createUser('foo');
     
    $outlet = new OutletModule($this->db, 'outlet1');
    $this->assertEquals($out1, $outlet->getId());
    $outlet->date = date('Y-m-d', strtotime('-1 day')); //yesterday
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->date = date('Y-m-d'); //today
    $outlet->addItem('aaa', $catB->id, 1);
    $outlet->payByCash($foo); //should not be visible in "today" Z Report
    
  }
  
  
  /**
   * For Daily Z Report 
   */
  function testEachEventAfterAnother(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Java Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 50);
    
    $evt = $this->createEvent('Net Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catM = $this->createCategory('Senior Admins', $evt->id, 100);
    $catN = $this->createCategory('Admins', $evt->id, 50);
    
    $foo = $this->createUser('foo');
     
    $outlet = new OutletModule($this->db, 'outlet1');
    $this->assertEquals($out1, $outlet->getId());
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->addItem('bbb', $catN->id, 1);
    $outlet->payByCash($foo);
    
  }
  
  /**
   * "When a parent outlet looks at the Z report, 
      they should see all the information, 
      including the sales made by the sub-outlets that are their children, 
      grouped by sub-outlets with 
      sub-totals and grand total at the end.
      " 
   * http://jira.mobination.net:8080/browse/TIXCAR-271
   */
  function testSubOutletReport(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $ganga = $this->createOutlet('1', '0010', array('parent'=>$out1));
    $pycca = $this->createOutlet('2', '0010', array('parent'=>$out1));
    $gamma = $this->createOutlet('3', '0010', array('parent'=>$out1));
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Monstro Sales', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 50);
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
    //add another event for laughts
    $evt = $this->createEvent('ALL CAPS EVENTS', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('ADULT', $evt->id, 65);
    $catY = $this->createCategory('KID', $evt->id, 35);
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
    
    $foo = $this->createUser('foo');
     
    $outlet = new OutletModule($this->db, 'outlet1');
    $this->assertEquals($out1, $outlet->getId());
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
    
    $outlet = new OutletModule($this->db, 'outlet1-1');
    $this->assertEquals($ganga, $outlet->getId()); //verify login logic works 
    $outlet->addItem('aaa', $catB->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->addItem('bbb', $catX->id, 1);
    $outlet->payByCash($foo);
    
    
    
    $outlet = new OutletModule($this->db, 'outlet1-3');
    $outlet->addItem('aaa', $catB->id, 4);
    $outlet->payByCash($foo);
    
    $outlet->addItem('bbb', $catX->id, 1);
    $outlet->addItem('bbb', $catY->id, 2);
    $outlet->payByCash($foo);
    
  }
  
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
  
  //Tax is now 14.89361
  function testNewTax(){
  	$this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    $this->setUserHomePhone($seller, '111');
    $box = $this->createBoxoffice('xbox', 'seller');//placeholder box for testing in box offices
    
    $evt = $this->createEvent('A Simple Event', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('Adult', $evt->id, 100, 5);
    $catB = $this->createCategory('Kid', $evt->id, 50, 10);
    
    
    $foo = $this->createUser('foo');

    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('aaa', $catA->id, 1);
    Utils::clearLog();
    $txn_id = $outlet->payByCash($foo);
  	
  }
  
  function testCC(){
    $this->clearAll();
    
    $venue_id = $this->createVenue('Kignston Oval');
    $out1 = $this->createOutlet('outlet 1', '0001');
    
    
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    $bar = $this->createUser('bar');
    
    
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, cheating
    $catB = $cats[0];

    ModuleHelper::showEventInAll($this->db, $build->event_id);
    
    //return;
    
    $evt = $this->createEvent("Feria Biess", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'n0rm41');
    $this->setEventGroupId($evt, '0001');
    $cat = $this->createCategory('Adult', $evt->id, 10.00);
    
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
    //normal event purchase
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $cat->id, 1);
    $txn_id = $outlet->payByCC($foo, $this->getCCData() );
    
    
    Utils::clearLog();
    
    //tour1 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 1);
    $txn_id = $outlet->payByCC($foo, $this->getCCData());
    
    //tour2 purchase
    $outlet->logout();
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour2', $catA->id, 2);
    $txn_id = $outlet->payByCC($foo, $this->getCCData());
    
    
    //a purchase of multiple items
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 3);
    $outlet->addItem('tour1', $catB->id, 1);
    $outlet->addItem('tour2', $catB->id, 1);
    $txn_id = $outlet->payByCC($bar, $this->getCCData());
    
  }
  
  /**
   * Sets up event with has_tax=0, makes a single 100.00 purchase.
   * Verify that everywhere the VAT is reported as 0.00
   */
  function testNoTax(){
  
      $this->clearAll();
      $out1 = $this->createOutlet('Outlet 1', '0010');
  
      $seller = $this->createUser('seller');
  
      $evt = $this->createEvent('Technology Event', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Pool'));
      $this->setEventParams($evt->id, array('has_tax'=>0));
      $catA = $this->createCategory('Adult', $evt->id, 100);
      $catB = $this->createCategory('Kid', $evt->id, 150);
  
      
      $foo = $this->createUser('foo');
       
      $outlet = new OutletModule($this->db, 'outlet1');
      $outlet->addItem('aaa', $catA->id, 1);
      $outlet->payByCash($foo);
      
      
  }
  
  
  /**
   * Setup to verify that:
   * - Merchant only access his events
   * - outlets availabe for the selected event/tour are shown
   * - No suboutlets are shown
   */
  function test_com_website_setup(){
      $this->clearAll();
      
      $this->loadOulets();
      
      $out_id = $this->createOutlet('Outlet Z', '0010', array('identifier'=>'outlet1'));
      $ganga = $this->createOutlet('1', '0010', array('parent'=>$out_id));
      $pycca = $this->createOutlet('2', '0010', array('parent'=>$out_id));
      $gamma = $this->createOutlet('3', '0010', array('parent'=>$out_id));
      
      $out_x = $this->createOutlet('Outlet X', '0100');
      $this->createOutlet('Outlet Halo', '1000'); //should be unreachable
      
      $seller = $this->createUser('seller');
      
      //return;
      
      $evt = $this->createEvent('ABC', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'abc');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Crystal Palace'));
      $catA = $this->createCategory('Adult', $evt->id, 100);
      $catB = $this->createCategory('Kid',   $evt->id, 50);
      $catC = $this->createCategory('Pet',   $evt->id, 10);
      
      $this->createOutletCommission($out_id, $evt->id, $catB->id, 'p', 10);
      
      $evt = $this->createEvent('Campus Party', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'campus');
      $this->setEventGroupId($evt, '1111'); //gets full list of outlets
      $this->setEventVenue($evt, $this->createVenue('ExpoPlaza'));
      $catA = $this->createCategory('Black Box', $evt->id, 175);
      $catB = $this->createCategory('VIP',   $evt->id, 105);
      
      $this->createOutletCommission($out_x, $evt->id, $catB->id, 'f', 5);
      
      $build = new TourBuilder($this, $seller);
      $build->build();
      
      
      $seller2 = $this->createUser('seller2');
      $evt = $this->createEvent('Galaxy S4 Launch', $seller2->id, $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'g414xy');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $this->createVenue('Quicentro Sur'));
      $catA = $this->createCategory('Foo', $evt->id, 100);
      $catB = $this->createCategory('Bar',   $evt->id, 50);
      
      //Trying to save a non number should fail
      $post = array (
  'page' => 'OutletCommissions',
  'action' => 'save-commissions',
  'event_id' => 'aaa',
  'outlet_id' => '21',
  'cat' => 
  array (
    335 => 
    array (
      'com_type' => 'p',
      'com_value' => '10',
    ),
    336 => 
    array (
      'com_type' => 'f',
      'com_value' => 'asd',
    ),
  ),
);
      //fail string
      $ajax = new \ajax\OutletCommissions();
      $ajax->post = $post;
      $ajax->Process();
      $this->assertFalse($ajax->ok);
      
      //fail negative
      $post['cat'][336]['com_value'] = '-10';
      $ajax = new \ajax\OutletCommissions();
      $ajax->post = $post;
      $ajax->Process();
      $this->assertFalse($ajax->ok);
      
      //accept 0
      $post['cat'][336]['com_value'] = '0';
      $ajax = new \ajax\OutletCommissions();
      $ajax->post = $post;
      $ajax->Process();
      $this->assertTrue($ajax->ok);
      
      //accept ''
      $post['cat'][336]['com_value'] = '';
      $ajax = new \ajax\OutletCommissions();
      $ajax->post = $post;
      $ajax->Process();
      $this->assertTrue($ajax->ok);
      
      //accept decimal
      $post['cat'][336]['com_value'] = 1.5;
      $ajax = new \ajax\OutletCommissions();
      $ajax->post = $post;
      $ajax->Process();
      $this->assertTrue($ajax->ok);
      
      
  }
  
  

  

 
}