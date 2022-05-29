<?php
  // ENDPOINT: /auth/token-refresh
  //
  // just to set the record straight:
  //  * node <3
  //  * my webhost only does php so at this point i dont even
  //    give a fuck about the quality of this code. As long as
  //    it runs.
  //  * i'd do a proper api /w express and node but again, my
  //    webhost only does php
  
  function refresh($authToken) {
    include '../../lib/auth.php';
    include '../../conf/db-config.php';
      
    // Get user:
    $res = new stdClass();
    
    if (empty($authToken)) {
      $res->errorCode = "401";
      $res->error = "User is not logged in.";
      // echo json_encode($res);
      die($res);
    }
    
    // TODO: check permissions
    $user = getUser($authToken);
    if (!empty($user->error)) {
      $res->errorCode = "403";
      $res->error = "There's problems with the JWT token.";
      $res->jwt = $authToken;
      $res->user = $user;
      // echo json_encode($res);
      die($res);
    }
    
    // set expiration time
    $date = new DateTime();
    $exptime = $date->getTimestamp() + 30*24*3600;
    
    $token = generateJwt($user['id'], $exptime);

    returnJson($token);
  }
  
  function returnJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
  }
  
  function isValidJSON($str) {
    json_decode($str);
    return json_last_error() == JSON_ERROR_NONE;
  }
  
  $headers = apache_request_headers();
  
  if (isset($headers['Authorization'])) {
    refresh($headers['Authorization']);
  } else {
    $response->errorCode = 403;
    $response->error = "Authorization header not present";
    
    echo json_encode($response);
    return;
  }    
?>
