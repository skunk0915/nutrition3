<?php
// 共通ユーティリティ関数

/**
 * Authorization ヘッダの Bearer トークンからユーザーIDを取得
 * 未認証・不正トークンの場合は 401 エラー
 */
function getAuthenticatedUserId(): int {
    global $pdo;
    $headers = getallheaders();
    
    // 大文字小文字を無視してAuthorizationヘッダを探す
    $authHeader = null;
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    
    if (!$authHeader) {
        errorResponse('認証トークンがありません', 401);
    }
    
    if (!preg_match('/Bearer\s+(\w{32})/', $authHeader, $m)) {
        errorResponse('トークン形式が無効です', 401);
    }
    $token = $m[1];
    
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE auth_token = :t LIMIT 1');
    $stmt->execute([':t' => $token]);
    $row = $stmt->fetch();
    
    if (!$row) {
        errorResponse('無効なトークン', 401);
    }
    
    return intval($row['user_id']);
}

/**
 * 年齢と性別に応じた栄養目標値を返す
 * 値幅がある指標は中央値を採用
 * 目標値は nutrition_summary.php の達成率計算に合わせたキー名で返す
 *
 * @param int|null $age 年齢 (null の場合は 30-49 とみなす)
 * @param string|null $gender 'male'|'female' いずれか (null の場合は male)
 * @return array
 */
function getReferenceTargets(?int $age, ?string $gender): array {
    // デフォルト
    $gender = $gender === 'female' ? 'female' : 'male';
    if ($age === null) {
        $ageGroup = '30-49';
    } elseif ($age >= 18 && $age <= 29) {
        $ageGroup = '18-29';
    } elseif ($age >= 30 && $age <= 49) {
        $ageGroup = '30-49';
    } else { // 50-64 とそれ以外はまとめる
        $ageGroup = '50-64';
    }

    // 栄養基準データ
    $table = [
        // エネルギー kcal, たんぱく質 g, FAT % (DG), 炭水化物 % (DG), 食物繊維 g, ビタミンC mg, カルシウム mg, 鉄 mg, ナトリウム mg (食塩相当量 g -> Na mg 換算: g*1000*393/1000= g*393?)
        // 簡易対応として表に載っている値をそのまま使用
        '18-29_male'   => [2650, 65, 0.25, 0.575, 21, 100, 800, 7.5, 7500],
        '18-29_female' => [2000, 50, 0.25, 0.575, 18, 100, 650, 8.5, 6500],
        '30-49_male'   => [2700, 65, 0.25, 0.575, 21, 100, 750, 7.5, 7500],
        '30-49_female' => [2050, 50, 0.25, 0.575, 18, 100, 650, 8.5, 6500],
        '50-64_male'   => [2600, 65, 0.25, 0.575, 20, 100, 750, 7.5, 7500],
        '50-64_female' => [1950, 50, 0.25, 0.575, 18, 100, 650, 8.5, 6500],
    ];

    $key = $ageGroup . '_' . $gender;
    if (!isset($table[$key])) {
        $key = '30-49_male';
    }

    [$energy, $protein, $fatRate, $carbRate, $fiber, $vitaminC, $calcium, $iron, $sodiumNaCl] = $table[$key];

    // 脂質と炭水化物はエネルギー比率 → g に換算
    $fatTargetG  = ($energy * $fatRate) / 9; // 1g fat = 9kcal
    $carbTargetG = ($energy * $carbRate) / 4; // 1g carb = 4kcal

    return [
        'energy_kcal_target'       => $energy,
        'protein_g_target'         => $protein,
        'fat_g_target'             => round($fatTargetG, 1),
        'carbohydrate_g_target'    => round($carbTargetG, 1),
        'fiber_g_target'           => $fiber,
        'vitamin_c_mg_target'      => $vitaminC,
        'calcium_mg_target'        => $calcium,
        'iron_mg_target'           => $iron,
        'sodium_mg_target'         => $sodiumNaCl, // すでにmg単位として扱う
    ];
}
