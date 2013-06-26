<?php
/**
 * @author MASTER
 *
 */
use ajax\Reservationcheckin;
use Forms\VehicleAssigner;
use model\Eventsmanager;
use tool\Date;
class VehiclesTest extends DatabaseBaseTest{
  
  function vehicleSetup(){
  	$sql = "
INSERT INTO `vehicles` (`id`, user_id, `name`, `capacity`) VALUES
(1, 'seller', 'Boat 1', 5),
(2, 'seller', 'Boat 2', 5);

INSERT INTO `vehicles_tour` (`id`, `vehicle_id`, `event_id`, `active`) VALUES
(1, 1, 'tour1', 1),
(3, 2, 'tour1', 1),
(5, 1, 'tour3', 1),
(6, 2, 'tour3', 1)

;";
  	$this->db->executeBlock($sql);
  	
  }
  
  function testTour(){
    $this->clearAll();
    
    
    $venue_id = $this->createVenue('Kignston Oval');
    $out1 = $this->createOutlet('outlet 1', '0001');
    
    
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');

    //return; //no tours
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, peeking at the data to find it.
    $catB = $cats[0];
    
    //return; //no vehicules
    
    $this->vehicleSetup();
    
    
    //return; //fixture. create event template and tour dates from here as seller.
    /*
    $evt = $this->createEvent("Feria Biess", $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, 'n0rm41');
    $this->setEventGroupId($evt, '0001');
    $cat = $this->createCategory('Adult', $evt->id, 10.00);
    //return;
    
	    
    //normal event purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem($evt->id, $cat->id, 1);
    $txn_id = $outlet->payByCash($foo);
 		*/
    
    //tour1 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 1);
    $txn_id = $outlet->payByCash($foo);
    
    
    
    //tour2 purchase
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour2', $catA->id, 2);
    $txn_id = $outlet->payByCash($foo);
    
    //moar purchases
    $bar = $this->createUser('bar');
    $outlet->addItem('tour3', $catA->id, 10);Utils::clearLog(); //Some edge date case prevents the creation of tour3
    $txn_id = $outlet->payByCash($bar);
    
    

    
    //return;
    
    //a purchase of multiple items
    /*
    $outlet = new OutletModule($this->db, 'outlet1');
    $outlet->addItem('tour1', $catA->id, 3);
    $outlet->addItem('tour1', $catB->id, 1);
    $outlet->addItem('tour2', $catB->id, 1);
    $txn_id = $outlet->payByCash($foo);
		*/
    Utils::clearLog();
    
    //we must be able to assing
    $data = $this->okAssignData();
    $form = new VehicleAssigner();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    
    //Assigment of 6 tickets to a vehicle of capacity 5 should fail
    $data = $this->badAssignData();
    $form = new VehicleAssigner();
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
    //5 ticketes should do fine
    $data = $this->okFiveTickets();
    $form = new VehicleAssigner();
    $form->setData($data);
    $form->process();
    $this->assertTrue($form->success());
    
    //6th should fail. Previous boat already full.
    $data = $this->badSixthTicketData();
    $form = new VehicleAssigner();
    $form->setData($data);
    $form->process();
    $this->assertFalse($form->success());
    
  }
  
  function okAssignData(){
  	return array (
		  'vehicle_id' => '1',
		  'tickets' => 
		  array (
		    0 => '777',
		  ),
		  'sent' => '1',
		  'page' => 'Reservationvehicles',
		  'cmd' => 'assign',
		);
  }
  
  function badAssignData(){
  	return array (
  'vehicle_id' => '5',
  'tickets' => 
  array (
    0 => '780',
    1 => '781',
    2 => '782',
    3 => '783',
    4 => '784',
    5 => '785',
  ),
  'sent' => '1',
  'page' => 'Reservationvehicles',
  'cmd' => 'assign',
);
  }
  
	function okFiveTickets(){
		return array (
		  'vehicle_id' => '5',
		  'tickets' => 
		  array (
		    0 => '780',
		    1 => '781',
		    2 => '782',
		    3 => '783',
		    4 => '784',
		  ),
		  'sent' => '1',
		  'page' => 'Reservationvehicles',
		  'cmd' => 'assign',
		);
	}
	
	function badSixthTicketData(){
		return array (
		  'vehicle_id' => '5',
		  'tickets' => 
		  array (
		    0 => '785',
		  ),
		  'sent' => '1',
		  'page' => 'Reservationvehicles',
		  'cmd' => 'assign',
		);
	}
	
	function testCheckInListLook(){
	  
	  $this->clearAll();
    
    
    $venue_id = $this->createVenue('Kignston Oval');
    $out1 = $this->createOutlet('outlet 1', '0001');
    
    
    $seller = $this->createUser('seller');

    $build = new TourBuilder( $this, $seller);
    $build->build();
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, peeking at the data to find it.
    $catB = $cats[0];
    
    
    $this->vehicleSetup();
    
    $outlet = new OutletModule($this->db, 'outlet1');
    	  
    //Mr. X purchase: 2 adults and 3 kids
    $x = $this->createUser('mr_x');
    $outlet->addItem('tour3', $catA->id, 2); //2 adults
    $outlet->addItem('tour3', $catB->id, 3); //3 kids
    $txn_id = $outlet->payByCash($x);
    
    //Mr. Y: 2 adults, 1 kid
    $y = $this->createUser('mr_y');
    $outlet->addItem('tour3', $catA->id, 2); //2 adults
    $outlet->addItem('tour3', $catB->id, 1); //3 kids
    $txn_id = $outlet->payByCash($y);
    
    // **************************************************************
    
    $vehicle_id = 5; //for now some magic index
    //now let's test some inline field edition
    $this->assertNull($this->db->get_one("SELECT params FROM vehicles_tour WHERE id='$vehicle_id'"));
    
    $row_id = 'mr_x-1';
    $value = 'Digicel';
    $data = array (
        'page' => 'Reservationcheckin',
        'cmd' => 'update',
        'vehicle_id' => '5',
        'row_id' => $row_id ,
        'key' => 'hotel',
        'value' => $value,
      );
      $ajax = new \ajax\Reservationcheckin();
      $ajax->setData($data);
      $ajax->Process();
      
      $pdata = $this->db->get_one("SELECT params FROM vehicles_tour WHERE id='$vehicle_id'");
      $this->assertTrue($pdata!=false);
      
      //write again - update it;
      $ajax = new \ajax\Reservationcheckin();
      $ajax->setData($data);
      $ajax->Process();
      
      //Try to retreive it
      $v = new \model\VehiclesTour($vehicle_id);
      $params = $v->params;
      $this->assertEquals($value, $params->get($row_id, 'hotel'));
      $this->assertFalse($params->get($row_id, 'blah'));
      $this->assertFalse($params->get('xxxx', 'blah'));
    
	}

 
}