<?php
require_once 'config.php';
require_once 'utils.php';

$userId = getAuthenticatedUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('GET method required', 405);
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    switch ($type) {
        case 'daily':
            // 今日の栄養摂取量と目標値
            $sql = "
                SELECT 
                    nt.energy_kcal_target,
                    nt.protein_g_target,
                    nt.fat_g_target,
                    nt.carbohydrate_g_target,
                    nt.fiber_g_target,
                    nt.vitamin_c_mg_target,
                    nt.calcium_mg_target,
                    nt.iron_mg_target,
                    nt.sodium_mg_target
                FROM nutrition_targets nt
                WHERE nt.user_id = :user_id
                LIMIT 1
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch();
            
            if (!$result) {
                errorResponse('データが見つかりません');
            }
            
            // 今日の摂取栄養素を取得する別のクエリ
            
            // 34種類の栄養素に対応した達成率計算
            $sql_consumed = "
                SELECT 
                    ".generateNutrientSql('エネルギー').",
                    ".generateNutrientSql('タンパク質').",
                    ".generateNutrientSql('脂質').",
                    ".generateNutrientSql('飽和脂肪酸').",
                    ".generateNutrientSql('n-6系脂肪酸').",
                    ".generateNutrientSql('n-3系脂肪酸').",
                    ".generateNutrientSql('炭水化物').",
                    ".generateNutrientSql('食物繊維').",
                    ".generateNutrientSql('ビタミンA').",
                    ".generateNutrientSql('ビタミンD').",
                    ".generateNutrientSql('ビタミンE').",
                    ".generateNutrientSql('ビタミンK').",
                    ".generateNutrientSql('ビタミンB1').",
                    ".generateNutrientSql('ビタミンB2').",
                    ".generateNutrientSql('ビタミンB6').",
                    ".generateNutrientSql('ビタミンB12').",
                    ".generateNutrientSql('ナイアシン').",
                    ".generateNutrientSql('葉酸').",
                    ".generateNutrientSql('パントテン酸').",
                    ".generateNutrientSql('ビオチン').",
                    ".generateNutrientSql('ビタミンC').",
                    ".generateNutrientSql('ナトリウム（食塩相当量）').",
                    ".generateNutrientSql('カリウム').",
                    ".generateNutrientSql('カルシウム').",
                    ".generateNutrientSql('マグネシウム').",
                    ".generateNutrientSql('リン').",
                    ".generateNutrientSql('鉄').",
                    ".generateNutrientSql('亜鉛').",
                    ".generateNutrientSql('銅').",
                    ".generateNutrientSql('マンガン').",
                    ".generateNutrientSql('ヨウ素').",
                    ".generateNutrientSql('セレン').",
                    ".generateNutrientSql('クロム').",
                    ".generateNutrientSql('モリブデン')."
                FROM meal_records mr
                JOIN foods f ON mr.food_id = f.id
                WHERE mr.user_id = :user_id AND mr.meal_date = :date
            ";
            
            $stmt_consumed = $pdo->prepare($sql_consumed);
            $stmt_consumed->execute([':date' => $date, ':user_id' => $userId]);
            $consumed_data = $stmt_consumed->fetch();
            
            // 栄養目標値を取得
            $target_sql = "SELECT * FROM nutrition_targets WHERE user_id = :user_id LIMIT 1";
            $target_stmt = $pdo->prepare($target_sql);
            $target_stmt->execute([':user_id' => $userId]);
            $targets = $target_stmt->fetch();
            
            // 達成率計算
            $achievements = [];
            $nutrients = getNutritionLabels();
            $nutrient_units = [
                'エネルギー' => 'kcal',
                'タンパク質' => 'g',
                '脂質' => 'g',
                '飽和脂肪酸' => 'g',
                'n-6系脂肪酸' => 'g',
                'n-3系脂肪酸' => 'g',
                '炭水化物' => 'g',
                '食物繊維' => 'g',
                'ビタミンA' => 'μg',
                'ビタミンD' => 'μg',
                'ビタミンE' => 'mg',
                'ビタミンK' => 'μg',
                'ビタミンB1' => 'mg',
                'ビタミンB2' => 'mg',
                'ビタミンB6' => 'mg',
                'ビタミンB12' => 'μg',
                'ナイアシン' => 'mg',
                '葉酸' => 'μg',
                'パントテン酸' => 'mg',
                'ビオチン' => 'μg',
                'ビタミンC' => 'mg',
                'ナトリウム（食塩相当量）' => 'g',
                'カリウム' => 'mg',
                'カルシウム' => 'mg',
                'マグネシウム' => 'mg',
                'リン' => 'mg',
                '鉄' => 'mg',
                '亜鉛' => 'mg',
                '銅' => 'mg',
                'マンガン' => 'mg',
                'ヨウ素' => 'μg',
                'セレン' => 'μg',
                'クロム' => 'μg',
                'モリブデン' => 'μg'
            ];
            
            foreach ($nutrients as $nutrient) {
                $consumed = floatval($consumed_data[$nutrient] ?? 0);
                $target = 0;
                
                // 基本的な栄養素の目標値を設定
                switch ($nutrient) {
                    case 'エネルギー':
                        $target = floatval($targets['energy_kcal_target'] ?? 2000);
                        break;
                    case 'タンパク質':
                        $target = floatval($targets['protein_g_target'] ?? 60);
                        break;
                    case '脂質':
                        $target = floatval($targets['fat_g_target'] ?? 65);
                        break;
                    case '炭水化物':
                        $target = floatval($targets['carbohydrate_g_target'] ?? 250);
                        break;
                    case '食物繊維':
                        $target = floatval($targets['fiber_g_target'] ?? 25);
                        break;
                    case 'ビタミンC':
                        $target = floatval($targets['vitamin_c_mg_target'] ?? 90);
                        break;
                    case 'カルシウム':
                        $target = floatval($targets['calcium_mg_target'] ?? 1000);
                        break;
                    case '鉄':
                        $target = floatval($targets['iron_mg_target'] ?? 8);
                        break;
                    case 'ナトリウム（食塩相当量）':
                        $target = floatval($targets['sodium_mg_target'] ?? 2300) / 393; // mg を g に変換
                        break;
                    default:
                        $target = 100; // デフォルト目標値
                }
                
                $rate = $target > 0 ? ($consumed / $target) * 100 : 0;
                
                $achievements[$nutrient] = [
                    'consumed' => $consumed,
                    'target' => $target,
                    'rate' => $rate,
                    'unit' => $nutrient_units[$nutrient] ?? 'mg'
                ];
            }
            
            jsonResponse(['date' => $date, 'achievements' => $achievements]);
            break;
            
        case 'daily_by_meal':
            // 食事別の栄養摂取量（全栄養素対応）
            $sql = "
                SELECT 
                    meal_type,
                    ".generateNutrientSql('エネルギー').",
                    ".generateNutrientSql('タンパク質').",
                    ".generateNutrientSql('脂質').",
                    ".generateNutrientSql('飽和脂肪酸').",
                    ".generateNutrientSql('n-6系脂肪酸').",
                    ".generateNutrientSql('n-3系脂肪酸').",
                    ".generateNutrientSql('炭水化物').",
                    ".generateNutrientSql('食物繊維').",
                    ".generateNutrientSql('ビタミンA').",
                    ".generateNutrientSql('ビタミンD').",
                    ".generateNutrientSql('ビタミンE').",
                    ".generateNutrientSql('ビタミンK').",
                    ".generateNutrientSql('ビタミンB1').",
                    ".generateNutrientSql('ビタミンB2').",
                    ".generateNutrientSql('ビタミンB6').",
                    ".generateNutrientSql('ビタミンB12').",
                    ".generateNutrientSql('ナイアシン').",
                    ".generateNutrientSql('葉酸').",
                    ".generateNutrientSql('パントテン酸').",
                    ".generateNutrientSql('ビオチン').",
                    ".generateNutrientSql('ビタミンC').",
                    ".generateNutrientSql('ナトリウム（食塩相当量）').",
                    ".generateNutrientSql('カリウム').",
                    ".generateNutrientSql('カルシウム').",
                    ".generateNutrientSql('マグネシウム').",
                    ".generateNutrientSql('リン').",
                    ".generateNutrientSql('鉄').",
                    ".generateNutrientSql('亜鉛').",
                    ".generateNutrientSql('銅').",
                    ".generateNutrientSql('マンガン').",
                    ".generateNutrientSql('ヨウ素').",
                    ".generateNutrientSql('セレン').",
                    ".generateNutrientSql('クロム').",
                    ".generateNutrientSql('モリブデン')."
                FROM meal_records mr
                JOIN foods f ON mr.food_id = f.id
                WHERE mr.user_id = :user_id AND mr.meal_date = :date
                GROUP BY meal_type
                ORDER BY 
                    CASE meal_type
                        WHEN 'breakfast' THEN 1
                        WHEN 'lunch' THEN 2
                        WHEN 'dinner' THEN 3
                        WHEN 'snack' THEN 4
                    END
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':date' => $date, ':user_id' => $userId]);
            $results = $stmt->fetchAll();
            
            // 栄養目標値を取得
            $target_sql = "SELECT * FROM nutrition_targets WHERE user_id = :user_id LIMIT 1";
            $target_stmt = $pdo->prepare($target_sql);
            $target_stmt->execute([':user_id' => $userId]);
            $targets = $target_stmt->fetch();
            
            jsonResponse(['date' => $date, 'meals' => $results, 'targets' => $targets]);
            break;
            
        case 'weekly':
            // 週間の栄養推移（期間指定対応）
            $start_date = isset($_GET['start_date']) && $_GET['start_date'] ? $_GET['start_date'] : date('Y-m-d', strtotime($date . ' -6 days'));
            $end_date = isset($_GET['end_date']) && $_GET['end_date'] ? $_GET['end_date'] : $date;
            
            $sql = "
                SELECT 
                    meal_date,
                    ".generateNutrientSql('エネルギー').",
                    ".generateNutrientSql('タンパク質').",
                    ".generateNutrientSql('脂質').",
                    ".generateNutrientSql('飽和脂肪酸').",
                    ".generateNutrientSql('n-6系脂肪酸').",
                    ".generateNutrientSql('n-3系脂肪酸').",
                    ".generateNutrientSql('炭水化物').",
                    ".generateNutrientSql('食物繊維').",
                    ".generateNutrientSql('ビタミンA').",
                    ".generateNutrientSql('ビタミンD').",
                    ".generateNutrientSql('ビタミンE').",
                    ".generateNutrientSql('ビタミンK').",
                    ".generateNutrientSql('ビタミンB1').",
                    ".generateNutrientSql('ビタミンB2').",
                    ".generateNutrientSql('ビタミンB6').",
                    ".generateNutrientSql('ビタミンB12').",
                    ".generateNutrientSql('ナイアシン').",
                    ".generateNutrientSql('葉酸').",
                    ".generateNutrientSql('パントテン酸').",
                    ".generateNutrientSql('ビオチン').",
                    ".generateNutrientSql('ビタミンC').",
                    ".generateNutrientSql('ナトリウム（食塩相当量）').",
                    ".generateNutrientSql('カリウム').",
                    ".generateNutrientSql('カルシウム').",
                    ".generateNutrientSql('マグネシウム').",
                    ".generateNutrientSql('リン').",
                    ".generateNutrientSql('鉄').",
                    ".generateNutrientSql('亜鉛').",
                    ".generateNutrientSql('銅').",
                    ".generateNutrientSql('マンガン').",
                    ".generateNutrientSql('ヨウ素').",
                    ".generateNutrientSql('セレン').",
                    ".generateNutrientSql('クロム').",
                    ".generateNutrientSql('モリブデン')."
                FROM meal_records mr
                JOIN foods f ON mr.food_id = f.id
                WHERE mr.user_id = :user_id 
                    AND mr.meal_date BETWEEN :start_date AND :end_date
                GROUP BY meal_date
                ORDER BY meal_date
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':start_date' => $start_date,
                ':end_date' => $end_date,
                ':user_id' => $userId
            ]);
            $results = $stmt->fetchAll();
            
            // 期間分のデータを作成（データがない日は0で埋める）
            $period_data = [];
            
            // 日付の検証とデフォルト値設定
            if (empty($start_date) || !$start_date || $start_date === 'undefined' || $start_date === 'null') {
                $start_date = date('Y-m-d', strtotime($date . ' -6 days'));
            }
            if (empty($end_date) || !$end_date || $end_date === 'undefined' || $end_date === 'null') {
                $end_date = $date;
            }
            
            // 日付フォーマットの追加検証
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
                $start_date = date('Y-m-d', strtotime($date . ' -6 days'));
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                $end_date = $date;
            }
            
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
            
            foreach ($period as $date) {
                $current_date = $date->format('Y-m-d');
                $found = false;
                
                foreach ($results as $result) {
                    if ($result['meal_date'] === $current_date) {
                        $period_data[] = $result;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $empty_data = ['meal_date' => $current_date];
                    $nutrients = getNutritionLabels();
                    foreach ($nutrients as $nutrient) {
                        $empty_data[$nutrient] = 0;
                    }
                    $period_data[] = $empty_data;
                }
            }
            
            // 栄養目標値を取得
            $target_sql = "SELECT * FROM nutrition_targets WHERE user_id = :user_id LIMIT 1";
            $target_stmt = $pdo->prepare($target_sql);
            $target_stmt->execute([':user_id' => $userId]);
            $targets = $target_stmt->fetch();
            
            jsonResponse(['period' => $start_date . ' to ' . $end_date, 'data' => $period_data, 'targets' => $targets]);
            break;
            
            
        default:
            errorResponse('無効なtypeパラメータです。daily, daily_by_meal, weekly のいずれかを指定してください');
    }
    
} catch (PDOException $e) {
    errorResponse('データ取得エラー: ' . $e->getMessage(), 500);
}
?>