<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('GET method required', 405);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

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
            飽和脂肪酸 as saturated_fat_g,
            炭水化物 as carbohydrate_g,
            食物繊維総量 as fiber_g,
            レチノール活性当量 as vitamin_a_ug,
            ビタミンD as vitamin_d_ug,
            α_トコフェロール as vitamin_e_mg,
            ビタミンK as vitamin_k_ug,
            ビタミンB1 as vitamin_b1_mg,
            ビタミンB2 as vitamin_b2_mg,
            ビタミンB6 as vitamin_b6_mg,
            ビタミンB12 as vitamin_b12_ug,
            ナイアシン as niacin_mg,
            葉酸 as folate_ug,
            パントテン酸 as pantothenic_acid_mg,
            ビオチン as biotin_ug,
            ビタミンC as vitamin_c_mg,
            ナトリウム as sodium_mg,
            カリウム as potassium_mg,
            カルシウム as calcium_mg,
            マグネシウム as magnesium_mg,
            リン as phosphorus_mg,
            鉄 as iron_mg,
            亜鉛 as zinc_mg,
            銅 as copper_mg,
            マンガン as manganese_mg,
            ヨウ素 as iodine_ug,
            セレン as selenium_ug,
            クロム as chromium_ug,
            モリブデン as molybdenum_ug,
            `n-6系多価不飽和脂肪酸` as n6_fat_g,
            `n-3系多価不飽和脂肪酸` as n3_fat_g
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