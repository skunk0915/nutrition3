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
            
            // 達成率計算
            $achievements = [
                'energy' => [
                    'consumed' => $result['consumed_energy_kcal'],
                    'target' => $result['energy_kcal_target'],
                    'rate' => $result['energy_kcal_target'] > 0 ? ($result['consumed_energy_kcal'] / $result['energy_kcal_target']) * 100 : 0,
                    'unit' => 'kcal'
                ],
                'protein' => [
                    'consumed' => $result['consumed_protein_g'],
                    'target' => $result['protein_g_target'],
                    'rate' => $result['protein_g_target'] > 0 ? ($result['consumed_protein_g'] / $result['protein_g_target']) * 100 : 0,
                    'unit' => 'g'
                ],
                'fat' => [
                    'consumed' => $result['consumed_fat_g'],
                    'target' => $result['fat_g_target'],
                    'rate' => $result['fat_g_target'] > 0 ? ($result['consumed_fat_g'] / $result['fat_g_target']) * 100 : 0,
                    'unit' => 'g'
                ],
                'carbohydrate' => [
                    'consumed' => $result['consumed_carbohydrate_g'],
                    'target' => $result['carbohydrate_g_target'],
                    'rate' => $result['carbohydrate_g_target'] > 0 ? ($result['consumed_carbohydrate_g'] / $result['carbohydrate_g_target']) * 100 : 0,
                    'unit' => 'g'
                ],
                'fiber' => [
                    'consumed' => $result['consumed_fiber_g'],
                    'target' => $result['fiber_g_target'],
                    'rate' => $result['fiber_g_target'] > 0 ? ($result['consumed_fiber_g'] / $result['fiber_g_target']) * 100 : 0,
                    'unit' => 'g'
                ],
                'vitamin_c' => [
                    'consumed' => $result['consumed_vitamin_c_mg'],
                    'target' => $result['vitamin_c_mg_target'],
                    'rate' => $result['vitamin_c_mg_target'] > 0 ? ($result['consumed_vitamin_c_mg'] / $result['vitamin_c_mg_target']) * 100 : 0,
                    'unit' => 'mg'
                ],
                'calcium' => [
                    'consumed' => $result['consumed_calcium_mg'],
                    'target' => $result['calcium_mg_target'],
                    'rate' => $result['calcium_mg_target'] > 0 ? ($result['consumed_calcium_mg'] / $result['calcium_mg_target']) * 100 : 0,
                    'unit' => 'mg'
                ],
                'iron' => [
                    'consumed' => $result['consumed_iron_mg'],
                    'target' => $result['iron_mg_target'],
                    'rate' => $result['iron_mg_target'] > 0 ? ($result['consumed_iron_mg'] / $result['iron_mg_target']) * 100 : 0,
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
                    SUM(fn.energy_kcal * mr.quantity_g / 100) as meal_energy_kcal,
                    SUM(fn.protein_g * mr.quantity_g / 100) as meal_protein_g,
                    SUM(fn.fat_g * mr.quantity_g / 100) as meal_fat_g,
                    SUM(fn.carbohydrate_g * mr.quantity_g / 100) as meal_carbohydrate_g
                FROM meal_records mr
                JOIN food_nutrition fn ON mr.food_id = fn.food_id
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
                    COALESCE(SUM(fn.energy_kcal * mr.quantity_g / 100), 0) as total_energy_kcal,
                    COALESCE(SUM(fn.protein_g * mr.quantity_g / 100), 0) as total_protein_g,
                    COALESCE(SUM(fn.fat_g * mr.quantity_g / 100), 0) as total_fat_g,
                    COALESCE(SUM(fn.carbohydrate_g * mr.quantity_g / 100), 0) as total_carbohydrate_g
                FROM meal_records mr
                JOIN food_nutrition fn ON mr.food_id = fn.food_id
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
                    SUM(fn.energy_kcal * mr.quantity_g / 100) as total_energy_kcal,
                    SUM(fn.protein_g * mr.quantity_g / 100) as total_protein_g,
                    SUM(fn.fat_g * mr.quantity_g / 100) as total_fat_g,
                    SUM(fn.carbohydrate_g * mr.quantity_g / 100) as total_carbohydrate_g
                FROM meal_records mr
                JOIN food_nutrition fn ON mr.food_id = fn.food_id
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