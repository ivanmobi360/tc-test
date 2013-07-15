<?php
/**
 * @author Ivan Rodriguez
 * This is the one in the administration page! 
 * http://localhost/tixprocaribbean/website/administration-ticketvalidation
 * For the Validation module, use ValidationTest!
 * 
 * Update: website module has been dropped: http://jira.mobination.net:8080/browse/TIXCAR-408
 * 
 */
namespace ajax;


use tool\Request;

use Utils;

class TicketvalidationTest extends ValidationTest {
  
  function createInstance(){
    return new Ticketvalidation();
  }
  
  
  
}