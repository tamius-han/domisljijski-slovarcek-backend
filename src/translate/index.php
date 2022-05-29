<?php
  // ENDPOINT: /translate
  // (the one for listing translations)
  //
  // Valid query params:
  //    s     - search
  //    cat   - category ID
  //    lang  - language of the source word (en | sl)
  //    w     - word ID (if word ID is present, s, cat, and lang are ignored)
  //    m     - meaning ID (has no effect unless w is provided)
  //    tm    - translated meaning ID (has no effect unless w is provided)
  //    tw    - translated word ID (has no effect unless w is provided)
  //
  //    page  - number of pages to skip
  //    limit - number of hits per page (max. 100)
  //
  // just to set the record straight:
  //  * node <3
  //  * my webhost only does php so at this point i dont even
  //    give a fuck about the quality of this code. As long as
  //    it runs.
  //  * i'd do a proper api /w express and node but again, my
  //    webhost only does php
  
  include '../conf/db-config.php';
  
  try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($conn->connect_error) {
      die("oopsie whoopsie! php just had a fucky wucky! " . $conn->connect_error);
    }
    
    // get query params
    $search = $_GET['s'];
    $category = $_GET['cat'];
    $language = $_GET['lang'];
    $word = $_GET['w'];
    $meaning = $_GET['m'];
    $translatedWord = $_GET['tw'];
    $translatedMeaning = $_GET['tm'];
    
    $page = $_GET['page'];
    $limit = $_GET['limit'];
    
    
    // pagination parameters
    if (empty($page)) {
      $page = 0;
    }
    if (empty($limit)) {
      $limit = 16;
    }
    if ($limit < 16) {
      $limit = 16;
    }
    if ($limit > 100) {
      $limit = 100;
    }
    
    $skip = $limit * $page;
    
    $sql_select_word = "
      SELECT
        sourceWord.id as id,
        sourceWord.language as language,
        sourceWord.word as word,
        sourceWord.altSpellings as altSpellings,
        sourceWord.type,
        sourceWord.genderExtras,
        sourceWord.notes,
        sourceWord.credit,
        sourceWord.communitySuggestion,
        
        meaning.id as meaningId,
        meaning.meaning as meaning,
        meaning.notes as meaningNotes,
        meaning.priority as meaningPriority,
        meaning.communitySuggestion as meaningCommunitySuggestion,
        
        translatedMeaning.id as translatedMeaningId,
        translatedMeaning.meaning as translatedMeaning,
        translatedMeaning.notes as translatedMeaningNotes,
        translatedMeaning.priority as translatedMeaningPriority,
        translatedMeaning.communitySuggestion as translatedMeaningCommunitySuggestion,
        
        translatedWord.id as translatedWordId,
        translatedWord.word as translatedWord,
        translatedWord.genderExtras as translatedWordGenderExtras,
        translatedWord.priority as translatedWordPriority,
        translatedWord.credit as translatedWordCredit,
        translatedWord.notes as translatedWordNotes,
        
        category.id as categoryId,
        category.parentId as categoryParentId,
        category.nameEn as categoryNameEn,
        category.nameSl as categoryNameSl
      ";
      
      $sql_select_count = "SELECT COUNT(*) as count";
        
      // it is not possible to insert parameters that can be NULL _or_ something else
      // (unknown at the time of writing) into a query with bindParam, so we need to 
      // build our where statement manually
      
      $sql_where_array = array();
      
      // if ID is provided, where statement has a mildly special case:
      if (!empty($word)) {
        $sql_where_array[] = "sourceWord.id = :wordId";
        if (!empty($meaning))           { $sql_where_array[] = "meaning.id = :meaningId";  }
        if (!empty($translatedMeaning)) { $sql_where_array[] = "translatedMeaning.id = :translatedMeaningId"; }
        if (!empty($translatedWord))    { $sql_where_array[] = "translatedWord.id = :translatedWordId"; }
      } else {
        if (!empty($search))            { $sql_where_array[] = "(sourceWord.word LIKE CONCAT('%', :search, '%') OR sourceWord.altSpellings LIKE CONCAT('%', :search1, '%') OR sourceWord.altSpellingsHidden LIKE CONCAT('%', :search2, '%'))"; }
        if (!empty($category))          { $sql_where_array[] = "category.id = :categoryId"; }
        if (!empty($language))          { $sql_where_array[] = "sourceWord.language = :language"; }
      }
      
      $sql_common_join = "
        FROM 
          words AS sourceWord
          LEFT JOIN words2meanings AS w2m_src      ON w2m_src.word_id = sourceWord.id
          LEFT JOIN meanings AS meaning            ON meaning.id = w2m_src.meaning_id
          LEFT JOIN translations AS t              ON (sourceWord.language = 'en' AND meaning.id = t.meaning_en) OR (sourceWord.language = 'sl' AND meaning.id = t.meaning_sl)
          LEFT JOIN meanings AS translatedMeaning  ON (sourceWord.language = 'en' AND translatedMeaning.id = t.meaning_sl) OR (sourceWord.language = 'sl' AND translatedMeaning.id = t.meaning_en)
          LEFT JOIN words2meanings AS w2m_dst      ON w2m_dst.meaning_id = translatedMeaning.id
          LEFT JOIN words AS translatedWord        ON w2m_dst.word_id = translatedWord.id
          LEFT JOIN meanings2categories AS m2c     ON m2c.meaning_id = meaning.id
          LEFT JOIN categories AS category         ON category.id = m2c.category_id
      ";
      
      // if we query by any combination of params involving word ID, our query
      // can be much simpler
      if (!empty($word)) {
        $sql_common_join = $sql_common_join . "  WHERE " . join(" AND ", $sql_where_array);
      } else {
        $sql_common_join = $sql_common_join . "
          INNER JOIN (
            SELECT sourceWord.id
            FROM words                      AS sourceWord
              LEFT JOIN words2meanings      AS w2m_src            ON w2m_src.word_id = sourceWord.id
              LEFT JOIN meanings            AS meaning            ON meaning.id = w2m_src.meaning_id
              LEFT JOIN translations        AS t                  ON (sourceWord.language = 'en' AND meaning.id = t.meaning_en) OR (sourceWord.language = 'sl' AND meaning.id = t.meaning_sl)
              LEFT JOIN meanings            AS translatedMeaning  ON (sourceWord.language = 'en' AND translatedMeaning.id = t.meaning_sl) OR (sourceWord.language = 'sl' AND translatedMeaning.id = t.meaning_en)
              LEFT JOIN words2meanings      AS w2m_dst            ON w2m_dst.meaning_id = translatedMeaning.id
              LEFT JOIN words               AS translatedWord     ON w2m_dst.word_id = translatedWord.id
              LEFT JOIN meanings2categories AS m2c                ON m2c.meaning_id = meaning.id
              LEFT JOIN categories          AS category           ON category.id = m2c.category_id
            WHERE 
              " . join(" AND ", $sql_where_array) . "
            GROUP BY 
              sourceWord.id
            LIMIT " . $skip . ", " . $limit . "
          )                               AS swltd              ON swltd.id = sourceWord.id
          
          ";
      }
       
      // we only order in select queries, but not in count queries
      $sql_order_word = "          
        ORDER BY
          meaningPriority ASC, translatedMeaningPriority ASC, translatedWordPriority ASC
      ";

    // i hope my employer isn't checking my github lol
    
    try {
      $stmt_select = $conn->prepare($sql_select_word . $sql_common_join . $sql_order_word );
      $stmt_count = $conn->prepare("
        SELECT COUNT(*) AS total
          FROM words 
          INNER JOIN (
            SELECT sourceWord.id
            FROM words                      AS sourceWord
              LEFT JOIN words2meanings      AS w2m_src            ON w2m_src.word_id = sourceWord.id
              LEFT JOIN meanings            AS meaning            ON meaning.id = w2m_src.meaning_id
              LEFT JOIN translations        AS t                  ON (sourceWord.language = 'en' AND meaning.id = t.meaning_en) OR (sourceWord.language = 'sl' AND meaning.id = t.meaning_sl)
              LEFT JOIN meanings            AS translatedMeaning  ON (sourceWord.language = 'en' AND translatedMeaning.id = t.meaning_sl) OR (sourceWord.language = 'sl' AND translatedMeaning.id = t.meaning_en)
              LEFT JOIN words2meanings      AS w2m_dst            ON w2m_dst.meaning_id = translatedMeaning.id
              LEFT JOIN words               AS translatedWord     ON w2m_dst.word_id = translatedWord.id
              LEFT JOIN meanings2categories AS m2c                ON m2c.meaning_id = meaning.id
              LEFT JOIN categories          AS category           ON category.id = m2c.category_id
            WHERE 
              " . join(" AND ", $sql_where_array) . "
            GROUP BY 
              sourceWord.id
          ) AS swltd              ON swltd.id = words.id
      ");
    } catch (Exception $e) {
      $res = new stdClass();
      
      $res->err = $e;
      $res->selectQuery = $stmt_select;
      $res->countQuery = $stmt_count;
      
      header('Content-Type: application/json');
      echo json_encode($res);
      return;
    }
    
    try {
      if (!empty($search)) {
        $stmt_select->bindParam(":search", $search);
        $stmt_select->bindParam(":search1", $search);
        $stmt_select->bindParam(":search2", $search);
        
        $stmt_count->bindParam(":search", $search);
        $stmt_count->bindParam(":search1", $search);
        $stmt_count->bindParam(":search2", $search);
      }
      if (!empty($category)) {
        $stmt_select->bindParam(":categoryId", $category);
        $stmt_count->bindParam(":categoryId", $category);
      }
      if (!empty($language)) {
        $stmt_select->bindParam(":language", $language);
        $stmt_count->bindParam(":language", $language);
      }
      
      // we won't execute count query if word ID is given, so we don't need to 
      // bind params for count query in this case
      if (!empty($word)) {
        $stmt_select->bindParam(":wordId", $word);
        
        if (!empty($meaning)) {
          $stmt_select->bindParam(":meaningId", $meaning);
        }
        if (!empty($translatedMeaning)) {
          $stmt_select->bindParam(":translatedMeaningId", $translatedMeaning);
        }
        if (!empty($translatedWord)) {
          $stmt_select->bindParam(":translatedWordId", $translatedWord);
        }
      }
      
          
    } catch (Exception $e) {
      $res = new stdClass();
      
      $res->err = $e;
      $res->selectQuery = $stmt_select;
      $res->countQuery = $stmt_count;
      
      header('Content-Type: application/json');
      echo json_encode($res);
      return;
    }
    
    $res = new stdClass();
    
    try {
      if (empty($word)) {
        $stmt_select->execute();
        $stmt_count->execute();
        
        $words = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
        $total = $stmt_count->fetchAll(PDO::FETCH_ASSOC);
        
        $res->words = $words;
        $res->total = $total;
      } else {
        $stmt_select->execute();
        
        $words = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
        
        $res->words = $words;
      }
      
    } catch (Exception $e) {
      $res->error = $e;
      $res->stmt_select = $stmt_words;
      $res->stmt_count = $stmt_count;
      
      header('Content-Type: application/json');
      echo json_encode($res);
      return;
    }
      
    header('Content-Type: application/json');
    echo json_encode($res);
  } catch (Exception $e) {
    echo json_encode($e);
  }
?>
