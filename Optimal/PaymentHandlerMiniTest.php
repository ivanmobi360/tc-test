<?php
namespace Optimal;
use Utils;

class PaymentHandlerMiniTest extends PaymentHandlerTest{

  function createInstance($user_id){
    return new MockPaymentHandlerMini($user_id);
  }
  
  function getCCPurchaseData(){
    //$data = parent::getCCPurchaseData();
    //as of Outlet module, this is what is posted
    $data = array (
      'page' => 'Occpay',
      'fname' => 'James',
      'sname' => 'Bond',
      'is_rsv' => 'false',
      'cellphone' => '',
      'email' => 'b4tm4n@blah.com',
      'address' => 'Some street',
      'province' => '',
      'cc_num' => '4715320629000001',
      'cc_cvd' => '1255',
      'cc_type' => 'visa',
      'exp_month' => '1',
      'exp_year' => '2015',
      'zipcode' => 'HN7Q2',
      'amount' => $this->cat_price/2, //the amount is sent directly right here
      'currency' => 'BBD',
      'txn_id' => '',
      'mod' => 'outlet',
      'outlet_id' => '21',
      'special_charge' => '0',
    );
    return $data;
  }
  
  
 
}