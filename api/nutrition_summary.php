<?php
require_once 'config.php';

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
                    COALESCE(dns.total_energy_kcal, 0) as consumed_energy_kcal,
                    COALESCE(dns.total_protein_g, 0) as consumed_protein_g,
                    COALESCE(dns.total_fat_g, 0) as consumed_fat_g,
                    COALESCE(dns.total_carbohydrate_g, 0) as consumed_carbohydrate_g,
                    COALESCE(dns.total_fiber_g, 0) as consumed_fiber_g,
                    COALESCE(dns.total_vitamin_c_mg, 0) as consumed_vitamin_c_mg,
                    COALESCE(dns.total_calcium_mg, 0) as consumed_calcium_mg,
                    COALESCE(dns.total_iron_mg, 0) as consumed_iron_mg,
                    nt.energy_kcal_target,
                    nt.protein_g_target,
                    nt.fat_g_target,
                    nt.carbohydrate_g_target,
                    nt.fiber_g_target,
                    nt.vitamin_c_mg_target,
                    nt.calcium_mg_target,
                    nt.iron_mg_target
                FROM nutrition_targets nt
                LEFT JOIN daily_nutrition_summary dns ON nt.user_id = dns.user_id AND dns.meal_date = :date
                WHERE nt.user_id = 1
                LIMIT 1
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':date' => $date]);
            $result = $stmt->fetch();
            
            if (!$result) {
                errorResponse('データが見つかりません');
            }
            
            // 達成率計算（主要栄養素を拡大）
            $achievements = [
                'エネルギー' => [
                    'consumed' => floatval($result['consumed_energy_kcal'] ?? 0),
                    'target' => floatval($result['energy_kcal_target'] ?? 2000),
                    'rate' => ($result['energy_kcal_target'] ?? 2000) > 0 ? (floatval($result['consumed_energy_kcal'] ?? 0) / floatval($result['energy_kcal_target'] ?? 2000)) * 100 : 0,
                    'unit' => 'kcal'
                ],
                'タンパク質' => [
                    'consumed' => floatval($result['consumed_protein_g'] ?? 0),
                    'target' => floatval($result['protein_g_target'] ?? 60),
                    'rate' => ($result['protein_g_target'] ?? 60) > 0 ? (floatval($result['consumed_protein_g'] ?? 0) / floatval($result['protein_g_target'] ?? 60)) * 100 : 0,
                    'unit' => 'g'
                ],
                '脂質' => [
                    'consumed' => floatval($result['consumed_fat_g'] ?? 0),
                    'target' => floatval($result['fat_g_target'] ?? 65),
                    'rate' => ($result['fat_g_target'] ?? 65) > 0 ? (floatval($result['consumed_fat_g'] ?? 0) / floatval($result['fat_g_target'] ?? 65)) * 100 : 0,
                    'unit' => 'g'
                ],
                '炭水化物' => [
                    'consumed' => floatval($result['consumed_carbohydrate_g'] ?? 0),
                    'target' => floatval($result['carbohydrate_g_target'] ?? 250),
                    'rate' => ($result['carbohydrate_g_target'] ?? 250) > 0 ? (floatval($result['consumed_carbohydrate_g'] ?? 0) / floatval($result['carbohydrate_g_target'] ?? 250)) * 100 : 0,
                    'unit' => 'g'
                ],
                '食物繊維' => [
                    'consumed' => floatval($result['consumed_fiber_g'] ?? 0),
                    'target' => floatval($result['fiber_g_target'] ?? 25),
                    'rate' => ($result['fiber_g_target'] ?? 25) > 0 ? (floatval($result['consumed_fiber_g'] ?? 0) / floatval($result['fiber_g_target'] ?? 25)) * 100 : 0,
                    'unit' => 'g'
                ],
                'ナトリウム' => [
                    'consumed' => floatval($result['consumed_sodium_mg'] ?? 0),
                    'target' => floatval($result['sodium_mg_target'] ?? 2300),
                    'rate' => ($result['sodium_mg_target'] ?? 2300) > 0 ? (floatval($result['consumed_sodium_mg'] ?? 0) / floatval($result['sodium_mg_target'] ?? 2300)) * 100 : 0,
                    'unit' => 'mg'
                ],
                'ビタミンA' => [
                    'consumed' => floatval($result['consumed_vitamin_a_ug'] ?? 0),
                    'target' => floatval($result['vitamin_a_ug_target'] ?? 800),
                    'rate' => ($result['vitamin_a_ug_target'] ?? 800) > 0 ? (floatval($result['consumed_vitamin_a_ug'] ?? 0) / floatval($result['vitamin_a_ug_target'] ?? 800)) * 100 : 0,
                    'unit' => 'μg'
                ],
                'ビタミンB1' => [
                    'consumed' => floatval($result['consumed_vitamin_b1_mg'] ?? 0),
                    'target' => floatval($result['vitamin_b1_mg_target'] ?? 1.2),
                    'rate' => ($result['vitamin_b1_mg_target'] ?? 1.2) > 0 ? (floatval($result['consumed_vitamin_b1_mg'] ?? 0) / floatval($result['vitamin_b1_mg_target'] ?? 1.2)) * 100 : 0,
                    'unit' => 'mg'
                ],
                'ビタミンB2' => [
                    'consumed' => floatval($result['consumed_vitamin_b2_mg'] ?? 0),
                    'target' => floatval($result['vitamin_b2_mg_target'] ?? 1.4),
                    'rate' => ($result['vitamin_b2_mg_target'] ?? 1.4) > 0 ? (floatval($result['consumed_vitamin_b2_mg'] ?? 0) / floatval($result['vitamin_b2_mg_target'] ?? 1.4)) * 100 : 0,
                    'unit' => 'mg'
                ],
                'ビタミンC' => [
                    'consumed' => floatval($result['consumed_vitamin_c_mg'] ?? 0),
                    'target' => floatval($result['vitamin_c_mg_target'] ?? 90),
                    'rate' => ($result['vitamin_c_mg_target'] ?? 90) > 0 ? (floatval($result['consumed_vitamin_c_mg'] ?? 0) / floatval($result['vitamin_c_mg_target'] ?? 90)) * 100 : 0,
                    'unit' => 'mg'
                ],
                'カルシウム' => [
                    'consumed' => floatval($result['consumed_calcium_mg'] ?? 0),
                    'target' => floatval($result['calcium_mg_target'] ?? 1000),
                    'rate' => ($result['calcium_mg_target'] ?? 1000) > 0 ? (floatval($result['consumed_calcium_mg'] ?? 0) / floatval($result['calcium_mg_target'] ?? 1000)) * 100 : 0,
                    'unit' => 'mg'
                ],
                '鉄' => [
                    'consumed' => floatval($result['consumed_iron_mg'] ?? 0),
                    'target' => floatval($result['iron_mg_target'] ?? 8),
                    'rate' => ($result['iron_mg_target'] ?? 8) > 0 ? (floatval($result['consumed_iron_mg'] ?? 0) / floatval($result['iron_mg_target'] ?? 8)) * 100 : 0,
                    'unit' => 'mg'
                ]
            ];
            
            jsonResponse(['date' => $date, 'achievements' => $achievements]);
            break;
            
        case 'daily_by_meal':
            // 食事別の栄養摂取量
            $sql = "
                SELECT 
                    meal_type,
                    SUM(f.カロリー * mr.quantity_g / 100) as meal_energy_kcal,
                    SUM(f.たんぱく質 * mr.quantity_g / 100) as meal_protein_g,
                    SUM(f.脂質 * mr.quantity_g / 100) as meal_fat_g,
                    SUM(f.炭水化物 * mr.quantity_g / 100) as meal_carbohydrate_g
                FROM meal_records mr
                JOIN foods f ON mr.food_id = f.id
                WHERE mr.user_id = 1 AND mr.meal_date = :date
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
            $stmt->execute([':date' => $date]);
            $results = $stmt->fetchAll();
            
            jsonResponse(['date' => $date, 'meals' => $results]);
            break;
            
        case 'weekly':
            // 週間の栄養推移
            $start_date = date('Y-m-d', strtotime($date . ' -6 days'));
            $end_date = $date;
            
            $sql = "
                SELECT 
                    meal_date,
                    COALESCE(SUM(f.カロリー * mr.quantity_g / 100), 0) as total_energy_kcal,
                    COALESCE(SUM(f.たんぱく質 * mr.quantity_g / 100), 0) as total_protein_g,
                    COALESCE(SUM(f.脂質 * mr.quantity_g / 100), 0) as total_fat_g,
                    COALESCE(SUM(f.炭水化物 * mr.quantity_g / 100), 0) as total_carbohydrate_g
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
            $results = $stmt->fetchAll();
            
            // 7日分のデータを作成（データがない日は0で埋める）
            $weekly_data = [];
            for ($i = 0; $i < 7; $i++) {
                $current_date = date('Y-m-d', strtotime($start_date . ' +' . $i . ' days'));
                $found = false;
                
                foreach ($results as $result) {
                    if ($result['meal_date'] === $current_date) {
                        $weekly_data[] = $result;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $weekly_data[] = [
                        'meal_date' => $current_date,
                        'total_energy_kcal' => 0,
                        'total_protein_g' => 0,
                        'total_fat_g' => 0,
                        'total_carbohydrate_g' => 0
                    ];
                }
            }
            
            jsonResponse(['period' => $start_date . ' to ' . $end_date, 'data' => $weekly_data]);
            break;
            
        case 'monthly':
            // 月間の栄養摂取量
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $start_date = $year . '-' . $month . '-01';
            $end_date = date('Y-m-t', strtotime($start_date));
            
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
            $results = $stmt->fetchAll();
            
            jsonResponse(['period' => $year . '年' . $month . '月', 'data' => $results]);
            break;
            
        default:
            errorResponse('無効なtypeパラメータです。daily, daily_by_meal, weekly, monthly のいずれかを指定してください');
    }
    
} catch (PDOException $e) {
    errorResponse('データ取得エラー: ' . $e->getMessage(), 500);
}
?>