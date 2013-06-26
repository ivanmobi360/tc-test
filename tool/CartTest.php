<?php
namespace tool;
use Utils;
class CartTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $loc = $this->createLocation();
    
    $evt = $this->createEvent('Circo', $seller->id, $loc->id, '2012-05-05');
    $cat = $this->createCategory('Fila', $evt->id, 15.45);
    
    $foo = $this->createUser('foo');
    
    $n = Cart::calculateRowValues($cat->id, 2, $foo->id );
    
    Utils::log(print_r($n, true));
    
  }
  
  function testQuantityAvailable(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');

    $evt = $this->createEvent('Circo', $seller->id, $this->createLocation()->id);
    $cat = $this->createCategory('Fila', $evt->id, 100, 6);
    
    $category = \model\Categoriesmanager::loadCategoryFromID($cat->id);
    
    $cart = new Cart();
    $this->assertEquals(6, $cart->getQuantityAvailable($evt->id, $category, 'add'));
    
    //let's purchase
    $web = new \WebUser($this->db);
    $web->login($foo->username);
    $web->addToCart($evt->id, $cat->id, 6);
    $txn_id = $web->payByCashBtn();
    
    $cart = new Cart();
    $this->assertEquals(0, $cart->getQuantityAvailable($evt->id, $category, 'add'));
    
    $this->manualCancel($txn_id);
    $cart = new Cart();
    $this->assertEquals(6, $cart->getQuantityAvailable($evt->id, $category, 'add'));
    
  }
  
  function testTour(){
    $this->clearAll();
    
    $seller = $this->createUser('seller');
    $foo = $this->createUser('foo');
    
    $v1 = $this->createVenue('Pool'); //needs a venue
    
    $build = new \TourBuilder($this, $seller);
    $build->capacity[0] = 5;
    $build->capacity[1] = 7;
    $build->build();
    $cats = $build->categories;
    $catX = $cats[1]; //the 100.00 one, yep, cheating
    $catY = $cats[0];
    Utils::log(__METHOD__ . " cats are $catX, $catY ");
    $category = \model\Categoriesmanager::loadCategoryFromID($catX);
    
    $cart = new Cart();
    $this->assertEquals(5, $cart->getQuantityAvailable('tour1', $category, 'add'));
    
    
  }
  
   
}