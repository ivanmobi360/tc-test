<?php


use tool\Request;

class VatTest extends DatabaseBaseTest{
  
  protected function fixture(){
    //let's create some events
    $this->clearAll();
    
    $this->createUser('foo');


    $this->seller = $this->createUser('seller');
    $loc = $this->createLocation();
    //$loc->
    $evt = $this->createEvent('Barcelona vs Real Madrid', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'aaa');
    $this->catA = $this->createCategory('Category A', $evt->id, 25.00, 100);
    $this->catB = $this->createCategory('Category B', $evt->id, 10.00);
    $this->catC = $this->createCategory('Category C', $evt->id, 5.00);
    $this->setEventOtherTaxes($evt, 'VAT', 17.5, 'barbud4as');
    
    $loc = $this->createLocation();
    $evt = $this->createEvent('Water March', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'bbb');
    $this->createCategory('Zamora Branch', $evt->id, 14.00);
    
    $evt = $this->createEvent('Third Event', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'ccc');
    $this->createCategory('Heaven', $evt->id, 22.50);
    $this->createCategory('Limbo', $evt->id, 22.50);
    
    
    $this->seller = $this->createUser('seller2');
    $loc = $this->createLocation();
    $evt = $this->createEvent('Transformers Con', $this->seller->id, $loc->id, date('Y-m-d H:i:s', strtotime('+1 day')));
    $this->setEventId($evt, 'ttt');
    $this->createCategory('Autobots', $evt->id, 55.00);
  }
  
  public function testList(){
    $this->fixture();
    
    $this->db->beginTransaction();
    for ($i = 1; $i <=130; $i++ ){
      $end = $i + 5;
      $evt = $this->createEvent("Event $i", $this->seller->id, 1
                                , date('Y-m-d H:i:s', strtotime("+$i day"))
                                , false
                                , date('Y-m-d H:i:s', strtotime("+$end day"))
                                );
    }
    $this->db->commit();
    
  }
  
  function testBit(){
    $this->clearAll();
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('UNO', 'seller', 1);
    $this->setEventGroupId($evt->id, '0001');
    
    $evt = $this->createEvent('DOS', 'seller', 1);
    $this->setEventGroupId($evt->id, '0010');
    
    $evt = $this->createEvent('CUATRO', 'seller', 1);
    $this->setEventGroupId($evt->id, '0100');
    
    $evt = $this->createEvent('OCHO', 'seller', 1);
    $this->setEventGroupId($evt->id, '1000');
    
    $evt = $this->createEvent('SIETE', 'seller', 1);
    $this->setEventGroupId($evt->id, '0111');
  }
  
  function testStats(){
    $this->fixture();
    $bar = $this->createUser('bar');
    $this->buyTicketsWithCC('foo', $this->catA->id, 5);
    $this->buyTicketsWithCC('bar', $this->catB->id, 3);
  }
  
  function testSales(){
    $this->clearAll();
    
    $out1 = $this->createOutlet('Outlet 1', '0001');
    $out2 = $this->createOutlet('Outlet 2', '0010');
    $out3 = $this->createOutlet('Outlet 3', '0011');
    
    $this->createOutlet('Outlet 4', '0100');
    $this->createOutlet('Outlet 8', '1000');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1"), '09:00', $this->dateAt("+3"), '20:00');
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0011');
    $this->setEventOtherTaxes($evt, 'VAT', 17.5, 'c4r1b3an');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    //$this->setEventParams($evt->id, array('capacity'=>69));
    $catA = $this->createCategory('SILVER', $evt->id, 100, 20);
    $catB = $this->createCategory('GOLD', $evt->id, 150, 15);
    $evt1 = $evt;
    
    
    $evt = $this->createEvent('Otro Evento', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '1100');
    $catX = $this->createCategory('Cat X', $evt->id, 100);
    $catY = $this->createCategory('Cat Y', $evt->id, 20);
    $evt2 = $evt;
    
    //tickets
    $this->createPromocode("COED", $catA, 50, 'p');
    $this->createPromocode("COMP", $catA, 100, 'p', true);
    
    
    $foo = $this->createUser('foo');
    //$bar = $this->createUser('bar');
    
    //return; //no activity
    
    //let's try comp code purchase of tickets on website for now
    /*
    $site = new WebUser($this->db);
    $site->login($foo->username) ;
    $site->addToCart($catA->id, 1, 'COMP');
    Utils::clearLog();
    $site->getTickets(); 
    */
    
    //now have a discounted purchase
    
    
    
    //return;
    
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt1->id, $catA->id, 3);
    $outlet->payByCash($foo);
    //return;
    
    $bar = $this->createUser('bar');
    $outlet->login('outlet2');
    $outlet->addItem($evt1->id, $catB->id, 2);
    $outlet->payByCash($bar);
    
    
    $baz = $this->createUser('baz');
    $outlet->login('outlet1');
    $outlet->addItem($evt1->id, $catB->id, 3);
    $outlet->payByCash($baz);
    
    /*
    $paz = $this->createUser('paz');
    $outlet->login('outlet2');
    $outlet->addItem($catB->id, 1);
    $outlet->payByCash($paz);
    //$this->buyTicketsWithCC('paz', $catB->id, 1, $out2);
    
    $liz = $this->createUser('liz');
    $outlet->login('outlet3');
    $outlet->addItem($catB->id, 4);
    $outlet->payByCash($liz);
    //$this->buyTicketsWithCC('liz', $catB->id, 4, $out3);
    
    
    $zod = $this->createUser('zod');
    $outlet->login('outlet1');
    $outlet->addItem($catX->id, 1);
    $outlet->payByCash($zod);
    //$this->buyTicketsWithCC('zod', $catX->id, 1, $out1);
    */
    
    //let's create 50 outlets (for 2 columns popup display test)
    //$this->createOutlets();
    
  }
  
  protected function createOutlets(){
    $this->db->beginTransaction();
    for ($i = 10; $i<=100; $i++){
      $this->createOutlet("Outlet $i", '0010');
    }
    $this->db->commit();
  }
  
  /*
   * fixture for this:
   * they want to see all 
      •	discounted tickets and 
      •	complimentary tickets separately
   for the first report on top it means that each category will end up taking three lines
   for the details below, it means that each outlet will have three lines also
   */
  function testDiscounts(){
    
    $this->clearAll();
    
    $out1 = $this->createOutlet('Outlet 1', '0001');
    $out2 = $this->createOutlet('Outlet 2', '0010');
    $out3 = $this->createOutlet('Outlet 3', '0011');
    
    $this->createOutlet('Outlet 4', '0100');
    $this->createOutlet('Outlet 8', '1000');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1"), '09:00', $this->dateAt("+3"), '20:00');
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0011');
    $this->setEventOtherTaxes($evt, 'VAT', 17.5, 'c4r1b3an');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    $catA = $this->createCategory('SILVER', $evt->id, 100, 20);
    $catB = $this->createCategory('GOLD', $evt->id, 150, 15);
    $evt1 = $evt;
    
    
    $evt = $this->createEvent('Otro Evento', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '1100');
    $catX = $this->createCategory('Cat X', $evt->id, 100);
    $catY = $this->createCategory('Cat Y', $evt->id, 20);
    $evt2 = $evt;
    
    //promocodes
    $this->createPromocode("COED", $catA, 50, 'p');
    $this->createPromocode("COMP", $catA, 100, 'p', true);
    $this->createPromocode("FREE", $catB, 100, 'p', true);
    
    
    $foo = $this->createUser('foo');
    $bar = $this->createUser('bar');
    $baz = $this->createUser('baz');
    
    //return; //no activity
    
    //Outlet can't do complimentary, so use website for this one
    $site = new WebUser($this->db);
    $site->login($foo->username) ;
    $site->addToCart($evt1->id,  $catA->id, 1, 'COMP');
    Utils::clearLog();
    $site->getTickets(); 
    $site->logout();
    
    //return;
   
    $outlet = new OutletModule($this->db, 'outlet1'); Utils::clearLog();
    $outlet->addItem($evt1->id, $catA->id, 1); //Utils::clearLog();
    $outlet->applyPromoCode($evt1->id/*'aaa'*/, 'COED');
    $res = $outlet->payByCash($bar);
    //Utils::log(__METHOD__ . "cart result: " . print_r($res, true));
    
    //outlet full payment
    $outlet = new OutletModule($this->db, 'outlet1'); Utils::clearLog();
    $outlet->addItem($evt1->id, $catA->id, 2);
    $res = $outlet->payByCash($baz);
    
    
    //have another free one for kicks
    $site = new WebUser($this->db);
    $site->login($foo->username) ;
    $site->addToCart($evt1->id, $catB->id, 1, 'FREE');
    Utils::clearLog();
    $site->getTickets(); 
    $site->logout();
    
    //make last one 'printed' for laughs; //Not anymore - Use a proper PRINTED fixture of you want to have printed tickets in the report
    /*$id = $this->db->get_one("SELECT id FROM ticket ORDER BY ID DESC LIMIT 1");
    $this->db->update('ticket', array('printed'=>1, 'paid'=>0), "id=?", $id);
    */
  }
  
  /**
   * " for the details below, it means that each outlet will have three lines also"
   * "normal, complimentary, discount
   */
  function testOutletWithQualifiers(){
    
    $this->clearAll();
    
    $out1 = $this->createOutlet('Outlet 1', '0001');
    $out2 = $this->createOutlet('Outlet 2', '0010');
    $out3 = $this->createOutlet('Sigma', '0010', array('type'=>'W'));
    
    /*
    $this->createOutlet('Outlet 4', '0100');
    $this->createOutlet('Outlet 8', '1000');
    */
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1"), '09:00', $this->dateAt("+3"), '20:00');
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0011');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    $catA = $this->createCategory('Kid', $evt->id, 100, 20);
    $catB = $this->createCategory('Adult', $evt->id, 150, 15);
    
    
    $evt = $this->createEvent('Otro Evento', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '1100');
    $catX = $this->createCategory('Cat X', $evt->id, 100);
    $catY = $this->createCategory('Cat Y', $evt->id, 20);
    
    //promocodes
    $this->createPromocode("COED", $catA, 50, 'p');
    $this->createPromocode("COMP", $catA, 100, 'p', true);
    $this->createPromocode("FREE", $catB, 100, 'p', true);
    
    
    $foo = $this->createUser('foo');
    $bar = $this->createUser('bar');
    $baz = $this->createUser('baz');
    
    //return; //no activity
    
    //Outlet can't do complimentary, so use website for this one
    $site = new WebUser($this->db);
    $site->login($foo->username) ;
    $site->setOutletId($out3); //this will appear as the outlet sale
    $site->addToCart('aaa', $catA->id, 1, 'COMP');
    Utils::clearLog();
    $site->getTickets(); 
    $site->logout();
    
    
    $outlet = new OutletModule($this->db, 'outlet1'); Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1); //Utils::clearLog();
    $outlet->applyPromoCode('aaa', 'COED');
    $res = $outlet->payByCash($bar);
    
    //outlet full payment
    $outlet = new OutletModule($this->db, 'outlet1'); Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $res = $outlet->payByCash($baz);
    
    /*
    //have another free one for kicks
    $site = new WebUser($this->db);
    $site->login($foo->username) ;
    $site->addToCart($catB->id, 1, 'FREE');
    Utils::clearLog();
    $site->getTickets(); 
    $site->logout();
    */    

  }
  
  
  /**
   *  "the tixpro website must be counted as an outlet as well for the VAT report,
   *  that mean that any sale for that event where ticket_transaction.outlet_id = 0 
   *  needs to be shown in the VAT report as if it was the outlet "www.tixprocaribbean.com" "
   *  
   *  "also Venue report: you have to add the www.tixprocaribbean.com as a virtual outlet to show everything that was bought on the website (ie no outlet_id)"
   *  
   */
  function test_website_is_outlet(){
    $this->clearAll();
    
    //$out1 = $this->createOutlet('Outlet 1', '0001');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1"), '09:00', $this->dateAt("+3"), '20:00');
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0011');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    $catA = $this->createCategory('Kid', $evt->id, 100);
    $catB = $this->createCategory('Adult', $evt->id, 150);
    
    
    $evt = $this->createEvent('Otro Evento', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '1100');
    $catX = $this->createCategory('Cat X', $evt->id, 100);
    $catY = $this->createCategory('Cat Y', $evt->id, 20);
    
    //promocodes
    $this->createPromocode("COED", $catA, 50, 'p');
    $this->createPromocode("COMP", $catA, 100, 'p', true);
    $this->createPromocode("FREE", $catB, 100, 'p', true);
    
    
    $foo = $this->createUser('foo');
    $bar = $this->createUser('bar');
    $baz = $this->createUser('baz');
    
    //return; //no activity
    
    //Complimentary, so use website for this one
    $site = new WebUser($this->db);
    $site->login($foo->username) ;
    $site->addToCart('aaa', $catA->id, 1, 'COMP');
    $site->getTickets(); 
    $site->logout();
    
    //return; //complimentary only
    
    //Normal
    $this->buyTicketsWithCC($bar->id, 'aaa', $catB->id);
    
    //Discount
    $site = new WebUser($this->db);
    $site->login($bar->username) ;
    $site->addToCart('aaa', $catA->id, 1, 'COED');
    $site->payByCash($site->placeOrder()); 
    $site->logout();
  }


  function testEventsByVenue(){
    //fixture to test "Number of events held by the merchant, by venue"
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    
    $v1 = $this->createVenue('Piscina Olimpica');
    $v2 = $this->createVenue("Comedor");
    
    $evt = $this->createEvent('Carrera', 'seller', $this->createLocation()->id);
    $this->setEventVenue($evt, $v1 );
    
    
    $evt = $this->createEvent('Olympic', 'seller', $this->createLocation()->id);
    $this->setEventVenue($evt, $v1 );
    
    $evt = $this->createEvent('Torneo', 'seller', $this->createLocation()->id);
    $this->setEventVenue($evt, $v1 );
    
    $evt = $this->createEvent('Almuerzo', 'seller', $this->createLocation()->id);
    $this->setEventVenue($evt, $v2 );
    
    
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Feriado', $seller->id, $this->createLocation()->id);
    $this->setEventVenue($evt, $this->createVenue('Garage') );

  }
  
  /**
   * Verify cancelled tickets are not shown in any ticket
   */
  function test_cancelled_transaction_is_not_counted_in_any_report(){
    $this->clearAll();
    
    $out1 = $this->createOutlet('Outlet 1', '0001');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Ecuador Vs Venezuela', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0001');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    $catA = $this->createCategory('Adult', $evt->id, 100);
    $catB = $this->createCategory('Kid', $evt->id, 50);
    
    //tickets
    $this->createPromocode("COED", $catA, 50, 'p');
    $this->createPromocode("COMP", $catA, 100, 'p', true);
    
    
    $foo = $this->createUser('foo');

    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $catA->id, 1);
    $outlet->addItem($evt->id, $catB->id, 2);
    $txn_id = $outlet->payByCash($foo);
    
    //$this->manualCancel($txn_id);
    $this->voidTransaction($txn_id); //Supposedly we'll do proper cancellations through admin360
    
    //this one should be shown
    $outlet->addItem($evt->id, $catA->id, 1);
    $txn_id = $outlet->payByCash($foo);
    
    /*
    $bar = $this->createUser('bar');
    $outlet->login('outlet2');
    $outlet->addItem($evt->id, $catB->id, 2);
    $outlet->payByCash($bar);
    */
    
  }
  
  /**
   * "we need to be able to produce reports for more than one event at the same time."
   */
  function test_multiple_events(){
    
    $this->clearAll();
    
    $out1 = $this->createOutlet('Outlet 1', '0001');
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pitbull Quito', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0011');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    $catA = $this->createCategory('GOLD',   $evt->id, 100);
    $catB = $this->createCategory('SILVER', $evt->id, 50);
    $evt1 = $evt;
    
    
    $evt = $this->createEvent('Chayanne Guayaquil', 'seller', $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0011');
    $this->setEventVenue($evt, $this->createVenue('Piscina'));
    $catX = $this->createCategory('Adult', $evt->id, 40);
    $catY = $this->createCategory('Kid', $evt->id, 20);
    $catZ = $this->createCategory('GOLD', $evt->id, 10.5); //categories with the same name should be grouped in multiple event report
    $evt2 = $evt;
    
    
    $foo = $this->createUser('foo');
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt1->id, $catA->id, 1);
    $outlet->payByCash($foo);
    //return;
    
    $bar = $this->createUser('bar');
    $outlet->addItem($evt1->id, $catX->id, 1);
    $outlet->payByCash($bar);
   
    $outlet->addItem($evt1->id, $catZ->id, 1);
    $outlet->payByCash($bar);
    
  }
  
  function test_count_printed(){
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
      
      //A normal purchase
      /*$outlet = new OutletModule($this->db, 'outlet1');
      $outlet->addItem('aaa', $catA->id, 5);
      $outlet->payByCash($foo);
      
      //Make them printed
      $this->db->update('ticket', array('printed'=>1, 'paid'=>0), "1");*/
      
      //Create 5 printed tickets

      
      Utils::clearLog();
      $this->createPrintedTickets(5, 'aaa', $catA->id, $catA->name);
      $this->assertRows(5, 'ticket');
      
      
      //$this->createPromocode("MITAD", $catA, 50, 'p'); //This doesn't seem to be working on the admin360 editor
  
  }
  

   

  
 
}