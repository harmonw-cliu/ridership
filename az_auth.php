<?php

<<<<<<< HEAD
namespace FakeDebugHeader;

=======
>>>>>>> refs/remotes/origin/main
require_once "include/debug_header.php";

//This login script is based on Sami Sipponen's Simple Azure Oauth2 Example with PHP:
//https://www.sipponen.com/archives/4024

session_start();  //Since you likely need to maintain the user session, let's start it an utilize it's ID later
//error_reporting(-1);  //Remove from production version
//ini_set("display_errors", "on");  //Remove from production version

//Configuration, needs to match with Azure app registration
require_once "include/secrets.php";
$redirect_uri = "https://ridership.cliu.org/az_auth.php";  //This needs to match 100% what is set in Azure
$error_email = "harmonw@cliu.org";  //If your php.ini doesn't contain sendmail_from, use: ini_set("sendmail_from", "user@example.com");
$site_url = "ridership.cliu.org"; //For sending error reports
$landing_url = "https://ridership.cliu.org/index.php";

if (!isset($_GET["code"]) and !isset($_GET["error"])) {  //Real authentication part begins
  //First stage of the authentication process; This is just a simple redirect (first load of this page)
  $url = "https://login.microsoftonline.com/" . $ad_tenant . "/oauth2/v2.0/authorize?";
  $url .= "state=" . session_id();  //This at least semi-random string is likely good enough as state identifier
  $url .= "&scope=user.read.all+GroupMember.Read.All";  //This scope seems to be enough, but you can try "&scope=profile+openid+email+offline_access+User.Read" if you like
  $url .= "&response_type=code";
  $url .= "&approval_prompt=auto";
  $url .= "&client_id=" . $client_id;
  $url .= "&redirect_uri=" . urlencode($redirect_uri);
  $url .= "&response_mode=query";

  header("Location: " . $url);  //So off you go my dear browser and welcome back for round two after some redirects at Azure end

} elseif (isset($_GET["error"])) {  //Second load of this page begins, but hopefully we end up to the next elseif section...
  mail($error_email, $site_url . " error", "$site_url az_auth.php error received at second stage \r\n" . print_r($_GET, true));
  die("Authentication error 1");

} elseif (strcmp(session_id(), $_GET["state"]) == 0) {  //Checking that the session_id matches to the state for security reasons
  //And now the browser has returned from its various redirects at Azure side and carrying some gifts inside $_GET
  
  //Verifying the received tokens with Azure and finalizing the authentication part
  $content = "grant_type=authorization_code";
  $content .= "&client_id=" . $client_id;
  $content .= "&redirect_uri=" . urlencode($redirect_uri);
  $content .= "&code=" . $_GET["code"];
  $content .= "&client_secret=" . urlencode($client_secret);
  $options = array(
    "http" => array(  //Use "http" even if you send the request with https
      "method"  => "POST",
      "header"  => "Content-Type: application/x-www-form-urlencoded\r\n" .
      "Content-Length: " . strlen($content) . "\r\n",
      "content" => $content
    )
  );
  
  $context  = stream_context_create($options);
  $json = file_get_contents("https://login.microsoftonline.com/" . $ad_tenant . "/oauth2/v2.0/token", false, $context);
  $authdata = json_decode($json, true);
  if ($json === false){
   mail($error_email, $site_url . " error", "az_auth.php error received during Bearer token fetch \r\n " . print_r(error_get_last()) . " \r\n\ " . print_r($_GET, true));
   die("Authentication error 2");
   }
  
  if (isset($authdata["error"])){
   mail($error_email, $site_url . " error", "az_auth.php error received during Bearer token fetch contained an error \r\n " . $authdata . " \r\n\ " . print_r($_GET, true));
   die("Authentication error 3");
   }
   
  //Fetching the basic user information that is likely needed by your application
  $options = array(
    "http" => array(  //Use "http" even if you send the request with https
      "method" => "GET",
      "header" => "Accept: application/json\r\n" .
        "Authorization: Bearer " . $authdata["access_token"] . "\r\n"
      )
    );

  $context = stream_context_create($options);
  $json = file_get_contents("https://graph.microsoft.com/v1.0/me", false, $context);

  if ($json === false){
   mail($error_email, $site_url . " error", "az_auth.php error received during user data fetch. \r\n" . error_get_last() . " \r\n\ " . print_r($_GET, true));
   die("Authentication error 4");
   }

  $userdata = json_decode($json, true);  //This should now contain your logged on user information

  if (isset($userdata["error"])){
   mail($error_email, $site_url . " error", "az_auth.php error received User data fetch contained an error. \r\n" . print_r($userdata, TRUE) . "\r\n \r\n " . print_r($_GET, true));
   die("Authentication error 5");
  } else {
  
  //Fetch groups the user is a member of
  $json = file_get_contents("https://graph.microsoft.com/v1.0/me/memberOf", false, $context);

  if ($json === false){
   mail($error_email, $site_url . " error", "az_auth.php error received during group data fetch. \r\n" . error_get_last() . " \r\n\ " . print_r($_GET, true));
   die("Authentication error 6");
   }

  $groupdata = json_decode($json, true);  //This should now contain your user group data
  
   if (isset($groupdata["error"])){
   mail($error_email, $site_url . " error", "az_auth.php error received Group data fetch contained an error. \r\n" . print_r($userdata, TRUE) . "\r\n \r\n " . print_r($_GET, true));
   die("Authentication error 7");
  }
  
  //Let's make a nice clean array of the group names
  $az_groups = array();
  foreach($groupdata["value"] as $az_groupinfo){
     $az_groups[] = $az_groupinfo["displayName"];
  }
  
  //Set session vars and send user to the index page now since they are logged in and we have their information...
  $_SESSION["az_user_data"] = $userdata;
  //$_SESSION["az_group_data"] = $groupdata;
  $_SESSION["az_groups"] = $az_groups;
  
  //mail($error_email, $site_url . " error", "az_auth.php data: \r\n" . print_r($userdata, true));
  header("Location: " . $landing_url);
  }
} else {
  //If we end up here, something has obviously gone wrong... Likely a hacking attempt since sent and returned state aren't matching and no $_GET["error"] received.
  mail($error_email, $site_url . " error", "az_auth.php error received Possible hacking attempt since sent and returned states do not match. \r\n" . print_r($userdata, TRUE) . "\r\n \r\n " . print_r($_GET, true));
  die("Authentication error 8");
}
