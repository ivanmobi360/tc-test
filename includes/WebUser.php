<?php
/**
 * Driver to simulate interactions done from the /website
 * @author Ivan Rodriguez
 *
 */
class WebUser{
    public $db, $username, $id;
    
    protected $reminders;

    //factory method
    static function loginAs($db, $username, $password='123456'){
        $w = new self($db);
        $w->login($username, $password);
        return $w;
    }
    
    static function logAs($db, $username, $password='123456'){
        $web = new static($db);
        $web->login($username, $password);
        return $web;
    }

    function __construct($db){
        $this->db = $db;
        $this->reminders = array();
    }

    function login($username, $password='123456'){
        
        $this->logout();
        
        $this->clearRequest();
        $_POST = array('username' => $username, 'password'=> $password);
        $cont = new \controller\Login();
        
        Utils::log(__METHOD__ . " session: " . print_r($_SESSION, true) );
        
        if ( !\model\Usersmanager::isLoggedIn()){
            throw new Exception(__METHOD__ . " Login failed ");
        }


        $user = \tool\Session::getUser();

        if (!$user){
            throw new Exception(__METHOD__ . " Login failed ");
        }

        $this->username = $username;
        $this->id = $user['id'];

    }

    

    function addToCart($event_id, $category_id, $quantity, $promocode=''){

        //workaround for now
        /*$cat = new Categories($category_id);
         $event_id = $cat->event_id;
        */
        $data = array( 'page'=>'Event', 'method'=>'add-cart',  'event_id_in_cart'=> $event_id
                , 'category_id'=> array($category_id), 'quantity'=> array($quantity) //single item array now? (from event details)
                , 'promocode'=> '' );
        $p = new ajax\Event();
        $p->setData($data);
        $p->addCart();
        //$p->Process();

        if ($p->res && 'failed' == Utils::getArrayParam('result', $p->res) ){
            throw new Exception(__METHOD__ . "failure : " . $p->res['msg']  );
        }

        Utils::log( __METHOD__ . " session so far: " .   print_r($_SESSION, true));

        $this->clearRequest();

        if (!empty($promocode)){
            $cat = new \model\Categories($category_id);
            $this->applyPromoCode($cat->event_id, $promocode);
        }

    }
    
    /**
     * use to simulate the action of changing the select Quantity from the cart page . You have to actually send the actual new number of tickets 
     */
    function quantityUpdate($event_id, $category_id, $new_quantity){
        $_POST = array( 'page'=>'Cart', 'method'=>'quantity-update',  'event_id_in_cart'=> $event_id
                , 'category_id'=> $category_id, 'quantity'=> $new_quantity
                );
        $p = new ajax\Cart();
        $p->Process();
    }
    
    
    function addReminder($event_id, $type, $address, $when){
        $rem = new CheckoutReminder();
        $rem->event_id = $event_id;
        $rem->type = $type;
        $rem->address = $address;
        $rem->when = $when;
        $this->reminders[] = $rem;
    }
    
    function getRemindersData(){
        $data = array();
        foreach($this->reminders as $rem){
            $base = $rem->type .'-' . $rem->event_id;
            $data[$base] = 'on';
            $data[$base . '-to'] = $rem->address;
        
            $when = new DateTime($rem->when);
        
            $data[$base.'-date'] = $when->format('Y-m-d');
            $data[$base.'-time'] = $when->format('H:i:s');
        }
        return $data;
    }
    
    protected function addReminders($data){
        return array_merge($data, $this->getRemindersData());
    }

    function setOutletId($ref_outlet_id){
        $_SESSION['ref_outlet_id'] = $ref_outlet_id;
    }

    //Each row on the cart has a promocode input
    function applyPromoCode($event_id, $promocode){
        $_POST = array( 'page'=>'Cart', 'method'=>'verify-code', 'event_id'=> $event_id, 'code'=>$promocode);
        $p = new ajax\Cart();
        $p->Process();

        //Utils::log(print_r($_SESSION, true));

        $this->clearRequest();
    }


    function placeOrder($gateway=false, $date_of_placement=false){
        $gateway = $gateway? $gateway: 'paypal';
        $_POST = array( 'method'=>'cart-payment', 'page'=>'Cart', 'name_pay'=>$gateway);
        $p = new \ajax\Cart();
        $p->Process();
        $this->clearRequest();
        $res = $p->res;
        $txn_id = $res['txn_id'];
        if($date_of_placement){
            $this->db->update('ticket_transaction', array('date_processed'=>$date_of_placement), 'txn_id=?', $txn_id);
        }
        return $txn_id;
    }

    /**
     * @deprecated New workflow for cash is a singe click button. Use payByCashBtn
     */
    function payByCash($txn_id){
        throw new Exception('DEPRECATED. Use payByCashBtn');
        $data = array(
                'txn_id' => $txn_id,
                'type_pay' => \model\DeliveryMethod::PAY_BY_CASH //'paybycash'
        );
        
        $data = $this->addReminders($data);

        $_POST = $data;

        //Now see if controller reacts properly
        $cnt = new \controller\Payment();

        //do this manually?
        $this->db->update('ticket_transaction', array('completed'=>1), 'txn_id=?', $txn_id); //?????????????????

        $this->clearRequest();
    }

    /**
     * Call this directly. No need to call placeOrder first. No need of existing txn_id
     */
    function payByCashBtn(){
        $_GET = array('page'=>'337c'); //????????????? - Apparently did nothing when commented out, what gives?

        $data = array(
                'pay_cash' => 'Pay By Cash' //whatever

                //extra recent Jonathan's parameters, not send by website, but put in place with dummy values to not get errors
                //, 'mod' => 'blah'

        );
        
        $data = $this->addReminders($data);

        $_POST = $data;

        $cnt = new \controller\Checkout();
        //Utils::log(__METHOD__ . " completed checkout");
        $this->clearRequest();

        return $cnt->txn_id;

    }
    
    /**
     * 2014-04-15
     * Apparently this has not been tested before O_o
     * At the moment, in the website/checkout page, a cc form appears at the bottom. Logged in, we click and pay.
     * Verify that ticket_transaction.fee_cc has money > 0
     * There's nothing stored on each ticket.price_ccfee value
     */
    function payWithCreditCard(){
        $data = array (
  'sms-ccc-to' => '550437724',
  'sms-ccc-date' => '2014-04-19',
  'sms-ccc-time' => '15:00:52',
  'ema-ccc-to' => $this->username,// 'Foo@gmail.com',
  'ema-ccc-date' => '2014-04-19',
  'ema-ccc-time' => '15:00:52',
  'cc_type' => 'MasterCard',
  'cc_name_on_card' => 'Bill Gates',
  'cc_num' => '5301250070000050',
  'exp_month' => '1',
  'exp_year' => '2019',
  'cc_cvd' => '1234',
  'username' => $this->username, //'foo@blah.com',
  'street' => 'Calle 1',
  'city' => 'Carter',
  'state' => 'Carter',
  'zipcode' => 'CA',
  'country' => '52',
  'pay_cc' => 'on',
  'submit' => 'Complete Your Payment',
);
          $this->clearRequest();
          $_POST = $data;
          
          $cnt = new \controller\Checkout();
          $this->clearRequest();
          
          return $cnt->txn_id;
    }




    function getCart(){
        $cart = new \tool\Cart();
        $cart->load();
        return $cart;
    }
    
    //a.k.a cc fees
    function getOnlineFees(){
        
        /* for now we'll make some assumptions
        1. This is the first and only cc payment (it will be in full)
        */
        $cart = $this->getCart();
        $base =  $cart->getTotalCash();
        return \model\TransactionsManager::getCCFee($cart->ccfeeable);
    }

    //This action should be called in case the cart contents are 0.00 (because of discounts) and the user was presented that button
    function getTickets(){
        /*$this->clearRequest();
         $_GET = array('page'=>'337c'); //????????????? - Apparently did nothing when commented out, what gives?
        $page = new \controller\Checkout();*/

        $this->clearRequest();
        $_POST = array('get_free_ticket' => 'blah');
        $page = new \controller\Checkout();
    }

    // ***************** POS Point of Sale *************************************************
    function posAddItem($category_id, $qty=1){

        $_POST = array( 'page'=>'Cart', 'method'=>'add-item', 'category_id'=> $category_id, 'quantity'=> $qty);
        $p = new ajax\Cart();
        $p->Process();

        Utils::log(print_r($_SESSION, true));

        $this->clearRequest();
    }

    function posPay(){
        $_POST = array( 'page'=>'Cart', 'method'=>'pos-pay');
        $p = new ajax\Cart();
        $p->Process();

        Utils::log(print_r($_SESSION, true));

        $this->clearRequest();
    }
    
    function logout(){

        $_SESSION = array();
        \model\Usersmanager::clear();
        \tool\Cookie::clean();
        if(session_id() != '')
            \tool\Session::clean();
    }

    protected function clearRequest(){
        tool\Request::clear();
    }





}

class CheckoutReminder{
    public $event_id, $type, $address, $when;
}
