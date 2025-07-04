<?php
require_once 'config.php';
require_once 'utils.php';

$userId = getAuthenticatedUserId();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // プロフィール取得
        $stmt = $pdo->prepare('SELECT email, age, gender FROM users WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $profile = $stmt->fetch();
        if (!$profile) {
            errorResponse('ユーザーが見つかりません', 404);
        }
        jsonResponse($profile);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || (!isset($input['age']) && !isset($input['gender']))) {
            errorResponse('age または gender を指定してください');
        }

        $age = isset($input['age']) ? intval($input['age']) : null;
        $gender = isset($input['gender']) ? $input['gender'] : null;

        if ($age !== null && ($age < 0 || $age > 120)) {
            errorResponse('年齢が無効です');
        }
        if ($gender !== null && !in_array($gender, ['male', 'female'])) {
            errorResponse('性別が無効です');
        }

        try {
            $pdo->beginTransaction();
            // users テーブル更新
            $updateParts = [];
            $params = [':uid' => $userId];
            if ($age !== null) {
                $updateParts[] = 'age = :age';
                $params[':age'] = $age;
            }
            if ($gender !== null) {
                $updateParts[] = 'gender = :gender';
                $params[':gender'] = $gender;
            }
            $sql = 'UPDATE users SET ' . implode(', ', $updateParts) . ' WHERE user_id = :uid';
            $pdo->prepare($sql)->execute($params);

            // 栄養目標値再計算
            $stmt = $pdo->prepare('SELECT age, gender FROM users WHERE user_id = :uid');
            $stmt->execute([':uid' => $userId]);
            $user = $stmt->fetch();
            $targets = getReferenceTargets($user['age'] ?? null, $user['gender'] ?? null);

            // nutrition_targets update
            $setParts = [];
            $tParams = [':uid' => $userId];
            foreach ($targets as $col => $val) {
                $setParts[] = "$col = :$col";
                $tParams[":$col"] = $val;
            }
            $pdo->prepare('UPDATE nutrition_targets SET ' . implode(', ', $setParts) . ' WHERE user_id = :uid')
                ->execute($tParams);

            $pdo->commit();
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            errorResponse('更新失敗: ' . $e->getMessage(), 500);
        }
        break;

    default:
        errorResponse('許可されていないメソッド', 405);
}
