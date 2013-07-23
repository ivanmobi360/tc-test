<?php
/**
 * Stub class to test the box office module
 * @author MASTER
 *
 */

class BoxOfficeModule{
  public $user=false;
  public $date=false; //override for the date of the transactions
  /** @var \DatabaseBaseTest */
  public $sys;
  /** @var \Database */
  public $db;
  
  function __construct($sys, $username=false, $password='123456'){
    $this->sys = $sys;
    $this->db = $sys->db;
    
    if($username)
      $this->login($username, $password);
  }
  
  function login($username, $password='123456'){
    $this->logout();
    $this->user = new \model\BoxOfficeUser();
    return $this->user->login($username, $password);
  }
  
  function logout(){
    if ($this->user){
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
      $ajax->outletUpdate();
      
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
  
  function payByCash($params=array()){

    //Utils::log(__METHOD__ . " cart:" . print_r($this->getCart()->getCartItems(), true) );
    $req = array( 
                    'method'=>'pos-pay', 'page'=>'Cart'
                  //, 'outlet_flag'=>1
                  , 'is_box_office' => 1
                  , 'amount_pay' => 'NaN'//mimic state
                          
            , 'automatic_validation' => '0'
            , 'is_rsv' => 'false'
            , 'mod' => 'boxoffice'
            , 'special_charge' => '0'
            , 'txn_id' => ''
                   );
    $req = array_merge($req, $params);               
    $_POST = $req;               
    $ajax = new ajax\Cart();
    //$ajax->setData($req);
    //$res = $ajax->posPay();
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
  
  
  
  
  function getCart(){
    $cart = new \tool\Cart();
    $cart->load();
    return $cart;
  }
  
  protected function clearRequest(){
    tool\Request::clear();
  }
  
  static function showEventIn($db, $event_id, $bo_id){
      $item = $db->auto_array("SELECT * FROM bo_user WHERE id=?", $bo_id);
      $rows = $db->getIterator("SELECT id FROM category WHERE event_id=?", $event_id);
      foreach ($rows as $row){
          $db->insert('disponibility', array('category_id'=>$row['id'], 'module_id'=>3, 'groupe_id' => $item['id'] ));
      }
  }
  
}