<?php
namespace tool;
use Utils;
use model\Module;

class CalculatorTest extends \DatabaseBaseTest{
  
  //No discounts
  function test100(){
    $data = $this->getCalculatorOkData();
    
    $data['fee_fixed'] = 1;
    $data['fee_percentage'] = 2.5;
    $data['fee_max'] = 9.99;
    
    $calc = new \tool\Calculator($data);
	$calc->doTheMath();
	$res = $calc->toArray(1);
	foreach ($res as $i=> $val){
	  $res[$i] = Utils::formatMoney($val);
	}
	Utils::log("calculator results: " . print_r($res, true) );
	
	$this->assertEquals(3.5, $res['price_fee'], '', 0.01  );
	$this->assertEquals(0.61, $res['fee_tax_1'], '', 0.01  );
	$this->assertEquals(14.89, $res['tax_1'], '', 0.01  );
	$this->assertEquals(14.89, $res['tax'], '', 0.01  );
	
  }
  
  function testDiscount(){
    $data = $this->getCalculatorOkData();
    
    $data['fee_fixed'] = 1;
    $data['fee_percentage'] = 2.5;
    $data['fee_max'] = 9.99;

    /*$data['discount_fixed'] = ;
    $data['discount_percentage'] = ;*/
    $data['discount_percentage'] = 0.5; //only one type of discount can act at a time
    
    $calc = new \tool\Calculator($data);
	$calc->doTheMath();
	$res = $calc->toArray(1);
	foreach ($res as $i=> $val){
	  $res[$i] = Utils::formatMoney($val);
	}
	Utils::log("calculator results: " . print_r($res, true) );
	
	$this->assertEquals(2.25, $res['price_fee'], '', 0.01  );
	$this->assertEquals(0.39, $res['fee_tax_1'], '', 0.01  );
	$this->assertEquals(7.44, $res['tax_1'], '', 0.01  );
	$this->assertEquals(7.44, $res['tax'], '', 0.01  );
	
  }
  
  function testFeeMax(){
    $data = $this->getCalculatorOkData();
    
    $fee_max = 3.0;
    
    $data['fee_fixed'] = 1;
    $data['fee_percentage'] = 2.5;
    $data['fee_max'] = $fee_max;


    $calc = new \tool\Calculator($data);
	$calc->doTheMath();
	$res = $calc->toArray(1);
	foreach ($res as $i=> $val){
	  $res[$i] = Utils::formatMoney($val);
	}
	Utils::log("calculator results: " . print_r($res, true) );
	
	//$this->assertEquals(3.5, $res['price_fee'], '', 0.01  );
	$this->assertEquals($fee_max, $res['price_fee'] + $res['fee_tax_1'], '', 0.01  );
	$this->assertEquals(14.89, $res['tax_1'], '', 0.01  );
	$this->assertEquals(14.89, $res['tax'], '', 0.01  );
	
  }
  
  
  function testSingle(){ //no discount
    $this->clearAll();
    
    $fee_max = 3;
    
    $v1 = $this->createVenue('Pool');
    
    $out = $this->createOutlet('Outlet 1', '0010');
    
    //create sellers
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID', $evt->id, 50);

    //create buyers
    $foo = $this->createUser('foo');
    
    
    $this->createModuleFee('test', 1, 2.5, $fee_max, Module::OUTLET);
    
    $outlet = new \OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->payByCash($foo);
  }
  
  
  function testDis50(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out = $this->createOutlet('Outlet 1', '0010');
    
    //create sellers
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID', $evt->id, 50);

    //A 50% discount
    $this->createPromocode('HALF', $catA, 50, 'p');
    $this->createPromocode('COMP', $catA, 100, 'p',true);

    //create buyers
    $foo = $this->createUser('foo');
    
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::OUTLET);
    
    $outlet = new \OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->applyPromocode('aaa', 'HALF');
    $outlet->payByCash($foo);
  }
  
  function testDis90(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out = $this->createOutlet('Outlet 1', '0010');
    
    //create sellers
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID', $evt->id, 50);

    //A 50% discount
    $code ='D90';
    $this->createPromocode($code, $catA, 100, 'p');

    //create buyers
    $foo = $this->createUser('foo');
    
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::OUTLET);
    
    $outlet = new \OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->applyPromocode('aaa', $code);
    $outlet->payByCash($foo);
  }
  
  /**
   * This is a crazy discount
   */
  function testDisMad(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out = $this->createOutlet('Outlet 1', '0010');
    
    //create sellers
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID', $evt->id, 50);

    $code ='MAD';
    $this->createPromocode($code, $catA, 999.99, 'f');

    //create buyers
    $foo = $this->createUser('foo');
    
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::OUTLET);
    
    $outlet = new \OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->applyPromocode('aaa', $code);
    $outlet->payByCash($foo);
    
    //we expect the ticket created to have a price_category of 0.00
    $this->assertEquals(0.00, $this->db->get_one("SELECT price_category FROM ticket LIMIT 1"));
    
  }
  
  
  
  function testFixedEdge(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out = $this->createOutlet('Outlet 1', '0010');
    
    //create sellers
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID', $evt->id, 50);

    //A 50% discount
    $code ='DFix';
    $this->createPromocode($code, $catA, 98.57, 'f');

    //create buyers
    $foo = $this->createUser('foo');
    
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::OUTLET);
    
    $outlet = new \OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->applyPromocode('aaa', $code);
    $outlet->payByCash($foo);
  }
  
  
  
  function getCalculatorOkData(){
    return array (
  'tcapa' => 1,
  'price' => '100.00',
  'is_tax_inc' => true,
  'is_fee_inc' => true,
  'is_cc_fee_inc' => false,
  'fee_fixed' => 1.08,
  'fee_percentage' => 2.5,
  'fee_max' => 9.95,
  'discount_fixed' => 0,
  'discount_percentage' => 0,
  'fee_cc_fixed' => 0.3,
  'fee_cc_percentage' => 2.9,
  'fee_cc_max' => NULL,
  'tax_1' => '14.8936',
  'tax_2' => 0,
  'tax_additive' => 0,
  'tax_fee_1' => 14.8936,
  'tax_fee_2' => 0,
  'tax_fee_additive' => false,
  'tax_other' => 0,
  'tax_fee_other' => 14.89361,
);
  }
  
  
  
  //Only website can use complimentary tickets
  function testComplimentaryTicket(){
    $this->clearAll();
    
    $v1 = $this->createVenue('Pool');
    
    $out = $this->createOutlet('Outlet 1', '0010');
    
    //create sellers
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
    $this->setEventId($evt, 'aaa');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID', $evt->id, 50);

    //A 50% discount
    $this->createPromocode('HALF', $catA, 50, 'p');
    $this->createPromocode('COMP', $catA, 100, 'p',true);

    //create buyers
    $foo = $this->createUser('foo');
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::WEBSITE);
    
    $web = new \WebUser($this->db);
    $web->login($foo->username);
    Utils::clearLog();
    $web->addToCart($evt->id, $catA->id, 1, 'COMP' );
    Utils::clearLog();
    @$web->getTickets(); //for now supress warnings
    
    
    //we expect the ticket created to have a price_category of 0.00
    $this->assertEquals(0.00, $this->db->get_one("SELECT price_category FROM ticket LIMIT 1"));
    
    
  }
  
  
  /*
  function okValues(){
  	return array (
		  'tcapa' => 1,
		  'price' => '100.00',
		  'is_tax_inc' => true,
		  'is_fee_inc' => true,
		  'is_cc_fee_inc' => false,
		  'fee_fixed' => 1.08,
		  'fee_percentage' => 2.5,
		  'fee_max' => 9.95,
		  'discount_fixed' => 0,
		  'discount_percentage' => 0,
		  'fee_cc_fixed' => 0,
		  'fee_cc_percentage' => 0,
		  'fee_cc_max' => NULL,
		  'tax_1' => '14.8936',
		  'tax_2' => 0,
		  'tax_additive' => 0,
		  'tax_fee_1' => 14.8936,
		  'tax_fee_2' => 0,
		  'tax_fee_additive' => false,
		  'tax_other' => 0,
		  'tax_fee_other' => 17.5,
			);
  }*/

}