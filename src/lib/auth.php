<?php
//   include '../conf/db-config.php';
//   include '../conf/php-vars.php';

  function base64UrlEncode($text) {
    return str_replace(
      ['+', '/', '='],
      ['-', '_', ''],
      base64_encode($text)
    );
  }
  
  function signJwt($header, $payload) {
    include(dirname(__DIR__).'/conf/php-vars.php');
    
    $base64UrlHeader = base64UrlEncode($header);
    $base64UrlPayload = base64UrlEncode($payload);
    
    $jwtString = $base64UrlHeader . "." . $base64UrlPayload;
    
    $signature = hash_hmac('sha256', $jwtString, $jwt_secret, true);
    $base64UrlSignature = base64UrlEncode($signature);
    
    return $jwtString . "." . $base64UrlSignature;
  }
  
  
  function generateJwt($uid, $exp) {
    // create header
    $header = json_encode([
      'typ' => 'JWT',
      'alg' => 'HS256'
    ]);
    
    $payload = json_encode([
      'userId' => $uid,
      'exp' => $exp
    ]);
    
    // encode header & payload
    return signJwt($header, $payload);
  }
  

  function getUserByEmail($email) {
    include(dirname(__DIR__).'/conf/db-config.php');
    
    // get user from db
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    $stmt_user = $conn->prepare("
    SELECT
      id, email, nickname
    FROM
      users
    WHERE
      email = :email
    ");
    
    $stmt_user->bindParam(":email", $email);
    $stmt_user->execute();
    $uar = $stmt_user->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($uar) !== 1) {
      $userDoesntExistResponse = new stdClass();
      $userDoesntExistResponse->error = "USER_DOESNT_EXIST";
      return $userDoesntExistResponse;
    } 
    
    $user = $uar[0];
   
    // get permissions for said user
//     $stmt_permissions = $conn->prepare("
//     SELECT
//       p.id AS id, p.name AS name
//     FROM
//       user_permissions up
//       LEFT JOIN permissions p ON p.id = up.permission_id
//     WHERE
//       up.user_id = :userId
//     ");
    
//     $stmt_permissions->bindParam(":userId", $user['id']);
//     $stmt_permissions->execute();
//     $permissions = $stmt_permissions->fetchAll(PDO::FETCH_ASSOC);
//     
//     $user->permissions = $permissions;
    
    return json_decode(json_encode($user));
  }
  
  /**
   * Gets user whom the jwt belongs to
   * TODO: FIX THIS, IT'S BROKEN
   */
  function getUser($jwt) {
    include(dirname(__DIR__).'/conf/php-vars.php');
    include(dirname(__DIR__).'/conf/db-config.php');
    
    $tokenParts = explode('.', $jwt);
    
    $jwtString = $tokenParts[0] . "." . $tokenParts[1];
    
    $header = base64_decode($tokenParts[0]);
    $payload = base64_decode($tokenParts[1]);
    $signature = $tokenParts[2];
    
    $payloadObj = json_decode($payload);
    
    $date = new DateTime();
    $ctime = $date->getTimestamp();
      
    // validate expiration
    if ($payloadObj->exp < $ctime) {
      $jwtTimeoutResponse = new stdClass();
      $jwtTimeoutResponse->error = "JWT_EXPIRED";
      return $jwtTimeoutResponse;
    }
    
    // validate signature
    $verificationSignature = hash_hmac('sha256', $jwtString, $jwt_secret, true);
        
    if ($signature !== base64UrlEncode($verificationSignature)) {
      $jwtSignatureInvalidResponse = new stdClass();
      $jwtSignatureInvalidResponse->error = "JWT_INVALID";
      
      return $jwtSignatureInvalidResponse;
    }
    
    // get user from db
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    $stmt_user = $conn->prepare("
      SELECT
        id, email, nickname
      FROM
        users
      WHERE
        id = :userId
    ");
    
    $stmt_user->bindParam(":userId", $payloadObj->userId);
    $stmt_user->execute();
    $uar = $stmt_user->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($uar) !== 1) {
      $userDoesntExistResponse = new stdClass();
      $userDoesntExistResponse->error = "USER_DOESNT_EXIST";
      $userDoesntExistResponse->userId = $payloadObj->userId;
      $userDoesntExistResponse->jwtPayload = $payloadObj;
      return $userDoesntExistResponse;
    } 
    
    $user = $uar[0];
    
    // get permissions for said user
//     $stmt_permissions = $conn->prepare("
//       SELECT
//         p.id AS id, p.name AS name
//       FROM
//         user_permissions up
//         LEFT JOIN permissions p ON p.id = up.permission_id
//       WHERE
//         up.user_id = :userId
//     ");
    
//     $stmt_permissions->bindParam(":userId", $payloadObj->userId);
//     $stmt_permissions->execute();
//     $permissions = $stmt_permissions->fetchAll(PDO::FETCH_ASSOC);
    
//     $user->permissions = $permissions;
    
    return $user;
  }
?>
