<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('GET method required', 405);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

if (empty($query)) {
    errorResponse('検索クエリが必要です');
}

if (strlen($query) < 2) {
    jsonResponse([]);
}

try {
    // 部分一致検索（LIKE）を使用
    $sql = "
        SELECT 
            id as food_id,
            食品名 as food_name,
            食品名英語 as food_name_en,
            グループ as category,
            カロリー as energy_kcal,
            たんぱく質 as protein_g,
            脂質 as fat_g,
            炭水化物 as carbohydrate_g
        FROM foods 
        WHERE 
            食品名 LIKE :query1 OR 
            食品名英語 LIKE :query_en
        ORDER BY 
            CASE 
                WHEN 食品名 = :exact_match THEN 1
                WHEN 食品名 LIKE :start_query THEN 2
                WHEN 食品名 LIKE :query2 THEN 3
                ELSE 4
            END,
            食品名
        LIMIT :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query1', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':query2', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':query_en', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':exact_match', $query, PDO::PARAM_STR);
    $stmt->bindValue(':start_query', $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    jsonResponse($results);
    
} catch (PDOException $e) {
    errorResponse('検索エラー: ' . $e->getMessage(), 500);
}
?>