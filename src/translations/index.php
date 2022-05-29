<?php
  // ENDPOINT: /translations
  // (this is the one for creating and updating translations)
  //
  // just to set the record straight:
  //  * node <3
  //  * my webhost only does php so at this point i dont even
  //    give a fuck about the quality of this code. As long as
  //    it runs.
  //  * i'd do a proper api /w express and node but again, my
  //    webhost only does php
  header('Content-Type: application/json');
  
  function isValidJSON($str) {
    json_decode($str);
    return json_last_error() == JSON_ERROR_NONE;
  }
  
  function checkUser($authToken) {
    $res = new stdClass();
    
    if (empty($authToken)) {
      $res->errorCode = "401";
      $res->error = "User is not logged in.";
      // echo json_encode($res);
      die(json_encode($res));
    }
    
    // TODO: check permissions
    $user = getUser($authToken);
    if (!empty($user->error)) {
      $res->errorCode = "403";
      $res->error = "There's problems with the JWT token.";
      $res->jwt = $authToken;
      $res->user = $user;
      // echo json_encode($res);
      die(json_encode($res));
    }
  }
  
  function createTranslation($translationData, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';
    checkUser($authToken);
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }
    
    // if translation priority isn't provided, priority is set to last.
    if (empty($translationData->priority)) {
      $sql_select_count = "
        SELECT COUNT(id) as existingTranslationsCount
        FROM translation
        WHERE 
          en_id = :en_id
          AND sl_id = :sl_id
        ;
      ";
      $stmt_translation = $conn->prepare($sql_select_count);
      $stmt_translation->bindParam(":en_id", $translationData->enWordId);
      $stmt_translation->bindParam(":sl_id", $translationData->slWordId);
      
      try {
        $stmt_translation->execute();
        $translationResult = $stmt_translation->fetchAll(PDO::FETCH_ASSOC);
        $translationData->priority = $translationResult[0]->existingTranslationsCount + 1;
      } catch (Exception $e) {
        $res->error = $e;
        echo json_encode($res);
        return;
      }
    }
    
    $sql_select_insert = "
      INSERT INTO translation (en_id, sl_id, translation_priority, rfc, notes)
        VALUES (:en_id, :sl_id, :priority, :rfc, :notes);
    ";
    
    $sql_select_update = "
      UPDATE translation
      SET
        en_id = COALESCE(:en_id, en_id),
        sl_id = COALESCE(:sl_id, sl_id),
        translation_priority = COALESCE(:priority, translation_priority),
        rfc = :rfc,
        notes = :notes
      
      WHERE
        id = :id;
    ";
    
    if (empty($translationData->id)) {
      $stmt_en2si = $conn->prepare($sql_select_insert);
    } else {
      $stmt_en2si = $conn->prepare($sql_select_update);
    }
    
    $stmt_en2si->bindParam(":en_id", $translationData->enWordId);
    $stmt_en2si->bindParam(":sl_id", $translationData->slWordId);
    $stmt_en2si->bindParam(":priority", $translationData->priority);
    $stmt_en2si->bindParam(":rfc", $translationData->rfc);
    $stmt_en2si->bindParam(":notes", $translationData->notes);
    
    if (!empty($translationData->id)) {
      $stmt_en2si->bindParam(":id", $translationData->id);
    } 
    
    // insert new value:
    try {
      $stmt_en2si->execute();
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }
    
    // get newly inserted value from base, as it was inserted
    if (empty($translationData->id)) {
      $last_id = $conn->lastInsertId();
    } else {
      $last_id = $translationData->id;
    }
    
    $sql_select_inserted = "
      SELECT
        id, en_id, sl_id, translation_priority as priority, rfc, notes
      FROM
        translation
      WHERE
        id = :id;
    ";
    $stmt_inserted = $conn->prepare($sql_select_inserted);
    $stmt_inserted->bindParam(":id", $last_id);
    
    try {
      $stmt_inserted->execute();
      $res = $stmt_inserted->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }
    
    echo json_encode($res[0]);
  }
  
  function removeTranslation($translationId, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';
    checkUser($authToken);
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }
    
    $res = new stdClass();
    
    if (empty($translationId)) {
      $res->error = "Translation ID must be provided";
      echo json_encode($res);
      return;
    }
    
    $sql_delete = "
      DELETE FROM translation
      WHERE id = :id;
    ";
    
    $stmt_en2si = $conn->prepare($sql_delete);
    
    try {
      $stmt_en2si->bindParam(":id", $translationId);
      $stmt_en2si->execute();
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }
    
    $res->message = "ok";
    $res->deletedTranslationId = $translationId;
    echo json_encode($res);
  }
  
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = apache_request_headers();
    
    $json_params = file_get_contents("php://input");
    
    if (strlen($json_params) > 0 && isValidJSON($json_params)) {
      $decoded_params = json_decode($json_params);
    } else {
      $response->error="There's been a fucky wucky with the post request.";
      echo json_encode($response);
      return;
    }
    
    $response = new stdClass();
    
    if (isset($headers['Authorization'])) {
      $response->message="authorization header present!";
      $response->postJson=$decoded_params;
      
      createTranslation($decoded_params, $headers['Authorization']);
    } else {
      $response->errorCode = 403;
      $response->error = "Authorization header not present";
      
      echo json_encode($response);
      return;
    }
  }
  
  if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $headers = apache_request_headers();
    
    $json_params = file_get_contents("php://input");
    
    if (strlen($json_params) > 0 && isValidJSON($json_params)) {
      $decoded_params = json_decode($json_params);
    } else {
      $response->error="There's been a fucky wucky with the post request.";
      echo json_encode($response);
      return;
    }
    
    $response = new stdClass();
    
    if (isset($headers['Authorization'])) {
      $response->message="authorization header present!";
      $response->postJson=$decoded_params;
      
      removeTranslation($decoded_params->id, $headers['Authorization']);
    } else {
      $response->errorCode = 403;
      $response->error = "Authorization header not present";
      
      echo json_encode($response);
      return;
    }
  }
?>
