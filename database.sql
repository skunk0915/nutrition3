-- 栄養管理システム データベース設計

-- ユーザーテーブル
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    auth_token CHAR(32) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- 食品情報テーブル (foods-0-5k.sqlからインポートされる)
-- このテーブルは外部SQLファイルで作成・データ投入される

-- 食事記録テーブル
CREATE TABLE meal_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    meal_date DATE NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    food_id INT NOT NULL,
    quantity_g DECIMAL(8,2) NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (food_id) REFERENCES foods(id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_user_date (user_id, meal_date),
    INDEX idx_meal_type (meal_type)
);

-- 栄養目標値テーブル (推奨摂取量)
CREATE TABLE nutrition_targets (
    target_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT 1,
    age_group VARCHAR(20) DEFAULT 'adult',
    gender ENUM('male', 'female') DEFAULT 'male',
    
    -- 基本栄養素目標値
    energy_kcal_target DECIMAL(8,2) DEFAULT 2000,
    protein_g_target DECIMAL(8,2) DEFAULT 60,
    fat_g_target DECIMAL(8,2) DEFAULT 65,
    carbohydrate_g_target DECIMAL(8,2) DEFAULT 250,
    fiber_g_target DECIMAL(8,2) DEFAULT 25,
    sodium_mg_target DECIMAL(8,2) DEFAULT 2300,
    
    -- ビタミン目標値
    vitamin_a_ug_target DECIMAL(8,2) DEFAULT 800,
    vitamin_b1_mg_target DECIMAL(8,2) DEFAULT 1.2,
    vitamin_b2_mg_target DECIMAL(8,2) DEFAULT 1.4,
    vitamin_b3_mg_target DECIMAL(8,2) DEFAULT 16,
    vitamin_b6_mg_target DECIMAL(8,2) DEFAULT 1.4,
    vitamin_b12_ug_target DECIMAL(8,2) DEFAULT 2.4,
    vitamin_c_mg_target DECIMAL(8,2) DEFAULT 90,
    vitamin_d_ug_target DECIMAL(8,2) DEFAULT 10,
    vitamin_e_mg_target DECIMAL(8,2) DEFAULT 15,
    vitamin_k_ug_target DECIMAL(8,2) DEFAULT 120,
    folate_ug_target DECIMAL(8,2) DEFAULT 400,
    
    -- ミネラル目標値
    calcium_mg_target DECIMAL(8,2) DEFAULT 1000,
    iron_mg_target DECIMAL(8,2) DEFAULT 8,
    magnesium_mg_target DECIMAL(8,2) DEFAULT 400,
    phosphorus_mg_target DECIMAL(8,2) DEFAULT 700,
    potassium_mg_target DECIMAL(8,2) DEFAULT 3500,
    zinc_mg_target DECIMAL(8,2) DEFAULT 11,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 食品データはfoods-0-5k.sqlからインポートされます

-- デフォルト栄養目標値の挿入
INSERT INTO nutrition_targets (user_id, energy_kcal_target, protein_g_target, fat_g_target, carbohydrate_g_target, fiber_g_target, sodium_mg_target, vitamin_c_mg_target, calcium_mg_target, iron_mg_target) VALUES
(1, 2000, 60, 65, 250, 25, 2300, 90, 1000, 8);

-- ビューの作成: 日別栄養摂取量集計
CREATE VIEW daily_nutrition_summary AS
SELECT 
    mr.user_id,
    mr.meal_date,
    SUM(f.カロリー * mr.quantity_g / 100) as total_energy_kcal,
    SUM(f.たんぱく質 * mr.quantity_g / 100) as total_protein_g,
    SUM(f.脂質 * mr.quantity_g / 100) as total_fat_g,
    SUM(f.炭水化物 * mr.quantity_g / 100) as total_carbohydrate_g,
    SUM(f.食物繊維総量 * mr.quantity_g / 100) as total_fiber_g,
    SUM(f.ナトリウム * mr.quantity_g / 100) as total_sodium_mg,
    SUM(f.ビタミンC * mr.quantity_g / 100) as total_vitamin_c_mg,
    SUM(f.カルシウム * mr.quantity_g / 100) as total_calcium_mg,
    SUM(f.鉄 * mr.quantity_g / 100) as total_iron_mg
FROM meal_records mr
JOIN foods f ON mr.food_id = f.id
GROUP BY mr.user_id, mr.meal_date;

-- ビューの作成: 食事別栄養摂取量集計
CREATE VIEW meal_nutrition_summary AS
SELECT 
    mr.user_id,
    mr.meal_date,
    mr.meal_type,
    SUM(f.カロリー * mr.quantity_g / 100) as meal_energy_kcal,
    SUM(f.たんぱく質 * mr.quantity_g / 100) as meal_protein_g,
    SUM(f.脂質 * mr.quantity_g / 100) as meal_fat_g,
    SUM(f.炭水化物 * mr.quantity_g / 100) as meal_carbohydrate_g
FROM meal_records mr
JOIN foods f ON mr.food_id = f.id
GROUP BY mr.user_id, mr.meal_date, mr.meal_type;