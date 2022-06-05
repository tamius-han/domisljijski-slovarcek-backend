<?php
  // ENDPOINT: /bindMeaning
  // (this is the one for creating and deleting word:meaning mappings)
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

  /**
   * Creates translation between two meanings.
   */
  function createWordMeaningBind($wordMeaningBind, $authToken) {
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
      empty($wordMeaningBind->meaning_id)
      || empty($wordMeaningBind->word_id)
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

    $values = array();
    $values[] = ":meaning_id, :word_id";

    if (empty($wordMeaningBind->wordPriority))     { $values[] = "0"; } else { $values[] = ":word_priority"; }
    if (empty($wordMeaningBind->meaningPriority))  { $values[] = "0"; } else { $values[] = ":meaning_priority"; }

    $sql_select_insert = "
      INSERT INTO words2meanings (meaning_id, word_id, word_priority, meaning_priority)
        VALUES (" . join(', ', $values) . ");
    ";

    $stmt_en2si = $conn->prepare($sql_select_insert);

    $stmt_en2si->bindParam(":meaning_id", $wordMeaningBind->meaning_id);
    $stmt_en2si->bindParam(":word_id", $wordMeaningBind->word_id);
    if (!empty($wordMeaningBind->wordPriority))    { $stmt_en2si->bindParam(":word_priority", $wordMeaningBind->wordPriority); }
    if (!empty($wordMeaningBind->meaningPriority))  { $stmt_en2si->bindParam(":meaning_priority", $wordMeaningBind->meaningPriority); }

    // insert new value:
    try {
      $stmt_en2si->execute();
    } catch (Exception $e) {
      $res->error = $e;
      http_response_code(422);
      echo json_encode($res);
      return;
    }

    try {
      $res->msg = "Inserted.";
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      http_response_code(422);
      return;
    }

    echo json_encode($res);
  }

  /**
   * Removes word<-->meaning binding
   */
  function deleteWordMeaningBind($meaning_id, $word_id, $authToken) {
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
      empty($meaning_id)
      || empty($word_id)
    ) {
      $res->error = "Request is missing a word or category associated with this meaning.";
      echo json_encode($res);
      http_response_code(422);
      return;
    }

    $sql_delete = "
      DELETE FROM words2meanings
      WHERE meaning_id = :meaning_id AND word_id = :word_id
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

      createWordMeaningBind($decoded_params, $headers['Authorization']);
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

      deleteWordMeaningBind($decoded_params->meaning_id, $decoded_params->word_id, $headers['Authorization']);
    } else {
      $response->errorCode = 403;
      $response->error = "Authorization header not present";

      echo json_encode($response);
      return;
    }
  }
?>
