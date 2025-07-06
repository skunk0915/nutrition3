-- 栄養目標値テーブルに新しい栄養素カラムを追加
ALTER TABLE nutrition_targets 
ADD COLUMN saturated_fat_g_target DECIMAL(8,2) DEFAULT 18,
ADD COLUMN n6_fat_g_target DECIMAL(8,2) DEFAULT 10,
ADD COLUMN n3_fat_g_target DECIMAL(8,2) DEFAULT 2,
ADD COLUMN niacin_mg_target DECIMAL(8,2) DEFAULT 16,
ADD COLUMN pantothenic_acid_mg_target DECIMAL(8,2) DEFAULT 5,
ADD COLUMN biotin_ug_target DECIMAL(8,2) DEFAULT 50,
ADD COLUMN copper_mg_target DECIMAL(8,2) DEFAULT 1,
ADD COLUMN manganese_mg_target DECIMAL(8,2) DEFAULT 4,
ADD COLUMN iodine_ug_target DECIMAL(8,2) DEFAULT 150,
ADD COLUMN selenium_ug_target DECIMAL(8,2) DEFAULT 60,
ADD COLUMN chromium_ug_target DECIMAL(8,2) DEFAULT 35,
ADD COLUMN molybdenum_ug_target DECIMAL(8,2) DEFAULT 45;

-- 既存のvitamin_b3_mg_targetをniacin_mg_targetに統一
ALTER TABLE nutrition_targets 
CHANGE COLUMN vitamin_b3_mg_target niacin_mg_target DECIMAL(8,2) DEFAULT 16;

-- 既存のユーザーの栄養目標値を更新
UPDATE nutrition_targets SET 
    saturated_fat_g_target = 18,
    n6_fat_g_target = 10,
    n3_fat_g_target = 2,
    niacin_mg_target = 16,
    pantothenic_acid_mg_target = 5,
    biotin_ug_target = 50,
    copper_mg_target = 1,
    manganese_mg_target = 4,
    iodine_ug_target = 150,
    selenium_ug_target = 60,
    chromium_ug_target = 35,
    molybdenum_ug_target = 45
WHERE target_id IS NOT NULL;