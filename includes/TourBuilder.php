<?php
use tool\Request;
/**
 * Creation is a 2 step process. This class performs the 2 steps with some defaults
 * @author Ivan Rodriguez
 * (Consider deprecation if TemplateBuilder logic is completed)
 *
 */ 
class TourBuilder{
  public $db, $user
  , $site, $sys
  
  , $event_id = 'aaa'
  , $pre = 'tour'
  , $capacity = array(99,49)
  
  , $date_start = false
  , $date_end = false
  , $time_start = false
  , $cycle = 'weekly' //'weekly', 'daily'
  		
  , $template_name = 'Pizza Tiem'		
  , $name = 'Friday Meal'

    //extra override data (this creates a single tour_settings entry, and then the tour dates based on that setting. Usually this will be done in a different screen in a different step)       
  , $data = array()        
  ;
  
  //settings
  public $outlets = '0111';
  
  //generated
  public $categories;
  
  function __construct($sys, $seller){
    $this->sys = $sys;
    $this->db = $sys->db;
    $this->user = $seller;
    
    $site = new WebUser($this->db);
    $site->login($seller->username);
    $this->site = $site;
    
  }
  
  protected function template_okData(){
    $data =  array (
  'MAX_FILE_SIZE' => '3000000',
  'copy_event' => '053c3da3',
  'e_name' => $this->template_name,
  'e_capacity' => '100',
    
  //now controller by $outlets property  
  //'outlet_2' => 'on',
  //'outlet_4' => 'on',
    
  'venue' => $this->db->get_one("SELECT id FROM venue LIMIT 1"),  //'1', //some id
  'e_time_from' => '08:00',
  'e_time_to' => '01:00', //now it is duration apparently
  'e_description' => '<p>asdf</p>',
  'c_id' => '2',
  'reminder_email' => '',
  'reminder_sms' => '',
  'dialog_video_title' => '',
  'dialog_video_content' => '',
  'id_ticket_template' => '1',
  'e_currency_id' => '5',
  'payment_method' => '3',
  'tax_other_name' => 'VAT', //apparently needs this to show VAT label in tickets
  'tax_other_percentage' => '0',
  'tax_ref_other' => 'b4rb4ad0s',
  'ticket_type' => 'open',
    
  'cat_all' => 
  array (
    0 => '1',
    1 => '0',
  ),
  
  'cat_1_type' => 'open',
  'cat_1_name' => 'Kid',
  'cat_1_description' => '',
  'cat_1_capa' => $this->capacity[1],// '49',
  'cat_1_over' => '0',
  'cat_1_price' => '50.00',
  'cat_1_soldout' => '0',
  
  'cat_0_type' => 'open',
  'cat_0_name' => 'Adult',
  'cat_0_description' => '',
  'cat_0_capa' => $this->capacity[0], //'99',
  'cat_0_over' => '20',
  'cat_0_price' => '100.00',
  'cat_0_soldout' => '0',            
  'has_ccfee' => '1',          
  'create' => 'do',
            
    );
    
    return array_merge($data, $this->getOutletSetup());
    
    return $data;
    
  }
  
  function getOutletSetup(){
    
    $ids = array(1,2,4,8);
    
    $test = bindec($this->outlets);
    
    $outs = array();
    foreach( $ids as $id  ){
      if ($test & $id ){
        $outs['outlet_'.$id]='on';
      }
    }
    
    Utils::log( "Outlets: " . $test. " \n".  print_r($outs, true));
    
    return $outs; 
  }
  
  //With this data we essentially create both the tour setting (x1) and the tour dates (xN depending of the date range) 
  function tourDatesOkData(){
    return array (
      'page' => 'Tour',
      'method' => 'save-tour',
      'id' => '0',
      'name' => $this->name, //tour setting name
      'event_id' => '<TEMPLATE_ID_HERE>',
      'time' => $this->time_start? $this->time_start: '08:30',
      'cycle' => $this->cycle,// ? $this->cycle : 'weekly',
      'interval' => '1',
      'date-start' => $this->date_start? $this->date_start: date('Y-m-d', strtotime('+4 day')),   //'2012-10-29', //future so we 
      'date-end' => $this->date_end? $this->date_end: date('Y-m-d', strtotime('+25 days')), //'2012-11-16',
      'repeat-on' => 
      array (
        0 => 'FR',
      ),
      'repeat-by' => 'day_of_the_month',
      'color' => '#DB0D0D',
    );
  }
  
  function build(){
    //build the template
    $data = $this->template_okData();
    $data['c_id'] = $this->user->contact_id;
    $_POST = $data;
    $ctrl = new \controller\Template(); //This creates templates and categories
    
    if (!isset($ctrl->event_id)){
        Utils::log(print_r($ctrl->errors));
        throw new Exception(__METHOD__ . " tour was not created. Not logged in? No venues?");
    }
    
    $event_id = $ctrl->event_id;
    //will have to override event_ids
    $this->sys->changeEventId($event_id, $this->event_id);
    
    //return categories
    $this->findCategories(); 
    $this->registerDisponibility();
    
    //this is a next step, in the tours screen, but we do it here already for brevety
    $this->buildTours($this->data, $this->pre);
    
    $this->site->logout();
  }
  
  /**
   * This is the equivalent of pressing Save on the Add Tour dialog at website/tour
   * It will create a tour_settings row (x1) and n tour_dates rows depending on the date range and repeat settings
   * @param array $data post 
   * @param string $prefix to override with each of the tour_dates event_id's
   */
  function buildTours($data, $prefix){
      Request::clear();
      $data = array_merge($this->tourDatesOkData(), $data);
      $data['event_id'] = $this->event_id;
      $_POST = $data;
      $ajax = new \ajax\Tour();
      $ajax->Process();
      
      //and while we're at it, let's override the ids of the created tours with our patterns
      $this->overrideTourIds($ajax->tour_settings_id, $prefix);
  }
  
  function findCategories(){
      if(!empty($this->categories)){
          return $this->categories;
      }
      
    $rows = $this->db->getAll("SELECT id FROM category WHERE event_id=?", $this->event_id );
    if($rows){
      foreach($rows as $row){
        $this->categories[] = new \model\Categories($row['id']);
      }
    }
    
    return $this->categories;
  }
  
  //create default website disponibility
  function registerDisponibility(){
      foreach($this->categories as $cat){
          //$this->db->insert('disponibility', array('module_id'=>1, 'category_id'=>$cat->id));
          ModuleHelper::showInWebsite($this->db, $cat->id);
      }
  }
  
  function overrideTourIds($tour_settings_id, $prefix){
    $rows = $this->db->getIterator("SELECT event_id FROM tour_dates WHERE tour_settings_id=? ORDER BY `date` ASC", $tour_settings_id);
    $n=1;
    foreach ($rows as $row){
      $this->db->update('tour_dates', array('event_id'=>$prefix . $n++), "event_id=?", $row['event_id']);
    }
  }
  
  
}
