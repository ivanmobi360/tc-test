<?php
/**
 * @author Ivan Rodriguez
 *
 */
class PromoterTxnReportTest extends DatabaseBaseTest{
  
  function testShort(){
      
    $this->clearAll();
    
    $user = $this->createUser('foo');
    $v1 = $this->createVenue('Pool');
    $out1 = $this->createOutlet('Outlet 1', '0010');
    $seller = $this->createUser('seller');
    $bo_id = $this->createBoxoffice('111-xbox', $seller->id);
    $rsv1 = $this->createReservationUser('tixpro', $v1);
    

    $evt = $this->createEvent('Some Event', 'seller', $this->createLocation()->id, $this->dateAt('+5 day'));
    $this->setEventId($evt, 'aaargh');
    $this->setEventGroupId($evt, '0010');
    $this->setEventVenue($evt, $v1);
    $catA = $this->createCategory('VIP', $evt->id, 100.00);
    $catB = $this->createCategory('NORMAL', $evt->id, 50.00);
    
    
    ModuleHelper::showEventInAll($this->db, $evt->id);
    
    $this->createPrintedTickets(5, $evt->id, $catA->id, 'CAT VIP');
    $this->createPrintedTickets(50, $evt->id, $catB->id, 'CAT NORMAL');
    
    $tickets = $this->db->getAll("SELECT * from ticket");

    /*
    for($i=0; $i<4; $i++){
        $this->activateTicket($tickets[$i]['code'], 1);
    }
    
    for($i=4; $i<7; $i++){
        $this->activateTicket($tickets[$i]['code'], 2);
    }
    */
    
    $this->activateTicket($tickets[0]['code'], 2);
    $this->activateTicket($tickets[1]['code'], 2);
    $this->activateTicket($tickets[5]['code'], 2);
    $this->activateTicket($tickets[6]['code'], 2);
    $this->activateTicket($tickets[10]['code'], 2);
    
    $this->activateTicket($tickets[2]['code'], 1);
    $this->activateTicket($tickets[3]['code'], 1);
    $this->activateTicket($tickets[4]['code'], 1);
    $this->activateTicket($tickets[11]['code'], 1);
    //$this->activateTicket($tickets[7]['code'], 2);
    
    $this->assertRows(9, 'ticket', " activation_id IS NOT NULL ");
    
    //scramble the dates
    foreach($tickets as $row){
        $this->db->update('ticket', ['date_creation'=>
                date('Y-m-d H:i:s', rand(strtotime($this->dateAt("-1 day")),  strtotime($this->dateAt("+1 day"))   ))
                ], "id=?", $row['id']);
    }
    
    
  }
  
  function activateTicket($code, $activation_id){
      $post = ['data'=> $code, 'activator' => $activation_id, 
              'tour_settings_id'=>null, 'mode'=>'activate'];
      
      $this->clearRequest();
      $ajax = new \ajax\Activation();
      $ajax->post = $post;
      $ajax->Process();
  }
  
}