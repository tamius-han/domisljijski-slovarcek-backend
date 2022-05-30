<?php
// ENDPOINT: /api/categories
// (this is the one for listing, creating and updating categories)
//
// just to set the record straight:
//  * node <3
//  * my webhost only does php so at this point i dont even
//    give a fuck about the quality of this code. As long as
//    it runs.
//  * i'd do a proper api /w express and node but again, my
//    webhost only does php)
//
// We expect payload to look like this:
//
// For POST:
// {
//   id?: number        (only provided if updating existing category)
//   enName: string     (english name of category)
//   slName: string     (slovenian name of category)
//   communitySuggestion: boolean       (indicates whether category is in "we need to figure this out" stage)
//   parentId?: number  (parent category, optional)
// }

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

function createCategory($categoryData, $authToken) {
  include '../conf/db-config.php';
  include '../lib/auth.php';
  checkUser($authToken);

  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  if (empty($conn)) {
    die('oopsie whoopsie! conn unitialized for some reason! ' . $conn);
  }

  if ($conn->connect_error) {
    die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    return;
  }

  // category needs to have english name and slovenian name, while parentId is optional
  // TODO: checkUser should return whether user is authorized to add pappa-blessed categories
  // only in this case communitySuggestion should be set to 0
  $sql_select_insert = "
    INSERT INTO categories (nameEn, nameSl, communitySuggestion, parentId)
    VALUES (:nameEn, nameSl, 0, parentId);
  ";

  $sql_select_update = "
    UPDATE categories
    SET
      nameEn = COALESCE(:nameEn, nameEn),
      nameSl = COALESCE(:nameSl, nameSl),
      communitySuggestion = COALESCE(:communitySuggestion, communitySuggestion)
      parentId = COALESCE(:parentId, parentId)

    WHERE
      id = :id;
  ";

  if (empty($categoryData->id)) {
    $stmt_en2si = $conn->prepare($sql_select_insert);
  } else {
    $stmt_en2si = $conn->prepare($sql_select_update);
  }

  $stmt_en2si->bindParam(":nameEn", $categoryData->enName);
  $stmt_en2si->bindParam(":nameSl", $categoryData->slName);

  // TODO: checkUser should return whether user is authorized to add pappa-blessed categories
  // only in this case communitySuggestion should be set to 0
  $stmt_en2si->bindParam(":communitySuggestion", 0);
  $stmt_en2si->bindParam(":parentId", $categoryData->parentId);

  if (!empty($categoryData->id)) {
    $stmt_en2si->bindParam(":id", $categoryData->id);
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
  if (empty($categoryData->id)) {
    $last_id = $conn->lastInsertId();
  } else {
    $last_id = $categoryData->id;
  }

  $sql_select_inserted = "
    SELECT
      id, nameEn, nameSl, communitySuggestion, parentId
    FROM
      categories
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

function removeCategory($categoryId, $authToken) {
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
    $res->error = "Category ID must be provided";
    echo json_encode($res);
    return;
  }

  $sql_delete = "
    DELETE FROM categories
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

function listCategories() {
  include '../conf/db-config.php';
  include '../lib/auth.php';
  // checkUser($authToken); // all users can get categories, wtf tam

  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

  if ($conn->connect_error) {
    die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    return;
  }

  $sql_select = "
    SELECT
      id, nameEn, nameSl, communitySuggestion, parentId
    FROM
      categories
  ";

  $stmt_select = $conn->prepare($sql_select);

  try {
    $stmt_select->execute();
    $res = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    $res->error = $e;
    echo json_encode($res);
    return;
  }

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

    createCategory($decoded_params, $headers['Authorization']);
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

    removeCategory($decoded_params->id, $headers['Authorization']);
  } else {
    $response->errorCode = 403;
    $response->error = "Authorization header not present";

    echo json_encode($response);
    return;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  return listCategories();
}

?>

