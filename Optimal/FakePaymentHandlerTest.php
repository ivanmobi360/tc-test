<?php
namespace Optimal;
use Utils;

class FakePaymentHandlerTest extends PaymentHandlerTest{
  /*
  function getError(){
    return $this->getResponse("error.xml");
  }
  
  function getSuccess(){
    return $this->getResponse("success.xml");
  }
  
  function getResponse($filename){
    return file_get_contents(__DIR__ . "/responses/" . $filename);
  }
  */
  function createInstance($user_id){
    return new FakePaymentHandler($user_id);
  }
  
  
 
}