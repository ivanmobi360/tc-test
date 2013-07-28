<?php

class CheckoutTest extends DatabaseBaseTest{
  
  function testPurchase(){
    $this->clearAll();
    
    //create buyer
    $user = $this->createUser('foo');
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    
    $seller = $this->createUser('seller');
    
    // **********************************************
    // Eventually this test will break for the dates
    // **********************************************
    $evt = $this->createEvent('Elecciones 2013', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    
    $client = new \WebUser($this->db);
    $client->login($user->username);
    $client->addToCart($evt->id, $catA->id, 1); //cart in session
    //$client->addReminder($evt->id, 'sms', 'foo@blah.com', '2013-07-28 20:53:50');
    Utils::clearLog();
    $this->clearRequest();
    $_POST = $this->getRequest( $client->getRemindersData() );
    $page = new \controller\Checkout();
    
    
    //we do expect cc fees - money equality fails if reminders are added
    $ccfee = $this->db->get_one("SELECT fee_cc FROM ticket_transaction LIMIT 1");
    $this->assertTrue( $ccfee > 0 );
    $amt = 100 + $ccfee;
    $this->assertEquals($amt/2, $this->db->get_one("SELECT amount FROM transactions_processor LIMIT 1"), '', 0.01);
    $this->assertEquals($amt/2, $this->db->get_one("SELECT amount FROM transactions_optimal LIMIT 1"), '', 0.01);
  }
  
  protected function getRequest($params = array()){
    $data = array(
      'cc_name_on_card' => 'CHUCK NORRIS'
      , 'pay_cc' => 'on'
    );
    
    $data = array_merge($this->getCCPurchaseData(), $data, $params);
    return $data;
  }
  
  /**
   * has_ccfee=0 test
   */
  function testNoCCfee(){
      $this->clearAll();
      
      //create buyer
      $user = $this->createUser('foo');
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      // **********************************************
      // Eventually this test will break for the dates
      // **********************************************
      $evt = $this->createEvent('Elecciones 2013', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('SWIM', $evt->id, 100);
      
      $client = new \WebUser($this->db);
      $client->login($user->username);
      $client->addToCart($evt->id, $catA->id, 1);
      
        Utils::clearLog();
        $this->clearRequest();
        $_POST = $this->getRequest( $client->getRemindersData() );
        $page = new \controller\Checkout();
        
      //expect no cc fees in transaction
      $this->assertEquals(0, $this->db->get_one("SELECT fee_cc FROM ticket_transaction LIMIT 1"));
      //expect no cc fees in optimal_transaction
      $this->assertEquals(100/2, $this->db->get_one("SELECT amount FROM transactions_processor LIMIT 1"));
      $this->assertEquals(100/2, $this->db->get_one("SELECT amount FROM transactions_optimal LIMIT 1"));
      
  }
  
  /**
   * has_ccfee=0 test
   */
  function testTourNoCCfee(){
      $this->clearAll();
  
      //create buyer
      $user = $this->createUser('foo');
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');

      $build = new TourBuilder( $this, $seller);
      $build->event_id = 'tourtpl';
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      $this->setEventParams($build->event_id, array('has_ccfee' => 0));
  
      $client = new \WebUser($this->db);
      $client->login($user->username);
      $client->addToCart('tour1', $catA->id, 1);
      //$client->addToCart('tour2', $catB->id, 1);
  
      Utils::clearLog();
      $this->clearRequest();
      $_POST = $this->getRequest( $client->getRemindersData() );
      $page = new \controller\Checkout();
  
      //expect no cc fees in transaction
      $this->assertEquals(0, $this->db->get_one("SELECT fee_cc FROM ticket_transaction LIMIT 1"));
      $this->assertEquals(100/2, $this->db->get_one("SELECT amount FROM transactions_processor LIMIT 1")); //tour1
      $this->assertEquals(100/2, $this->db->get_one("SELECT amount FROM transactions_optimal LIMIT 1")); //tour1
  
  }
  
  //
  function testViewSetup(){
      
      $this->clearAll();
      
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
      $build->event_id = 'tourtpl';
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      $this->setEventParams($build->event_id, array('has_ccfee' => 0));
      
      
      //Tour has_ccfee=1
      $build = new TourBuilder( $this, $seller);
      $build->template_name = 'Wolverine Template (teh fees)';
      $build->name = 'Wolverine Display (has ccfees)';
      $build->event_id = 'wolvietp';
      $build->pre = 'jack';
      $build->build();
      $cats = $build->categories;
      $catA = $cats[1]; //the 100.00 one, yep, cheating
      $catB = $cats[0];
      //$this->setEventParams($build->event_id, array('has_ccfee' => 0));
      
      //Event no ccfee
      $evt = $this->createEvent('Swiming competition (No ccfees)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $this->setEventParams($evt->id, array('has_ccfee'=>0));
      $catA = $this->createCategory('RAGE ON', $evt->id, 100);
      
      //Event with ccfees
      $evt = $this->createEvent('Amazon Purchase (ccfees apply)', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'ccc');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('Redbirth seats', $evt->id, 100);
      
      /*
      \OutletModule::showEventIn($this->db, 'aaa', $out1);
      \OutletModule::showEventIn($this->db, 'ccc', $out1);
      \BoxOfficeModule::showEventIn($this->db, 'aaa', $bo_id);
      \BoxOfficeModule::showEventIn($this->db, 'ccc', $bo_id);
      \ReservationsModule::showEventIn($this->db, 'aaa', $rsv1);
      \ReservationsModule::showEventIn($this->db, 'ccc', $rsv1);*/
      ModuleHelper::showEventInAll($this->db, 'aaa');
      ModuleHelper::showEventInAll($this->db, 'ccc');
  }
  

 
}