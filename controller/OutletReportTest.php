<?php
use controller\Outletzreport;

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
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
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
  
  public function testCommission(){
    $this->clearAll();
    
    $this->db->beginTransaction();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet Z', '0010', array('identifier'=>'outlet1'));
    $out2 = $this->createOutlet('Outlet A', '0100');
    $out3 = $this->createOutlet('Outlet B', '1000');
    
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $bo = $this->createBoxoffice('xbox', $seller->id);
    
    $foo = $this->createUser('foo');
    
    
    //Create event
    
    
    
    $evt = $this->createEvent('ABC' , $seller->id, $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $this->setEventId($evt, 'reckt7' );
    $catA = $this->createCategory('First', $evt->id, 100.00);
    $catB = $this->createCategory('Second', $evt->id, 50.00);
    $catC = $this->createCategory('Third', $evt->id, 10.00);
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $catA->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->addItem($evt->id, $catB->id, 1);
    $outlet->payByCash($foo);
    
    $outlet->addItem($evt->id, $catC->id, 1);
    $outlet->payByCash($foo);
    
    
    
    
    $this->db->commit();
    
    //Default state is 3% (empty outlet_commission table)
    $data = $outlet->getZReportData();
    $com_event_total = 100*(1-3/100) + 50*(1-3/100) + 10*(1-3/100);
    $this->assertEquals($com_event_total, $data['outlets'][0]->events[$evt->id]->total, null, 0.001);
    
    //return;
    
    //create
    $this->createOutletCommission($outlet->getId(), $evt->id, $catA->id, 'f', 10);
    $this->createOutletCommission($outlet->getId(), $evt->id, $catB->id, 'p', 3);
    $this->createOutletCommission($outlet->getId(), $evt->id, $catC->id, 'f', 1);
    
    Utils::clearLog();
    //Inspect
    $data = $outlet->getZReportData();
    Utils::log(print_r($data, true));
    foreach ($data['outlets'][0]->events[$evt->id]->rows as $row){
        Utils::log(print_r($row, true));
    }
    
    //•	$10 on the first category, 
    //•	3% on the second category and 
    //•	$1 on the third category
    
    $com_event_total = (100-10) + 50*(1-3/100) + (10-1 )   /*155.2*/;
    $this->assertEquals($com_event_total, $data['outlets'][0]->events[$evt->id]->total);
    
    // ***************
    
    //since this is dynamic, we could just NULL the last one for the next test
    $this->db->update('outlet_commission', array('com_type'=>null, 'com_value'=>null), "outlet_id=? AND event_id=? AND category_id=?", array($outlet->getId(), $evt->id, $catC->id));
    
    //in this case, we expect the bottom one to be 3%
    $data = $outlet->getZReportData();
    $com_event_total = (100-10) + 50*(1-3/100) + 10*(1-3/100 );
    $this->assertEquals($com_event_total, $data['outlets'][0]->events[$evt->id]->total, null, 0.001);
    
    // *************************
    //0 case, no comission    
    $this->db->update('outlet_commission', array('com_type'=>'p', 'com_value'=>0), "outlet_id=? AND event_id=? AND category_id=?", array($outlet->getId(), $evt->id, $catC->id));
    //in this case, we expect the bottom one to be 3%
    $data = $outlet->getZReportData();
    $com_event_total = (100-10) + 50*(1-3/100) + 10;
    $this->assertEquals($com_event_total, $data['outlets'][0]->events[$evt->id]->total, null, 0.001);
    
    
  
  }
  
  /**
   * sub-outlets gets the commissions of their parent.
   */
  function testParentCommission(){
      
      $this->clearAll();
  
      $v1 = $this->createVenue('Pool');
  
      $out_id = $this->createOutlet('Outlet Z', '0010', array('identifier'=>'outlet1'));
      $ganga = $this->createOutlet('1', '0010', array('parent'=>$out_id));
      $pycca = $this->createOutlet('2', '0010', array('parent'=>$out_id));
      $gamma = $this->createOutlet('3', '0010', array('parent'=>$out_id));
  
      $seller = $this->createUser('seller');
  
      $evt = $this->createEvent('Monstro Sales', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('Adult', $evt->id, 100);
      $catB = $this->createCategory('Kid',   $evt->id, 50);
      $catC = $this->createCategory('Pet',   $evt->id, 10);
  
      //add another event for laughts
      /*$evt = $this->createEvent('ALL CAPS EVENTS', 'seller', $this->createLocation()->id);
      $this->setEventId($evt, 'bbb');
      $this->setEventGroupId($evt, '0110');
      $this->setEventVenue($evt, $v1);
      $catX = $this->createCategory('ADULT', $evt->id, 65);
      $catY = $this->createCategory('KID', $evt->id, 35);*/
      
      $this->createOutletCommission($out_id, $evt->id, $catA->id, 'f', 10);
      $this->createOutletCommission($out_id, $evt->id, $catB->id, 'p', 3);
      $this->createOutletCommission($out_id, $evt->id, $catC->id, 'f', 1);
  
  
      $foo = $this->createUser('foo');
      
      /*
      $outlet = new OutletModule($this->db, 'outlet1');
      $this->assertEquals($out1, $outlet->getId());
      $outlet->addItem('aaa', $catA->id, 1);
      $outlet->payByCash($foo);
      */
      
      $outlet = new OutletModule($this->db, 'outlet1-1');
      $this->assertEquals($ganga, $outlet->getId()); //verify login logic works
      $outlet->addItem('aaa', $catA->id, 1);
      $outlet->payByCash($foo);
      
      Utils::clearLog();
      //in this case, I expect the only sale to be using the commission rate defined on the parent
      $data = $outlet->getZReportData();
      Utils::log(print_r($data, true));
      $com_event_total = (100-10) /*+ 50*(1-3/100) + 10*(1-3/100 )*/;
      $this->assertEquals($com_event_total, $data['outlets'][0]->events[$evt->id]->total, null, 0.001);
      
      
      //parent purchase for laughs
      $outlet = new OutletModule($this->db, 'outlet1');
      $this->assertEquals($out_id, $outlet->getId());
      $outlet->addItem('aaa', $catC->id, 1);
      $outlet->payByCash($foo);
      
  
      /*
      $outlet->addItem('bbb', $catX->id, 1);
      $outlet->payByCash($foo);
  
  
  
      $outlet = new OutletModule($this->db, 'outlet1-3');
      $outlet->addItem('aaa', $catB->id, 4);
      $outlet->payByCash($foo);
  
      $outlet->addItem('bbb', $catX->id, 1);
      $outlet->addItem('bbb', $catY->id, 2);
      $outlet->payByCash($foo);
      */
  
  }
  
  
  
}

