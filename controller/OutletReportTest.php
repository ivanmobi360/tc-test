<?php
/**
 * Admin360 report
  * @author MASTER
 *
 */
class OutletReportTest extends \DatabaseBaseTest{
  
  
  public function testTours(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    $out3 = $this->createOutlet('Outlet 3', '1000');
    
    $foo = $this->createUser('foo');
    
    
    //Create event
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Normal' , $seller->id, $this->createLocation()->id);
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $normal = 'n0rm4l';
    $this->setEventId($evt, $normal );
    $cat = $this->createCategory('Verde', $evt->id, 100.00);
    
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($normal, $cat->id, 1);
    $outlet->payByCash($foo);
    
    return; //fixture to test values
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($normal, $cat->id, 3);
    $outlet->payByCash($foo);
    
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];
    
    $bar = $this->createUser('bar');
    
    $outlet = new OutletModule($this->db, 'outlet2');
    $outlet->addItem('tour1', $catX->id, 2);
    $outlet->payByCash($bar);
    
    $outlet->addItem($normal, $catX->id, 1);
    $outlet->payByCash($bar);
    
    //a website purchase
    $client = new WebUser($this->db);
    $client->login($bar->username);
    $client->addToCart('tour2', $catX->id, 1);
    $client->payByCash($client->placeOrder());
    
    // *************************
    /*
    //activity in another merchant
    $seller = $this->createUser('seller2');
    $evt = $this->createEvent('Film', $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'bbb');
    $cat = $this->createCategory('Die Hard', $evt->id, 15.00);
    $this->buyTickets($foo->id, 'bbb', $cat->id);
    */    
   
    
  }
  
  /**
   * "When no event has been chosen, 
			the results will be grouped by event in DESC order of event date."
   */
  function testNoEventSelected(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    $foo = $this->createUser('foo');
    
    
    //Create event
    
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Event B' , $seller->id, $this->createLocation()->id, '2012-06-06');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $this->setEventId($evt, 'bbb' );
    $cat = $this->createCategory('Adult B', $evt->id, 10.00);
    
    
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $cat->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->addItem($evt->id, $cat->id, 1);
    $outlet->payByCash($foo);
    
    $evt = $this->createEvent('Event A' , $seller->id, $this->createLocation()->id, '2012-05-05');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $this->setEventId($evt, 'aaa' );
    $cat = $this->createCategory('Adult A', $evt->id, 10.00);
    
    $outlet->addItem($evt->id, $cat->id, 1);
    $outlet->payByCash($foo);
    
    $evt = $this->createEvent('Event C' , $seller->id, $this->createLocation()->id, '2012-07-07');
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $this->setEventId($evt, 'ccc' );
    $cat = $this->createCategory('Adult C', $evt->id, 10.00);
    
    $outlet->addItem($evt->id, $cat->id, 1);
    $outlet->payByCash($foo);
    
  }
  
  /**
   * 
   * "if the ticket_transaction.outlet_id = 0, 
      you need to look to see if the bo_id is != NULL
      if outlet_id = 0 and bo_id = 8 for example, 
      instead of writing the outlet name, 
      you write the box office name (bo_user.name)
      "
      
      "and box offices are a kind of outlet 
      although it was elected to have them separated at some point, 
      so I'm adding the specification that the dropdown needs both outlets and box offices 
      and I pointed out the way to identify the two cases
      of course, if outlet_id = 0 and we have no bo_id, 
      the name we print is still TixPro Caribbean as before
      "

   */
  function testBoxOfficesAndOutlets(){
    
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    
    
    $foo = $this->createUser('foo');
    
    
    //Create event
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    
    $box1 = $this->createBoxoffice('xbox', $seller->id);
    $box2 = $this->createBoxoffice('ps3', $seller->id);
    
    $evt = $this->createEvent('Some Event' , $seller->id, $this->createLocation()->id);
    $this->setEventGroupId($evt, '0110');
    $this->setEventVenue($evt, $v1);
    $this->setEventId($evt, 's0m3' );
    $catA = $this->createCategory('Adult', $evt->id, 10.00);
    $catB = $this->createCategory('Kid', $evt->id, 5.00);
    
    //create buyer
    /*$foo = $this->createUser('foo');
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
    */
    
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $catA->id, 1);
    $outlet->payByCash($foo);
    $outlet->logout();
    
    $this->clearRequest();
    
    
    $box = new BoxOfficeModule($this, '111-xbox');
    $box->addItem($evt->id, $catB->id, 2);
    $box->payByCash();
    $box->logout();
    
    
    Utils::clearLog();
    
    //A web purchase for fun
    $client = new WebUser($this->db);
    $client->login($foo->username);
    $client->addToCart($evt->id, $catA->id, 3); //Utils::clearLog();
    $client->payByCashBtn();
    $client->logout();
    
  }
  
  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

