<?php
//****************************************************************************
// Database Configuration
//****************************************************************************

require_once('Database.php');
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PW', '');
define('DB_DBNAME', '');

//****************************************************************************
// Validation of script call
//****************************************************************************

//Log the entire request into a log file for easy inspection
LogFile($_REQUEST);

//Check if the request contains a value for "friends" or for "username/identity"
$valid = false;

if( isset( $_REQUEST['friends'] ))
{
  $valid = true;
  ProcessFriendRequest();      
}

if( (isset($_REQUEST['username']) && (isset($_REQUEST['identity'])) ))
{
  $valid = true;
  ProcessRegistration();  
}

if(! $valid )
{  
  ProcessError();
}

//****************************************************************************
// Process a friend request
//****************************************************************************
function ProcessFriendRequest()
{
  $db = new Database();
  $query = sprintf("SELECT id, appid, username, identity, updated FROM registrations WHERE username='%s'",
    mysql_real_escape_string($_REQUEST['friends']));
  LogFile($query);
    
  $result = $db->GetObject($query);
  
  if(! $result)
  {
    $update = 'false'; 
    $reply = file_get_contents('update.xml');
    $reply = str_replace('$update', $update, $reply);  
    echo $reply;    
  }
  else
  {  
    //Get the XML Response Template file and format it 
    $reply = file_get_contents('friends.xml');
    $reply = str_replace('$user', $result->username, $reply);
    $reply = str_replace('$identity', $result->identity, $reply);
    LogFile($reply);
    echo $reply;
  }      
}

//****************************************************************************
// Process a registration (identity will be 0 when disconnecting)
//****************************************************************************
function ProcessRegistration()
{
	
  $db = new Database();  
  $query = sprintf("SELECT id, appid, username, identity, updated FROM registrations WHERE username='%s'",
    mysql_real_escape_string($_REQUEST['username']));
  
  LogFile($query); 
  $result = $db->GetObject($query);
  
  if(! $result)
  {
    //Record does not exist yet
    $update = "true";
    $sql = sprintf("INSERT INTO registrations (appid, username, identity, updated) VALUES ( 0, '%s', '%s', NOW())",
      mysql_real_escape_string($_REQUEST['username']),
      mysql_real_escape_string($_REQUEST['identity']));
    $db->execute($sql);  
    LogFile($sql);    
  }
  else
  {
    //Record already exists
    $update = "true";
    $sql = sprintf("UPDATE registrations SET updated = NOW(), identity = '%s' WHERE username = '%s'",
      mysql_real_escape_string($_REQUEST['identity']),
      mysql_real_escape_string($_REQUEST['username']));
    $db->execute($sql); 
    LogFile($sql);         
  }
  
  //Get the XML Response Template file and format it 
  $reply = file_get_contents('update.xml');
  $reply = str_replace('$update', $update, $reply);  
  LogFile($reply);
  echo $reply;    
}

//****************************************************************************
// Process an error
//****************************************************************************
function ProcessError()
{
  $update = 'false'; 
  $reply = file_get_contents('update.xml');
  $reply = str_replace('$update', $update, $reply); 
  LogFile($reply); 
  echo $reply;    
}

//****************************************************************************
// Log File function
//****************************************************************************
function LogFile($msg)
{
  $smsg = print_r($msg, true);
  $time = date("F jS Y, h:iA"); 
  $ip = $REMOTE_ADDR; 
  $referer = $HTTP_REFERER; 
  $browser = $HTTP_USER_AGENT;   
  $fp = fopen("log.txt", "a");   
  fputs($fp, "Time: $time IP: $ipReferer: $referer Browser: $browser | $smsg");   
  fclose($fp);   
}

?>