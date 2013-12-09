<?php

use model\FeeVO;
use model\Module;
use tool\FeeFinder;
use model\Eventsmanager;
use tool\Date;
class FeeTest extends DatabaseBaseTest{
  
  function testReset(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $out2 = $this->createOutlet('Outlet 2', '0100');
    
    //create sellers
    $seller = $this->createUser('seller');
    $this->createUser('seller2');
    $this->createUser('seller3');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    $evt = $this->createEvent('Tacos Time', 'seller', $this->createLocation()->id, $this->dateAt("+3 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $catQ = $this->createCategory('Cuates', $evt->id, 100);
    
    $evt = $this->createEvent('Dynamite', 'seller', $this->createLocation()->id, $this->dateAt("+5 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    $evt = $this->createEvent('Elecciones 2013', 'seller2', $this->createLocation()->id, $this->dateAt("+5 day"), '15:00', $this->dateAt("+2 day") , '17:00' );
    
    
    //create buyers
    $this->createUser('foo');
    $this->createUser('bar');
    $this->createUser('baz');
    
  }
  
  

  
  function testRetrieval(){
    
    $this->clearAll();
    
    $this->db->beginTransaction();
    $v1 = $this->createVenue('Pool');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $box = $this->createBoxoffice('xbox', $seller->id);
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    
    
    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    $this->makeVehicle($catB->id);
    
    /*
    $evt = $this->createEvent('Filler Event', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'filler');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $caXA = $this->createCategory('HORIZON', $evt->id, 100);*/
    
    $this->db->commit();
    
    
    //Create
    $this->baseModuleFees();

  }
  
  function baseModuleFees(){
    $this->createModuleFee('Outlet Fees', 2.2, 2.3, 122, Module::OUTLET);
    $this->createModuleFee('Boxoffice Fees', 3, 3.3, 133, Module::BOX_OFFICE);
    $this->createModuleFee('Reservation Fees', 4, 4.44, 100.44, Module::RESERVATION);
    $this->createModuleFee('Vehicle Fees', 5, 5.55, 100.55, Module::VEHICLE);
  }
  
  function testTour(){
    
    $this->clearAll();
    
    //$this->db->beginTransaction();
    $v1 = $this->createVenue('Pool');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');
    $this->setUserHomePhone($seller, '111');
    $box = $this->createBoxoffice('xbox', $seller->id);
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    
    
    $builder = new TourBuilder($this, $seller);
    $builder->build();
    $cats = $builder->categories;
    $catA = $cats[1]; //the 100.00 one, yep, cheating
    $catB = $cats[0];
    
    $this->makeVehicle($catB->id);
    
    /*
    $evt = $this->createEvent('Filler Event', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'filler');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $caXA = $this->createCategory('HORIZON', $evt->id, 100);*/
    
    $this->db->commit();
    
    //When are fee logics called anyway?
    //find the module?
    /*
    $outlet = new OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('tacos', $catA->id, 1);
    */
    
    //Let's try to isolate the calculation part to see how fees are calculated:
    Utils::clearLog();
    \tool\Session::set('module_id', Module::WEBSITE);
    \tool\Cart::calculateRowValues($catA->id, 1 );
    //inspect output
    
    //Create
    $this->baseModuleFees();
    
  }
  
  
  
  
  
  function testFinder(){
    
    $this->clearAll();
    $this->db->beginTransaction();
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');

    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $this->createVenue('Pool'));
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    
    $evt = $this->createEvent('Lunch', $this->createUser('seller2')->id, $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'lunch');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $this->createVenue('Food Court'));
    $catX = $this->createCategory('CHEAPO', $evt->id, 2);
    
    
    $this->db->commit();
    Utils::clearLog();
    
    // *** Should find the default global fees
    $finder = new FeeFinder();
    $feeVo = $finder->find(Module::WEBSITE, $catA->id );
    
    $fee_global = $this->currentGlobalFee();// new FeeVO(1.08, 2.5, 9.95);
    
    $this->assertEquals($fee_global, $feeVo);
    
    
    // *** Should find the default module fees
    $fee_web          = $this->createModuleFee("Website Default Fee", 1.25, 3.5, 11.25, Module::WEBSITE);
    $fees_reservation = $this->createModuleFee("Reservation Default Fee", 5, 10, 15, Module::RESERVATION);
    
    $feeVo = $finder->find(Module::WEBSITE, $catA->id );
    $this->assertEquals($fee_web, $feeVo);

    
    // *** Should find the specifice venue fees
    /*$fees_web_v1 = $this->createSpecificFee('venue', 3.1, 3.2, 3.3, Module::WEBSITE);
    $fees_out_v1 = $this->createSpecificFee('venue', 4, 4, 4, Module::OUTLET);
    
    $feeVo = $finder->find(Module::VEHICLE, $catA->id ); 
    $this->assertEquals($fee_global, $feeVo); //still global
    Utils::clearLog();
    $feeVo = $finder->find(Module::WEBSITE, $catX->id ); 
    $this->assertEquals($fee_web, $feeVo); //still default website
    
    Utils::clearLog();
    $feeVo = $finder->find(Module::WEBSITE, $catA->id ); 
    $this->assertEquals($fees_web_v1, $feeVo); //should use the module venue defined fees*/

    $fees_out_v2 = $this->createSpecificFee('venue', 1.1, 1.2, 1.3, Module::OUTLET);
    $feeVo = $finder->find(Module::WEBSITE, $catX->id );
    $this->assertEquals($fee_web, $feeVo); //on Website, still default website
    $feeVo = $finder->find(Module::OUTLET, $catX->id );
    $this->assertEquals($fees_out_v2, $feeVo); //on Outlet, venue specific

    
    // ****** User specific
    $fees_web_seller = $this->createSpecificFee('user', 4.51, 4.52, 4.53, Module::WEBSITE, 'seller');Utils::clearLog();
    $this->assertEquals($fees_web_seller, $finder->find(Module::WEBSITE, $catA->id ));
    $this->assertEquals($fees_web_seller, $finder->find(Module::WEBSITE, $catB->id ));
    $this->assertEquals($fee_web, $finder->find(Module::WEBSITE, $catX->id )); //on Website, use default website
    $this->assertEquals($fee_global, $finder->find(Module::BOX_OFFICE, $catX->id )); //global
    
    //now it would appear that I can define many fees for the same item, but only one is is_default=1
    $fees_web_seller2 = $this->createSpecificFee('user', 4.61, 4.62, 4.63, Module::WEBSITE, 'seller');
    $this->assertEquals($fees_web_seller2, $finder->find(Module::WEBSITE, $catA->id ));
    
    // ******* Find Event specific
    $fees_web_evt1 = $this->createSpecificFee('event', 2.3, 2.4, 2.5, Module::WEBSITE, 'seller', 'tacos');
    $this->assertEquals($fees_web_evt1, $finder->find(Module::WEBSITE, $catA->id )); //on Website, use de event one
    $this->assertEquals($fee_web, $finder->find(Module::WEBSITE, $catX->id )); //on Website, use default website
    $this->assertEquals($fee_global, $finder->find(Module::BOX_OFFICE, $catX->id )); //global
    
    // ****** Category specific
    $fees_web_catA = $this->createSpecificFee('category', 0.15, 0.16, 0.17, Module::WEBSITE, 'seller', 'tacos', $catA->id );
    $this->assertEquals($fees_web_catA, $finder->find(Module::WEBSITE, $catA->id )); //on Website, use the category one
    $this->assertEquals($fees_web_evt1, $finder->find(Module::WEBSITE, $catB->id )); //on Website, use the event one
    $this->assertEquals($fee_web, $finder->find(Module::WEBSITE, $catX->id )); //on Website, use default website
    $this->assertEquals($fee_global, $finder->find(Module::BOX_OFFICE, $catX->id )); //global
    
  }
  
  function makeVehicle($category_id){
    $this->db->update('category', array('vehicle'=>1), "id=?", $category_id);
  }
  
  /**
   * "when we decide to buy the category which is a vehicle, we detect that it's a vehicle and then we look through that module, even if we're on the reservation module"
   */
  function testVehicle(){
    
    $this->clearAll();

    $this->db->beginTransaction();
    $v1 = $this->createVenue('Pool');
    $v2 = $this->createVenue('Food Court');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');

    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    //make the first one vehicle
    $this->makeVehicle($catB->id);
    
    $this->db->commit();
    Utils::clearLog();
    
    //return;
    
    //Define fees
    $fee_web = $this->createModuleFee("Website Default Fee", 1.25, 3.5, 11.25, Module::WEBSITE);
    $fee_reservation = $this->createModuleFee("Reservation Default Fee", 5, 10, 15, Module::RESERVATION);
    $fee_vehicles = $this->createModuleFee("Vehicle Default Fee", 7.11, 7.12, 7.13, Module::VEHICLE);
    
    // *** Should find the default global fees
    $ffinder = new FeeFinder();
    $feeVo = $ffinder->find(Module::WEBSITE, $catA->id );
    

    $this->assertEquals($fee_web, $ffinder->find(Module::WEBSITE, $catA->id ));
    $this->assertEquals($fee_vehicles, $ffinder->find(Module::WEBSITE, $catB->id ));
    
    //Verify specific override
    $fee_web_v1 = $this->createSpecificFee('venue', 3.1, 3.2, 3.3, Module::WEBSITE);
    $fee_vehicle_v1 = $this->createSpecificFee('venue', 4, 4, 4, Module::VEHICLE);
    
    
    $this->assertEquals($fee_web_v1, $ffinder->find(Module::WEBSITE, $catA->id ));
    $this->assertEquals($fee_vehicle_v1, $ffinder->find(Module::WEBSITE, $catB->id ));
    $this->assertEquals($fee_vehicle_v1, $ffinder->find(Module::BOX_OFFICE, $catB->id ));
    $this->assertEquals($fee_vehicle_v1, $ffinder->find(Module::OUTLET, $catB->id ));

  }
  
  function testDetectFee(){
    $this->clearAll();

    $this->db->beginTransaction();
    $v1 = $this->createVenue('Pool');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');

    $evt = $this->createEvent('Tacos Night', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
    $this->setEventId($evt, 'tacos');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('SILVER', $evt->id, 100);
    $catB = $this->createCategory('GOLD', $evt->id, 150);
    
    
    $this->db->commit();
    Utils::clearLog();
    
    //return;
    
    //Define fees
    $fee_web = $this->createModuleFee("Website Default Fee", 1.25, 3.5, 11.25, Module::WEBSITE);
    $fee_box = $this->createModuleFee("Box Office Default Fee", 2.25, 2.35, 2.45, Module::BOX_OFFICE);
    $fee_out = $this->createModuleFee("Outlet Default Fee", 7.11, 7.12, 7.13, Module::OUTLET);
    
    $ffinder = new FeeFinder();

    $this->assertEquals($fee_web, $ffinder->find(Module::WEBSITE, $catA->id ));
    $this->assertEquals($fee_box, $ffinder->find(Module::BOX_OFFICE, $catA->id ));
    $this->assertEquals($fee_out, $ffinder->find(Module::OUTLET, $catA->id ));
  }
  
  /**
   * Simple setup to try out the current specific fee logic 
   */
  function testSpecificFee(){
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Specific stories', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      $catB = $this->createCategory('KID', $evt->id, 150);
      ModuleHelper::showEventInAll($this->db, $evt->id);
      
      $fc = $this->createSpecificFee('', 1.1, 2.2, 3.3, Module::WEBSITE);
      $fo = $this->createSpecificFee('', 9.1, 9.2, 9.3, Module::OUTLET);
      
      $finder = new FeeFinder();
      $this->assertEquals($fc, $finder->find(Module::WEBSITE, $catA->id));
      $this->assertEquals($fo, $finder->find(Module::OUTLET, $catB->id));
      
  }
  
  function testNewRules(){
      $this->clearAll();
      
      $v1 = $this->createVenue('Pool');
      $out1 = $this->createOutlet('Outlet 1', '0010');
      $seller = $this->createUser('seller');
      
      $evt = $this->createEvent('Cancelling Traveling', 'seller', $this->createLocation()->id, $this->dateAt("+10 day"));
      $this->setEventId($evt, 'aaa');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('ADULT', $evt->id, 100);
      $catB = $this->createCategory('KID', $evt->id, 150);
      ModuleHelper::showEventInAll($this->db, $evt->id);
      
      //$fc = $this->createSpecificFee($catA->id, 'category', 1.1, 2.2, 3.3, Module::WEBSITE);
      //$fo = $this->createSpecificFee($catB->id, 'category', 9.1, 9.2, 9.3, Module::OUTLET);
      Utils::clearLog();
      
      $finder = new FeeFinder();
      
      //let's create from most global to most specific. on each case we should find the correct fee
      $global = $this->currentGlobalFee();
      $this->assertEquals($global, $finder->find(Module::WEBSITE, $catA->id));
      //return;

      //this time we'll use full column definition to index each fee
      $fee = $this->createSpecificFee('', 1.1, 1.2, 1.3, Module::WEBSITE);//Utils::clearLog();
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      //New, look for a user specific fee first
      $fee = $this->createSpecificFee('Seller specific', 11.1, 11.2, 11.3, null, $seller->id);Utils::clearLog();
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      //global is still global
      $this->assertEquals($global, $this->currentGlobalFee());//return;
      
      $fee = $this->createSpecificFee('', 2.1, 2.2, 2.3, Module::WEBSITE, $seller->id);
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      $fee = $this->createSpecificFee('', 3.1, 3.2, 3.3, Module::WEBSITE, $seller->id, $evt->id);
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      $fee = $this->createSpecificFee('', 4.1, 4.2, 4.3, Module::WEBSITE, $seller->id, $evt->id, $catA->id );
      $this->assertEquals($fee, $finder->find(Module::WEBSITE, $catA->id));
      
      //$this->assertEquals($fc, $finder->find(Module::WEBSITE, $catA->id));
      //$this->assertEquals($fo, $finder->find(Module::OUTLET, $catB->id));
  }
  
  
  //function assert
  



  
 
}