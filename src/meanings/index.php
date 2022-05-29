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
  
  function getWordById($id, $language) {
    include '../conf/db-config.php';
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }

    
    
    
    $sql_select_word = "
      SELECT
        src.id AS src_id,
        src.word AS src_word,
        src.word_m AS src_word_m,
        src.word_f AS src_word_f,
        src.word_plural AS src_word_plural,
        src.description AS src_description,
        src.notes AS src_notes,
        src.rfc AS src_rfc
      
      FROM " . $wordlistTable . " src
      
      WHERE
        src.id = :id;
    ";
    
    $sql_select_translations = "
      SELECT
        tr.id AS tr_id,
        tr.translation_priority AS tr_priority,
        tr.rfc AS tr_rfc,
        tr.notes AS tr_notes,
        
        target.id AS target_id,
        target.word AS target_word,
        target.word_f AS target_word_f,
        target.word_m AS target_word_m,
        target.word_plural AS target_word_plural,
        target.rfc AS target_rfc,
        target.description AS target_description,
        target.notes AS target_notes
    
      FROM " . $wordlistTable . " src
        LEFT JOIN translation tr ON src.id = tr." . $srcLang . "_id
        LEFT JOIN " . $endTable . " target ON target.id = tr." . $endLang . "_id
      
      WHERE
        src.id = :id
      
      ORDER BY tr_priority ASC
      LIMIT 0, 16;
    ";
    
    
    $stmt_word = $conn->prepare($sql_select_word);
    $stmt_translations = $conn->prepare($sql_select_translations);
    
    try {
      $stmt_word->bindParam(":id", $id);
      $stmt_translations->bindParam(":id", $id);
    } catch (Exception $e) {
      echo json_encode($e);
      return;
    }
    
    
    $res = new stdClass();
    
    try {
      $stmt_word->execute();
      $stmt_translations->execute();
      $res->word = $stmt_word->fetchAll(PDO::FETCH_ASSOC)[0];
      $res->translations = $stmt_translations->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      $res->error = $e;
    }
    
    echo json_encode($res);
  }
  
  function listWords($filter) {
    include '../conf/db-config.php';
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }
    
    $sql_select_word = "
      SELECT
        word.id as id,
        word.language as language,
        word.word as word,
        word.altSpellings as altSpellings,
        word.type,
        word.genderExtras,
        word.notes,
        word.credit,
        word.communitySuggestion,
        
        meaning.id as meaningId,
        meaning.meaning as meaning,
        meaning.notes as meaningNotes,
        meaning.priority as meaningPriority,
        meaning.communitySuggestion as meaningCommunitySuggestion,
        
        category.id as categoryId,
        category.nameEn as categoryNameEn,
        category.nameSl as categoryNameSl
        
        FROM 
          words word
          LEFT JOIN words2meanings w2m_src ON w2m_src.word_id = word.id
          LEFT JOIN meanings meaning ON meaning.id = w2m_src.meaning_id
          LEFT JOIN meanings2categories m2c ON m2c.meaning_id = meaning.id
          LEFT JOIN categories category ON category.id = m2c.category_id
          
        GROUP BY
          word.id, meaning.id
        
      ";
      
      $sql_count = "
        SELECT COUNT(*) as count
        
        FROM words
      ";
        
//         srcMeaning.id as meaningId,
//         srcMeaning.meaning as meaning,
//         srcMeaning.notes as meaningNotes,
//         srcMeaning.priority as meaningPriority,
//         srcMeaning.communitySuggestion as meaningCommunitySuggestion,
//         
//         translatedMeaning.id as translatedMeaningId,
//         translatedMeaning.meaning as translatedMeaning,
//         translatedMeaning.notes as translatedMeaningNotes,
//         translatedMeaning.priority as translatedMeaningPriority,
//         translatedMeaning.communitySuggestion as meaningCommunitySuggestion,
//         
//         translatedWord.id as translatedWordId,
//         translatedWord.language as translatedLanguage,
//         translatedWord.word as translatedWord,
//         translatedWord.notes as translatedWordNotes,
//         translatedWord.genderExtras as translatedWordGenderExtras,

    $stmt = $conn->prepare($sql_select_word);
    $stmt_count = $conn->prepare($sql_count);
    
    try {
      $stmt->execute();
      $stmt_count->execute();
      
      $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $total = $stmt_count->fetchAll(PDO::FETCH_ASSOC);
      
      $res->words = $words;
      $res->total = $total;
    } catch (Exception $e) {
      $res->error = $e;
      $res->stmt = $stmt;
    }
    
    header('Content-Type: application/json');
    echo json_encode($res);
  }
  
  function getWord($search, $language, $withTranslations = false) {
    include '../conf/db-config.php';
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }
    
    $wordlistTable = "wordlist_en";
    $endTable = "wordlist_sl";
    $srcLang = "en";
    $endLang = "si";
    
    if ($language == "sl") {
      $wordlistTable = "wordlist_sl";
      $endTable = "wordlist_en";
      $srcLang = "sl";
      $endLang = "en";
    }
    
    // get query params
    
    if (empty($search)) {
      return null;
    }
    
    $sql_select_common = "
      SELECT
        id, word, word_m, word_f, word_plural, description, notes, rfc
                
      FROM " . $wordlistTable . "
      
      WHERE
        word LIKE CONCAT(:search, '%')
        
      LIMIT 0, 16;
    ";
    
    $sql_select_translations = "
      SELECT
        src.id AS src_id,
        
        tr.id AS tr_id,
        tr.translation_priority AS tr_priority,
        tr.rfc AS tr_rfc,
        tr.notes AS tr_notes,
        
        target.id AS target_id,
        target.word AS target_word,
        target.word_f AS target_word_f,
        target.word_m AS target_word_m,
        target.word_plural AS target_word_plural,
        target.rfc AS target_rfc,
        target.description AS target_description,
        target.notes AS target_notes
      
      FROM " . $wordlistTable . " src
        LEFT JOIN translation tr ON src.id = tr." . $srcLang . "_id
        LEFT JOIN " . $endTable . " target ON target.id = tr." . $endLang . "_id
        
      WHERE
        word LIKE CONCAT(:search, '%')
      GROUP BY
        src.id
    ";
    
    $stmt_en2si = $conn->prepare($sql_select_common);
    
    
    try {
      $stmt_en2si->bindParam(":search", $search);
    } catch (Exception $e) {
      echo json_encode($e);
      return;
    }

    
    $res = new stdClass();
    
    if ($withTranslations == true) {
      $stmt_translations = $conn->prepare($sql_select_translations);
      
      try {
        $stmt_translations->bindParam(":search", $search);
        $stmt_translations->execute();
        $stmt_en2si->execute();
        
        
        $res->words = $stmt_en2si->fetchAll(PDO::FETCH_ASSOC);
        $res->translations = $stmt_translations->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) {
        $res->error = $e;
      }
    } else {
      try {
        $stmt_en2si->execute();
        $res = $stmt_en2si->fetchAll(PDO::FETCH_ASSOC);
      } catch (Exception $e) {
        $res->error = $e;
      }
    }
      
    echo json_encode($res);
  }
  
  function addWord($word, $language, $authToken) {
    include '../conf/db-config.php';
    include '../lib/auth.php';
    
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
      return;
    }
    $res = new stdClass();
    
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
    
    // validate data and stuff
    if (empty($word) || empty($language)) {
      $res->erorr = "Request is either missing word or language.";
      echo json_encode($res);
      return;
    }
    
    if ($language == "sl") {
      $wordlistTable = "wordlist_sl";
    } else if ($language == "en") {
      $wordlistTable = "wordlist_en";
    } else {
      $res->error = "Invalid language provided. Valid values: 'en', 'sl'.";
      echo json_encode($res);
      return;
    }
    
    $sql_select_insert = "
      INSERT INTO " . $wordlistTable . " (word, word_m, word_f, word_plural, description, notes, rfc)
        VALUES (:word, :word_m, :word_f, :word_plural, :description, :notes, :rfc);
    ";
    
    $sql_select_update = "
      UPDATE " . $wordlistTable . "
      SET
        word        = :word, 
        word_m      = :word_m,
        word_f      = :word_f,
        word_plural = :word_plural,
        description = :description,
        notes       = :notes,
        rfc         = :rfc
        
      WHERE
        id = :id;
    ";
    
    if (empty($word->id)) {
      $stmt_en2si = $conn->prepare($sql_select_insert);
    } else {
      $stmt_en2si = $conn->prepare($sql_select_update);
    }
    
    try {
      $stmt_en2si->bindParam(":word", $word->word);
      $stmt_en2si->bindParam(":word_m", $word->word_m);
      $stmt_en2si->bindParam(":word_f", $word->word_f);
      $stmt_en2si->bindParam(":word_plural", $word->word_plural);
      $stmt_en2si->bindParam(":description", $word->description);
      $stmt_en2si->bindParam(":notes", $word->notes);
      $stmt_en2si->bindParam(":rfc", $word->rfc);
      
      if (!empty($word->id)) {
        $stmt_en2si->bindParam(":id", $word->id);
      }
    } catch (Exception $e) {
      echo json_encode($e);
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
    if (empty($word->id)) {
      $last_id = $conn->lastInsertId();
    } else {
      $last_id = $word->id;
    }
    
    $sql_select_inserted = "
      SELECT
        id, word, word_m, word_f, word_plural, description, notes, rfc
      
      FROM " . $wordlistTable . "
      
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
  
  function editWord($word, $authToken) {
    return addWord($word, $word->languageKey, $authToken);
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
