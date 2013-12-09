<?php
namespace Gspay;

use tool\CurlHelper;

use controller\Checkout;

use tool\Request;

use Utils;

/**
 * This is actually a functional/integration test, since it is a multi step payment process
 * @author Ivan Rodriguez
 *
 */
class GspayTest extends \DatabaseBaseTest{
  
    
  function fixture(){
    $this->clearAll();
    
    
    
    $seller = $this->createUser('seller');
    $evt = $this->createEvent('Autoshow', $seller->id, $this->createLocation()->id, date('Y-m-d', strtotime("+5 day")) );
    $this->setEventId($evt, 'aaa');
    $this->setEventVenue($evt, $this->createVenue('Crystal Palace'));
    $this->setEventParams($evt->id, array('has_tax'=>0, 'has_ccfee'=>0)); //With this configuration, the category price becomes the final price, as is.
    $this->setEventPaymentMethodId($evt, self::GSPAY);
    $this->cat = $this->createCategory('Adult', $evt->id, 100.00, 100, 0);

    
    Utils::clearLog();
    
    $build = new \TourBuilder( $this, $seller);
    $build->template_name = 'Wolverine Template (teh fees)';
    $build->name = 'Wolverine Display (has ccfees)';
    $build->event_id = 'wolvie';
    $build->pre = 'jack';
    $build->build();
    $cats = $build->categories;
    $catA = $cats[1]; //the 100.00 one, yep, cheating
    $catB = $cats[0];
    
    //Transaction setup
    $this->foo = $this->createUser('foo');
    
    //let's buy
    $this->buyer = new \WebUser($this->db);
    $this->buyer->login($this->foo->username);

    //let's pay
    Utils::clearLog();
  }
  
  protected function doTransaction(){
      //post to check out to see what happens.
      Request::clear();
  
      $_POST = array(
              'sms-aaa-to' => '618994576'
              ,'sms-aaa-date' => '2013-06-03'
              ,'sms-aaa-time' => '09:00'
              ,'ema-aaa-to' => 'Foo@gmail.com'
              ,'ema-aaa-date' => '2013-06-01'
              ,'ema-aaa-time' => '09:00'
              ,'x' => '77'
              ,'y' => '41'
              ,'gspay' => 'on'
      );
  
      $cnt = new Checkout(); //used just to inspect output js in log
      return $this->db->get_one("SELECT txn_id FROM ticket_transaction ORDER BY id DESC LIMIT 1");
  }
  
  function testSuccess(){
    
    $this->fixture();
    
    $this->buyer->addToCart('aaa', $this->cat->id, 1); //cart in session
    $total_usd = ($this->buyer->getCart()->getTotal())/2;
    $txn_id = $this->doTransaction();
    
    //some simple expectations
    //$this->assertEquals(1, $this->db->get_one("SELECT COUNT(id) FROM ticket_transaction WHERE"))
    $this->assertRows(1, 'ticket_transaction', "delivery_method=? AND completed=0", 'gspay');
    
    //let's simulate a GET redirect from gspay
    //http://localhost/tixprocaribbean/website/gspay?transactionTransactionID=TR1381862812307&transactionStatus=test&customerOrderID=TC-IOZP5-6GLK8-5BKA5&transactionAmount=50
    Utils::clearLog();
    $this->clearRequest();
    $_GET = array(
            'transactionTransactionID' => 'TR1381862812307',
            'transactionStatus' => 'test',
            'customerOrderID' => $txn_id,
            'transactionAmount' => $total_usd
            );
    $cnt = new \controller\Gspay();
    $this->assertTrue($cnt->success); //we only test if redirected. we expect the tickets to be created asynchronously by some ipn send by gspay and processed by our ipn listener
  }
  
  
  
  function testListener(){

      $this->fixture();
      
      $buyer = $this->buyer;
      $buyer->addToCart('aaa', $this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $total_usd = $total/2;
      $txn_id = $this->doTransaction();
      
      
      
      //return;
      
      Utils::clearLog();
      Request::clear();
      $_POST = $_GET = array();
      
      //$xml = $this->createXml($buyer->id, $txn_id, $total);
      
      //Mimic a post response;
      Utils::clearLog();
      $_GET['pt'] = 'gspay';
      $_POST = $this->getFields($txn_id, $total_usd );
      $cnt = new \controller\Ipnlistener();
      
      $this->assertEquals(self::GSPAY, $this->db->get_one("SELECT payment_method_id FROM transactions_processor LIMIT 1"));
      $this->assertRows(1, 'transactions_processor', " amount=? AND currency=? ", array($total_usd, 'USD'));
      $this->assertRows(1, 'ticket');
      //return;
      
      
      //If the same message is sent again, nothing happens
      Request::clear();
      $_GET['pt'] = 'gspay';
      $_POST = $this->getFields($txn_id, $total_usd );
      $cnt = new \controller\Ipnlistener();
      
      $this->assertRows(1, 'transactions_gspay');
      $this->assertRows(1, 'transactions_processor', "amount=? AND currency=?", array(50, 'USD'));
      $this->assertRows(1, 'ticket');
      
  }
  
  /**
   * Ensure ccfees are sent to gateway
   */
  function testCCFees(){
      $this->fixture();
      $this->setEventParams('aaa', array( 'has_ccfee'=>1));
      $buyer = $this->buyer;
      $buyer->addToCart('aaa', $this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $total_usd = $total/2;
      $txn_id = $this->doTransaction();
      
      $this->assertTrue($this->db->get_one("SELECT fee_cc from ticket_transaction LIMIT 1")>0);
      
      // amount sent to gateway must be total_usd + ccfees_usd !
      // confirmed in controller/Payment (added ccfee logic) 
      
  }
  
  /**
   * If we charged ccfees, the listener must be able to process this
   */
  function testListenedCCFees(){
  
      $this->fixture();
      $this->setEventParams('aaa', array( 'has_ccfee'=>1));
        
      $buyer = $this->buyer;
      $buyer->addToCart('aaa', $this->cat->id, 1); //cart in session
      $total = $buyer->getCart()->getTotal();
      $total+= \model\TransactionsManager::getCCFee($total);
      $total_usd = number_format($total/2, 2);
      $txn_id = $this->doTransaction();
  
  
  
      //return;
  
      Utils::clearLog();
      Request::clear();
      $_POST = $_GET = array();
  
      //$xml = $this->createXml($buyer->id, $txn_id, $total);
  
      //Mimic a post response;
      Utils::clearLog();
      $_GET['pt'] = 'gspay';
      $_POST = $this->getFields($txn_id, $total_usd );
      $cnt = new \controller\Ipnlistener();
  
      $this->assertEquals(self::GSPAY, $this->db->get_one("SELECT payment_method_id FROM transactions_processor LIMIT 1"));
      $this->assertRows(1, 'transactions_processor', " ABS(amount-?<0.01) AND currency=? ", array($total_usd, 'USD'));
      $this->assertRows(1, 'ticket');
      return;
  
  
      //If the same message is sent again, nothing happens
      Request::clear();
      $_GET['pt'] = 'gspay';
      $_POST = $this->getFields($txn_id, $total_usd );
      $cnt = new \controller\Ipnlistener();
  
      $this->assertRows(1, 'transactions_gspay');
      $this->assertRows(1, 'transactions_processor', "amount=? AND currency=?", array(50, 'USD'));
      $this->assertRows(1, 'ticket');
  
  }
  
  
    protected function getFields($txn_id, $amount_usd, $inData = array()){
        
        $post = array(
    "key" => "",
    "transactionID" => "TR1381846694115",
    "transactionType" => "sale",
    "transactionStatus" => "test",
    "transactionAmount" => $amount_usd,
    "transactionCurrency" => "USD",
    "action" => "adduser",
    "username" => "694c77d9",
    "password" => "1cfa10fd",
    "OrderID" => $txn_id,
    "customerFullName" => "Mathias Kodjo AGBAYI",
    "customerPhone" => "14384025884",
    "customerAddress" => "8600  boul decarie",
    "customerCity" => "Montreal",
    "customerZip" => "H3L1p6",
    "customerCountryCode" => "CA",
    "customerCountry" => "Canada",
    "customerEmail" => "kagbayi@mobi360.ca",
    "returnURL" => "http://tixprocaribbean.com/taz/website/gspay",
    "DeclineURL" => "http://tixprocaribbean.com/taz/website/gspay?declined=1",
    "TransactionMode" => "test",
    "customerShippingState" => "XX",
    "customerShippingStateCode" => "XX",
    "customerStateCode" => "QC",
    "customerLogin" => "694c77d9",
    "customerPassword" => "1cfa10fd",
    "customerShippingFullName" => "",
    "customerShippingPhone" => "",
    "customerShippingAddress" => "",
    "customerShippingCity" => "",
    "customerShippingZip" => "",
    "customerShippingCountryCode" => "",
    "customerShippingEmail" => "",
    "customerAdditionalParameters" => "",
    "customerShippingCountry" => "",
    "customerIP" => "50.21.174.1",
    "customerOrderID" => $txn_id,
    "customerReferer" => "http://tixprocaribbean.com/taz/website/checkout-337c",
    "customerOrderProductListing" => "Tickets purchased on tixprocaribbean.com, qty 1, price $amount_usd, total $amount_usd",
	);
        return array_merge($post, $inData);
        
  }
  
  
}