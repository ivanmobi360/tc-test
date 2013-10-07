<?php
namespace tool;
use Utils;
use model\Module;

class CalculatorTest extends \DatabaseBaseTest{
    
    /**
     * "so the tixpro fees are really $1.91 + $0.34 (VAT)
     * just like the ticket is really $85.11 + $14.89 (VAT)"
     */
    function testFixed(){
        $data = $this->getCalculatorOkData();
        
        $data['fee_fixed'] = 1.91;
        $data['fee_percentage'] = 0;
        $data['fee_max'] = null;
        
        $calc = new \tool\Calculator($data);
        $calc->doTheMath();
        $res = $calc->toArray(1);
        $res = array_map(function($val){return Utils::formatMoney($val);}, $res);
        Utils::log("calculator results: " . print_r($res, true) );
        
        $this->assertEquals(1.91, $res['price_fee'], '', 0.01  );
        $this->assertEquals(0.34, $res['fee_tax_1'], '', 0.02  );
        $this->assertEquals(14.89, $res['tax_1'], '', 0.01  );
        $this->assertEquals(14.89, $res['tax'], '', 0.01  );
        
        //Hmmm, it is either 1.92+0.34 = 2.26 or 1.91+0.33 = 2.24
    }
    
    function testPercentageAndFixed(){
        $data = $this->getCalculatorOkData();
    
        $data['fee_fixed'] = 1.91;
        $data['fee_percentage'] = 10;
        $data['fee_max'] = null;
        //$data['price'] *= $n;
    
        $calc = new \tool\Calculator($data);
        $calc->doTheMath();
        $res = $calc->toArray(1);
        $res = array_map(function($val){return Utils::formatMoney($val);}, $res);
        Utils::log("calculator results: " . print_r($res, true) );
        
        $this->assertEquals(10.30, $res['price_fee'], '', 0.01  );
        $this->assertEquals(1.80, $res['fee_tax_1'], '', 0.02  );
        $this->assertEquals(14.89, $res['tax_1'], '', 0.01  );
        $this->assertEquals(14.89, $res['tax'], '', 0.01  );
    }
    
    //has_tax=0 (manual 0.00 input of taxes) should produce the same fee values results of above
    function testNoTaxProducesTaxedFees(){
        $data = $this->getCalculatorOkData();
    
        $data['fee_fixed'] = 1.91;
        $data['fee_percentage'] = 10;
        $data['fee_max'] = null;
        $data['tax_1'] = 0; //result of has_tax=0
    
        $calc = new \tool\Calculator($data);
        $calc->doTheMath();
        $res = $calc->toArray(1);
        $res = array_map(function($val){return Utils::formatMoney($val);}, $res);
        Utils::log("calculator results: " . print_r($res, true) );
    
        // "12.10 is the tixpro fee and the tixpro fee includes the vat, as the ticket includes the vat.
        // in has_tax = 0, the tixpro fees STILL have tax.
        // tixpro fees always have vat."
        $this->assertEquals(10.30, $res['price_fee'], '', 0.01  ); 
        $this->assertEquals(1.80, $res['fee_tax_1'], '', 0.02  );
        $this->assertEquals(0, $res['tax_1'], '', 0.01  );
        $this->assertEquals(0, $res['tax'], '', 0.01  );
    }
    
    /**
     * has_tax=1
     * single ticket must have:
     * original price = 100
     * price_category = 100 - VAT
     * VAT = 14.89
     * price_fee = 1.91
     * fee_tax_1 = 0.34
     * 
     */
    function test_fixed_fee(){ //no discount
        $this->clearAll();

        $base_fee = 1.92;
   
        $this->createOutlet('Outlet 1', '0010');
    
        //create sellers
        $seller = $this->createUser('seller');
    
        $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
        $this->setEventId($evt, 'aaa');
        $this->setEventGroupId($evt, '0010');
        $this->setEventVenue($evt, $this->createVenue('Pool'));
        $catA = $this->createCategory('ADULT', $evt->id, 100);
        $catB = $this->createCategory('KID', $evt->id, 50);
    
        //create buyers
        $foo = $this->createUser('foo');
    
    
        //$fee_id = $this->createFee($base_fee, 0, null); //obsolete?
        $fee_id = $this->createModuleFee('baseFee', $base_fee, 0, null, Module::OUTLET);
        $this->setUserParams($seller, array('fee_id'=>$fee_id));
        //return;
        
        $outlet = new \OutletModule($this->db, 'outlet1');
        Utils::clearLog();
        $outlet->addItem('aaa', $catA->id, 1);
        $outlet->payByCash($foo);
        
        //ASSERTIONS
        $data = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
        $this->assertEquals($base_fee, $data['price_fee']);
        $this->assertEquals(0.34, $data['fee_tax_1']);
        $this->assertEquals(14.89, $data['price_taxe_1']);
        $this->assertEquals(100 - 14.89, $data['price_category']);
        $this->assertEquals(100, $data['original_price']);
        
    }
    
    function test_on_no_tax_fee_still_has_tax(){
        $this->clearAll();
    
        $base_fee = 1.92;
         
        $this->createOutlet('Outlet 1', '0010');
    
        //create sellers
        $seller = $this->createUser('seller');
    
        $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
        $this->setEventId($evt, 'aaa');
        $this->setEventGroupId($evt, '0010');
        $this->setEventVenue($evt, $this->createVenue('Pool'));
        $this->setEventParams($evt->id, array('has_tax'=>0)); //THIS IS THE MAIN STATE TO TEST
        $catA = $this->createCategory('ADULT', $evt->id, 100);
    
        //create buyers
        $foo = $this->createUser('foo');

        //$fee_id = $this->createFee($base_fee, 0, null);
        $fee_id = $this->createModuleFee('baseFee', $base_fee, 0, null, Module::OUTLET);
        $this->setUserParams($seller, array('fee_id' => $fee_id)); //force fee
        //return;
    
        $outlet = new \OutletModule($this->db, 'outlet1');
        Utils::clearLog();
        $outlet->addItem('aaa', $catA->id, 1);
        $outlet->payByCash($foo);
    
        //ASSERTIONS
        $data = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
        $this->assertEquals($base_fee, $data['price_fee']);
        $this->assertEquals(0.34, $data['fee_tax_1']);
        $this->assertEquals(0, $data['price_taxe_1']);
        $this->assertEquals(100, $data['price_category']);
        $this->assertEquals(100, $data['original_price']);
    
    }
    
    /**
     *  "let's say we have a ticket at 100, to know what the tixpro fee is when we have a fixed 1.91 + a 10 percent, 
     *  we just some of the math above to get 12.10 (I'm taking your numbers), 
     *  then we find what part of that is the vat: 12.10 * (7/47) = 1.80, 
     *  so our fee is composed of 10.30 fees and 1.80 vat for a total of 12.10 tixpro fees
        ticket is $100
        vat is $14.89 (has_tax=1)
        tixpro fee is $12.10
        Doctor Delirium: total: $100
     */
    function test_percent_and_fixed_fee(){ //no discount
        $this->clearAll();
    
        $base_fee = 1.91;
         
        $this->createOutlet('Outlet 1', '0010');
    
        //create sellers
        $seller = $this->createUser('seller');
    
        $evt = $this->createEvent('Pizza Time', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
        $this->setEventId($evt, 'aaa');
        $this->setEventGroupId($evt, '0010');
        $this->setEventVenue($evt, $this->createVenue('Pool'));
        $catA = $this->createCategory('ADULT', $evt->id, 100);
    
        //create buyers
        $foo = $this->createUser('foo');
    
        //$fee_id = $this->createFee($base_fee, 10, null); //use this to force to use some custom fee setting (like for the tour 37bb4d4f on 2013-06-26)
        $fee_id = $this->createModuleFee('baseFee', $base_fee, 10, null, Module::OUTLET);
        $this->setUserParams($seller, array('fee_id'=>$fee_id));
        //return;
    
        $outlet = new \OutletModule($this->db, 'outlet1');
        Utils::clearLog();
        $outlet->addItem('aaa', $catA->id, 1);
        $outlet->payByCash($foo);
    
        //ASSERTIONS
        $data = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
        $this->assertEquals(10.30, $data['price_fee']);
        $this->assertEquals(1.80, $data['fee_tax_1']); //it should be the same with has_tax = 1|0
        $this->assertEquals(14.89, $data['price_taxe_1']);
        $this->assertEquals(100 - 14.89, $data['price_category']);
        $this->assertEquals(100, $data['original_price']);
    
    }
    
    /**
     * Special test to inspect Tour of Garrison Historic Area  
     * @return string
     */
    function test_garrison(){
        
        $this->clearAll();
        
        $base_fee = 1.92;
         
        $this->createOutlet('Outlet 1', '0010');
        
        //create sellers
        $seller = $this->createUser('seller');
        
        $evt = $this->createEvent('Tour of Garrison (Fake)', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
        $this->setEventId($evt, 'aaa');
        $this->setEventGroupId($evt, '0010');
        $this->setEventVenue($evt, $this->createVenue('Pool'));
        $this->setEventParams($evt->id, array('has_tax'=>0));
        $catA = $this->createCategory('Adult Ticket (Inc, Hotel Trans', $evt->id, 89);
        $this->createCategory('Children Ticket (Inc. Hotel Tr', $evt->id, 67);
        $this->createCategory('Adult Ticket (Excl. Hotel Tran', $evt->id, 82);
        $this->createCategory('Children Ticket (Excl. Hotel T', $evt->id, 60);
        
        //create buyers
        $foo = $this->createUser('foo');
        
        $fee_id = $this->createFee($base_fee, 0, null); //use this to force to use some custom fee setting (like for the tour 37bb4d4f on 2013-06-26)
        $this->setUserParams($seller, array('fee_id'=>$fee_id));
        //return;
        
        $outlet = new \OutletModule($this->db, 'outlet1');
        Utils::clearLog();
        $outlet->addItem('aaa', $catA->id, 1);
        //$outlet->payByCash($foo);
        Utils::clearLog();
        $outlet->payByCC($foo, $this->getCCData());
        
        //ASSERTIONS
        $data = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
        /*$this->assertEquals(10.30, $data['price_fee']);
        $this->assertEquals(1.80, $data['fee_tax_1']); //it should be the same with has_tax = 1|0
        $this->assertEquals(14.89, $data['price_taxe_1']);
        $this->assertEquals(100 - 14.89, $data['price_category']);
        $this->assertEquals(100, $data['original_price']);
        */
    
    }
    
    //same setup as previous test, but this one does have taxes (wth?)
    function test_garrison_tax(){
    
        $this->clearAll();
    
        $base_fee = 1.92;
         
        $this->createOutlet('Outlet 1', '0010');
    
        //create sellers
        $seller = $this->createUser('seller');
    
        $evt = $this->createEvent('Tour of Garrison (Fake)', 'seller', $this->createLocation()->id, $this->dateAt("+1 day"), '09:00', $this->dateAt("+5 day") );
        $this->setEventId($evt, 'aaa');
        $this->setEventGroupId($evt, '0010');
        $this->setEventVenue($evt, $this->createVenue('Pool'));
        //$this->setEventParams($evt->id, array('has_tax'=>0));
        $catA = $this->createCategory('Adult Ticket (Inc, Hotel Trans', $evt->id, 89);
        $this->createCategory('Children Ticket (Inc. Hotel Tr', $evt->id, 67);
        $this->createCategory('Adult Ticket (Excl. Hotel Tran', $evt->id, 82);
        $this->createCategory('Children Ticket (Excl. Hotel T', $evt->id, 60);
    
        //create buyers
        $foo = $this->createUser('foo');
    
        $fee_id = $this->createFee($base_fee, 0, null); //use this to force to use some custom fee setting (like for the tour 37bb4d4f on 2013-06-26)
        $this->setUserParams($seller, array('fee_id'=>$fee_id));
        //return;
    
        $outlet = new \OutletModule($this->db, 'outlet1');
        Utils::clearLog();
        $outlet->addItem('aaa', $catA->id, 1);
        //$outlet->payByCash($foo);
        Utils::clearLog();
        $outlet->payByCC($foo, $this->getCCData());
    
        //ASSERTIONS
        $data = $this->db->auto_array("SELECT * FROM ticket LIMIT 1");
        /*$this->assertEquals(10.30, $data['price_fee']);
         $this->assertEquals(1.80, $data['fee_tax_1']); //it should be the same with has_tax = 1|0
        $this->assertEquals(14.89, $data['price_taxe_1']);
        $this->assertEquals(100 - 14.89, $data['price_category']);
        $this->assertEquals(100, $data['original_price']);
        */
    
    }
    
    

    // ********************************* New calculator logic might make all the old tests assertiins obsolete ************************************ //
    
  
  //No discounts
  function xtest100(){
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
  
  function xtestDiscount(){
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
  
  function xtestFeeMax(){
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
  
  
  function xtestSingle(){ //no discount
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
    $this->createPromocode('HALF', $evt->id, $catA, 50, 'p');
    $this->createPromocode('COMP', $evt->id, $catA, 100, 'p',true);
    
    //return;
    
    //create buyers
    $foo = $this->createUser('foo');
    
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::OUTLET);
    
    $outlet = new \OutletModule($this->db, 'outlet1');
    Utils::clearLog();
    $outlet->addItem('aaa', $catA->id, 1);
    $outlet->applyPromocode('aaa', 'HALF');
    $outlet->payByCash($foo);
  }
  
  function xtestDis90(){
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
    $this->createPromocode($code, $evt->id, $catA, 100, 'p');

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
  function xtestDisMad(){
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
    $this->createPromocode($code, $evt->id, $catA, 999.99, 'f');

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
  
  
  
  function xtestFixedEdge(){
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
    $this->createPromocode($code, $evt->id, $catA, 98.57, 'f');

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
    
    Utils::clearLog();
    //A 50% discount
    $this->createPromocode('HALF', $evt->id, $catA, 50, 'p');
    $this->createPromocode('COMP', $evt->id, $catA, 100, 'p', true);
    
    //false success. expect 2 promocodes created
    $this->assertRows(2, 'promocode');
    

    //create buyers
    $foo = $this->createUser('foo');
    
    $this->createModuleFee('test', 1, 2.5, 100, Module::WEBSITE);
    
    $web = new \WebUser($this->db);
    $web->login($foo->username);
    Utils::clearLog();
    $web->addToCart($evt->id, $catA->id, 1, 'COMP' );
    Utils::clearLog();
    @$web->getTickets(); //for now supress warnings
    
    
    //we expect the ticket created price_category cancelled out by price_promocode
    $this->assertEquals(0.00, $this->db->get_one("SELECT price_category-price_promocode FROM ticket LIMIT 1"));
    
    
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