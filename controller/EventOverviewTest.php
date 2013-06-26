<?php
use model\Transaction;
use model\Payment;
use tool\PaymentProcessor\Paypal;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class EventOverviewTest extends \DatabaseBaseTest{
  
  
  public function testCreate(){
    $this->clearAll();
    
    // -------------------- event setup --------------------------------------------
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Quebec CES' , $seller->id, 1, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $this->setEventId($evt,'aaa');
    $cat = $this->createCategory('Green', $evt->id, 10.00, 30, 10);
    $cat->update();
    
    $this->createPromocode('DERP', $cat);
    
    //let's create some other codes
    $pid = $this->createPromocode('SAMSUNG', $cat);
    //let's make this one invalid
    $this->db->update('promocode', array('valid_from' =>date('Y-m-d', strtotime("-1 year"))
																				, 'valid_to' =>date('Y-m-d', strtotime("-1 month"))
                                        ), "id=$pid");
    
    $yellow = $this->createCategory('Yellow', $evt->id, 3.50);
    $red = $this->createCategory('Red', $evt->id, 6.50);

    //---------------- transactions ---------------------------------
    //Transaction setup
    $foo = $this->createUser('foo');
    $client = new WebUser($this->db);
    $client->login($foo->username);
    
    

    $client->addToCart('aaa', $cat->id, 1, 'DERP');
    
    //now create the tickets
    $this->completeTransaction($client->placeOrder(false, '2012-02-15'));
    
    //return;
    
    //flag as completed up to here
    //$this->flagTransactionsAsCompleted();
    
    //order other tickets, but don't buy them
    $client->addToCart('aaa', $yellow->id, 2);
    $client->placeOrder(false, '2012-02-20');
    
    
    //another purchase
    $bar = $this->createUser('bar');
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart('aaa', $cat->id, 2, 'SAMSUNG');
    $this->completeTransaction($client->placeOrder(false, '2012-02-17'));
    
    
    //purchase with no promo codes
    $baz = $this->createUser('baz');
    $client = new WebUser($this->db);
    $client->login($baz->username);
    $client->addToCart('aaa', $cat->id, 4);
    $this->completeTransaction($client->placeOrder(false, '2012-03-05'));
    
    
    //another buyer
    $elmer = $this->createUser('elmer');
    $client = new WebUser($this->db);
    $client->login($elmer->username);
    $client->addToCart('aaa', $cat->id, 6, 'DERP');
    $this->completeTransaction($client->placeOrder(false, '2012-03-05'));
    

    $rukia = $this->createUser('rukia');
    $this->buyTickets('rukia', 'aaa', $yellow->id, 5);
    
  }
  
  public function testTour(){
     //simple fixture
    $this->clearAll();

    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $this->createVenue('Pool');
    
    $outlet = $this->createOutlet('outlet1', '0001');
    
    //move this to a tour builder test
    $build = new TourBuilder($this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];

    //le'ts do a tour purchase
    $out = new OutletModule($this->db, 'outlet1');
    $out->addItem('tour1', $catX->id, 1);
    $out->payByCash($foo);
    
    
    //combine with a normal event for fun
    $evt = $this->createEvent('Inscripcion de Don Burro', $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $this->setEventGroupId($evt, '0001');
    $cat = $this->createCategory('Plaza', $evt->id, 49.99);
    
    $this->createPromocode('GARLIC', $cat, 50);
    
    $out->addItem('bbb', $cat->id, 1);
    $out->applyPromoCode('bbb', 'GARLIC');
    $out->payByCash($foo);
    
    //For the moment override manually the ticket with the id of the promocode :(
    $pid = $this->createPromocode('CHEESE', $catX);
    $this->db->update('ticket', array('promocode_id'=>$pid), "event_id=?", 'tour1');
    
  }
  
  function testPromoCodeTour(){
    
    $this->clearAll();

    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $this->createVenue('Pool');
    
    $outlet = $this->createOutlet('outlet1', '0001');
    
    //move this to a tour builder test
    $build = new TourBuilder($this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];
    
    //apparently promo code is associated to template
    $this->createPromocode('CHEESE', $catX);
    
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart('tour1', $catX->id, 1, 'CHEESE'); //failed??????
    $client->payByCashBtn();
  }
  
  
  //simple Fixture to create several events hold by the same merchant
  function xtestSeveralEvents(){
    $this->clearAll();
    $seller = $this->createUser('seller');
    
    $loc = $this->createLocation();
    $evt = $this->createEvent('Dinner' , $seller->id, $loc->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('Green', $evt->id, 10.00);
    $cat = $this->createCategory('Red', $evt->id, 15.00);
    $cat = $this->createCategory('Yellow', $evt->id, 20.00);
    $this->createPromocode('DERP', $evt);
    
    $loc = $this->createLocation();
    $evt = $this->createEvent('Lunch' , $seller->id, $loc->id, '2012-01-01', '9:00', '2014-01-10', '18:00' );
    $cat = $this->createCategory('LAN', $evt->id, 10.00);
    $cat = $this->createCategory('WAN', $evt->id, 15.00);
    $cat = $this->createCategory('MAN', $evt->id, 20.00);
    $this->createPromocode('HERP', $evt);
    
  }
  
 
  
  

  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

