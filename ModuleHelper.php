<?php
/**
 * Stub class to test the reservations module
 * Login system not designed by me
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
  
  
}

