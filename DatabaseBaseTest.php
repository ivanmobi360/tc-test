<?php
use Forms\BoxOffice;
use controller\Logout;
use controller\Checkout;
use tool\Session;
use model\Eventcontact;
use model\Users;
use model\Tickettransactionmanager;
use model\Tickettransaction;
use tool\Request;
use model\Events;use model\TicketBuilder;
use model\Locations;
use model\Categories;
use model\Transaction;

abstract class DatabaseBaseTest extends BaseTest{
  
  /* @var TestDatabase */
  public $db;
  
  protected $database_name = 'tixpro_caribbean';
  
  //payment methods id
  const PAYPAL = 1;
  const OUR_PAYPAL = 2;
  const OUR_CREDIT_CARD = 3;
  
  // ** THIS WAS FOR TIXPRO - PORTED HERE JUST TO TEST RELATED ADMIN MODULES ** apparently some code was written for this once in a lifetime event - Reserve Tickets, Move Seats
  const STRANGER_IN_THE_NIGHT_ID = 'a20f69f3';
  
  /**
  * Generic coupon code used in 
  * @see CouponCodeHandlerTest
  */
  protected $coupon_code = 'some-code';
  
  protected $test_email = 'tester_buyer@test.com';
  protected $testUserid = '99';
  
  protected $merchantid = 'mg3v3nt5';
  
  protected $serial;
  
  public function setUp(){
    Utils::log("setUp");
    date_default_timezone_set('America/Guayaquil');
    \Database::init(DB_HOSTNAME, $this->database_name, DB_USERNAME, DB_PASSWORD);
    $this->db = new TestDatabase();
    
    $this->resetSerial();
    Request::clear();
  }
  
  public function tearDown(){
    Utils::log("tearDown");
    /*$this->db->close();
    unset($GLOBALS['db']);*/
    unset($this->db);
    parent::tearDown();
  }
  
  protected function clearAll(){
  	
  	$this->db->Query("SET FOREIGN_KEY_CHECKS=0;"); //to allow truncates
    
    $this->db->Query("TRUNCATE TABLE ticket_transaction");
    $this->db->Query("ALTER TABLE `ticket_transaction` AUTO_INCREMENT = 875000000000903;");
    
    $this->db->Query("TRUNCATE TABLE disponibility");
    
    $this->db->Query("TRUNCATE TABLE location");
    $this->db->Query("TRUNCATE TABLE contact");
    $this->db->Query("TRUNCATE TABLE user");
    $this->db->Query("TRUNCATE TABLE category");
    $this->db->Query("ALTER TABLE `category` AUTO_INCREMENT = 330;");
    
    $this->db->Query("TRUNCATE TABLE ticket");
    $this->db->Query("ALTER TABLE `ticket` AUTO_INCREMENT = 777;");
    $this->db->Query("TRUNCATE TABLE ticket_info");
    
    //tours
    $this->db->Query("TRUNCATE TABLE tour_settings");
    $this->db->Query("TRUNCATE TABLE tour_dates");
    $this->db->Query("TRUNCATE TABLE ticket_reservation");
  	$this->db->Query("TRUNCATE TABLE vehicles");
  	$this->db->Query("TRUNCATE TABLE vehicles_tour");
    
    $this->db->Query("TRUNCATE TABLE event");
    $this->db->Query("TRUNCATE TABLE event_contact");
    $this->db->Query("TRUNCATE TABLE event_email");
    $this->db->Query("TRUNCATE TABLE media"); //for EventList test
    //mesas
    $this->db->Query("TRUNCATE TABLE room_designer");
    $this->db->Query("TRUNCATE TABLE ticket_table");
    
    
    $this->db->Query("TRUNCATE TABLE error_track");
    $this->db->Query("TRUNCATE TABLE transactions_processor");
    
    $this->db->Query("TRUNCATE TABLE promocode");
    $this->db->Query("TRUNCATE TABLE promocode_category");
    
    
    $this->db->Query("TRUNCATE TABLE transactions_optimal");
    $this->db->Query("TRUNCATE TABLE transactions_cash");
    
    
    $this->db->Query("TRUNCATE TABLE merchant_invoice");
    $this->db->Query("TRUNCATE TABLE merchant_invoice_line");
    $this->db->Query("TRUNCATE TABLE merchant_invoice_taxe");
    
    $this->db->Query("TRUNCATE TABLE email_processor");
    
    
    $this->db->Query("TRUNCATE TABLE banner");
    $this->db->Query(file_get_contents(__DIR__ . "/fixture/banner.sql"));
    
    $this->db->Query("TRUNCATE TABLE venue");
    $this->db->Query("TRUNCATE TABLE outlet");
    $this->db->Query("ALTER TABLE `outlet` AUTO_INCREMENT = 21;");
    
    $this->db->Query("TRUNCATE TABLE bo_user");
    $this->db->Query("ALTER TABLE `bo_user` AUTO_INCREMENT = 31;");
    
    $this->db->Query("TRUNCATE TABLE reservation");
    $this->db->Query("ALTER TABLE `reservation` AUTO_INCREMENT = 401;");
    $this->db->Query("TRUNCATE TABLE reservation_transaction");
    

  	
  	$this->db->Query("TRUNCATE TABLE  event_outlet_exclusion");
    
    $this->clearReminders();
    
    $this->resetFees();
    
    $this->insertJohnDoe();
    
  }
  
  function resetFees(){
    $this->db->executeBlock(file_get_contents(__DIR__ . "/fixture/fee-reset.sql"));
    //$this->db->Query("TRUNCATE TABLE specific_fee");
  }
  
  function createModuleFee($name, $fixed, $percentage, $fee_max, $module_id, $is_default=1){
    //Create some module fees
    /*$data = array(
        'type' => 'tf'
      , 'name' => $name
      , 'module_id' => $module_id
      , 'is_default' => $is_default
    );
    
    if ($is_default){
      \Database::update('fee', array('is_default'=>0), "module_id=? AND type='tf'", $module_id  );
    }
    $this->createFee($fixed, $percentage, $fee_max, $data);
    return new \model\FeeVO($fixed, $percentage, $fee_max);*/
    return $this->createSpecificFee($name, $fixed, $percentage, $fee_max, $module_id);
  }
  
  //function createSpecificFee($item_id, $item_type, $fixed, $percentage, $fee_max, $module_id ){
  function createSpecificFee($name, $fixed, $percentage, $fee_max, $module_id, $user_id=null, $event_id=null, $category_id=null ){
      return \model\SpecificFee::create($name, $fixed, $percentage, $fee_max, $module_id, $user_id, $event_id, $category_id);
  }
  
  function createFee($fixed, $percentage, $fee_max, $data=array()){
    $def = array_merge(array(
        'type' => 'tf'
        , 'fixed' => $fixed
        , 'percentage' => $percentage
        , 'fee_max' => $fee_max
    ), $data);
    \Database::insert('fee', $def);
    return \Database::getLastId();
  }
  
  //they seem to be changed these values, so we can't hardcore them in the long term. let's try to pick them up from the db
  protected function currentGlobalFee(){
      //for now, assume it is always the first one
      /*$res = $this->db->auto_array("SELECT id FROM `fee` WHERE `is_default` = 1 AND `type` = 'tf' AND module_id IS NULL LIMIT 1");
      return \model\Fee::load($res['id']);*/
      return \model\Fee::getGlobalFee();
  }
  
  //do not delete lightly, needed by buyTickets
  function getCCPurchaseData(){
    return array(
      'sent' => 1
      , 'cc_num' => '4715320629000001'
      , 'cc_name_on_card' => 'JOHN DOE'
      , 'exp_month' => '08'
      , 'exp_year' => date('Y', strtotime('+5 year'))
      , 'cc_cvd' => '1234'
      , 'cc_type' => 'Visa'
      , 'street' => 'Calle 1'
      , 'country' => '124'
      , 'city' => 'Ontario'
      , 'state' => 'QC'
      , 'zipcode' => 'HR54'
      , 'username' => 'foo@blah.com'
      
      
    );
  }
  
  
  protected function insertJohnDoe(){
    $sql = "
INSERT INTO `contact` (`id`, `user_id`, `name`, `email`, `phone`, `home_phone`, `company_name`, `position`) VALUES (1, 'johndoe1', 'POS Sale', 'johndoe1@tixpro.com', '5145555555', '5145555555', NULL, NULL);
    
INSERT INTO `location` (`id`, `user_id`, `name`, `street`, `street2`, `country_id`, `state_id`, `state`, `city`, `zipcode`, `longitude`, `latitude`) VALUES (1, 'johndoe1', 'My location', '8600 Decarie', 'Suite 100', 124, 2, 'Quebec', 'Montreal', 'H4P2N2', -73.6641, 45.5013);

INSERT INTO `user` (`id`, `username`, `password`, `created_at`, `active`, `contact_id`, `location_id`, `billing_location_id`, `language_id`, `paypal_account`, `tax_ref_hst`, `tax_ref_gst`, `tax_ref_pst`, `tax_ref_other`, `tax_other_name`, `tax_other_percentage`, `fee_id`, `cc_fee_id`, `code`, `locked_fee`, `maestro`, `sms_confirmation`) VALUES
('johndoe1', 'johndoe1@tixpro.com', '7d7a48d674347c4de6aa070950f3fbf2', '2012-03-23 11:38:59', 1, 1, 1, NULL, 'en', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 1);

    ";
    $this->db->executeBlock($sql);
    //$this->db->Query("INSERT INTO `user` (`id`, `username`, `password`, `created_at`, `active`, `contact_id`, `location_id`, `billing_location_id`, `language_id`, `paypal_account`, `tax_ref_hst`, `tax_ref_gst`, `tax_ref_pst`, `tax_ref_other`, `tax_other_name`, `tax_other_percentage`, `fee_id`, `cc_fee_id`, `code`) VALUES ('johndoe1', 'johndoe1@blah.com', 'e10adc3949ba59abbe56e057f20f883e', '2012-03-23 12:38:59', '1', '1', '1', NULL, 'en', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '');");
  }
  

  
  protected function clearTestTable(){
    $this->db->Query("TRUNCATE TABLE test");
  }
  
  protected function clearReminders(){
    $this->db->Query("TRUNCATE TABLE reminder");
    $this->db->Query("TRUNCATE TABLE reminder_sent");
  }
  
  protected function insertTestRows($total){
    $this->db->beginTransaction();
    for($i = 1; $i<=$total; $i++){
      $this->db->insert('test', array('title'=>"foo$i", 'content'=>"Some Content $i", 'hits'=>10+$i), true);
     }
    $this->db->commit();
  }

  
 public function getCreateCustomerData(){
    return array(
                  'email' => $this->test_email
                , 'new_password' => '123456'
                , 'confirm_password' => '123456'
                ,	'name' => 'Some'
                , 'surname' => 'Customer'
                , 'street' => 'Some Street'
                , 'city' => 'Some city'
                , 'state' => 'Some State'
                , 'country' => 'Canada'
                , 'zipcode' => 'ab0000'
                
                , 'cellphone' => "1234567890"
                , 'profession' => 'some profession'
                , 'language' => 'en'
                , 'team_id' => '99'
                , 'age' => 18
                , 'promcode' => 'foobarbaz'
                , 'position' => 'QTT'
                
                //??
                , 'company_id' => 'c0mp4ny1'
                , 'team_name' => 'blah'
                
                );
  }
  
  public function clearCustomers(){
    $this->db->Query("TRUNCATE TABLE customer");
    $this->db->Query("TRUNCATE TABLE master_cc");
  }
  
  public function clearPromoCodeTable(){
    $this->db->Query("TRUNCATE TABLE promo_codes");
  }
  
  
  protected function clearOrders(){
    $this->db->Query("TRUNCATE table `order`");
    $this->db->Query("TRUNCATE table `order_line`");
  }
  
  protected function clearTickets(){
    $this->db->Query("TRUNCATE table `ticket`");
  }
  
  protected function insertCustomer(){
    $this->db->Query("INSERT INTO `customer` (`id`, `username`, `rpassword`, `name`, `surname`, `street`, `city`, `state`, `country`, `zipcode`, `cellphone`, `profession`, `language`, `team_id`, `age`, `promcode`, `created_at`, `deleted`, `locked`, `active`) VALUES
(NULL, '$this->test_email', 'e10adc3949ba59abbe56e057f20f883e', 'Bill Gates', 'Dood', 'Pantalla', 'Somewhere2', 'Salta', 'Canada', '123456', '1234567890', 'Arquero', 'es', 98, 99, '', '2011-07-06 12:26:35', 0, 0, 1);
    ");
    return $this->db->insert_id();
  }
  // *********************************************************************************
  
 public function getCreditCardInsertData(){
        return array( 
                  //Credit Card data
                    'fname' => 'JOHN DOE'
                  , 'ccnum' => '4111111111111111'
                  , 'month' => 12
                  , 'year' => date('Y', strtotime("+ 1 year"))
                  , 'ccname' => 'visa'
                  , 'ccidnumber' => '111' //cvvv2, cvc2 or cid code 
                   );
  }
  
  //Credit card helpers
  protected function insertCustomerAndPartialCreditCard(){
    $this->db->Query("INSERT INTO `customer` (`id`, `username`, `rpassword`, `name`, `surname`, `street`, `city`, `state`, `country`, `zipcode`, `cellphone`, `profession`, `language`, `team_id`, `age`, `promcode`, `created_at`, `deleted`, `locked`, `active`) VALUES
(NULL, '$this->test_email', 'e10adc3949ba59abbe56e057f20f883e', 'Bill Gates', 'Dood', 'Pantalla', 'Somewhere2', 'Salta', 'Canada', '123456', '1234567890', 'Arquero', 'es', 98, 99, '', '2011-07-06 12:26:35', 0, 0, 1);
    ");
      
    //and partial credit card - no psiAccount at the moment
    $this->db->Query("INSERT INTO `master_cc` (`id`, `customer_id`, `psigate_id`, `psi_serial_no`, `holder`, `number`, `exp_month`, `exp_year`, `type`, `created_at`, `updated_at`, `ip`, `status`, `deleted`, `verified`) VALUES
(NULL, '{$this->testUserid}', '', '', 'CREDIT CARD TESTER', '', '12', '11', 'visa', '2010-12-23 10:19:55', '2010-12-23 10:19:55', '0.0.0.0', '1', 0, 0);");
    $this->ccid = $this->db->insert_id();
  }

  protected function createEvent($name, $user_id, $location_id, $date_from=false, $time_from=null, $date_to=null, $time_to=null){
    if(empty($location_id)){ throw new Exception(__METHOD__ . " location id is required");}
    $event = new Events();
    $event->name = $name;
    
    $event->date_from = $date_from?:date('Y-m-d');
    
    if(empty($time_from)){
      //to be listed in outlet, it must happen at least 4 hours in the future
      $time_from = date('H:i:s', strtotime("+5 hour"));
    }
    
    $event->time_from = $time_from;//?:date('H:i:s');
    
    $event->user_id = $user_id;
    $event->location_id = $location_id;
    
    $event->date_to = $date_to;
    $event->time_to = $time_to?:date('H:i:s', strtotime("+5 hour")); //Box office select needs this set to the future, otherwise defaults to 'date_from 00:00:00' 
    $event->active=1;
    
    //cant be null :/
    $event->description = 'blah';
    
    $event->currency_id = 5;// 1-CAD, 2-USD, 5-BBD;
    $event->private = 0;
    
    $event->ticket_template_id = '1';
    
    $event->payment_method_id = self::OUR_CREDIT_CARD;//     self::PAYPAL;// 1; //???
    $event->has_tax = 1;
    $event->fee_id = 1; //magic??
    
    $event->event_theme_id = 1;
    
    $event->group_id ='0001';
    
    $event->tour = 0;
    
    $event->insert();
    
    //contact?
    $user = new \model\Users($user_id);
    $ec = new \model\Eventcontact($event->id, $user->contact_id);
    $ec->insert();
    
    //further flag that contact with userid?
    $this->db->update('contact', array('user_id'=>$user_id), 'id=?', $user->contact_id);
    $this->db->update('location', array('user_id'=>$user_id), 'id=?', $location_id);
    

    return $event;
  }
  
  function setEventId($evt, $new_event_id){
    $this->changeEventId($evt->id, $new_event_id);
    $evt->id = $new_event_id;
  }
  
  function setCategoryId($cat, $new_category_id){
    $this->db->update('category', array( 'id' => $new_category_id), "id=?", $cat->id);
    $cat->id = $new_category_id;
  }
  
  
  function setPaymentMethod($evt, $payment_method_id){
    $this->db->update('event', array( 'payment_method_id' => $payment_method_id ), "id=?", $evt->id);
  }
  
  function setEventParams($event_id, $params){
    $this->db->update('event', $params, "id=?", $event_id);
  }
  
  function setEventGroupId($evt, $bits){
    $bits = "b'$bits'";
    $this->db->Query("UPDATE `event` SET `group_id` = $bits WHERE id=?", $evt->id);
  }
  
  function setEventVenue($evt, $venue_id){
    $this->db->update('event', array('venue_id'=> $venue_id), "id=?", $evt->id);
  }
  
  /**
   * 
   * Enter description here ...
   * @param Event $evt
   * @param string $tax_name VAT
   * @param string $percentage 17.5
   * @param string $ref_other c4r1b34n
   */
  function setEventOtherTaxes($evt, $tax_name, $percentage, $ref_other){
    $this->db->update('user', array(  'tax_other_name'=> $tax_name
                                      , 'tax_other_percentage' => $percentage
                                      , 'tax_ref_other' => $ref_other    
                                    ), "id=?", $evt->user_id);
  }
  
  function changeEventId($event_id, $new_event_id){
    $this->db->update('event', array( 'id' => $new_event_id ), "id=?", $event_id);
    $this->db->update('event_contact', array( 'event_id' => $new_event_id ), "event_id=?", $event_id);
    $this->db->update('event_email', array( 'event_id' => $new_event_id ), "event_id=?", $event_id);
    $this->db->update('category', array( 'event_id' => $new_event_id ), "event_id=?", $event_id);
  }
  
  function setEventPaymentMethodId($evt, $payment_method_id){
    $this->db->update('event', array('payment_method_id'=>$payment_method_id), " id=?", $evt->id);
  }
  
  protected function addUser($id, $name=false ){
    return $this->createUser($id, $name)->id;
  }
  
  function createUser($id, $name=false, $params=array()){
    $name = $name?$name:ucfirst($id);
    $location_id = $this->createLocation()->id;
    $contact_id = $this->createContact($name, "$name@gmail.com", rand(5000000000, 9000000000));
    
    $this->db->update('contact',  array('user_id' => $id), "id=?", $contact_id);
    $this->db->update('location',  array('user_id' => $id), "id=?", $location_id);
     
    $username = "$id@blah.com";
    $data = array( 'id'=> $id, 'username'=> $username , 'location_id' => $location_id, 'contact_id'=>$contact_id
        , 'password' => md5('123456')
        , 'code' => $id
    );
    
    if (strstr($id, 'seller')){
      $data['promoter'] = 1;
      $data['tax_other_name'] = 'VAT'; //apparently needs this to show VAT label in tickets
      $data['tax_ref_other'] = 'b4rb4d0s';
    }
    
    $this->db->insert('user', array_merge($data, $params) );
    
    $user = new MockUser($this->db, $name);
    $user->id = $id;
    $user->location_id = $location_id;
    $user->contact_id = $contact_id;
    $user->username =$username ;
    
    return $user;
  }
  
  function setUserPhone($user, $phone){
    $this->db->update('contact', array('phone'=>$phone), "id=?", $user->contact_id);
  }
  
  function setUserHomePhone($user, $home_phone){
    $this->db->update('contact', array('home_phone'=>$home_phone), "id=?", $user->contact_id);
  }
  
  function setUserParams($user, $params){
      $this->db->update('user', $params, "id=?", $user->id);
  }
  
  function createTour($evt){
    $okData = array (
      'page' => 'Tour',
      'method' => 'save-tour',
      'id' => '0',
      'name' => 'Indoor',
      'event_id' => $evt->id, //'70328da2',
      'time' => '08:00:00',
      'cycle' => 'weekly',
      'interval' => '1',
      'date-start' => '2012-08-24',
      'date-end' => '2012-09-07',
      'repeat-on' => 
      array (
        0 => 'FR',
      ),
      'repeat-by' => 'day_of_the_month',
    );
    Request::clear();
    $_POST = $okData;
    $ajax = new \ajax\Tour();
    $ajax->Process();
    
    return \Database::get_one("SELECT id FROM tour_settings ORDER BY id DESC");
    
  }
  
  protected function createContact($name, $email='', $phone=''){
    $this->db->insert('contact', array( 'name'=> $name, 'email'=> $email, 'phone'=> $phone, 'home_phone' => $phone ));
    return $this->db->insert_id();
  }
  
  protected function createLocation($name='myLoc'){
    $o = new Locations();
    $o->name = $name;
    $o->street = 'Calle 1';
    $o->country_id = '52';// '124' - Canada, '52' - Barbados;
    $o->state = 'Carter';
    $o->city = 'Carter';
    $o->zipcode = 'CA';
    $o->state_id = 52 ; //hardcoded to quebec? 52-> Barbados
    $o->latitude = 45.30;
    $o->longitude = -73.35;
    $o->insert();
    return $o;
  }
  
  function createBanner($evt, $banner_type, $price_id, $approved=true){
    
    //Hmmmmmm
    \tool\Session::setUser($this->db->auto_array("SELECT * FROM user WHERE id=?", array($evt->user_id)));
    
    \tool\Banner::setInactive ($evt->id, $banner_type);
    \tool\Banner::insert ($evt->id, $banner_type, $price_id, $evt->getEndDateTime());
    
    if ($approved){
      \tool\Banner::setNotPendind($evt->id, $banner_type);  
    }
    
    return $this->db->insert_id();
    
  }
  
  protected function createTransaction($category_id, $user_id, $price_paid, $ticket_count, $txn_id, $completed = 1, $date_processed=false){
    //$date_processed = $date_processed? $date_processed: date('Y-m-d H:i:s');
    Utils::log(__METHOD__ . " date_processed: $date_processed");
    
    
    $date = new DateTime($date_processed);
    try{
      $date->setTimestamp(strtotime($date_processed));
    }catch (Exception $e){}
    
    $data = array( 'category_id'=>$category_id
                  , 'user_id'=> $user_id
                  , 'price_paid'=>$price_paid
                  , 'currency_id' => 1
                  , 'ticket_count' => $ticket_count
                  , 'txn_id'=> $txn_id
                  , 'completed' => $completed
                  , 'date_processed' => $date->format('Y-m-d H:i:s')//  $date_processed 
                  );

    $this->db->insert('ticket_transaction', $data );
    
    return $this->db->insert_id();
  }
  
 
  /**
   * @return txn_id
   */
  function buyTickets($user_id, $event_id, $category_id, $quantity=1, $payment_method_id=false){
    
    //Inspect event
    $is_cc = self::OUR_CREDIT_CARD == $this->db->get_one("SELECT payment_method_id FROM event Inner Join category ON event.id=category.event_id AND category.id=?", $category_id);
    if ($is_cc){
      return $this->buyTicketsWithCC($user_id, $event_id, $category_id, $quantity);
    }
    
    
    $user = new \model\Users($user_id);
    $client = new WebUser($this->db);
    $client->login($user->username);
    
    $client->addToCart($event_id, $category_id, $quantity);
    $txn_id = $client->placeOrder();
    $this->completeTransaction($txn_id, $payment_method_id);
    
    return $txn_id;
    
  }
  
  
  /**
   * Use this to force a website pay by cash behaviour, regardless of the event payment configuration
   */
  function buyTicketsByCash($user_id, $event_id, $category_id, $quantity=1){
    $user = new \model\Users($user_id);
    $client = new WebUser($this->db);
    $client->login($user->username);
    
    $client->addToCart($event_id, $category_id, $quantity);
    $txn_id = $client->payByCashBtn();
    return $txn_id;// $tickets;
  }
  
  function buyTicketsWithCC($user_id, $event_id, $cat_id, $quantity=1){
    Utils::log(__METHOD__ . " user_id: $user_id, cat id: $cat_id, qty: $quantity "); 
    $user = new \model\Users($user_id);
    
    //let's buy
    $client = new \WebUser($this->db);
    $client->login($user->username);
    
    $client->addToCart($event_id, $cat_id, $quantity); //cart in session
    $txn_id = $this->payCartByCreditCard($user_id, $client->getCart());
    return $txn_id;
  }
  
  function payCartByCreditCard($user_id, $cart){
    $payer = new \Optimal\MockPaymentHandler($user_id);
    $payer->setData($this->getCCPurchaseData());
    $payer->setCart($cart);
    $payer->response = $this->getOptimalResponse('success.xml');
    $payer->process();

    $cart->clean();
    $_SESSION = array();
    return $payer->getTxnId();
  }
  
  function setDateOfTransaction($txn_id, $date){
    $this->db->update('ticket_transaction', array('date_processed'=> $date), "txn_id=?", $txn_id );
    $this->db->update('transactions_optimal', array('date_processed'=> $date), "txn_id=?", $txn_id );
    $sql = "UPDATE `ticket` JOIN ticket_transaction ON ticket_transaction.txn_id=? AND ticket.transaction_id=ticket_transaction.id  SET `date_creation` = ? ";
    $this->db->Query($sql, array($txn_id, $date));
  }
  
  /**
   * @param $group_id String either '0001', '0010', '0100', '1000'. It can't be '1100' or any other combination with more than one 1
   */
  function createOutlet($name, $group_id, $options=array()){
    $form = new \Forms\Outlet;
    $data = array_merge(array(  'identifier'=> strtolower(str_replace(' ', '', $name))
                              , 'name'=>$name
                              , 'group_id'=> bindec($group_id) //in case group_id is in the form '0100', it is stored as '4'
                              , 'password'=>'123456')
                      , $options);
    $form->setData($data);
    $form->process();
    if (!$form->success()){
      throw new Exception(__METHOD__ . " " . implode( "\n",  $form->getErrors()) );
    }
    return $form->getInsertedId();
  }
  
  function createVenue($name, $options=array()){
    $form = new \Forms\Venues;
    $data = array_merge(array( 'identifier'=> strtolower(str_replace(' ', '', $name))
                              , 'name'=>$name
                              , 'password'=>'123456'
                              
                              //required to be usable by tour template creation
                              , 'street' => 'Some street'
                              , 'city' => 'Some city'
                              , 'country_id' => '52' 
                              
                              )
                        , $options);
    $form->setData($data);
    $form->process();
    if(!$form->success()){
      throw new Exception(__METHOD__ . " " . implode( "\n",  $form->getErrors()) );
    }
    return $form->getInsertedId();
  }
  
  function createBoxoffice($name, $merchant_id, $options=array()){
    $form = new \Forms\BoxOffice();
    $data = array_merge(array( 'username'=> strtolower(str_replace(' ', '', $name))
                              , 'name'=>$name
                              , 'password'=>'123456'
                              , 'user_id' => $merchant_id
                              
                              //for boxoffice, pretend there's at least an options setup
                              
                              )
                        , $options);
    $form->setData($data);
    $form->process();
    if(!$form->success()){
      throw new Exception(__METHOD__ . " " . implode( "\n",  $form->getErrors()) );
    }
    return $form->getInsertedId();
  }
  
  function createReservationUser($name, $venue_id, $options=array()){
    $form = new \Forms\Reservation();
    $data = array_merge(array( 'username'=> strtolower(str_replace(' ', '', $name))
                              , 'name'=>$name
                              , 'password'=>'123456'
                              , 'venue_id' => $venue_id
                              
                              //for boxoffice, pretend there's at least an options setup
                              
                              )
                        , $options);
    $form->setData($data);
    $form->process();
    if(!$form->success()){
      throw new Exception(__METHOD__ . " " . implode( "\n",  $form->getErrors()) );
    }
    return $form->getInsertedId();
  }
  
  
  function getOptimalResponse($filename){
    return file_get_contents(__DIR__ . "/Optimal/responses/" . $filename);
  }
  
  function doRefund($merchant_id, $txn_id){
    //now let's refund it
    $data = array('txnid'=>$txn_id);
    $ref = new \Optimal\MockRefundHandler($merchant_id); //refund is done by the merchant
    $ref->setData($data);
    $ref->response = $this->getOptimalResponse('refund_success_cancel.xml');
    $ref->process();
  }
  
	/**
   * "Manually" cancels a transaction, as by the following specification:
   * "I go in the ticket_transaction table and I put `cancelled` to 1"
   */
  function manualCancel($txn_id){
    $sql = "UPDATE `ticket_transaction` SET `cancelled` = '1' WHERE txn_id = ?";
    $this->db->Query($sql, $txn_id);
  }
  
  function buyCart($user_id, $cart, $payment_method_id, $date=false){
    
    //for now, to avoid instrospection, provie the payment_method_id
    if ($payment_method_id == self::OUR_CREDIT_CARD){
      $this->payCartByCreditCard($user_id, $cart);
      return;
    }
    
    
    //Utils::log(__METHOD__ . " date: $date");
    $txn_id = $this->nextSerial('TX');
    $price_paid = 0;
    foreach ($cart->items as $line){
      $category_id = $line->item_id;
      $quantity = $line->quantity;
      $price = $line->price;
      $price_paid = $quantity * $price;
      $this->createTransaction($category_id, $user_id, $price_paid , $quantity, $txn_id, 1, $date );
    }

    $this->completeTransaction($txn_id, $payment_method_id, '', $date);
  }
  
  //Used to simulate the reception of a payment completed notification from a ipn based remote gateway (i.e. Paypal). Instant payments (cc, cash) are better modeled with other means. 
  function completeTransaction($txn_id, $date=false){
    $this->flagAsPaid($txn_id, '', $date);
    $builder = new TestTicketBuilder();
    $builder->setPaid(1);
    $builder->createFromTransaction(Tickettransactionmanager::load($txn_id));
  }
  
  protected function flagAsPaid($txn_id, $returned='', $date_processed=false){
    
    $date_processed = $date_processed ? $date_processed: date('Y-m-d H:i:s');
    
    $date = new DateTime($date_processed);
    try{
      $date->setTimestamp(strtotime($date_processed));
    }catch (Exception $e){}
    
    $this->db->update('ticket_transaction', array('completed'=>1), " txn_id=?", $txn_id );
    
    if (is_numeric($txn_id)){
      $txn_id = $this->db->get_one("SELECT txn_id FROM ticket_transaction WHERE id=?", $txn_id);
    }
    
    $trans = Tickettransactionmanager::load($txn_id);
    
    $payment_method_id = $this->db->get_one("SELECT payment_method_id FROM event 
    																				Inner JOin category ON category.event_id = event.id
    																				Inner Join ticket_transaction ON ticket_transaction.category_id = category.id 
    																				WHERE ticket_transaction.txn_id=?", $txn_id);
    
    $data = array(  'txn_id'=>$txn_id
                  , 'payment_method_id' => $payment_method_id
                  , 'returned' => $returned
                  , 'amount' => $trans->getTotalAmount()
                  , 'userid' => $trans->getUserid()
                  , 'date' => $date->format('Y-m-d H:i:s')
                  );
    $this->db->insert('transactions_processor', $data );                                      
  }
  
  
  
  
  protected function createCategory($name, $event_id, $price, $capacity=20, $overbooking=0, $params=false){
    $cat = new Categories();
    $cat->name = $name;
    $cat->event_id = $event_id;
    $cat->price = $price;
    //$cat->taxe_id = $taxe_id;
    
    $cat->capacity = $capacity;
    $cat->capacity_max = $capacity;
    $cat->capacity_min = 0;//?? $capacity;
    
    //$cat->sub_capacity = 1;
    $cat->overbooking = $overbooking;
    $cat->cc_fee_inc = 0;
    
    $cat->tax_inc = 1; //barbados
    $cat->fee_inc = 1; //barbados
    
    $cat->as_seats = '0';
    $cat->hidden = '0';
	$cat->sold_out='0';
    
    $cat->category_id = 0;
    
    $cat->assign = '';//?
    $cat->order = '';//?
    
    $cat->insert();
    
    if ($params){
      $this->db->update('category', $params, "id=?", $cat->id);
      $cat = new Categories($cat->id);
    }
    
    //default visible on WEBSITE
    //$this->db->insert('disponibility', array('module_id'=>1, 'category_id'=>$cat->id));
    ModuleHelper::showInWebsite($this->db, $cat->id);
    
    return $cat;
  }
  
  protected function createTicket($user_id, $category_id, $name, $price_fee){
    $this->db->insert('ticket', array(  'user_id'=>$user_id
                                      , 'category_id' => $category_id
                                      , 'name' => $name
                                      , 'price_fee' => $price_fee
                                      , 'code' => 'CODE-'. $this->getSerial()
                                      ));
  }
  
  protected  function createPromocode($code, $event_id, $cat, $reduction=100, $reduction_type='p', $complimentary=0, $params = array()){
    
    $cats = is_array($cat)? $cat: array($cat);
    array_walk($cats, function (&$cat){ $cat = is_object($cat)?$cat->id:$cat; } );

    Request::clear();
    $data = array (
            'promocodeid' => '',
            'operation' => '', ///?
            'event_id' => $event_id,
            'categories' =>
            $cats,
            'code' => $code,
            'description' => 'some descr',
            'reduction' => $reduction,
            'reduction_type' => $reduction_type,
            'capacity' => '0',
            /*'valid_from' => '2013-09-01',
            'valid_from_time' => '09:00',
            'valid_to' => '2013-10-31',
            'valid_to_time' => '07:00',*/ //these should be nullified at store time
            'edit' => 'Save',
    );
    $_POST = array_merge( $data, $params );
    $cnt = new \controller\Promocodes();
    $id = $cnt->inserted_id;
    Request::clear();
    /*
    $event = \Database::auto_array("SELECT * FROM event WHERE id=?", $event_id);
    
    
    $this->db->insert('promocode', array( 'code' => $code
                                      //, 'category_id' => $cat->id
                                      , 'event_id' => $event_id// $cat->event_id
                                      , 'user_id' => $event['user_id']
                                      
                                      , 'reduction' => $complimentary ? '100': $reduction
                                      , 'reduction_type' => $complimentary ? 'p': $reduction_type
                                       
                                      , 'capacity' => 100
                                      , 'valid_from' => date('Y-m-d', strtotime("-1 month"))
                                      , 'valid_to' => date('Y-m-d', strtotime("+1 month"))
                                      
                                      , 'complimentary' => $complimentary? '1': '0'
                                      
                                      ));
    $id = $this->db->insert_id();
    
    $this->db->insert('promocode_category', array('promocode_id'=>$id, 'category_id'=>$cat->id));
    */                                  
    return $id;                                  
  }
  
  protected function createAutonomousPromocodeBuilder($code, $event_id, $cat, $reduction=100, $reduction_type='p', $range_min = null, $range_max=null, $auto_type='ticket'){
  
      $cats = is_array($cat)? $cat: array($cat);
      array_walk($cats, function (&$cat){ $cat = is_object($cat)?$cat->id:$cat; } );
  
      $obj = new AutonomousPromocodeBuilder($this->db);
      $obj->code = $code;
      $obj->event_id = $event_id;
      $obj->categories = $cats;
      $obj->reduction = $reduction;
      $obj->reduction_type = $reduction_type;
      $obj->range_min = $range_min;
      $obj->range_max = $range_max;
      $obj->auto_type = $auto_type;
      
      return $obj;
  }
  
  
  protected function resetSerial(){
    $this->serial = rand(1000, 9999);;
  }
  
  protected function getSerial($pre=''){
    $n = ++$this->serial;
    return $pre . $n;
  }
  protected function nextSerial($pre=''){
    return $this->getSerial($pre);
  }
  
  protected function clearRequest(){
    Request::clear();
  }
  
  protected function login($user, $password='123456'){
    $resUser = \model\Usersmanager::login($user->username, $password);
    $resUser = \model\Usersmanager::exists($resUser['id']);
    \tool\Session::setUser($resUser);
  }
  
  protected function flagTransactionsAsCompleted(){
    $this->db->update('ticket_transaction', array("completed"=>1), " 1 ");
  }
  
   protected function createTickets($txn_id){
    
    //just in case, flag transaction as completed
    $this->db->update('ticket_transaction', array('completed'=>1), "txn_id=?", $txn_id);
    
    $bld = new TestTicketBuilder();
    $bld->setPaid(1);
    $bld->createFromTransaction(Tickettransactionmanager::load($txn_id));
  }
  
  /**
   * @deprecated Use Website::addReminder
   */
  protected function createReminder( $event_id, $user_id, $type=\model\ReminderType::EMAIL, $address, $send_at, $txn_id){
      //old logic pre Quentin
      /*$this->db->insert('reminder', array( 'event_id' => $event_id
                                         , 'send_at' => $send_at
                                         , 'type' => $type
                                         , 'content' => $content 
     ));*/
      $rem = new \model\ReminderSent();
      $rem->event_id = $event_id;
      $rem->user_id = $user_id;
      $rem->type = $type;
      $rem->send_to = $address;
      $rem->when = $send_at;
      $rem->sent = '0';
      $rem->txn_id = $txn_id ;
      $rem->insert();
  }
  
  protected function assertRows($total, $table){
    $this->assertEquals($total, $this->db->get_one("SELECT COUNT(*) FROM $table" ));
  }
  
  function dateAt($offset, $format = 'Y-m-d H:i:s'){
    if(strstr( $offset, ' ')==false){
      $offset = "$offset days";
    }
    return date($format, strtotime($offset) );
  }
  
  function getLastEventId(){
    return $this->db->get_one("SELECT event_id FROM event_email ORDER BY id DESC ");
  }
  
  protected function getIpnString(){
    //paypal
    return "mc_gross=113.93&protection_eligibility=Eligible&address_status=confirmed&payer_id=J9YZSMMYVD3UQ&tax=0.00&address_street=1 Maire-Victorin&payment_date=10:07:39 Aug 05, 2011 PDT&payment_status=Completed&charset=windows-1252&address_zip=M5A 1E1&first_name=Test&mc_fee=3.60&address_country_code=CA&address_name=Test User&notify_version=3.2&custom=eJxLtDKyqi62srBSyi9KSS2Kz0xRsi4GiimZWYAYhoZWSiX5JYk58XmJuanFStaZVobWtQCzZxBa&payer_status=verified&business=acn_1312402113_biz@yahoo.com&address_country=Canada&address_city=Toronto&quantity=1&verify_sign=AFcWxV21C7fd0v3bYYYRCpSSRl31AxeUax.JFWs3tO6a5onB3CDeTRHv&payer_email=gates_1312402289_per@yahoo.com&txn_id=7LM02875RB145043G&payment_type=instant&last_name=User&address_state=Ontario&receiver_email=acn_1312402113_biz@yahoo.com&payment_fee=&receiver_id=CVYPQJ2YRD2JW&txn_type=web_accept&item_name=ACN-PURCHASE&mc_currency=CAD&item_number=68&residence_country=CA&test_ipn=1&handling_amount=0.00&transaction_subject=eJxLtDKyqi62srBSyi9KSS2Kz0xRsi4GiimZWYAYhoZWSiX5JYk58XmJuanFStaZVobWtQCzZxBa&payment_gross=&shipping=0.00&ipn_track_id=-GCFMBpohvqr6iVPhtY9OA";
  }
  
  function getCCData(){
    return array(
      'cc_num' => '5301250070000050'
    , 'cc_cvd' => '123'
    , 'cc_type' => 'mastercard'
    , 'exp_month' => '12'
    , 'exp_year' => date('Y', strtotime("+5 year"))
    , 'currency' => 'BBD' //??
    , 'address' => 'calle 13'
    , 'zipcode' => 'NA'
    , 'country' => 'US'
    
    //Reservation apparently needs address instead of street
    , 'address' => 'The address'
    
                    
    
    
    );
  }
  
  /**
   * admin360
   */
  function voidTicket($ticket_id){
      \model\Transactions::cancelTicketFromID($ticket_id);
  }
  
  /**
   * admin360
   * Cancels a single order line
   * Notice that it takes the ticket_transaction.id value, not txn_id
   */
  function voidLine($transaction_id){
      \model\Transactions::cancelTransactionFromID($transaction_id);
  }
  
  /**
   * admin360
   * not to be confused with manualCancel(), which is another, albeit troublesome, use case
   */
  function voidTransaction($txn_id){
      \model\Transactions::cancelTransactionFromTxnId($txn_id);
  }
  
  function unvoidTransaction($txn_id){
      \model\Transactions::returnTransactionFromTxnId($txn_id);
  }
  
  function createPrintedTickets($nb, $evtid, $cat_id, $cat_name, $fee_fixed=0.6, $fee_percent=0){
      $ajax = new \ajax\TicketPrinting();
      $data = array(
              'eventid' => array($evtid, ''),
              'categoryId' => array($cat_id, ''),
              'categoryName' => array($cat_name, ''),
              'ticketAmount' => array($nb, ''),
              'tixproFees' => array(1, ''),
              'promocode' => array('', ''),
              'tixpro_fee_fix' => $fee_fixed,
              'tixpro_fee_percent'=> $fee_percent,
              'preActivated' => '0'
      );
      $_POST = array('tickets' => serialize($data));
      $ajax->Process();
      Request::clear();
  }
  
  protected function getTicket($code){
      return $this->db->auto_array("SELECT * FROM ticket WHERE code=?", $code);
  }
  
  
}




// **************************************************************************************

class MockUser{
  public $db, $id, $name, $location_id, $contact_id, $username;
  function __construct($db, $name){
    $this->db = $db;
    $this->name = $name;
  }

}

class TestTicketBuilder extends TicketBuilder{
  protected function sendTicketEmail($ticketid){
    Utils::log(__METHOD__ . " do nothing");
  }
}

class AutonomousPromocodeBuilder{
    public $db,
    $code='', $description='', $event_id, $categories,
    $reduction, $reduction_type, 
    $capacity = 0, $used = 0
    , $valid_from = '', $valid_from_time='', $valid_to = '', $valid_to_time='',
    $active = 1, $complimentary =0,
    $autonomous=1, $auto_type='ticket', $range_min=null, $range_max=null,
    $params = array()
    ;
    
    function __construct($db){
        $this->db = $db;
    }

    function build(){
        $cat = $this->categories;
        $cats = is_array($cat)? $cat: array($cat);
        array_walk($cats, function (&$cat){ $cat = is_object($cat)?$cat->id:$cat; } );
        
        Request::clear();
        $data = array (
                'promocodeid' => '',
                'operation' => '', ///?
                'event_id' => $this->event_id,
                'categories' => $cats,
                'code' => $this->code,
                'description' => $this->description,
                'reduction' => $this->reduction,
                'reduction_type' => $this->reduction_type,
                'capacity' => $this->capacity,
                'valid_from' => $this->valid_from,// '2013-09-01',
                'valid_from_time' =>  $this->valid_from_time,
                'valid_to' => $this->valid_to,
                'valid_to_time' => $this->valid_to_time, //these should be nullified at store time
                'edit' => 'Save',
        );
        $_POST = array_merge( $data, $this->params );
        $cnt = new \controller\Promocodes();
        $id = $cnt->inserted_id;
        Request::clear();
        
        //Since we're prototyping, let's just update the inserted row
        $this->db->update('promocode', array('autonomous'=>$this->autonomous
                , 'auto_type' => $this->auto_type
                , 'range_min' => $this->range_min
                , 'range_max' => $this->range_max
                ), "id=?", $id);
        
        
        return $id;
        
    }
    
}