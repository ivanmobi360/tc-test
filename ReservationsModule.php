<?php
/**
 * Stub class to test the reservations module
 * Login system not designed by me
 * @author MASTER
 *
 */

use tool\Request;
use controller\Reservationszreport;
class ReservationsModule extends BoxOfficeModule{
  
  function login($username, $password='123456'){
    $this->logout();
    $_POST = array( 'login_sent'=>1, 'id'=>$username, 'password'=>$password);
    $cnt = new \controller\Reservations();
    $this->user = $cnt->getUser();
    if(empty($this->user)){
      throw new Exception('login failed');
    }
    $this->clearRequest();
  }
  
  function logout(){
    //if(empty($this->user)) return;
    Utils::log(__METHOD__);
    $_POST = array( 'logout'=>1);
    $cnt = new \controller\Reservations();
    $this->clearRequest(); 
  }
  
  function getId(){
    return $this->user['id'];
  }
  
  function payByCash($amount, $params=array()){

    //Utils::log(__METHOD__ . " cart:" . print_r($this->getCart()->getCartItems(), true) );
    $req = array( 
                    'method'=>'pos-pay', 'page'=>'Cart'
                  , 'outlet_flag'=>1
                  , 'is_rsv' => 1
                  , 'amount_pay' => $amount
                  , 'fname' => 'Some'
                  , 'sname' => 'D00d'
                  , 'txn_id'
                   );
    $req = array_merge($req, $params);               
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
    $this->clearRequest();
    return $txn_id;
  }
  
  /**
   * Shortcut to simulate the second step of paymnet. Usually the js front end supplies the same parameters as payByCash 
   */
  function completePayment($txn_id){
    $params = array( 'sname' => 'POS Sale', 'txn_id'=>$txn_id );
    return $this->payByCash($this->getBalance($txn_id), $params);
  }
  
  function payByCC($buyer, $ccdata, $amount=false){
    $currency = $this->getCart()->getCurrency();
    if ($amount === false){
      $amount = $this->getCart()->getTotal();
      
      /*if ('BBD' == $this->getCart()->getCurrency()){
        $amount/=2; //converted to USD
      }*/
  	}
  	
    //Utils::log(__METHOD__ . " cart:" . print_r($this->getCart()->getCartItems(), true) );
    $req = array(   'cellphone'=>'', 'email' => $buyer->username
                  , 'fname'=> $buyer->name, 'sname' => 'D00d'
                  
                  , 'method'=>'pos-pay', 'page'=>'Occpay'
                  //, 'amount_pay' => $amount_to_pay_in_USD
                  , 'amount' => $amount //for some reason this one expects 'amount' instead of 'amount_pay'
                  , 'currency' => $currency //why this one does send currency? 
                  
                  , 'is_rsv' => '1'
                  //????
                  , 'province' => ''
                  , 'txn_id' => ''
                  
                  );
                  
    $req = array_merge($req, $ccdata);              

    Request::clear();
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
  
  function completePaymentByCC($txn_id, $buyer, $ccdata){
    $params = array_merge($ccdata, array( 'txn_id'=>$txn_id ));
    return $this->payByCC($buyer, $params, $this->getBalance($txn_id));
  }
  
  
  function getBalance($txn_id){
    $_POST = array(
        'method' => 'get_balance'
      , 'txn_id' => $txn_id
      , 'currency' => 'BBD'
    );
    $ajax = new ajax\Cart();
    $ajax->Process();
    $res = $ajax->res;
    
    return $res['amount'];
  }
  
  function getZReport(){
    $cont = new TestReservationszreport();
    return $cont->getViewData();
  }
  
  function getZReportGlobalTotalIncludedRemittance(){
    $rep = $this->getZReport();
    return $rep['global_total_included_remittance'];
  }
  
  
  
}

class TestReservationszreport extends Reservationszreport{
  function __construct(){
    //do nothing
  }
}