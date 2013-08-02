<?php
/**
 * I didn't write the payment part of this module, but since I'm being asked questions about it, I have to test.
 * @author MASTER
 */
use controller\Reservationszreport;
use model\DeliveryMethod;
use model\Eventsmanager;
use tool\Date;
class ReservationsTest extends DatabaseBaseTest{
  
  /**
   * "what could be the reason why 
			a ticket bought from the reservations module 
			could be seen in the "details" of the report in the VAT module, 
			but not in the compiled numbers on top?"
   */
  function testPurchase(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    
    $rsv = new ReservationsModule($this, 'tixpro');
    $this->assertEquals($rsv1, $rsv->getId());
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $rsv->registerCategory($catA->id);
    $rsv->registerCategory($catB->id);
    //return; //Simple fixture
    
    //let's try a partial purchase
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $rsv->payByCash(60);
    
    //rsv_id must be set
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM ticket_transaction WHERE rsv_id=? ", $rsv->getId() ));

    
    //return; //check here that only the partial amount shows up in the zreport
    
    Utils::clearLog();
    //Have another user
    $rsv2 = $this->createReservationUser('tuki', $v1);
    $rsv = new ReservationsModule($this, 'tuki');
    $this->assertEquals($rsv2, $rsv->getId());
    
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $rsv->payByCash(10.50);
    
    //rsv_id must be set
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM ticket_transaction WHERE rsv_id=? ", $rsv->getId() ));
    
    $bar = $this->createUser('bar');
    
    $rsv = new ReservationsModule($this, 'tixpro');
    //If there's a cc error, nothing should be written
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    try {
      $rsv->payByCC($bar, array_merge($this->getCCData(), array('exp_year'=>'2011') ) );  // 2013 - This won't fail when remote gateway is not queried. Local validation has been disabled too!
    } catch (Exception $e) {
    }
    //$this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM reservation_transaction WHERE cancelled=1" )); //line inserted but cancelled // 2013 - Since cc number is not cheked, this assertion fails.
    
    //Let's do a full cc payment while we're at it
    
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $rsv->payByCC($bar, $this->getCCData());
    $this->assertRows(4, 'reservation_transaction');
    
  }
  
  function testPartialPayment(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $txn_id = $rsv->payByCash(60);
    
    
    //rsv_id must be set
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM ticket_transaction WHERE rsv_id=? ", $rsv->getId() ));
    $this->assertRows(1, 'reservation_transaction');
    
    $this->assertEquals(40, $rsv->getBalance($txn_id));
    
    //Complete the payment
    Utils::clearLog();
    $rsv->completePayment($txn_id);
    $this->assertEquals(0, $rsv->getBalance($txn_id));
    $this->assertRows(2, 'reservation_transaction');
    /*$rsv = new ReservationsModule($this, 'tuki');
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $rsv->payByCash(60);*/
    
     
  }
  
  /**
   * Of a 100.00 payment, 60 is paid on rsv1, and 40 is paid on rsv2
   * -> 60 should appear on the zreport of rsv1
   * -> 40 should appear on the zreport of rsv2
   * 
   */
  function testDifferentReservationsPartialPayment(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $txn_id = $rsv->payByCash(60);
    
    
    //rsv_id must be set
    $this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM ticket_transaction WHERE rsv_id=? ", $rsv->getId() ));
    $this->assertRows(1, 'reservation_transaction');
    
    $this->assertEquals(40, $rsv->getBalance($txn_id));
    $this->assertEquals(60.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    //Complete the payment
    $rsv = new ReservationsModule($this, 'tuki');
    Utils::clearLog();
    $rsv->completePayment($txn_id);
    $this->assertRows(2, 'reservation_transaction');
    
    //inpsect zreport
    $this->assertEquals(40.00, $rsv->getZReportGlobalTotalIncludedRemittance());
     
  }
  
  /**
   * The purchase has 2x50.00 tickets. Total amount is 100.00, however only 70.00 is paid. Zreport should show a total of 70.00  
   * Enter description here ...
   */
  function testTwoTickets(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $evt = $this->createEvent('Bootcamp', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'mmm');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('JAVA', $evt->id, 100);
    $catB = $this->createCategory('NET'  , $evt->id, 50);
    
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('nnn', $catB->id, 2);
    Utils::clearLog();
    $txn_id = $rsv->payByCash(70);
    
  
    $this->assertEquals(30, $rsv->getBalance($txn_id));
    $this->assertEquals(70.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    //let's go grazy
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('nnn', $catA->id, 2);
    $rsv->addItem('nnn', $catB->id, 1);
    Utils::clearLog();
    $txn_id = $rsv->payByCash(30); //Bill is 250, but let's pay just 30
    $this->assertEquals(220.00, $rsv->getBalance($txn_id));
    $this->assertEquals(100.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    //return;
    
    //complete that last transaction
    $rsv->completePayment($txn_id);
    $this->assertEquals(320.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    
  }
  
  /**
   *  Q:
   * "Suppose you have in the same transaction tickets of event 1 and event 2.
      The total amount is $100. But you do a partial payment of just $10.00
      Where are those $10 reported at? In the event 1 block or the event 2 block?
      "
   *  A:
   *  "The simplest way is the better way, but it can cause a little bit of problem if we're not careful.
      In this case, we should send $5 on each event. Divide the deposit by the number of events... it would be easier to have the balance in the transaction without having to care where the money is going to
      "
   * 
   */
  function testMultipeEvents(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $evt = $this->createEvent('Bootcamp', 'seller', $this->createLocation()->id );
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('JAVA', $evt->id, 100);
    $catY = $this->createCategory('NET'  , $evt->id, 50);
    
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('aaa', $catB->id, 1);
    $rsv->addItem('bbb', $catY->id, 1);
    Utils::clearLog();
    $txn_id = $rsv->payByCash(10); //balance is 100
    
  
    $this->assertEquals(90, $rsv->getBalance($txn_id));
    $this->assertEquals(10.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    //let's go grazy
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('aaa', $catA->id, 2);
    $rsv->addItem('aaa', $catB->id, 1);
    Utils::clearLog();
    $txn_id = $rsv->payByCash(30); //Bill is 250, but let's pay just 30
    $this->assertEquals(220.00, $rsv->getBalance($txn_id));
    $this->assertEquals(40.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    //return;
    
    //complete that last transaction
    $rsv->completePayment($txn_id);
    $this->assertEquals(260.00, $rsv->getZReportGlobalTotalIncludedRemittance());
    
    
  }
  
  /**
   * website/reservationssearch
   * http://jira.mobination.net:8080/browse/TIXCAR-341
   * "All payments are in the past, but the date of the event might be in the past, present or future"
   * 
   * 
   */
  function testSearchList(){
    $this->clearAll();
    
    $this->db->beginTransaction();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Parque Histórico', 'seller', $this->createLocation()->id, '2012-10-10' ); //Past
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100, 1250);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $evt = $this->createEvent('Bootcamp', 'seller', $this->createLocation()->id ); //Present
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('JAVA', $evt->id, 100);
    $catY = $this->createCategory('NET'  , $evt->id, 50);
    
    //Tours are future
    $build = new TourBuilder( $this, $seller);
    $build->event_id = 'fu7ur3';
    $build->build();
    
    $cats = $build->categories;
    $catTA = $cats[1]; //the 100.00 one, yep, peeking at the data to find it.
    $catTB = $cats[0];
    
    //****
    $foo = $this->createUser('foo');
    
    //Let's create pending and complete transaction for event in the past
    $outlet = new OutletModule($this->db, 'outlet1');
    
    //For pagination testing - let's have a lot of resutls;
    /*$n = 30;
    for ($i = 1; $i<=$n; $i++){
      $outlet->addItem('aaa', $catA->id, rand(1,3));
      $outlet->date = '2012-09-05 11:30';
      $outlet->payByCash($foo);
    }
    */
    
    //Past - Paid
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->date = '2012-10-05 11:30';
    $outlet->payByCash($foo);
    
    //past, partial paid
    $outlet->addItem('aaa', $catB->id, 1);
    $outlet->date = '2012-10-06 15:30';
    $outlet->payByCash($foo, 10);
    
   
    //Future - Completed
    $outlet->addItem('tour1', $catTA->id, 1);
    $outlet->payByCash($foo);
    
    //Future - Partial
    $outlet->addItem('tour2', $catTB->id, 1);
    $outlet->payByCash($foo, 10);
    
    //Present - Completed
    $outlet->addItem('bbb', $catX->id, 1);
    $outlet->payByCash($foo);
    
    //Present - Parial
    $outlet->addItem('bbb', $catX->id, 1);
    $outlet->payByCash($foo, 10);
    
    $this->db->commit();
    
  }
  /*
  function testCC(){
    
  }*/
  
  
  function testCCSecondPayment(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Star Wars Con', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 110);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $foo = $this->createUser('foo');
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->date = '2012-10-10';
    $outlet->addItem('nnn', $catA->id, 1);
    $txn_id = $outlet->payByCash($foo, 10 );
    
    //later on
    $rsv = new ReservationsModule($this, 'tixpro');
    Utils::clearLog();
    $txn_id = $rsv->completePaymentByCC($txn_id, $foo, $this->getCCData()); //we should be able to default foo to the previous payment user
    
    
    //rsv_id must be set
    /*$this->assertEquals(1, $this->db->get_one("SELECT COUNT(*) FROM ticket_transaction WHERE rsv_id=? ", $rsv->getId() ));
    $this->assertRows(1, 'reservation_transaction');
    
    $this->assertEquals(40, $rsv->getBalance($txn_id));
    
    //Complete the payment
    Utils::clearLog();
    $rsv->completePayment($txn_id);*/
    $this->assertEquals(0, $rsv->getBalance($txn_id));
    $this->assertRows(1, 'reservation_transaction');
    /*$rsv = new ReservationsModule($this, 'tuki');
    $rsv->addItem('nnn', $catA->id, 1);
    Utils::clearLog();
    $rsv->payByCash(60);*/
    
     
  }
  
   
  function testRounding(){
    $this->clearAll();
    
    $this->db->beginTransaction();
    
    $v1 = $this->createVenue('Pool');
    
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    $rsv2 = $this->createReservationUser('tuki', $v1);
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
    
    $evt = $this->createEvent('Parque Histórico', 'seller', $this->createLocation()->id, '2012-10-10' ); //Past
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    $evt = $this->createEvent('Bootcamp', 'seller', $this->createLocation()->id ); //Present
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catX = $this->createCategory('JAVA', $evt->id, 100);
    $catY = $this->createCategory('NET'  , $evt->id, 50);
    
    //Tours are future
    $build = new TourBuilder( $this, $seller);
    $build->event_id = 'fu7ur3';
    $build->build();
    
    $cats = $build->categories;
    $catTA = $cats[1]; //the 100.00 one, yep, peeking at the data to find it.
    $catTB = $cats[0];
    
    //Add  3 categories, pay just 100.00. Ensure that parts are 33.33, 33.33, 33.34, and not 3x33.3333
    $rsv = new ReservationsModule($this, 'tixpro');
    $rsv->addItem('aaa', $catA->id, 1);
    $rsv->addItem('bbb', $catX->id, 1);
    $rsv->addItem('tour2', $catTA->id, 1);
    $rsv->payByCash(100);
    
    $this->db->commit();
  }
  
  
  function testValidation(){
      
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      
      $rsv1 = $this->createReservationUser('tixpro', $v1);
      
      $rsv = new ReservationsModule($this, 'tixpro');
      $this->assertEquals($rsv1, $rsv->getId());
      
      $out1 = $this->createOutlet('Outlet 1', '0010');
      
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      $bo_id = $this->createBoxoffice('xbox', $seller->id); //nice to have
      
      $evt = $this->createEvent('Normal Event', 'seller', $this->createLocation()->id );
      $this->setEventId($evt, 'nnn');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('VIP ADULT', $evt->id, 100);
      $catB = $this->createCategory('KID'  , $evt->id, 50);
      
      $rsv->registerCategory($catA->id);
      $rsv->registerCategory($catB->id);
      //return; //Simple fixture
      
      //let's try a partial purchase
      $rsv->addItem('nnn', $catA->id, 1);
      Utils::clearLog();
      $rsv->payByCash(100); //fully paid
      
      $this->db->update('ticket', array('code' => 'XXX' ), " 1 LIMIT 1");
      
      return; //test fixture. we should have a validable ticket in place
      Utils::clearLog();
      
      
      
  }
  
  /**
   * has_ccfee = 0
   */
  function testNoCCFee(){
  	
  	$this->clearAll();
  	
  	$foo = $this->createUser('foo');
  	$v1 = $this->createVenue('Pool');
  	$out1 = $this->createOutlet('Outlet 1', '0010');
  	$seller = $this->createUser('seller');
  	$this->setUserHomePhone($seller, '111');
  	$bo_id = $this->createBoxoffice('xbox', $seller->id);
  	$rsv1 = $this->createReservationUser('tixpro', $v1);
  	
  	
  	//has_ccfee = 1
  	$evt = $this->createEvent('Circus Carnival (has ccfees)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
  	$this->setEventId($evt, 'saber');
  	$this->setEventGroupId($evt, '0010');
  	$this->setEventVenue($evt, $v1);
  	$catA = $this->createCategory('SAIBAH', $evt->id, 100);
  	ModuleHelper::showEventInAll($this->db, $evt->id);
  	
  	
  	//Event no ccfee
  	$evt = $this->createEvent('Swiming competition (No ccfees)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
  	$this->setEventId($evt, 'aaa');
  	$this->setEventGroupId($evt, '0010');
  	$this->setEventVenue($evt, $v1);
  	$this->setEventParams($evt->id, array('has_ccfee'=>0));
  	$catA = $this->createCategory('RAGE ON', $evt->id, 100);
  	ModuleHelper::showEventInAll($this->db, $evt->id);
  	
  	
  	$rsv = new ReservationsModule($this, 'tixpro');
  	$rsv->addItem('aaa', $catA->id, 1);
  	Utils::clearLog();
  	$rsv->payByCC($foo, $this->getCCData());
  	
  	//expect no cc fees in transaction
  	$this->assertEquals(0, $this->db->get_one("SELECT fee_cc FROM ticket_transaction LIMIT 1"));
  	//expect no cc fees in optimal_transaction
  	$this->assertEquals(100/2, $this->db->get_one("SELECT amount FROM transactions_processor LIMIT 1"));
  	$this->assertEquals(100/2, $this->db->get_one("SELECT amount FROM transactions_optimal LIMIT 1"));
  	
  }
  
  /**
   * has_ccfee = 0
   * We purchase a 100.00 ticket in the oultet. We make a partial 20.00 payment. Balance should be 80.00
   * Later on, on reservation, we try to pay the remaining 80.00.
   * It should charge only 40.00 USD in transactions_processor/transactions_optimal
   * 
   */
  function test_partial_outlet(){
  	$this->clearAll();
  	 
  	$foo = $this->createUser('foo');
  	$v1 = $this->createVenue('Pool');
  	$out1 = $this->createOutlet('Outlet 1', '0010');
  	$seller = $this->createUser('seller');
  	$this->setUserHomePhone($seller, '111');
  	$bo_id = $this->createBoxoffice('xbox', $seller->id);
  	$rsv1 = $this->createReservationUser('tixpro', $v1);
  	 
  	//Event no ccfee
  	$evt = $this->createEvent('Swiming competition (No ccfees)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
  	$this->setEventId($evt, 'aaa');
  	$this->setEventGroupId($evt, '0010');
  	$this->setEventVenue($evt, $v1);
  	$this->setEventParams($evt->id, array('has_ccfee'=>0));
  	$catA = $this->createCategory('RAGE ON', $evt->id, 100);
  	 
  	ModuleHelper::showEventInAll($this->db, $evt->id);
  	
  	$out = new OutletModule($this->db, 'outlet1');
  	$out->addItem('aaa', $catA->id, 1);
  	$txn_id = $out->payByCash($foo, 20.00);
  	
  	//return; //manual test
  	
  	$rsv = new ReservationsModule($this, 'tixpro');
  	Utils::clearLog();
  	$rsv->completePaymentByCC($txn_id, $foo, $this->getCCData());
  	
  	//expect no cc fees in transaction
  	$this->assertEquals(0, $this->db->get_one("SELECT fee_cc FROM ticket_transaction LIMIT 1"));
  	//expect no cc fees in optimal_transaction
  	$this->assertEquals(80/2, $this->db->get_one("SELECT amount FROM transactions_processor LIMIT 1"));
  	$this->assertEquals(80/2, $this->db->get_one("SELECT amount FROM transactions_optimal LIMIT 1"));
  	
  	
  }
  
  
  /**
   * has_ccfee = 1
   * We purchase a 100.00 ticket in the oultet. We make a partial 30.00 payment. Balance should be 70.00
   * Later on, on reservation, we try to pay the remaining 70.00.
   * It should charge 35.00 USD + the fraction corresponding to the remaining 70.00 BBD, 
   * fees on 70.00 would be 3.25 as of 27/jul/2013
   *
   */
  function test_partial_outlet_fee(){
  	$this->clearAll();
  
  	$foo = $this->createUser('foo');
  	$v1 = $this->createVenue('Pool');
  	$out1 = $this->createOutlet('Outlet 1', '0010');
  	$seller = $this->createUser('seller');
  	$this->setUserHomePhone($seller, '111');
  	$bo_id = $this->createBoxoffice('xbox', $seller->id);
  	$rsv1 = $this->createReservationUser('tixpro', $v1);
  
  	//Event has_ccfee = 1
  	$evt = $this->createEvent('Pizza Express (has ccfees)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
  	$this->setEventId($evt, 'aaa');
  	$this->setEventGroupId($evt, '0010');
  	$this->setEventVenue($evt, $v1);
  	$catA = $this->createCategory('RAGE ON', $evt->id, 100);
  
  	ModuleHelper::showEventInAll($this->db, $evt->id);
  	 
  	$out = new OutletModule($this->db, 'outlet1');
  	$out->addItem('aaa', $catA->id, 1);
  	$txn_id = $out->payByCash($foo, 30.00);
  	 
  	//return; //manual test
  	 
  	$rsv = new ReservationsModule($this, 'tixpro');
  	Utils::clearLog();
  	$rsv->completePaymentByCC($txn_id, $foo, $this->getCCData());
  	 
  	//expect no cc fees in transaction
  	$ccfee = 3.25;
  	$this->assertEquals($ccfee, $this->db->get_one("SELECT fee_cc FROM ticket_transaction LIMIT 1"), '', 0.01);
  	//expect no cc fees in optimal_transaction
  	$amount_usd = (70+$ccfee)/2;
  	$this->assertEquals($amount_usd, $this->db->get_one("SELECT amount FROM transactions_processor LIMIT 1"), '', .01);
  	$this->assertEquals($amount_usd, $this->db->get_one("SELECT amount FROM transactions_optimal LIMIT 1"), '', .01);
  	 
  	 
  }
  
  /**
   * "A search in the Reservation module should only show results for the logged in user's Venue association"
   * http://jira.mobination.net:8080/browse/TIXCAR-475
   */
  function test_venue_restricted_search_results(){
      $this->clearAll();
  
      $foo = $this->createUser('foo');
      $v1 = $this->createVenue('Pool');
      $v2 = $this->createVenue('Stadium');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      $this->setUserHomePhone($seller, '111');
      $bo_id = $this->createBoxoffice('xbox', $seller->id);
      $rsv1 = $this->createReservationUser('tixpro', $v1);
      //$rsv1 = $this->createReservationUser('tixpro', $v1);
  
      $evt = $this->createEvent('Pool Activity', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('RAGE ON', $evt->id, 100);
      ModuleHelper::showEventInAll($this->db, $evt->id);
      
      
      $evt = $this->createEvent('Rock Star concert', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'bbb');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v2);
      $catB = $this->createCategory('Pista', $evt->id, 100);
      ModuleHelper::showEventInAll($this->db, $evt->id);
  
      $out = new OutletModule($this->db, 'outlet1');
      $out->addItem('aaa', $catA->id, 1);
      $txn_id = $out->payByCash($foo);
      
      $out->addItem('bbb', $catB->id, 1);
      $txn_id = $out->payByCash($foo);
      
      Utils::clearLog();
      
      //If I login as tixpro, I should only see the first transaction
      $res = \model\TransactionsManager::searchPendingTransactions('foo', false, $rsv1);
      Utils::log(__METHOD__ . print_r($res, true));
      
      $this->assertEquals(1, count($res)); //we expect to have only one result. the other transction is not visible because of the venue association.
      
      

  }
  
  
  
 
}