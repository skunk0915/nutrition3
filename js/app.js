class NutritionApp {
    constructor() {
        this.charts = {};
        this.nutrients = {
            'エネルギー': { target: 2000, unit: 'kcal', key: 'energy_kcal' },
            'タンパク質': { target: 60, unit: 'g', key: 'protein_g' },
            '脂質': { target: 65, unit: 'g', key: 'fat_g' },
            '飽和脂肪酸': { target: 18, unit: 'g', key: 'saturated_fat_g' },
            'n-6系脂肪酸': { target: 10, unit: 'g', key: 'n6_fat_g' },
            'n-3系脂肪酸': { target: 2, unit: 'g', key: 'n3_fat_g' },
            '炭水化物': { target: 250, unit: 'g', key: 'carbohydrate_g' },
            '食物繊維': { target: 25, unit: 'g', key: 'fiber_g' },
            'ビタミンA': { target: 800, unit: 'μg', key: 'vitamin_a_ug' },
            'ビタミンD': { target: 10, unit: 'μg', key: 'vitamin_d_ug' },
            'ビタミンE': { target: 15, unit: 'mg', key: 'vitamin_e_mg' },
            'ビタミンK': { target: 120, unit: 'μg', key: 'vitamin_k_ug' },
            'ビタミンB1': { target: 1.2, unit: 'mg', key: 'vitamin_b1_mg' },
            'ビタミンB2': { target: 1.4, unit: 'mg', key: 'vitamin_b2_mg' },
            'ビタミンB6': { target: 1.4, unit: 'mg', key: 'vitamin_b6_mg' },
            'ビタミンB12': { target: 2.4, unit: 'μg', key: 'vitamin_b12_ug' },
            'ナイアシン': { target: 16, unit: 'mg', key: 'niacin_mg' },
            '葉酸': { target: 400, unit: 'μg', key: 'folate_ug' },
            'パントテン酸': { target: 5, unit: 'mg', key: 'pantothenic_acid_mg' },
            'ビオチン': { target: 50, unit: 'μg', key: 'biotin_ug' },
            'ビタミンC': { target: 90, unit: 'mg', key: 'vitamin_c_mg' },
            'ナトリウム': { target: 2300, unit: 'mg', key: 'sodium_mg' },
            'カリウム': { target: 3500, unit: 'mg', key: 'potassium_mg' },
            'カルシウム': { target: 1000, unit: 'mg', key: 'calcium_mg' },
            'マグネシウム': { target: 400, unit: 'mg', key: 'magnesium_mg' },
            'リン': { target: 700, unit: 'mg', key: 'phosphorus_mg' },
            '鉄': { target: 8, unit: 'mg', key: 'iron_mg' },
            '亜鉛': { target: 11, unit: 'mg', key: 'zinc_mg' },
            '銅': { target: 1, unit: 'mg', key: 'copper_mg' },
            'マンガン': { target: 4, unit: 'mg', key: 'manganese_mg' },
            'ヨウ素': { target: 150, unit: 'μg', key: 'iodine_ug' },
            'セレン': { target: 60, unit: 'μg', key: 'selenium_ug' },
            'クロム': { target: 35, unit: 'μg', key: 'chromium_ug' },
            'モリブデン': { target: 45, unit: 'μg', key: 'molybdenum_ug' }
        };
        this.periodSettings = {
            startDate: null,
            endDate: null
        };
        this.init();
    }

    init() {
        this.initEventListeners();
        this.loadInitialData();
        this.initializePeriodControls();
    }

    initEventListeners() {
        // 食事記録フォーム
        document.getElementById('meal-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitMealRecord();
        });

        // 食品入力欄追加
        document.getElementById('add-food').addEventListener('click', () => {
            this.addFoodInput();
        });

        // 期間更新ボタン
        document.getElementById('update-period').addEventListener('click', () => {
            this.updatePeriod();
        });

        // 食品検索（動的に追加される要素に対応）
        document.addEventListener('input', (e) => {
            if (e.target.name === 'food_name[]') {
                this.handleFoodSearch(e.target);
            }
        });
    }

    initializePeriodControls() {
        const today = new Date();
        const oneWeekAgo = new Date(today.getTime() - 6 * 24 * 60 * 60 * 1000);
        
        const startDate = oneWeekAgo.toISOString().split('T')[0];
        const endDate = today.toISOString().split('T')[0];
        
        document.getElementById('start-date').value = startDate;
        document.getElementById('end-date').value = endDate;
        
        this.periodSettings.startDate = startDate;
        this.periodSettings.endDate = endDate;
    }

    loadInitialData() {
        this.loadDailyNutrition();
        this.loadWeeklyTrend();
    }

    async loadDailyNutrition() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await fetch(`api/nutrition_summary.php?type=daily_by_meal&date=${today}`);
            const data = await response.json();
            
            if (data.error) {
                console.error('データ取得エラー:', data.error);
                return;
            }
            
            this.createStackedBarChart(data);
        } catch (error) {
            console.error('API呼び出しエラー:', error);
        }
    }

    createStackedBarChart(data) {
        const ctx = document.getElementById('daily-nutrition-chart').getContext('2d');
        
        if (this.charts.daily) {
            this.charts.daily.destroy();
        }

        const mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
        const mealColors = {
            breakfast: 'rgba(255, 99, 132, 0.8)',
            lunch: 'rgba(54, 162, 235, 0.8)',
            dinner: 'rgba(255, 205, 86, 0.8)',
            snack: 'rgba(75, 192, 192, 0.8)'
        };
        const mealLabels = {
            breakfast: '朝食',
            lunch: '昼食',
            dinner: '夕食',
            snack: '間食'
        };

        const datasets = mealTypes.map(mealType => ({
            label: mealLabels[mealType],
            data: Object.keys(this.nutrients).map(nutrient => {
                const nutrientKey = this.nutrients[nutrient].key;
                const target = this.nutrients[nutrient].target;
                const meal = data.meals.find(m => m.meal_type === mealType);
                const value = meal ? parseFloat(meal[nutrientKey] || 0) : 0;
                return (value / target) * 100; // 達成率に変換
            }),
            backgroundColor: mealColors[mealType],
            borderColor: mealColors[mealType].replace('0.8', '1'),
            borderWidth: 1
        }));

        this.charts.daily = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(this.nutrients),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: '栄養素'
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 200,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: '充足率 (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const nutrient = context.label;
                                const mealType = context.dataset.label;
                                return `${mealType}: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                }
            }
        });
    }

    async loadWeeklyTrend() {
        try {
            const params = new URLSearchParams({
                type: 'weekly',
                start_date: this.periodSettings.startDate,
                end_date: this.periodSettings.endDate
            });
            
            const response = await fetch(`api/nutrition_summary.php?${params}`);
            const data = await response.json();
            
            if (data.error) {
                console.error('データ取得エラー:', data.error);
                return;
            }
            
            this.createWeeklyChart(data);
        } catch (error) {
            console.error('API呼び出しエラー:', error);
        }
    }

    createWeeklyChart(data) {
        const ctx = document.getElementById('weekly-trend-chart').getContext('2d');
        
        if (this.charts.weekly) {
            this.charts.weekly.destroy();
        }

        const labels = data.data.map(item => {
            const date = new Date(item.meal_date);
            return `${date.getMonth() + 1}/${date.getDate()}`;
        });

        // 主要な4つの栄養素のみ表示
        const mainNutrients = ['エネルギー', 'タンパク質', '脂質', '炭水化物'];
        const colors = ['#667eea', '#f093fb', '#f6d55c', '#20bf6b'];

        const datasets = mainNutrients.map((nutrient, index) => {
            const nutrientInfo = this.nutrients[nutrient];
            return {
                label: `${nutrient}達成率 (%)`,
                data: data.data.map(item => {
                    const value = parseFloat(item[nutrientInfo.key] || 0);
                    return (value / nutrientInfo.target) * 100;
                }),
                borderColor: colors[index],
                backgroundColor: colors[index].replace('1)', '0.1)'),
                tension: 0.4
            };
        });

        this.charts.weekly = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 150,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        title: {
                            display: true,
                            text: '達成率 (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }

    updatePeriod() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;
        
        if (!startDate || !endDate) {
            alert('開始日と終了日を選択してください');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('開始日は終了日より前の日付を選択してください');
            return;
        }
        
        this.periodSettings.startDate = startDate;
        this.periodSettings.endDate = endDate;
        
        // ローカルストレージに保存
        localStorage.setItem('nutritionPeriod', JSON.stringify(this.periodSettings));
        
        this.loadWeeklyTrend();
    }

    async handleFoodSearch(input) {
        const query = input.value.trim();
        const suggestionsDiv = input.parentElement.querySelector('.suggestions');
        
        if (query.length < 2) {
            suggestionsDiv.innerHTML = '';
            return;
        }

        try {
            const response = await fetch(`api/food_search.php?q=${encodeURIComponent(query)}&limit=10`);
            const foods = await response.json();
            
            suggestionsDiv.innerHTML = '';
            
            foods.forEach(food => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.textContent = `${food.food_name} (${food.energy_kcal}kcal/100g)`;
                div.addEventListener('click', () => {
                    input.value = food.food_name;
                    input.dataset.foodId = food.food_id;
                    suggestionsDiv.innerHTML = '';
                });
                suggestionsDiv.appendChild(div);
            });
        } catch (error) {
            console.error('食品検索エラー:', error);
        }
    }

    addFoodInput() {
        const container = document.getElementById('food-inputs');
        const newInput = document.createElement('div');
        newInput.className = 'food-input';
        newInput.innerHTML = `
            <div class="input-group">
                <label>食品名:</label>
                <input type="text" name="food_name[]" placeholder="食品名を入力" required>
                <div class="suggestions"></div>
            </div>
            <div class="input-group">
                <label>分量 (g):</label>
                <input type="number" name="quantity[]" min="1" required>
            </div>
            <button type="button" class="remove-food">削除</button>
        `;
        
        newInput.querySelector('.remove-food').addEventListener('click', () => {
            newInput.remove();
        });
        
        container.appendChild(newInput);
    }

    async submitMealRecord() {
        const formData = new FormData(document.getElementById('meal-form'));
        const date = formData.get('date');
        const mealType = formData.get('meal_type');
        const foodNames = formData.getAll('food_name[]');
        const quantities = formData.getAll('quantity[]');
        
        const foods = [];
        for (let i = 0; i < foodNames.length; i++) {
            const input = document.querySelector(`input[name="food_name[]"][value="${foodNames[i]}"]`);
            const foodId = input ? input.dataset.foodId : null;
            
            if (!foodId) {
                alert(`${foodNames[i]} は有効な食品を選択してください`);
                return;
            }
            
            foods.push({
                food_id: parseInt(foodId),
                quantity: parseFloat(quantities[i])
            });
        }
        
        try {
            const response = await fetch('api/meal_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    date: date,
                    meal_type: mealType,
                    foods: foods
                })
            });
            
            const result = await response.json();
            
            if (result.error) {
                alert('記録エラー: ' + result.error);
                return;
            }
            
            alert('食事記録が追加されました！');
            document.getElementById('meal-form').reset();
            
            // グラフを更新
            this.loadDailyNutrition();
            this.loadWeeklyTrend();
            
        } catch (error) {
            alert('記録エラー: ' + error.message);
        }
    }
}

// アプリケーション初期化
document.addEventListener('DOMContentLoaded', () => {
    new NutritionApp();
});