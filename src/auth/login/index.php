<?php
  // ENDPOINT: /auth/login
  //
  // just to set the record straight:
  //  * node <3
  //  * my webhost only does php so at this point i dont even
  //    give a fuck about the quality of this code. As long as
  //    it runs.
  //  * i'd do a proper api /w express and node but again, my
  //    webhost only does php
  
  function login($googleToken) {
    include '../../lib/auth.php';
    include '../../conf/db-config.php';
    
    $curl = curl_init(); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, "https://oauth2.googleapis.com/tokeninfo?id_token=" . $googleToken);
    
    $responseDataJson = curl_exec($curl);
    curl_close($curl);
    
    $responseData = json_decode($responseDataJson);
    
    // Get user:
    $user = getUserByEmail($responseData->email);
    
    if ($user->id == NULL) {
      $response = new stdClass();
      $response->error="user not found!";
      $response->googleResponseData = $responseData;
      http_response_code(401);
      die(json_encode($response));
    }
    
    // set expiration time
    $date = new DateTime();
    $exptime = $date->getTimestamp() + 30*24*3600;
    
    $token = generateJwt($user->id, $exptime);

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
  
  $json_params = file_get_contents("php://input");
  
  if (strlen($json_params) > 0 && isValidJSON($json_params)) {
    $decoded_params = json_decode($json_params);
    
    login($decoded_params->idToken);
  }
?>
