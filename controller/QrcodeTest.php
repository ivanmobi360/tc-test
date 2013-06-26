<?php
use model\Transaction;
use model\Payment;
use tool\PaymentProcessor\Paypal;
use reports\ErrorTrackHelper;
use reports\ProcessorReturnParser;
use reports\ReportLib;
class QrcodeTest extends \DatabaseBaseTest{
  
  /**
   * "We will keep the exact same length of the QR Code, but the first 8 characters will be the event ID for which the ticket is being created.
	  For example, a QR Code that would normally look like this "BBDSA3PE75TUYF7Q" for the event ID "0541C021" will now look like: 0541C02175TUYF7Q
	  Let's keep in mind that on TixPro Caribbean, the "tour" events have a different event ID for each date. That "tour_dates.event_id" is the event ID we need to use in the case of Tours."
   */ 
  public function testTour(){
     //simple fixture
    $this->clearAll();

    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $v1 = $this->createVenue('Pool');
    
    $outlet = $this->createOutlet('outlet1', '0001');
    $rsv1 = $this->createReservationUser('tixpro', $v1); //for reservations login
    
    //move this to a tour builder test
    $build = new TourBuilder($this, $seller);
    $build->time_start = '13:00'; //hmmmmm
    $build->date_start = date('Y-m-d');
    $build->date_end = date('Y-m-d', strtotime('+5 day'));
    $build->cycle = 'daily';
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];

    
    //le'ts do a tour purchase
    $out = new OutletModule($this->db, 'outlet1');
    $out->addItem('tour1', $catX->id, 1); Utils::clearLog();
    $out->payByCash($foo);
    
    //return;
    
    //combine with a normal event for fun
    $event_id = 'b1b2b3';
    $evt = $this->createEvent('Inscripcion de Don Burro', $seller->id, $this->createLocation()->id);
    $this->setEventId($evt, $event_id);
    $this->setEventGroupId($evt, '0001');
    $this->setEventVenue($evt, $v1);
    $cat = $this->createCategory('Plaza', $evt->id, 49.99);
    
    $this->createPromocode('GARLIC', $cat, 50);
    
    $out->addItem($event_id, $cat->id, 1);
    $out->applyPromoCode($event_id, 'GARLIC');
    $out->payByCash($foo);
    
    //For the moment override manually the ticket with the id of the promocode :(
    $pid = $this->createPromocode('CHEESE', $catX);
    $this->db->update('ticket', array('promocode_id'=>$pid), "event_id=?", 'tour1');
    
    // ******************* ACTUAL TEST ************************* //
    $tour1 = 'tour1';
    
    $this->assertCode($event_id);
    $this->assertCode($tour1);
    
  }
  
  function assertCode($event_id){
    $ticket = $this->db->auto_array("SELECT code FROM ticket WHERE event_id=? LIMIT 1", $event_id);
    $this->assertEquals(strtoupper($event_id), substr($ticket['code'], 0, strlen($event_id)) );
  }
  
  
  
 
  
  

  
  public function tearDown(){
    $_GET = array();
    $_SESSION = array();
    parent::tearDown();
  }
 
}

