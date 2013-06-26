<?php
namespace Optimal;
use Utils;

class FakePaymentHandlerMiniTest extends PaymentHandlerMiniTest{

  function createInstance($user_id){
    return new FakePaymentHandlerMini($user_id);
  }
  
  
 
}