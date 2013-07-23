<?php 

class OutletModule{
    public $user=false;
    public $date=false; //override for the date of the transactions
    /** @var \Database */
    public $db;
    function __construct(\TestDatabase $db, $username, $password='123456'){
        $this->db = $db;
        $this->login($username, $password);
    }

    function login($username, $password='123456' ){
        $this->logout();
        $this->user = new \model\OutletUser();
        $this->user->login($username, $password);
    }

    function logout(){
        if($this->user){
            $this->user->logout();
        }
    }

    function getId(){
        return $this->user->getId();
    }

    function addItem($event_id, $category_id, $quantity, $promocode=''){
        //add item
        while($quantity>0){

            $req = array( 'page'=>'Cart', 'method'=>'outlet-update', 'event_id_in_cart'=> $event_id, 'category_id'=>$category_id, 'quantity'=>1 );
            $ajax = new ajax\Cart();
            $ajax->setData($req);
            $res = $ajax->outletUpdate();

            //sense if it was added, if not, fail
            if (true == Utils::getArrayParam('invalid', $res)){
                throw new Exception( __METHOD__ .  " Error: " . $res['msg']);
            }


            $quantity--;

        }

        $this->getCart()->save();

        if (!empty($promocode)){
            throw new Exception("You must apply promocode as a step");
            $cat = new \model\Categories($category_id);
            $this->applyPromoCode($cat->event_id, $promocode);
        }

    }




    //Each row on the cart has a promocode input
    function applyPromoCode($event_id, $promocode){
        $data = array( 'page'=>'Cart', 'method'=>'verify-code-outlet', 'event_id'=> $event_id, 'code'=>$promocode);
        $p = new ajax\Cart();
        $p->setData($data);
        $res = $p->verifyCodeOutlet();

        Utils::log( __METHOD__ . " ". print_r($_SESSION, true));
        Utils::log( __METHOD__ . " ". print_r($res, true));

        //$this->clearRequest();
        //$this->getCart()->save();
    }

    /**
     *
     * @param $buyer A user object
     */
    function payByCash($buyer, $amount_pay=false){
         
        if ($amount_pay === false){
            $amount_pay = $this->getCart()->getTotal();
        }
         
        //Utils::log(__METHOD__ . " cart:" . print_r($this->getCart()->getCartItems(), true) );
        $req = array( 'cellphone'=>'', 'email' => $buyer->username
                , 'fname'=> $buyer->name, 'sname' => 'D00d'

                , 'method'=>'pos-pay', 'page'=>'Cart'
                , 'outlet_flag'=>1
                , 'amount_pay' => $amount_pay

                //????
                , 'province' => ''
                , 'is_rsv' => 'false' //????
                , 'txn_id' => ''

                //new parameters added by Jonathan apparently
                , 'mod' => 'outlet', 'special_charge' => '0'

        );

        $_POST = $req;
        $ajax = new ajax\Cart();
        $ajax->Process();
        $res = $ajax->res;
         
        if ('success' != \Utils::getArrayParam('result', $res)){
            $msg =  __METHOD__ . "response: ". print_r($res, true);
            throw new \Exception($msg);
        }

        Utils::log(__METHOD__ . "res: " . print_r($res, true));
        $txn_id = $res['txn_id'];

        $this->overrideTxnDate($txn_id, $this->date);
        return $txn_id;
    }

    protected function overrideTxnDate($txn_id, $date){
        if(empty($date)) return;
        $this->db->update('ticket_transaction', array('date_processed'=> $date), "txn_id=?", $txn_id );
        $sql = "UPDATE `ticket` JOIN ticket_transaction ON ticket_transaction.txn_id=? AND ticket.transaction_id=ticket_transaction.id  SET `date_creation` = ? ";
        $this->db->Query($sql, array($txn_id, $date));
    }

    function payByCC($buyer, $ccdata, $amount_to_pay_in_USD=false){
        if ($amount_to_pay_in_USD === false){
            $amount_to_pay_in_USD = $this->getCart()->getTotal();
            if ('BBD' == $this->getCart()->getCurrency()){
                $amount_to_pay_in_USD/=2; //converted to USD
            }
        }
         
        //Utils::log(__METHOD__ . " cart:" . print_r($this->getCart()->getCartItems(), true) );
        $req = array(   'cellphone'=>'', 'email' => $buyer->username
                , 'fname'=> $buyer->name, 'sname' => 'D00d'

                , 'method'=>'pos-pay', 'page'=>'Occpay'
                , 'amount_pay' => $amount_to_pay_in_USD

                //????
                , 'province' => ''
                , 'txn_id' => ''
        );

        $req = array_merge($req, $ccdata);

        $_POST = $req;
        $ajax = new ajax\Occpay();
        $ajax->Process();
        $res = $ajax->res;
         
        if ('success' != \Utils::getArrayParam('result', $res)){
            $msg =  __METHOD__ . "response: ". print_r($res, true);
            throw new \Exception($msg);
        }

        $this->getCart()->clean(); //explicit?

        Utils::log(__METHOD__ . "res: " . print_r($res, true));
        $txn_id = $res['txn_id'];

        $this->overrideTxnDate($txn_id, $this->date);
        return $txn_id;
    }


    function getCart(){
        $cart = new \tool\Cart();
        $cart->load();
        return $cart;
    }

    protected function clearRequest(){
        tool\Request::clear();
    }

    static function showEventIn($db, $event_id, $outlet_id){
        $out = $db->auto_array("SELECT * FROM outlet WHERE id=?", $outlet_id);
        $rows = $db->getIterator("SELECT id FROM category WHERE event_id=?", $event_id);
        foreach ($rows as $row){
            $db->insert('disponibility', array('category_id'=>$row['id'], 'module_id'=>2, 'groupe_id'=>$out['group_id'], 'specific_id' => $out['id'] ));
        }
    }

}
