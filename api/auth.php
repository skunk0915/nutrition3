<?php
require_once 'config.php';
require_once 'utils.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // パス: /api/auth.php?action=register|login
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $input  = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            errorResponse('無効な JSON データ');
        }
        if ($action === 'register') {
            registerUser($input);
        } elseif ($action === 'login') {
            loginUser($input);
        } else {
            errorResponse('action パラメータが必要です (register|login)');
        }
        break;
    default:
        errorResponse('POST method required', 405);
}

/**
 * 会員登録
 */
function registerUser(array $data)
{
    global $pdo;
    $required = ['email', 'password', 'age', 'gender'];
    foreach ($required as $f) {
        if (!isset($data[$f]) || $data[$f] === '') {
            errorResponse("{$f} は必須です");
        }
    }
    $email    = strtolower(trim($data['email']));
    $password = $data['password'];
    $age      = intval($data['age']);
    $gender   = $data['gender'] === 'female' ? 'female' : 'male';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        errorResponse('メールアドレス形式が無効です');
    }
    if ($age < 0 || $age > 120) {
        errorResponse('年齢が無効です');
    }

    // 既存チェック
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        errorResponse('既に登録されています');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $token        = bin2hex(random_bytes(16));

    try {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO users(email, password_hash, age, gender, auth_token) VALUES(:email, :hash, :age, :gender, :token)')
            ->execute([
                ':email' => $email,
                ':hash'  => $passwordHash,
                ':age'   => $age,
                ':gender'=> $gender,
                ':token' => $token
            ]);
        $userId = $pdo->lastInsertId();

        // 個別栄養目標値を挿入
        $targets = getReferenceTargets($age, $gender);
        $columns = array_keys($targets);
        $placeH  = array_map(fn($c)=>":$c", $columns);
        $sql = 'INSERT INTO nutrition_targets(user_id,' . implode(',', $columns) . ') VALUES(:user_id,' . implode(',', $placeH) . ')';
        $targets['user_id'] = $userId;
        $pdo->prepare($sql)->execute($targets);

        $pdo->commit();
        jsonResponse(['token' => $token]);
    } catch (Exception $e) {
        $pdo->rollBack();
        errorResponse('登録エラー: ' . $e->getMessage(), 500);
    }
}

/**
 * ログイン
 */
function loginUser(array $data)
{
    global $pdo;
    if (!isset($data['email'], $data['password'])) {
        errorResponse('email と password が必要です');
    }
    $email = strtolower(trim($data['email']));
    $stmt  = $pdo->prepare('SELECT user_id, password_hash, auth_token FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        errorResponse('メールアドレスまたはパスワードが間違っています', 401);
    }
    jsonResponse(['token' => $user['auth_token']]);
}
