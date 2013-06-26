<?php
/**
 * Tixpro Caribbean fixture generator
 * @author Ivan Rodriguez
 *
 */

use model\DeliveryMethod;
require_once '../website/global_report_api.php';

class GlobalCreditCardReportApiTest extends DatabaseBaseTest {
  
  function testRefund(){
    $this->clearAll();
    
    $this->db->beginTransaction();
    
    $v1 = $this->createVenue('Pool');
   
    $seller = $this->createUser('seller');
    
    $evt = $this->createEvent('Carbiean Normal Event A', $seller->id, $this->createLocation()->id, '2012-08-07', '09:00', '2012-08-24' );
    $this->setEventId($evt, 'nnn');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $this->setEventPaymentMethodId($evt, self::OUR_CREDIT_CARD);
    $catA = $this->createCategory('ADULT', $evt->id, 100);
    $catB = $this->createCategory('KID'  , $evt->id, 50);
    
    
    $build = new TourBuilder( $this, $seller);
    $build->build();
    
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];
    
    $foo = $this->createUser('foo');
    $txn_id = $this->buyTicketsWithCC($foo->id, 'nnn', $catA->id, 2);
    $this->setDateOfTransaction($txn_id, '2012-07-25');
    
    $txn_id = $this->buyTicketsWithCC($foo->id, 'nnn', $catA->id, 1);
    $this->setDateOfTransaction($txn_id, '2012-07-27');
    $txn_id = $this->buyTicketsWithCC($foo->id, 'nnn', $catA->id, 2);
    $this->setDateOfTransaction($txn_id, '2012-07-27');
    
    $bar = $this->createUser('bar');
    $txn_id = $this->buyTicketsWithCC($bar->id, 'tour1', $catX->id, 5);
    $this->setDateOfTransaction($txn_id, '2012-07-24');
    
    
    $this->db->commit();
    
    $txn_list = $this->db->get_col("SELECT txn_id FROM transactions_optimal");
    
    Utils::clearLog();
    Utils::log(var_export($txn_list, true));
    
    //return;
    
    $api = new MockGlobalReportApi();
    $api->setData($txn_list);
    $api->process();
    $this->assertTrue(is_array($api->getRows()));
    
  }
  

}


class MockGlobalReportApi extends GlobalReportApi{
  
}