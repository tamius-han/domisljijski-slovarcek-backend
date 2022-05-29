<?php
  // ENDPOINT: api/words
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
   * Inserts a new word into the database.
   *
   * $word needs to have the following parameters:
   *    - ID: you're using the wrong endpoint for that, m9
   *    - credit_userId: should be added by the backend (but is currently not)
   *
   * MANDATORY FIELDS:
   *    - language: 'si' | 'en'
   *    - word: string
   *      the word we're adding
   *    - type: number
   *      (see: enum WordType on frontend!)
   *
   * OPTIONAL FIELDS
   *    - alternativeSpellings: string
   *      alternative spellings of a word. Will be displayed on frontend.
   *
   *    - alternativeSpellingsHidden:
   *      common incorrect spellings of a word, if any.
   *      Used for search, but frontend shouldn't display that.
   *
   *    - genderExtras: json string (NOT json proper!)
   *      alternative gender-based spellings. More or less only present on slovenian words,
   *      where genderExtras _should_ be present where applicable, but that's a CoC issue
   *
   *    - notes: string
   *      any other notes regarding word
   *
   *    - credit: string
   *      user credit
   *
   *    - communitySuggestion: boolean
   *      should be restricted to certain users
   *
   *    - priority: number
   *      deprecated af
   **/
  function addWord($word, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';

    // ------------------------------------------------------------------------------------------------- [ AUTHORIZATION
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

    // ------------------------------------------------------------------------------------------------- [ DATA VALIDATION
    // validate data and stuff
    if (empty($word) || empty($language)) {
      $res->erorr = "Request is either missing word or language.";
      echo json_encode($res);
      return;
    }

    // ------------------------------------------------------------------------------------------------- [ ESTABLISH DB CONNECTION
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }
    $res = new stdClass();


    // ------------------------------------------------------------------------------------------------- [ BUILD QUERY AND BIND PARAMS
    $values = array();

    $values[] = ":language, :word, :type";
    if (empty($word->altSpellings))       { $values[] = "NULL"; } else { $values[] = ":altSpellings"; }
    if (empty($word->altSpellingsHidden)) { $values[] = "NULL"; } else { $values[] = ":altSpellingsHidden"; }
    if (empty($word->genderExtras))       { $values[] = "NULL"; } else { $values[] = ":genderExtras"; }
    if (empty($word->notes))              { $values[] = "NULL"; } else { $values[] = ":notes"; }
    if (empty($word->credit))             { $values[] = "NULL"; } else { $values[] = ":credit"; }

    // TODO: add credit based on authentication.
    // Also TODO: add words as communitySuggestion unless user has sufficient perms
    // Also TODO: only trusted users should have the permission to set word priority
    // Also TODO: priority should be moved to meanings2words table
    if (true)                             { $values[] = "NULL"; } else { $values[] = ":credit_userId"; }
    if (true)                             { $values[] = 0;      } else { $values[] = ":communitySuggestion"; }
    if (empty($word->priority))           { $values[] = 0;      } else { $values[] = ":priority"; }


    $sql_insert = "
      INSERT INTO words (language, word, type, altSpellings, altSpellingsHidden, genderExtras, notes, credit, credit_userId, communitySuggestion, priority)
        VALUES (" . join(", ", $values) . ");
    ";

    $stmt_insert = $conn->prepare($sql_insert);

    $stmt_insert->bindParam(":language", $word->language);
    $stmt_insert->bindParam(":word", $word->word);
    $stmt_insert->bindParam(":type", $word->type);

    if (!empty($word->altSpellings))        { $stmt_insert->bindParam(":altSpellings", $word->altSpellings); }
    if (!empty($word->altSpellingsHidden))  { $stmt_insert->bindParam(":altSpellingsHidden", $word->altSpellingsHidden); }
    if (!empty($word->genderExtras))        { $stmt_insert->bindParam(":genderExtras", $word->genderExtras); }
    if (!empty($word->notes))               { $stmt_insert->bindParam(":notes", $word->notes); }
    if (!empty($word->credit))              { $stmt_insert->bindParam(":credit", $word->credit); }
    // todo: bind user credit?
    // todo: bind communitySuggestion
    // todo: move priority to other table, priority should be set during bind in words2meanings table
    if (!empty($word->priority))            { $stmt_insert->bindParam(":priority", $word->priority); }


    // insert new value:
    try {
      $stmt_insert->execute();
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    // return the inserted value to backend:
    $last_id = $conn->lastInsertId();

    $sql_select_inserted = "
      SELECT
        id, language, word, type, genderExtras, altSpellings, altSpellingsHidden, notes, credit, credit_userId, communitySuggestion, priority

      FROM words

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
   * Edits an existing word in the database
   *
   * $word needs to have the following parameters:
   *    - credit_userId: should be added by the backend (but is currently not)
   *
   * MANDATORY FIELDS:
   *    - id: the word we're editing
   *    - language: 'si' | 'en'
   *    - word: string
   *      the word we're adding
   *    - type: number
   *      (see: enum WordType on frontend!)
   *
   * OPTIONAL FIELDS
   *    - alternativeSpellings: string
   *      alternative spellings of a word. Will be displayed on frontend.
   *
   *    - alternativeSpellingsHidden:
   *      common incorrect spellings of a word, if any.
   *      Used for search, but frontend shouldn't display that.
   *
   *    - genderExtras: json string (NOT json proper!)
   *      alternative gender-based spellings. More or less only present on slovenian words,
   *      where genderExtras _should_ be present where applicable, but that's a CoC issue
   *
   *    - notes: string
   *      any other notes regarding word
   *
   *    - credit: string
   *      user credit
   *
   *    - communitySuggestion: boolean
   *      should be restricted to certain users
   *
   *    - priority: number
   *      deprecated af
   **/
  function editWord($word, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';

    // ------------------------------------------------------------------------------------------------- [ AUTHORIZATION
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

    // ------------------------------------------------------------------------------------------------- [ DATA VALIDATION
    // validate data and stuff
    if (empty($word) || empty($language)) {
      $res->erorr = "Request is either missing word or language.";
      echo json_encode($res);
      return;
    }

    // ------------------------------------------------------------------------------------------------- [ ESTABLISH DB CONNECTION
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }
    $res = new stdClass();

    // ------------------------------------------------------------------------------------------------- [ BUILD QUERY AND BIND PARAMS
    $values = array();

    $values[] = ":language, :word, :type";
    if (empty($word->altSpellings))       { $values[] = "altSpellings = NULL"; }        else { $values[] = "altSpellings = :altSpellings"; }
    if (empty($word->altSpellingsHidden)) { $values[] = "altSpellingsHidden = NULL"; }  else { $values[] = "altSpellingsHidden = :altSpellingsHidden"; }
    if (empty($word->genderExtras))       { $values[] = "genderExtras = NULL"; }        else { $values[] = "genderExtras = :genderExtras"; }
    if (empty($word->notes))              { $values[] = "notes = NULL"; }               else { $values[] = "notes = :notes"; }
    if (empty($word->credit))             { $values[] = "credit = NULL"; }              else { $values[] = "credit = :credit"; }

    // TODO: add credit based on authentication.
    // Also TODO: add words as communitySuggestion unless user has sufficient perms
    // Also TODO: only trusted users should have the permission to set word priority
    // Also TODO: priority should be moved to meanings2words table
    if (true)                             { $values[] = "credit_userId = NULL"; }       else { $values[] = "credit_userId = :credit_userId"; }
    if (true)                             { $values[] = "communitySuggestion = 0"; }    else { $values[] = "communitySuggestion = :communitySuggestion"; }
    if (empty($word->priority))           { $values[] = "priority = 0"; }               else { $values[] = "priority = :priority"; }


    $sql_update = "
      UPDATE words
        SET " . join(", ", $values) . "
        WHERE id = :id;
    ";

    $stmt_update = $conn->prepare($sql_update);

    $stmt_update->bindParam(":language", $word->language);
    $stmt_update->bindParam(":word", $word->word);
    $stmt_update->bindParam(":type", $word->type);

    if (!empty($word->altSpellings))        { $stmt_update->bindParam(":altSpellings", $word->altSpellings); }
    if (!empty($word->altSpellingsHidden))  { $stmt_update->bindParam(":altSpellingsHidden", $word->altSpellingsHidden); }
    if (!empty($word->genderExtras))        { $stmt_update->bindParam(":genderExtras", $word->genderExtras); }
    if (!empty($word->notes))               { $stmt_update->bindParam(":notes", $word->notes); }
    if (!empty($word->credit))              { $stmt_update->bindParam(":credit", $word->credit); }
    // todo: bind user credit?
    // todo: bind communitySuggestion
    // todo: move priority to other table, priority should be set during bind in words2meanings table
    if (!empty($word->priority))            { $stmt_update->bindParam(":priority", $word->priority); }

    $stmt_update->bindParam(":id", $word->id);

    // update the word
    try {
      $stmt_update->execute();
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    // return the updated word to backend
    $sql_select_updated = "
      SELECT
        id, language, word, type, genderExtras, altSpellings, altSpellingsHidden, notes, credit, credit_userId, communitySuggestion, priority

      FROM words

      WHERE
        id = :id;
    ";

    $stmt_updated = $conn->prepare($sql_select_updated);
    $stmt_updated->bindParam(":id", $last_id);

    try {
      $stmt_updated->execute();
      $res = $stmt_updated->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    echo json_encode($res[0]);
  }

  function deleteWord($wordId, $languageCode, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';

    if (empty($authToken)) {
      $res->errorCode = "401";
      $res->error = "User is not logged in.";
      die(json_encode($res));
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

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }

    $res = new stdClass();

    if (empty($wordId)) {
      $res->error = "Word ID must be provided";
      echo json_encode($res);
      return;
    }

    if ($languageCode == 'en') {
      $sql_delete = "
        DELETE FROM wordlist_en
        WHERE id = :id;
      ";
    } else if ($languageCode == 'sl') {
      $sql_delete = "
        DELETE FROM wordlist_sl
        WHERE id = :id;
      ";
    } else {
      $res->error = "Invalid language code. languageCode must be provided and miust be one of the following: 'sl', 'en'.";
      echo json_encode($res);
      return;
    }

    $stmt_en2si = $conn->prepare($sql_delete);

    try {
      $stmt_en2si->bindParam(":id", $wordId);
      $stmt_en2si->execute();
    } catch (Exception $e) {
      $res->error = $e;
      echo json_encode($res);
      return;
    }

    $res->message = "ok";
    $res->langKey = $languageCode;
    $res->wordId = $wordId;
    echo json_encode($res);
  }


  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = $_GET['s'];
    $language = $_GET['lang'];
    $id = $_GET['id'];

    if (empty($id)) {
      listWords(null);
    } else {
      getWordById($id, $language);
    }

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
        addWord($decoded_params, $decoded_params->lang, $headers['Authorization']);
      } else {
        addWord($decoded_params, $decoded_params->langKey, $headers['Authorization']);
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

    if (isset($headers['Authorization'])) {
      $response->message="authorization header present!";
      $response->postJson=$decoded_params;

      deleteWord($decoded_params->id, $decoded_params->lang, $headers['Authorization']);
    } else {
      $response->errorCode = 403;
      $response->error = "Authorization header not present";

      echo json_encode($response);
      return;
    }
  }
?>
