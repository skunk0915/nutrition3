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
                    COALESCE(dns.total_sodium_mg, 0) as consumed_sodium_mg,
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
            // 食事別の栄養摂取量（全栄養素対応）
            $sql = "
                SELECT 
                    meal_type,
                    SUM(f.カロリー * mr.quantity_g / 100) as energy_kcal,
                    SUM(f.たんぱく質 * mr.quantity_g / 100) as protein_g,
                    SUM(f.脂質 * mr.quantity_g / 100) as fat_g,
                    SUM(f.飽和脂肪酸 * mr.quantity_g / 100) as saturated_fat_g,
                    SUM(f.`n-6系多価不飽和脂肪酸` * mr.quantity_g / 100) as n6_fat_g,
                    SUM(f.`n-3系多価不飽和脂肪酸` * mr.quantity_g / 100) as n3_fat_g,
                    SUM(f.炭水化物 * mr.quantity_g / 100) as carbohydrate_g,
                    SUM(f.食物繊維総量 * mr.quantity_g / 100) as fiber_g,
                    SUM(f.レチノール活性当量 * mr.quantity_g / 100) as vitamin_a_ug,
                    SUM(f.ビタミンD * mr.quantity_g / 100) as vitamin_d_ug,
                    SUM(f.α_トコフェロール * mr.quantity_g / 100) as vitamin_e_mg,
                    SUM(f.ビタミンK * mr.quantity_g / 100) as vitamin_k_ug,
                    SUM(f.ビタミンB1 * mr.quantity_g / 100) as vitamin_b1_mg,
                    SUM(f.ビタミンB2 * mr.quantity_g / 100) as vitamin_b2_mg,
                    SUM(f.ビタミンB6 * mr.quantity_g / 100) as vitamin_b6_mg,
                    SUM(f.ビタミンB12 * mr.quantity_g / 100) as vitamin_b12_ug,
                    SUM(f.ナイアシン * mr.quantity_g / 100) as niacin_mg,
                    SUM(f.葉酸 * mr.quantity_g / 100) as folate_ug,
                    SUM(f.パントテン酸 * mr.quantity_g / 100) as pantothenic_acid_mg,
                    SUM(f.ビオチン * mr.quantity_g / 100) as biotin_ug,
                    SUM(f.ビタミンC * mr.quantity_g / 100) as vitamin_c_mg,
                    SUM(f.ナトリウム * mr.quantity_g / 100) as sodium_mg,
                    SUM(f.カリウム * mr.quantity_g / 100) as potassium_mg,
                    SUM(f.カルシウム * mr.quantity_g / 100) as calcium_mg,
                    SUM(f.マグネシウム * mr.quantity_g / 100) as magnesium_mg,
                    SUM(f.リン * mr.quantity_g / 100) as phosphorus_mg,
                    SUM(f.鉄 * mr.quantity_g / 100) as iron_mg,
                    SUM(f.亜鉛 * mr.quantity_g / 100) as zinc_mg,
                    SUM(f.銅 * mr.quantity_g / 100) as copper_mg,
                    SUM(f.マンガン * mr.quantity_g / 100) as manganese_mg,
                    SUM(f.ヨウ素 * mr.quantity_g / 100) as iodine_ug,
                    SUM(f.セレン * mr.quantity_g / 100) as selenium_ug,
                    SUM(f.クロム * mr.quantity_g / 100) as chromium_ug,
                    SUM(f.モリブデン * mr.quantity_g / 100) as molybdenum_ug
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
            
            // 栄養目標値を取得
            $target_sql = "SELECT * FROM nutrition_targets WHERE user_id = 1 LIMIT 1";
            $target_stmt = $pdo->prepare($target_sql);
            $target_stmt->execute();
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
                    COALESCE(SUM(f.カロリー * mr.quantity_g / 100), 0) as energy_kcal,
                    COALESCE(SUM(f.たんぱく質 * mr.quantity_g / 100), 0) as protein_g,
                    COALESCE(SUM(f.脂質 * mr.quantity_g / 100), 0) as fat_g,
                    COALESCE(SUM(f.飽和脂肪酸 * mr.quantity_g / 100), 0) as saturated_fat_g,
                    COALESCE(SUM(f.`n-6系多価不飽和脂肪酸` * mr.quantity_g / 100), 0) as n6_fat_g,
                    COALESCE(SUM(f.`n-3系多価不飽和脂肪酸` * mr.quantity_g / 100), 0) as n3_fat_g,
                    COALESCE(SUM(f.炭水化物 * mr.quantity_g / 100), 0) as carbohydrate_g,
                    COALESCE(SUM(f.食物繊維総量 * mr.quantity_g / 100), 0) as fiber_g,
                    COALESCE(SUM(f.レチノール活性当量 * mr.quantity_g / 100), 0) as vitamin_a_ug,
                    COALESCE(SUM(f.ビタミンD * mr.quantity_g / 100), 0) as vitamin_d_ug,
                    COALESCE(SUM(f.α_トコフェロール * mr.quantity_g / 100), 0) as vitamin_e_mg,
                    COALESCE(SUM(f.ビタミンK * mr.quantity_g / 100), 0) as vitamin_k_ug,
                    COALESCE(SUM(f.ビタミンB1 * mr.quantity_g / 100), 0) as vitamin_b1_mg,
                    COALESCE(SUM(f.ビタミンB2 * mr.quantity_g / 100), 0) as vitamin_b2_mg,
                    COALESCE(SUM(f.ビタミンB6 * mr.quantity_g / 100), 0) as vitamin_b6_mg,
                    COALESCE(SUM(f.ビタミンB12 * mr.quantity_g / 100), 0) as vitamin_b12_ug,
                    COALESCE(SUM(f.ナイアシン * mr.quantity_g / 100), 0) as niacin_mg,
                    COALESCE(SUM(f.葉酸 * mr.quantity_g / 100), 0) as folate_ug,
                    COALESCE(SUM(f.パントテン酸 * mr.quantity_g / 100), 0) as pantothenic_acid_mg,
                    COALESCE(SUM(f.ビオチン * mr.quantity_g / 100), 0) as biotin_ug,
                    COALESCE(SUM(f.ビタミンC * mr.quantity_g / 100), 0) as vitamin_c_mg,
                    COALESCE(SUM(f.ナトリウム * mr.quantity_g / 100), 0) as sodium_mg,
                    COALESCE(SUM(f.カリウム * mr.quantity_g / 100), 0) as potassium_mg,
                    COALESCE(SUM(f.カルシウム * mr.quantity_g / 100), 0) as calcium_mg,
                    COALESCE(SUM(f.マグネシウム * mr.quantity_g / 100), 0) as magnesium_mg,
                    COALESCE(SUM(f.リン * mr.quantity_g / 100), 0) as phosphorus_mg,
                    COALESCE(SUM(f.鉄 * mr.quantity_g / 100), 0) as iron_mg,
                    COALESCE(SUM(f.亜鉛 * mr.quantity_g / 100), 0) as zinc_mg,
                    COALESCE(SUM(f.銅 * mr.quantity_g / 100), 0) as copper_mg,
                    COALESCE(SUM(f.マンガン * mr.quantity_g / 100), 0) as manganese_mg,
                    COALESCE(SUM(f.ヨウ素 * mr.quantity_g / 100), 0) as iodine_ug,
                    COALESCE(SUM(f.セレン * mr.quantity_g / 100), 0) as selenium_ug,
                    COALESCE(SUM(f.クロム * mr.quantity_g / 100), 0) as chromium_ug,
                    COALESCE(SUM(f.モリブデン * mr.quantity_g / 100), 0) as molybdenum_ug
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
                    $period_data[] = [
                        'meal_date' => $current_date,
                        'energy_kcal' => 0, 'protein_g' => 0, 'fat_g' => 0, 'saturated_fat_g' => 0,
                        'n6_fat_g' => 0, 'n3_fat_g' => 0, 'carbohydrate_g' => 0, 'fiber_g' => 0,
                        'vitamin_a_ug' => 0, 'vitamin_d_ug' => 0, 'vitamin_e_mg' => 0, 'vitamin_k_ug' => 0,
                        'vitamin_b1_mg' => 0, 'vitamin_b2_mg' => 0, 'vitamin_b6_mg' => 0, 'vitamin_b12_ug' => 0,
                        'niacin_mg' => 0, 'folate_ug' => 0, 'pantothenic_acid_mg' => 0, 'biotin_ug' => 0,
                        'vitamin_c_mg' => 0, 'sodium_mg' => 0, 'potassium_mg' => 0, 'calcium_mg' => 0,
                        'magnesium_mg' => 0, 'phosphorus_mg' => 0, 'iron_mg' => 0, 'zinc_mg' => 0,
                        'copper_mg' => 0, 'manganese_mg' => 0, 'iodine_ug' => 0, 'selenium_ug' => 0,
                        'chromium_ug' => 0, 'molybdenum_ug' => 0
                    ];
                }
            }
            
            // 栄養目標値を取得
            $target_sql = "SELECT * FROM nutrition_targets WHERE user_id = 1 LIMIT 1";
            $target_stmt = $pdo->prepare($target_sql);
            $target_stmt->execute();
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