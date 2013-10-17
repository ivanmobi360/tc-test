<?php
/**
 * @author MASTER
 *
 */

class ModuleHelper {
  
  static function showEventInAll($db, $event_id){
  	$rows = $db->getIterator("SELECT id FROM reservation");
  	foreach ($rows as $row){
  		ReservationsModule::showEventIn($db, $event_id, $row['id']);
  	}
  	
  	$rows = $db->getIterator("SELECT id FROM outlet");
  	foreach ($rows as $row){
  		OutletModule::showEventIn($db, $event_id, $row['id']);
  	}
  	
  	$rows = $db->getIterator("SELECT id FROM bo_user");
  	foreach ($rows as $row){
  		BoxOfficeModule::showEventIn($db, $event_id, $row['id']);
  	}
     
  }
  
    static function showInWebsite($db, $category_id){
        $db->insert('disponibility', array('module_id'=>1, 'category_id'=>$category_id));
    }
    
    // *********************************************
    
    public $user=false;
    public $date=false; //override for the date of the transactions
    /** @var \DatabaseBaseTest */
    public $sys;
    /** @var \Database */
    public $db;
    
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
    
}