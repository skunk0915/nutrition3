# 栄養管理システム

チャットGPTの会話内容を元に作成した栄養摂取量追跡Webアプリケーションです。

## 機能

### 主要機能
- **食事入力**: 日付、食事タイプ（朝食・昼食・夕食・間食）、食品名、分量を記録
- **食品検索**: 食品データベースからのオートコンプリート機能
- **栄養分析**: 日別、週別、月別の栄養摂取量分析
- **視覚化**: 3種類のグラフによる栄養データの可視化

### グラフ機能
1. **今日の栄養達成率**: 目標値に対する達成率を棒グラフで表示
2. **週間栄養推移**: 過去7日間の栄養摂取量の推移を線グラフで表示
3. **月間栄養摂取量**: 当月の日別エネルギー摂取量を棒グラフで表示

## ファイル構成

```
nutrition3/
├── index.html          # メインページ
├── css/
│   └── style.css       # スタイルシート
├── js/
│   └── app.js          # フロントエンドJavaScript
├── api/
│   ├── config.php      # データベース設定
│   ├── auth.php        # ユーザー登録・ログインAPI
│   ├── profile.php     # プロフィール取得・更新API
│   ├── food_search.php # 食品検索API
│   ├── meal_record.php # 食事記録API
│   └── nutrition_summary.php # 栄養集計API
├── database.sql        # データベーススキーマ
└── README.md           # このファイル
```

## データベース設計

### テーブル構成
- `foods`: 食品栄養情報（100以上の栄養素データ）
- `meal_records`: 食事記録
- `nutrition_targets`: 栄養目標値
- `daily_nutrition_summary`: 日別栄養集計ビュー
- `meal_nutrition_summary`: 食事別栄養集計ビュー

## セットアップ

### 1. データベース設定
```sql
-- データベース作成
CREATE DATABASE nutrition_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- テーブル作成
mysql -u [username] -p nutrition_db < database.sql
```

### 2. PHP設定
`api/config.php`のデータベース接続情報を更新：
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nutrition_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Webサーバー設定
- PHP 7.4以上
- MySQL 5.7以上またはMariaDB 10.2以上
- PHPのPDO拡張が有効

## API仕様

### 食品検索
```
GET /api/food_search.php?q={query}&limit={limit}
```

### 食事記録
```
POST /api/meal_record.php
Content-Type: application/json

{
  "date": "2024-01-01",
  "meal_type": "breakfast",
  "foods": [
    {"food_id": 1, "quantity": 150},
    {"food_id": 2, "quantity": 100}
  ]
}
```

### 栄養集計
```
GET /api/nutrition_summary.php?type={daily|weekly|monthly}&date={date}
```

### 認証
ユーザー登録・ログインで取得したトークンを各API呼び出し時の HTTP Header に付与してください。
```
Authorization: Bearer <token>
```

#### ユーザー登録
```
POST /api/auth.php?action=register
Content-Type: application/json
{
  "email": "user@example.com",
  "password": "password",
  "age": 30,
  "gender": "male"
}
```
レスポンス:
```
{
  "token": "<generated_token>"
}
```

#### ログイン
```
POST /api/auth.php?action=login
Content-Type: application/json
{
  "email": "user@example.com",
  "password": "password"
}
```
レスポンスは登録時と同じ形式でトークンを返します。

### プロフィール取得・更新
```
GET /api/profile.php
```
レスポンス:
```
{
  "email": "user@example.com",
  "age": 30,
  "gender": "male"
}
```

```
PUT /api/profile.php
Authorization: Bearer <token>
Content-Type: application/json
{
  "age": 35,
  "gender": "female"
}
```
成功時:
```
{
  "success": true
}
```

## 技術スタック

- **フロントエンド**: HTML5, CSS3, JavaScript (ES6+)
- **バックエンド**: PHP 7.4+
- **データベース**: MySQL 5.7+
- **グラフライブラリ**: Chart.js
- **スタイル**: CSS Grid, Flexbox

## サンプルデータ

データベースには以下の食品データが含まれています：
- 穀類（白米、玄米、食パン）
- 肉類（鶏むね肉、豚ロース、牛肩ロース）
- 魚介類（さけ、まぐろ）
- 卵・乳類（鶏卵、牛乳、ヨーグルト）
- 野菜類（キャベツ、にんじん、ほうれん草等）
- 果実類（りんご、バナナ、オレンジ）

## ライセンス

MIT License