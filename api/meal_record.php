<?php
require_once 'config.php';

// 食事記録の追加
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        errorResponse('無効なJSONデータです');
    }
    
    $required_fields = ['date', 'meal_type', 'foods'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            errorResponse("必須フィールド '{$field}' が不足しています");
        }
    }
    
    $date = $input['date'];
    $meal_type = $input['meal_type'];
    $foods = $input['foods'];
    
    // 日付フォーマットチェック
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        errorResponse('日付フォーマットが無効です (YYYY-MM-DD)');
    }
    
    // 食事タイプチェック
    $valid_meal_types = ['breakfast', 'lunch', 'dinner', 'snack'];
    if (!in_array($meal_type, $valid_meal_types)) {
        errorResponse('無効な食事タイプです');
    }
    
    if (!is_array($foods) || empty($foods)) {
        errorResponse('食品データが必要です');
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO meal_records (user_id, meal_date, meal_type, food_id, quantity_g) 
            VALUES (1, :date, :meal_type, :food_id, :quantity)
        ");
        
        foreach ($foods as $food) {
            if (!isset($food['food_id']) || !isset($food['quantity'])) {
                throw new Exception('食品IDと分量が必要です');
            }
            
            $food_id = intval($food['food_id']);
            $quantity = floatval($food['quantity']);
            
            if ($food_id <= 0 || $quantity <= 0) {
                throw new Exception('食品IDと分量は正の値である必要があります');
            }
            
            $stmt->execute([
                ':date' => $date,
                ':meal_type' => $meal_type,
                ':food_id' => $food_id,
                ':quantity' => $quantity
            ]);
        }
        
        $pdo->commit();
        jsonResponse(['success' => true, 'message' => '食事記録が追加されました']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        errorResponse('記録エラー: ' . $e->getMessage(), 500);
    }
}

// 食事記録の取得
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
    
    try {
        if ($date) {
            // 特定日の記録取得
            $sql = "
                SELECT 
                    mr.record_id,
                    mr.meal_date,
                    mr.meal_type,
                    mr.quantity_g,
                    f.食品名 as food_name,
                    f.カロリー as energy_kcal,
                    f.たんぱく質 as protein_g,
                    f.脂質 as fat_g,
                    f.炭水化物 as carbohydrate_g,
                    (f.カロリー * mr.quantity_g / 100) as consumed_energy_kcal,
                    (f.たんぱく質 * mr.quantity_g / 100) as consumed_protein_g,
                    (f.脂質 * mr.quantity_g / 100) as consumed_fat_g,
                    (f.炭水化物 * mr.quantity_g / 100) as consumed_carbohydrate_g
                FROM meal_records mr
                JOIN foods f ON mr.food_id = f.id
                WHERE mr.user_id = 1 AND mr.meal_date = :date
                ORDER BY mr.meal_type, mr.record_id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':date' => $date]);
            
        } else if ($start_date && $end_date) {
            // 期間の記録取得
            $sql = "
                SELECT 
                    meal_date,
                    SUM(f.カロリー * mr.quantity_g / 100) as total_energy_kcal,
                    SUM(f.たんぱく質 * mr.quantity_g / 100) as total_protein_g,
                    SUM(f.脂質 * mr.quantity_g / 100) as total_fat_g,
                    SUM(f.炭水化物 * mr.quantity_g / 100) as total_carbohydrate_g
                FROM meal_records mr
                JOIN foods f ON mr.food_id = f.id
                WHERE mr.user_id = 1 
                    AND mr.meal_date BETWEEN :start_date AND :end_date
                GROUP BY meal_date
                ORDER BY meal_date
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ]);
            
        } else {
            errorResponse('日付または期間の指定が必要です');
        }
        
        $results = $stmt->fetchAll();
        jsonResponse($results);
        
    } catch (PDOException $e) {
        errorResponse('取得エラー: ' . $e->getMessage(), 500);
    }
}

errorResponse('無効なリクエストメソッドです', 405);
?>