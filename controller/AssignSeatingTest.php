<?php
/**
 * Multiple AssignSeating event logic is tested here
 * 
 * Pool generation at
 * http://localhost/tixprocaribbean/website/louis_pool_generator.php
 *  
 * @author Ivan Rodriguez
 *
 */
class AssignSeatingTest extends DatabaseBaseTest{
    
    const LOUIS_EVENT_ID = '26fc242e';
  
  function xtestCreate(){
      $this->clearAll();
      $this->db->Query("TRUNCATE TABLE ticket_pool");
      
      $user = $this->createUser('foo');
      $seller = $this->createUser('seller');
      $v1 = $this->createVenue('Pool');
      
      $this->createSimpleEvent($seller->id, $v1);
      $this->createLouis($seller, true);
      
      $this->assertRows(504, 'ticket_pool');
    
  }
  
  
  function testPurchase(){
      $this->commonFixture();
      $this->assertRows(504, 'ticket_pool');
      
      
      //Now we'll do some purchases. First we'll do a purchase of a map selected 4-8 table
      $web = new \WebUser($this->db); $web->login('foo@blah.com'); //login for laughs
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchaseRequest();
      $_GET = array('page' => 'pay');
      $cont = new \controller\Assignseating();
      
      //Expect a transaction
      $this->assertRows(1, 'ticket');
      $this->assertRows(1, 'ticket_transaction', " ticket_count=1 AND completed=1 ");
      $this->assertRows(1, 'ticket_pool', 'ticket_id IS NOT NULL AND txn_id IS NOT NULL');
      
      //purchase another
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchaseRequest('P1');
      $_GET = array('page' => 'pay');
      $cont = new \controller\Assignseating();
      
      //Expect a transaction
      $this->assertRows(2, 'ticket');
      $this->assertRows(2, 'ticket_transaction', " ticket_count=1 AND completed=1 ");
      $this->assertRows(2, 'ticket_pool', 'ticket_id IS NOT NULL AND txn_id IS NOT NULL');
  }
  
  //fixes for names like 'P1'
  function testBug01(){
      $this->commonFixture();
  
  
      //Now we'll do some purchases. First we'll do a purchase of a map selected 4-8 table
      $web = new \WebUser($this->db); $web->login('foo@blah.com'); //login for laughs
      $this->clearRequest(); Utils::clearLog();
      $_POST = $this->purchaseRequest('P1');
      $_GET = array('page' => 'pay');
      $cont = new \controller\Assignseating();
  
      //Expect a transaction
      $this->assertRows(1, 'ticket');
      $this->assertRows(1, 'ticket_transaction', " ticket_count=1 AND completed=1 ");
      $this->assertRows(1, 'ticket_pool', 'ticket_id IS NOT NULL AND txn_id IS NOT NULL');

  }
  
  protected function commonFixture(){
      $this->clearAll();
  
  
      $user = $this->createUser('foo');
      $seller = $this->createUser('seller');
      $v1 = $this->createVenue('Pool');
      /*$out1 = $this->createOutlet('Outlet 1', '0010');
       $bo_id = $this->createBoxoffice('111-xbox', $seller->id);
      $rsv1 = $this->createReservationUser('tixpro', $v1);*/
  
  
      $this->createSimpleEvent($seller->id, $v1);
    
      $this->createLouis($seller, false);
  }
  
  //this will create us the location too
  protected function createSimpleEvent($seller_id, $v1){
      $evt = $this->createEvent('Simple Event', $seller_id, $this->createLocation()->id, $this->dateAt('+5 day'));
      $this->setEventId($evt, 'aaarghhh');
      $this->setEventGroupId($evt, '0010');
      $this->setEventVenue($evt, $v1);
      $catA = $this->createCategory('MINION', $evt->id, 100.00);
      ModuleHelper::showEventInAll($this->db, $evt->id);
  }
  
  /**

   * @param $create_pool if false, it means you'll populate the ticket_pool table, from some fixture for instance
   */
  protected function createLouis($seller, $create_pool= true){
      $client = new WebUser($this->db);
      $client->login($seller->username);
      $_POST = $this->LOUIS_create_data();
      Utils::clearLog();
       
      $cont = new controller\Newevent(); //all the logic in the constructor haha
       
      $event_id = $this->getLastEventId();
      $event_id = $this->changeEventId($event_id, self::LOUIS_EVENT_ID);
      //ModuleHelper::showEventInAll($this->db, $event_id);
      //Have to do this manually
      foreach( $rows = $this->db->getAll("SELECT id FROM category WHERE event_id=?", $event_id) as $row){
          ModuleHelper::showInWebsite($this->db, $row['id']);
      }
      
      //for now we'll have to rely in this hack
      $cat_id = \Database::get_one("SELECT id FROM category WHERE category_id IS NOT NULL AND category_id!=0 AND event_id=?", $event_id);
      
      
      if($create_pool){
          $gen = new \tool\LouisLynchTicketPoolGenerator();
          $gen->event_id = self::LOUIS_EVENT_ID;
          $gen->cat_vip = $cat_id;
          $gen->build();
          $xml = $gen->getAssignXml();
          Utils::log($xml);
          file_put_contents('C:\wamp\www\tixprocaribbean\website\resources\images\event\26\fc\24\2e\assign\assign.xml', $xml);
      }else{
          $this->louisTicketFixture();
      }
      
      
  }
  
  
  protected function louisTicketFixture(){
      if ($this->db->get_one("SELECT id FROM ticket_pool LIMIT 1")){
          //just reset them
          $this->db->Query("UPDATE ticket_pool SET time_reserved=NULL, txn_id=NULL, reserved=0, ticket_id=NULL, name=''");
      }else{
          $this->db->beginTransaction();
          $this->db->executeBlock(file_get_contents(__DIR__ . "/fixture/louis_ticket_pool.sql"));
          $this->db->commit();
      }
  }
  
  protected function LOUIS_create_data(){
      return array (
  'MAX_FILE_SIZE' => '3000000',
  'is_logged_in' => '1',
  'copy_event' => 'aaarghhh',
  'e_name' => 'Louis Lynch',
  'e_capacity' => '',
  'outlet_2' => 'on',
  'venue' => '1',
  'e_date_from' => '2014-03-15',
  'e_more_date' => 'on',
  'e_time_from' => '20:02',
  'e_date_to' => '',
  'e_time_to' => '20:02',
  'e_description' => '<p>blah</p>',
  'reminder_email' => '',
  'sms' => 
  array (
    'content' => '',
  ),
  'c_id' => '3',
  'l_id' => '4',
  'l_latitude' => '53.9332706',
  'l_longitude' => '-116.5765035',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '1',
  'e_currency_id' => '5',
  'payment_method' => '3',
  'tax_ref_other' => 'b4rb4d0s',
  'ticket_type' => 'open',
  'cat_all' => 
  array (
    0 => '3',
    1 => '2',
    2 => '1',
  ),
  'cat_3_type' => 'open',
  'cat_3_name' => 'Stand A',
  'cat_3_description' => '',
  'cat_3_sms' => '1',
  'cat_3_multiplier' => '1',
  'cat_3_capa' => '700',
  'cat_3_over' => '0',
  'cat_3_price' => '15.00',
  'copy_to_categ_3' => '',
  'copy_from_categ_3' => '-1',
  'modules_3_' => 
  array (
    0 => '4',
  ),
  'cat_2_type' => 'open',
  'cat_2_name' => 'Grounds',
  'cat_2_description' => '',
  'cat_2_sms' => '1',
  'cat_2_multiplier' => '1',
  'cat_2_capa' => '400',
  'cat_2_over' => '0',
  'cat_2_price' => '20.00',
  'copy_to_categ_2' => '',
  'copy_from_categ_2' => '-1',
  'modules_2_' => 
  array (
    0 => '4',
  ),
  'cat_1_type' => 'table',
  'cat_1_name' => 'VIP Stand',
  'cat_1_description' => '',
  'cat_1_sms' => '1',
  'cat_1_capa' => '504',
  'cat_1_over' => '0',
  'cat_1_tcapa' => '1',
  'cat_1_price' => '25.00',
  'cat_1_ticket_price' => '0.00',
  'cat_1_seat_name' => '',
  'cat_1_seat_desc' => '',
  'create' => 'do',
  'has_ccfee' => '0',
);
  }
  
  protected function purchaseRequest($seat='J18'){
      return array (
          'reg_new_username' => '',
          'reg_confirm_username' => '',
          'reg_new_password' => '',
          'reg_confirm_password' => '',
          'reg_language_id' => '',
          'reg_name' => '',
          'reg_home_phone' => '',
          'reg_phone' => '',
          'reg_l_street' => '',
          'reg_l_country_id' => '',
          'reg_l_state' => '',
          'reg_l_city' => '',
          'reg_l_zipcode' => '',
          'reg_l_street2' => '',
          'user_id' => 'foo',
          'total' => 'BBD 25.00 <br><span style=',
          332 => '0',
          'cat_list' => 
          array (
            0 => '332',
            1 => '333',
            2 => '334',
          ),
          333 => '0',
          334 => '1',
          'table' => 
          array (
            0 => '334-' . $seat . '-1',
          ),
          'cc_holder_name' => 'asd asd',
          'cc_number' => '5301250070000050',
          'cc_ccv' => '123',
          'cc_month' => '01',
          'cc_year' => '2020',
          'bil_name' => 'Calle 1',
          'bil_city' => 'Carter',
          'bil_state' => 'Carter',
          'bil_country' => 'Barbados',
          'bil_zipcode' => 'CA',
          'mailing_list' => 'yes',
        );
  }

 
}