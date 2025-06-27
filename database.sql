-- 栄養管理システム データベース設計

-- 食品栄養情報テーブル
CREATE TABLE food_nutrition (
    food_id INT PRIMARY KEY AUTO_INCREMENT,
    food_name VARCHAR(255) NOT NULL,
    food_name_en VARCHAR(255),
    category VARCHAR(100),
    
    -- 基本栄養素 (100gあたり)
    energy_kcal DECIMAL(8,2) DEFAULT 0,
    protein_g DECIMAL(8,2) DEFAULT 0,
    fat_g DECIMAL(8,2) DEFAULT 0,
    carbohydrate_g DECIMAL(8,2) DEFAULT 0,
    fiber_g DECIMAL(8,2) DEFAULT 0,
    sugar_g DECIMAL(8,2) DEFAULT 0,
    sodium_mg DECIMAL(8,2) DEFAULT 0,
    
    -- ビタミン類
    vitamin_a_ug DECIMAL(8,2) DEFAULT 0,
    vitamin_b1_mg DECIMAL(8,2) DEFAULT 0,
    vitamin_b2_mg DECIMAL(8,2) DEFAULT 0,
    vitamin_b3_mg DECIMAL(8,2) DEFAULT 0,
    vitamin_b6_mg DECIMAL(8,2) DEFAULT 0,
    vitamin_b12_ug DECIMAL(8,2) DEFAULT 0,
    vitamin_c_mg DECIMAL(8,2) DEFAULT 0,
    vitamin_d_ug DECIMAL(8,2) DEFAULT 0,
    vitamin_e_mg DECIMAL(8,2) DEFAULT 0,
    vitamin_k_ug DECIMAL(8,2) DEFAULT 0,
    folate_ug DECIMAL(8,2) DEFAULT 0,
    
    -- ミネラル類
    calcium_mg DECIMAL(8,2) DEFAULT 0,
    iron_mg DECIMAL(8,2) DEFAULT 0,
    magnesium_mg DECIMAL(8,2) DEFAULT 0,
    phosphorus_mg DECIMAL(8,2) DEFAULT 0,
    potassium_mg DECIMAL(8,2) DEFAULT 0,
    zinc_mg DECIMAL(8,2) DEFAULT 0,
    copper_mg DECIMAL(8,2) DEFAULT 0,
    manganese_mg DECIMAL(8,2) DEFAULT 0,
    selenium_ug DECIMAL(8,2) DEFAULT 0,
    
    -- 脂肪酸類
    saturated_fat_g DECIMAL(8,2) DEFAULT 0,
    monounsaturated_fat_g DECIMAL(8,2) DEFAULT 0,
    polyunsaturated_fat_g DECIMAL(8,2) DEFAULT 0,
    cholesterol_mg DECIMAL(8,2) DEFAULT 0,
    
    -- その他
    water_g DECIMAL(8,2) DEFAULT 0,
    ash_g DECIMAL(8,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_food_name (food_name),
    INDEX idx_category (category),
    FULLTEXT KEY ft_food_name (food_name, food_name_en)
);

-- 食事記録テーブル
CREATE TABLE meal_records (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT 1, -- 現在は単一ユーザー対応
    meal_date DATE NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    food_id INT NOT NULL,
    quantity_g DECIMAL(8,2) NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (food_id) REFERENCES food_nutrition(food_id),
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- サンプル食品データの挿入
INSERT INTO food_nutrition (food_name, food_name_en, category, energy_kcal, protein_g, fat_g, carbohydrate_g, fiber_g, sodium_mg, calcium_mg, iron_mg, vitamin_c_mg) VALUES
('白米', 'White Rice', '穀類', 356, 6.1, 0.9, 77.6, 0.5, 1, 5, 0.8, 0),
('玄米', 'Brown Rice', '穀類', 350, 6.8, 2.7, 71.8, 3.0, 1, 9, 2.1, 0),
('食パン', 'White Bread', '穀類', 264, 9.3, 4.4, 46.7, 2.3, 500, 29, 0.6, 0),
('鶏むね肉', 'Chicken Breast', '肉類', 191, 23.3, 11.6, 0, 0, 55, 4, 0.3, 1),
('豚ロース', 'Pork Loin', '肉類', 263, 19.3, 19.2, 0.2, 0, 54, 3, 0.6, 1),
('牛肩ロース', 'Beef Chuck', '肉類', 318, 17.9, 26.4, 0.3, 0, 48, 4, 0.9, 1),
('さけ', 'Salmon', '魚介類', 133, 22.3, 4.1, 0.1, 0, 59, 10, 0.5, 1),
('まぐろ', 'Tuna', '魚介類', 125, 26.4, 1.4, 0.1, 0, 49, 5, 1.1, 2),
('鶏卵', 'Chicken Egg', '卵類', 151, 12.3, 10.3, 0.3, 0, 140, 51, 1.8, 0),
('牛乳', 'Milk', '乳類', 67, 3.3, 3.8, 4.8, 0, 41, 110, 0.02, 1),
('ヨーグルト', 'Yogurt', '乳類', 62, 3.6, 3.0, 4.9, 0, 48, 120, 0.1, 1),
('キャベツ', 'Cabbage', '野菜類', 23, 1.3, 0.2, 5.2, 1.8, 5, 43, 0.3, 41),
('にんじん', 'Carrot', '野菜類', 39, 0.6, 0.1, 9.3, 2.5, 28, 28, 0.2, 4),
('たまねぎ', 'Onion', '野菜類', 37, 1.0, 0.1, 8.8, 1.6, 2, 21, 0.2, 8),
('トマト', 'Tomato', '野菜類', 19, 0.7, 0.1, 4.7, 1.0, 3, 7, 0.2, 15),
('ほうれん草', 'Spinach', '野菜類', 20, 2.2, 0.4, 3.1, 2.8, 16, 49, 2.0, 35),
('ブロッコリー', 'Broccoli', '野菜類', 33, 4.3, 0.5, 5.2, 4.4, 20, 38, 1.0, 120),
('りんご', 'Apple', '果実類', 54, 0.2, 0.1, 14.6, 1.5, 0, 3, 0.1, 4),
('バナナ', 'Banana', '果実類', 86, 1.1, 0.2, 22.5, 1.1, 0, 6, 0.3, 16),
('オレンジ', 'Orange', '果実類', 39, 1.0, 0.1, 10.4, 1.0, 1, 21, 0.1, 60);

-- デフォルト栄養目標値の挿入
INSERT INTO nutrition_targets (user_id, energy_kcal_target, protein_g_target, fat_g_target, carbohydrate_g_target, fiber_g_target, sodium_mg_target, vitamin_c_mg_target, calcium_mg_target, iron_mg_target) VALUES
(1, 2000, 60, 65, 250, 25, 2300, 90, 1000, 8);

-- ビューの作成: 日別栄養摂取量集計
CREATE VIEW daily_nutrition_summary AS
SELECT 
    mr.user_id,
    mr.meal_date,
    SUM(fn.energy_kcal * mr.quantity_g / 100) as total_energy_kcal,
    SUM(fn.protein_g * mr.quantity_g / 100) as total_protein_g,
    SUM(fn.fat_g * mr.quantity_g / 100) as total_fat_g,
    SUM(fn.carbohydrate_g * mr.quantity_g / 100) as total_carbohydrate_g,
    SUM(fn.fiber_g * mr.quantity_g / 100) as total_fiber_g,
    SUM(fn.sodium_mg * mr.quantity_g / 100) as total_sodium_mg,
    SUM(fn.vitamin_c_mg * mr.quantity_g / 100) as total_vitamin_c_mg,
    SUM(fn.calcium_mg * mr.quantity_g / 100) as total_calcium_mg,
    SUM(fn.iron_mg * mr.quantity_g / 100) as total_iron_mg
FROM meal_records mr
JOIN food_nutrition fn ON mr.food_id = fn.food_id
GROUP BY mr.user_id, mr.meal_date;

-- ビューの作成: 食事別栄養摂取量集計
CREATE VIEW meal_nutrition_summary AS
SELECT 
    mr.user_id,
    mr.meal_date,
    mr.meal_type,
    SUM(fn.energy_kcal * mr.quantity_g / 100) as meal_energy_kcal,
    SUM(fn.protein_g * mr.quantity_g / 100) as meal_protein_g,
    SUM(fn.fat_g * mr.quantity_g / 100) as meal_fat_g,
    SUM(fn.carbohydrate_g * mr.quantity_g / 100) as meal_carbohydrate_g
FROM meal_records mr
JOIN food_nutrition fn ON mr.food_id = fn.food_id
GROUP BY mr.user_id, mr.meal_date, mr.meal_type;