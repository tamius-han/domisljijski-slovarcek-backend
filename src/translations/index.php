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

  /**
   * Creates translation between two meanings.
   */
  function createTranslation($translation, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';

    $res = new stdClass();

    //  --------------------------------------------- LOGIN/PERMISSION VALIDATION  ---------------------------------------------
    if (empty($authToken)) {
      $res->errorCode = "401";
      $res->error = "User is not logged in.";
      die(json_encode($res));
      return;
    }

    // TODO: check permissions
    $user = getUser($authToken);
    if (!empty($user->error)) {
      $res->errorCode = "403";
      $res->error = "There's problems with the JWT token.";
      $res->jwt = $authToken;
      $res->user = $user;
      die(json_encode($res));
    }

    //  --------------------------------------------- DATA VALIDATION  ---------------------------------------------
    // TODO: validate data and stuff
    if (
      empty($translation->meaning_en)
      || empty($translation->meaning_sl)
    ) {
      $res->error = "Request is missing a word or category associated with this meaning.";
      echo json_encode($res);
      http_response_code(422);
      return;
    }

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }

    // check that the languages were given correctly
    // if "meaning_en" links to a slovenian word, we need to swap meaning_en and meaning_sl
    $stmt_getLanguage = $conn->prepare("SELECT w.language as language FROM meanings m LEFT JOIN words2meanings wm ON m.id = wm.meaning_id LEFT JOIN words w ON w.id = wm.word_id WHERE m.id = :meaning_id");
    $stmt_getLanguage->bindParam(':meaning_id', $translation->meaning_en);

    try {
      $stmt_getLanguage->execute();
      $res = $stmt_getLanguage->fetch(PDO::FETCH_ASSOC);

      if ($res["language"] == 'sl') {
        $tmp = $translation->meaning_en;
        $translation->meaning_en = $translation->meaning_sl;
        $translation->meaning_sl = $tmp;
      }
    } catch (Exception $e) {
      $res->error = $e;
      http_response_code(422);
      echo json_encode($res);
      return;
    }

    $sql_select_insert = "
      INSERT INTO translations (meaning_en, meaning_sl)
        VALUES (:en_id, :sl_id);
    ";

    $stmt_en2si = $conn->prepare($sql_select_insert);

    $stmt_en2si->bindParam(":en_id", $translation->meaning_en);
    $stmt_en2si->bindParam(":sl_id", $translation->meaning_sl);

    // insert new value:
    try {
      $stmt_en2si->execute();
      echo json_encode($res);
    } catch (Exception $e) {
      $res2 = new stdClass();
      $res2->error = $e;
      $res2->orgRes = $res;
      http_response_code(422);
      echo json_encode($res2);
      return;
    }

  }

  function removeTranslation($enId, $slId, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';

    $res = new stdClass();

    //  --------------------------------------------- LOGIN/PERMISSION VALIDATION  ---------------------------------------------
    if (empty($authToken)) {
      $res->errorCode = "401";
      $res->error = "User is not logged in.";
      die(json_encode($res));
      return;
    }

    // TODO: check permissions
    $user = getUser($authToken);
    if (!empty($user->error)) {
      $res->errorCode = "403";
      $res->error = "There's problems with the JWT token.";
      $res->jwt = $authToken;
      $res->user = $user;
      die(json_encode($res));
    }

    //  --------------------------------------------- DATA VALIDATION  ---------------------------------------------
    // TODO: validate data and stuff
    if (
      empty($enId)
      || empty($slId)
    ) {
      $res->error = "Request is missing a word or category associated with this meaning.";
      echo json_encode($res);
      http_response_code(422);
      return;
    }

    $sql_delete = "
      DELETE FROM translations
      WHERE meaning_en = :en AND meaning_sl = :sl
    ";

    $stmt_en2si = $conn->prepare($sql_delete);

    try {
      $stmt_en2si->bindParam(":en", $enId);
      $stmt_en2si->bindParam(":sl", $slId);
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

      removeTranslation($decoded_params->meaning_en, $decoded_params->meaning_sl, $headers['Authorization']);
    } else {
      $response->errorCode = 403;
      $response->error = "Authorization header not present";

      echo json_encode($response);
      return;
    }
  }
?>
