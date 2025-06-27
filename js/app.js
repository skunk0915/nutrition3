class NutritionApp {
    constructor() {
        this.apiBase = 'api/';
        this.foodEntryCount = 1;
        this.charts = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setTodayDate();
        this.loadCharts();
    }

    setupEventListeners() {
        // 食事記録フォーム
        document.getElementById('meal-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitMealRecord();
        });

        // 食品追加ボタン
        document.getElementById('add-food').addEventListener('click', () => {
            this.addFoodEntry();
        });

        // 食品名入力でオートコンプリート
        this.setupFoodSearch();
    }

    setTodayDate() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;
    }

    setupFoodSearch() {
        const foodNameInput = document.getElementById('food-name');
        const suggestionsDiv = document.getElementById('suggestions');
        let searchTimeout;

        foodNameInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchFoods(query, suggestionsDiv, foodNameInput);
            }, 300);
        });

        // 入力フィールド外をクリックしたら候補を非表示
        document.addEventListener('click', (e) => {
            if (!foodNameInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
    }

    async searchFoods(query, suggestionsDiv, targetInput) {
        try {
            const response = await fetch(`${this.apiBase}food_search.php?q=${encodeURIComponent(query)}&limit=10`);
            const foods = await response.json();

            if (foods.error) {
                console.error('食品検索エラー:', foods.error);
                return;
            }

            this.displayFoodSuggestions(foods, suggestionsDiv, targetInput);
        } catch (error) {
            console.error('食品検索エラー:', error);
        }
    }

    displayFoodSuggestions(foods, suggestionsDiv, targetInput) {
        if (foods.length === 0) {
            suggestionsDiv.style.display = 'none';
            return;
        }

        suggestionsDiv.innerHTML = '';
        
        foods.forEach(food => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'suggestion-item';
            suggestionItem.innerHTML = `
                <strong>${food.food_name}</strong>
                <small>(${food.category} - ${food.energy_kcal}kcal/100g)</small>
            `;
            
            suggestionItem.addEventListener('click', () => {
                targetInput.value = food.food_name;
                targetInput.dataset.foodId = food.food_id;
                suggestionsDiv.style.display = 'none';
            });
            
            suggestionsDiv.appendChild(suggestionItem);
        });

        suggestionsDiv.style.display = 'block';
    }

    addFoodEntry() {
        this.foodEntryCount++;
        const foodEntriesDiv = document.querySelector('.food-entries');
        
        const newEntry = document.createElement('div');
        newEntry.className = 'food-entry';
        newEntry.innerHTML = `
            <div class="input-group">
                <label for="food-name-${this.foodEntryCount}">食品名:</label>
                <input type="text" id="food-name-${this.foodEntryCount}" name="food_name[]" placeholder="食品名を入力" required>
                <div class="suggestions" id="suggestions-${this.foodEntryCount}"></div>
            </div>
            
            <div class="input-group">
                <label for="quantity-${this.foodEntryCount}">分量 (g):</label>
                <input type="number" id="quantity-${this.foodEntryCount}" name="quantity[]" min="1" required>
            </div>
            
            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">削除</button>
        `;
        
        foodEntriesDiv.appendChild(newEntry);
        
        // 新しい入力フィールドにも検索機能を追加
        const newFoodInput = newEntry.querySelector(`#food-name-${this.foodEntryCount}`);
        const newSuggestionsDiv = newEntry.querySelector(`#suggestions-${this.foodEntryCount}`);
        
        let searchTimeout;
        newFoodInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                newSuggestionsDiv.style.display = 'none';
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.searchFoods(query, newSuggestionsDiv, newFoodInput);
            }, 300);
        });
    }

    async submitMealRecord() {
        const formData = new FormData(document.getElementById('meal-form'));
        const date = formData.get('date');
        const mealType = formData.get('meal_type');
        const foodNames = formData.getAll('food_name[]');
        const quantities = formData.getAll('quantity[]');

        if (!date || !mealType || foodNames.length === 0) {
            alert('すべての必須項目を入力してください。');
            return;
        }

        const foods = [];
        for (let i = 0; i < foodNames.length; i++) {
            const foodInput = document.querySelector(`input[name="food_name[]"]:nth-of-type(${i + 1})`);
            const foodId = foodInput?.dataset.foodId;
            
            if (!foodId) {
                alert(`食品「${foodNames[i]}」を候補から選択してください。`);
                return;
            }
            
            foods.push({
                food_id: parseInt(foodId),
                quantity: parseFloat(quantities[i])
            });
        }

        const mealData = {
            date: date,
            meal_type: mealType,
            foods: foods
        };

        try {
            const response = await fetch(`${this.apiBase}meal_record.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(mealData)
            });

            const result = await response.json();

            if (result.error) {
                alert('エラー: ' + result.error);
                return;
            }

            alert('食事記録が保存されました！');
            document.getElementById('meal-form').reset();
            this.setTodayDate();
            this.loadCharts(); // グラフの更新
            
        } catch (error) {
            console.error('食事記録エラー:', error);
            alert('記録の保存に失敗しました。');
        }
    }

    async loadCharts() {
        try {
            await Promise.all([
                this.loadDailyNutritionChart(),
                this.loadWeeklyTrendChart(),
                this.loadMonthlyIntakeChart()
            ]);
        } catch (error) {
            console.error('グラフ読み込みエラー:', error);
        }
    }

    async loadDailyNutritionChart() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await fetch(`${this.apiBase}nutrition_summary.php?type=daily&date=${today}`);
            const data = await response.json();

            if (data.error) {
                console.error('日次データエラー:', data.error);
                return;
            }

            const ctx = document.getElementById('daily-nutrition-chart').getContext('2d');
            
            if (this.charts.daily) {
                this.charts.daily.destroy();
            }

            const achievements = data.achievements;
            const labels = Object.keys(achievements);
            const rates = Object.values(achievements).map(item => item.rate);

            this.charts.daily = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '達成率 (%)',
                        data: rates,
                        backgroundColor: rates.map(rate => 
                            rate >= 100 ? '#28a745' : 
                            rate >= 80 ? '#ffc107' : 
                            rate >= 50 ? '#fd7e14' : '#dc3545'
                        ),
                        borderColor: '#333',
                        borderWidth: 1
                    }]
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
                        },
                        x: {
                            title: {
                                display: true,
                                text: '栄養素'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const nutrient = Object.keys(achievements)[context.dataIndex];
                                    const data = achievements[nutrient];
                                    const consumed = parseFloat(data.consumed) || 0;
                                    const target = parseFloat(data.target) || 1;
                                    return `${consumed.toFixed(1)}${data.unit} / ${target}${data.unit} (${context.parsed.y.toFixed(1)}%)`;
                                }
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('日次グラフエラー:', error);
        }
    }

    async loadWeeklyTrendChart() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await fetch(`${this.apiBase}nutrition_summary.php?type=weekly&date=${today}`);
            const data = await response.json();

            if (data.error) {
                console.error('週次データエラー:', data.error);
                return;
            }

            const ctx = document.getElementById('weekly-trend-chart').getContext('2d');
            
            if (this.charts.weekly) {
                this.charts.weekly.destroy();
            }

            const labels = data.data.map(item => {
                const date = new Date(item.meal_date);
                return `${date.getMonth() + 1}/${date.getDate()}`;
            });

            this.charts.weekly = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'エネルギー達成率 (%)',
                            data: data.data.map(item => (item.total_energy_kcal / 2000) * 100),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'タンパク質達成率 (%)',
                            data: data.data.map(item => (item.total_protein_g / 60) * 100),
                            borderColor: '#f093fb',
                            backgroundColor: 'rgba(240, 147, 251, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: '脂質達成率 (%)',
                            data: data.data.map(item => (item.total_fat_g / 65) * 100),
                            borderColor: '#f6d55c',
                            backgroundColor: 'rgba(246, 213, 92, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: '炭水化物達成率 (%)',
                            data: data.data.map(item => (item.total_carbohydrate_g / 250) * 100),
                            borderColor: '#20bf6b',
                            backgroundColor: 'rgba(32, 191, 107, 0.1)',
                            tension: 0.4
                        }
                    ]
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

        } catch (error) {
            console.error('週次グラフエラー:', error);
        }
    }

    async loadMonthlyIntakeChart() {
        try {
            const today = new Date().toISOString().split('T')[0];
            const response = await fetch(`${this.apiBase}nutrition_summary.php?type=monthly&date=${today}`);
            const data = await response.json();

            if (data.error) {
                console.error('月次データエラー:', data.error);
                return;
            }

            const ctx = document.getElementById('monthly-intake-chart').getContext('2d');
            
            if (this.charts.monthly) {
                this.charts.monthly.destroy();
            }

            const labels = data.data.map(item => {
                const date = new Date(item.meal_date);
                return date.getDate();
            });

            this.charts.monthly = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'エネルギー達成率 (%)',
                        data: data.data.map(item => (item.total_energy_kcal / 2000) * 100),
                        backgroundColor: data.data.map(item => {
                            const rate = (item.total_energy_kcal / 2000) * 100;
                            return rate >= 100 ? 'rgba(40, 167, 69, 0.8)' : 
                                   rate >= 80 ? 'rgba(255, 193, 7, 0.8)' : 
                                   rate >= 50 ? 'rgba(253, 126, 20, 0.8)' : 'rgba(220, 53, 69, 0.8)';
                        }),
                        borderColor: '#667eea',
                        borderWidth: 1
                    }]
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
                        },
                        x: {
                            title: {
                                display: true,
                                text: '日'
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

        } catch (error) {
            console.error('月次グラフエラー:', error);
        }
    }
}

// アプリケーション初期化
document.addEventListener('DOMContentLoaded', () => {
    new NutritionApp();
});

// 削除ボタン用のCSS追加
const style = document.createElement('style');
style.textContent = `
    .btn-remove {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 3px;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }
    
    .btn-remove:hover {
        background-color: #c82333;
    }
`;
document.head.appendChild(style);