<?php
/**
 * @author Ivan Rodriguez
 * This is the one in the administration page! 
 * http://localhost/tixprocaribbean/website/administration-ticketvalidation
 * For the Validation module, use ValidationTest!
 * 
 * Update: website module has been dropped: http://jira.mobination.net:8080/browse/TIXCAR-408
 * 
 * Update: Apparently it is also in use by website/reservationsticketvalidation (Reservations module) so let's clean it up a bit and modernize it
 * 
 */
namespace ajax;


use tool\Request;

use Utils;

class TicketvalidationTest extends ValidationTicketScanTest {
  
  function createInstance(){
    return new Ticketvalidation();
  }
  
  
  
}