<?php
abstract class BaseTest extends PHPUnit_Framework_TestCase{
  protected $merchantid = '48fkr844';

}


// *************************************************************************
/*

class CartBuilder{
  protected $cart;
  
  public function __construct(){
    $this->cart = new Cart_ShoppingCart();
  }
  
  public function add($classname, $id, $quantity=1){
    $item = new Cart_ShoppingCartItem($classname, $id );
    $item->setQuantity($quantity);
    
    $pb = call_user_func(array($item->getClass(), 'load'), $item->getId());
    
    $item->setPrice($pb->getFirstPayment());
    $this->cart->addItem($item);
  }
  
  public function getCart(){
    return $this->cart;
  }
  
      
  
}*/