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
    // 部分一致検索（LIKE）とフルテキスト検索を組み合わせ
    $sql = "
        SELECT 
            food_id,
            food_name,
            food_name_en,
            category,
            energy_kcal,
            protein_g,
            fat_g,
            carbohydrate_g
        FROM food_nutrition 
        WHERE 
            food_name LIKE :query OR 
            food_name_en LIKE :query_en OR
            (MATCH(food_name, food_name_en) AGAINST(:fulltext_query IN BOOLEAN MODE))
        ORDER BY 
            CASE 
                WHEN food_name = :exact_match THEN 1
                WHEN food_name LIKE :start_query THEN 2
                WHEN food_name LIKE :query THEN 3
                ELSE 4
            END,
            food_name
        LIMIT :limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':query_en', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':exact_match', $query, PDO::PARAM_STR);
    $stmt->bindValue(':start_query', $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':fulltext_query', $query . '*', PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    jsonResponse($results);
    
} catch (PDOException $e) {
    errorResponse('検索エラー: ' . $e->getMessage(), 500);
}
?>