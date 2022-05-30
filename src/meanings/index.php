<?php
  // ENDPOINT: api/meanings
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

  // TODO
  function getMeaningByWordId($id, $language) {
    include '../conf/db-config.php';

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }

    // echo json_encode($res);
  }

  // TODO
  function listMeaningsForWord($filter) {
    include '../conf/db-config.php';

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }
  }

  // TODO
  function getMeaning($id) {
    include '../conf/db-config.php';

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }

    // echo json_encode($res);
  }

  function addMeaning($meaning, $authToken) {
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
      empty($meaning->wordId)
      || empty($meaning->categoryIds)
      || $meaning->categoryIds->length == 0
    ) {
      $res->error = "Request is missing a word or category associated with this meaning.";
      echo json_encode($res);
      return;
    }

    //  --------------------------------------------- SETUP DB CONNECTION  ---------------------------------------------
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }

    // --------------------------------------------- BUILD QUERY AND BIND PARAMS  ---------------------------------------------
    $values = array();
    $values[] = ":meaning, :type";
    if (empty($meaning->notes))     { $values[] = "NULL"; } else { $values[] = ":notes";                }
    // communitySuggestion: should check for permissions and check accordingly
    if (true)                       { $values[] = "0";    } else { $values[] = ":communitySuggestion";  }

    $sql_insert = "
      INSERT INTO meanings
        (meaning, type, notes, communitySuggestion)
      VALUES (" . join(", ", $values) . ");
    ";

    $stmt_insert = $conn->prepare($sql_insert);

    $stmt_insert->bindParam(':meaning', $meaning->meaning);
    $stmt_insert->bindParam(':type', $meaning->type);
    if (!empty($meaning->notes))     { $stmt_insert->bindParam(':notes', $meaning->notes); }
    // communitySuggestion: should check for permissions and check accordingly
    if (false)                       { $stmt_insert->bindParam(":communitySuggestion", $meaning->communitySuggestion); }


    // --------------------------------------------- INSERT MEANING ---------------------------------------------
    try {
      $stmt_insert->execute();
    } catch (Exception $e) {
      $res->msg = "Failed to insert a meaning.";
      $res->query = $stmt_insert;
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    // --------------------------------------------- BIND MEANING TO WORD ---------------------------------------------
    $last_id = $conn->lastInsertId();

    $bind_words = "
      INSERT INTO
        words2meanings (meaning_id, word_id, meaning_priority, word_priority)
        VALUES (:meaning_id, :word_id, :word_priority, :meaning_priority)
    ";
    if (empty($meaning->meaningPriority)) {
      $meaning->meaningPriority = 0;
    }
    if (empty($meaning->wordPriority)) {
      $meaning->wordPriority = 0;
    }
    $stmt_bind = $conn->prepare($bind_words);

    $stmt_bind->bindParam(":meaning_id", $last_id);
    $stmt_bind->bindParam(":word_id", $meaning->wordId);
    $stmt_bind->bindParam(":meaning_priority", $meaning->meaningPriority);
    $stmt_bind->bindParam(":word_priority", $meaning->wordPriority);

    try {
      $stmt_bind->execute();
    } catch (Exception $e) {
      $res->msg = "Failed to insert bind categories to meaning.";
      $res->query = $bind_words;
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    // --------------------------------------------- BIND CATEGORIES TO WORD ---------------------------------------------
    $bind_categories = generateWordCategoryBindStatements(
      "INSERT INTO meanings2categories (meaning_id, category_id) VALUES ",
      $conn,
      $last_id,
      $meaning->categoryIds
    );
    try {
      $bind_categories->execute();
    } catch (Exception $e) {
      $res->msg = "Failed to insert bind categories to meaning.";
      $res->query = $bind_categories;
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    // --------------------------------------------- RETURN LAST INSERTED MEANING ---------------------------------------------
    $sql_select_inserted = "
      SELECT
        id, meaning, type, notes, communitySuggestion

      FROM meanings

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

  /**
   * Generates bind statements for category
   */
  function generateWordCategoryBindStatements($statementStart, $conn, $meaningId, $wordsOrCategories) {
    $values = array();

    foreach($wordsOrCategories as $i => $item) {
      $values[] = "( :meaningId" . $i . ", :bindToId" . $i . " )";
    }

    $stmt_insert = $conn->prepare(
      $statementStart . join($values, ', ') . ";"
    );

    foreach($wordsOrCategories as $i => $item) {
      $stmt_insert->bindParam(":meaningId" . $i, $meaningId);
      $stmt_insert->bindParam(":bindToId" . $i, $wordsOrCategories[$i]);
    }

    return $stmt_insert;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['s'];
    $id = $_GET['id'];

    // if (empty($id)) {
    //   listWords(null);
    // } else {
    //   getWordById($id, $language);
    // }

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

      if (empty($decoded_params->id)) {
        addMeaning($decoded_params, $headers['Authorization']);
      // } else {
      //   addMeaning($decoded_params, $headers['Authorization']);
      }
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

    // if (isset($headers['Authorization'])) {
    //   $response->message="authorization header present!";
    //   $response->postJson=$decoded_params;

    //   deleteWord($decoded_params->id, $decoded_params->lang, $headers['Authorization']);
    // } else {
    //   $response->errorCode = 403;
    //   $response->error = "Authorization header not present";

    //   echo json_encode($response);
    //   return;
    // }
  }
?>
