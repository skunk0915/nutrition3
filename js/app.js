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
        this.currentCalendarDate = new Date();
        this.recordDates = [];
        this.selectedDate = null;
        this.previewData = null;
        this.init();
    }

    init() {
        this.initEventListeners();
        this.initializeDateInputs();
        this.loadInitialData();
        this.initializePeriodControls();
    }

    hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
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

        // 日別栄養チャート日付変更
        document.getElementById('daily-chart-date').addEventListener('change', () => {
            this.loadDailyNutritionForDate();
        });

        // 履歴ボタン
        document.getElementById('open-history').addEventListener('click', () => {
            this.openHistoryModal();
        });

        // 食事入力モーダル開閉
        document.getElementById('open-meal-modal').addEventListener('click', () => {
            this.openMealModal();
        });

        document.getElementById('close-meal-modal').addEventListener('click', () => {
            this.closeMealModal();
        });

        // モーダル背景クリックで閉じる
        document.getElementById('meal-modal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('meal-modal')) {
                this.closeMealModal();
            }
        });

        // 日付変更時のプレビュー更新
        document.getElementById('date').addEventListener('change', () => {
            this.updatePreview();
        });

        // モーダル閉じるボタン
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('close')) {
                this.closeModals();
            }
            if (e.target.classList.contains('modal')) {
                this.closeModals();
            }
        });

        // 食品検索（動的に追加される要素に対応）
        document.addEventListener('input', (e) => {
            if (e.target.name === 'food_name[]') {
                this.handleFoodSearch(e.target);
            }
            // 数量変更時のプレビュー更新
            if (e.target.name === 'quantity[]') {
                this.updatePreview();
            }
        });

        // 食事タイプ変更時のプレビュー更新
        document.getElementById('meal-type').addEventListener('change', () => {
            this.updatePreview();
        });
    }

    initializeDateInputs() {
        // タイムゾーンを考慮した今日の日付を取得
        const today = new Date();
        const todayString = today.getFullYear() + '-' + 
                          String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(today.getDate()).padStart(2, '0');
        
        // 食事入力フォームの日付を今日に設定
        document.getElementById('date').value = todayString;
        // 日別栄養チャートの日付を今日に設定
        document.getElementById('daily-chart-date').value = todayString;
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

    async loadDailyNutrition() {
        let date = document.getElementById('daily-chart-date').value;
        if (!date) {
            const today = new Date();
            date = today.getFullYear() + '-' + 
                   String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(today.getDate()).padStart(2, '0');
        }
        
        try {
            const response = await fetch(`api/nutrition_summary.php?type=daily_by_meal&date=${date}`);
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

    async loadDailyNutritionForDate() {
        await this.loadDailyNutrition();
    }

    loadInitialData() {
        this.loadDailyNutrition();
        this.loadWeeklyTrend();
    }

    createStackedBarChart(data) {
        this.createStackedBarChartWithPreview(data, null, null);
    }

    clearPreview() {
        this.previewData = null;
        this.loadDailyNutrition();
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

        // 全栄養素を表示（色を循環させる）
        const colors = [
            '#667eea', '#f093fb', '#f6d55c', '#20bf6b', '#ff6b6b', '#4ecdc4', 
            '#45b7d1', '#96ceb4', '#ffeaa7', '#dda0dd', '#98d8c8', '#f7dc6f',
            '#bb8fce', '#85c1e9', '#f8c471', '#82e0aa', '#f1948a', '#85929e',
            '#d5a6bd', '#a9cce3', '#f9e79f', '#a2d9ce', '#d7bde2', '#aed6f1',
            '#fad7a0', '#a9dfbf', '#f5b7b1', '#d0d3d4', '#e8daef', '#d6eaf8',
            '#fcf3cf', '#d1f2eb', '#fadbd8', '#eaeded', '#f4ecf7', '#ebf5fb'
        ];

        const datasets = Object.keys(this.nutrients).map((nutrient, index) => {
            const nutrientInfo = this.nutrients[nutrient];
            return {
                label: `${nutrient}`,
                data: data.data.map(item => {
                    const value = Math.max(0, parseFloat(item[nutrientInfo.key] || 0));
                    return Math.max(0, (value / nutrientInfo.target) * 100);
                }),
                borderColor: colors[index % colors.length],
                backgroundColor: this.hexToRgba(colors[index % colors.length], 0.1),
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3
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

    async updatePreview() {
        const mealType = document.getElementById('meal-type').value;
        if (!mealType) return;

        const previewNutrition = this.calculatePreviewNutrition();
        if (!previewNutrition) return;

        try {
            let date = document.getElementById('daily-chart-date').value;
            if (!date) {
                const today = new Date();
                date = today.getFullYear() + '-' + 
                       String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(today.getDate()).padStart(2, '0');
            }
            
            const response = await fetch(`api/nutrition_summary.php?type=daily_by_meal&date=${date}`);
            const data = await response.json();
            
            if (data.error) {
                console.error('データ取得エラー:', data.error);
                return;
            }
            
            this.createStackedBarChartWithPreview(data, previewNutrition, mealType);
        } catch (error) {
            console.error('プレビュー更新エラー:', error);
        }
    }

    calculatePreviewNutrition() {
        const foodInputs = document.querySelectorAll('input[name="food_name[]"]');
        const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
        
        let totalNutrition = {};
        
        for (let i = 0; i < foodInputs.length; i++) {
            const foodInput = foodInputs[i];
            const quantityInput = quantityInputs[i];
            
            if (!foodInput.dataset.nutritionData || !quantityInput.value) continue;
            
            try {
                const nutritionData = JSON.parse(foodInput.dataset.nutritionData);
                const quantity = parseFloat(quantityInput.value);
                
                Object.keys(this.nutrients).forEach(nutrientName => {
                    const nutrientKey = this.nutrients[nutrientName].key;
                    const nutrientValue = parseFloat(nutritionData[nutrientKey] || 0);
                    const calculatedValue = (nutrientValue * quantity) / 100;
                    
                    if (!totalNutrition[nutrientKey]) {
                        totalNutrition[nutrientKey] = 0;
                    }
                    totalNutrition[nutrientKey] += calculatedValue;
                });
            } catch (error) {
                console.error('栄養計算エラー:', error);
            }
        }
        
        return Object.keys(totalNutrition).length > 0 ? totalNutrition : null;
    }

    createStackedBarChartWithPreview(data, previewNutrition, previewMealType) {
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
        const previewColors = {
            breakfast: 'rgba(255, 99, 132, 0.4)',
            lunch: 'rgba(54, 162, 235, 0.4)',
            dinner: 'rgba(255, 205, 86, 0.4)',
            snack: 'rgba(75, 192, 192, 0.4)'
        };
        const mealLabels = {
            breakfast: '朝食',
            lunch: '昼食',
            dinner: '夕食',
            snack: '間食'
        };

        const datasets = [];
        
        // 既存の記録データセット
        mealTypes.forEach(mealType => {
            datasets.push({
                label: mealLabels[mealType],
                data: Object.keys(this.nutrients).map(nutrient => {
                    const nutrientKey = this.nutrients[nutrient].key;
                    const target = this.nutrients[nutrient].target;
                    const meal = data.meals.find(m => m.meal_type === mealType);
                    const value = meal ? Math.max(0, parseFloat(meal[nutrientKey] || 0)) : 0;
                    return Math.max(0, (value / target) * 100);
                }),
                backgroundColor: mealColors[mealType],
                borderColor: mealColors[mealType].replace('0.8', '1'),
                borderWidth: 1
            });
        });

        // プレビューデータセット
        if (previewNutrition && previewMealType) {
            datasets.push({
                label: mealLabels[previewMealType] + ' (プレビュー)',
                data: Object.keys(this.nutrients).map(nutrient => {
                    const nutrientKey = this.nutrients[nutrient].key;
                    const target = this.nutrients[nutrient].target;
                    const value = Math.max(0, parseFloat(previewNutrition[nutrientKey] || 0));
                    return Math.max(0, (value / target) * 100);
                }),
                backgroundColor: previewColors[previewMealType],
                borderColor: mealColors[previewMealType].replace('0.8', '1'),
                borderWidth: 2,
                borderDash: [5, 5]
            });
        }

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
                                const mealType = context.dataset.label;
                                return `${mealType}: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                }
            }
        });
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
                    input.dataset.nutritionData = JSON.stringify(food);
                    console.log(`Food selected: ${food.food_name}, ID: ${food.food_id}`);
                    console.log('Input dataset after selection:', input.dataset);
                    suggestionsDiv.innerHTML = '';
                    this.updatePreview();
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
        
        console.log('Submit data:', { date, mealType, foodNames, quantities });
        
        const foods = [];
        const inputs = document.querySelectorAll('input[name="food_name[]"]');
        
        for (let i = 0; i < foodNames.length; i++) {
            let foodId = null;
            
            // 対応するinput要素を見つける
            for (let j = 0; j < inputs.length; j++) {
                if (inputs[j].value === foodNames[i]) {
                    foodId = inputs[j].dataset.foodId;
                    console.log(`Found food ID for ${foodNames[i]}: ${foodId}`);
                    break;
                }
            }
            
            if (!foodId) {
                console.error(`No food ID found for: ${foodNames[i]}`);
                console.log('All inputs:', inputs);
                inputs.forEach((input, idx) => {
                    console.log(`Input ${idx}: value='${input.value}', foodId='${input.dataset.foodId}'`);
                });
                alert(`${foodNames[i]} は有効な食品を選択してください`);
                return;
            }
            
            foods.push({
                food_id: parseInt(foodId),
                quantity: parseFloat(quantities[i])
            });
        }
        
        console.log('Foods to submit:', foods);
        
        try {
            const requestBody = {
                date: date,
                meal_type: mealType,
                foods: foods
            };
            
            console.log('Request body:', requestBody);
            
            const response = await fetch('api/meal_record.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const result = await response.json();
            console.log('Response result:', result);
            
            if (result.error) {
                console.error('API Error:', result.error);
                alert('記録エラー: ' + result.error);
                return;
            }
            
            console.log('Meal record saved successfully');
            alert('食事記録が追加されました！');
            document.getElementById('meal-form').reset();
            this.clearPreview();
            
            // グラフを更新
            console.log('Updating charts after meal record...');
            this.loadDailyNutrition();
            this.loadWeeklyTrend();
            
        } catch (error) {
            console.error('Fetch error:', error);
            alert('記録エラー: ' + error.message);
        }
    }

    // 履歴モーダルを開く
    async openHistoryModal() {
        document.getElementById('history-modal').style.display = 'block';
        await this.loadRecordDates();
        this.renderCalendar();
        this.loadHistoryList();
    }

    // モーダルを閉じる
    closeModals() {
        document.getElementById('history-modal').style.display = 'none';
        document.getElementById('edit-modal').style.display = 'none';
    }

    // 記録がある日付を取得
    async loadRecordDates() {
        try {
            const response = await fetch('api/meal_record.php?history=1');
            const data = await response.json();
            this.recordDates = data.map(item => item.meal_date);
        } catch (error) {
            console.error('記録日付取得エラー:', error);
        }
    }

    // カレンダーを描画
    renderCalendar() {
        const calendar = document.getElementById('calendar');
        const year = this.currentCalendarDate.getFullYear();
        const month = this.currentCalendarDate.getMonth();
        
        const monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        
        const firstDay = new Date(year, month, 1);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        let html = `
            <div class="calendar-header">
                <button class="calendar-nav" onclick="app.previousMonth()">&lt;</button>
                <span>${year}年 ${monthNames[month]}</span>
                <button class="calendar-nav" onclick="app.nextMonth()">&gt;</button>
            </div>
            <div class="calendar-grid">
        `;
        
        // 曜日ヘッダー
        dayNames.forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });
        
        // 日付
        for (let i = 0; i < 42; i++) {
            const date = new Date(startDate);
            date.setDate(startDate.getDate() + i);
            
            // タイムゾーンを考慮したローカル日付文字列を生成
            const dateStr = date.getFullYear() + '-' + 
                          String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(date.getDate()).padStart(2, '0');
            const isCurrentMonth = date.getMonth() === month;
            const hasRecords = this.recordDates.includes(dateStr);
            const isSelected = this.selectedDate === dateStr;
            
            let classes = ['calendar-day'];
            if (!isCurrentMonth) classes.push('other-month');
            if (hasRecords) classes.push('has-records');
            if (isSelected) classes.push('selected');
            
            html += `<div class="${classes.join(' ')}" onclick="app.selectDate('${dateStr}')">${date.getDate()}</div>`;
        }
        
        html += '</div>';
        calendar.innerHTML = html;
    }

    // カレンダー操作
    previousMonth() {
        this.currentCalendarDate.setMonth(this.currentCalendarDate.getMonth() - 1);
        this.renderCalendar();
    }

    nextMonth() {
        this.currentCalendarDate.setMonth(this.currentCalendarDate.getMonth() + 1);
        this.renderCalendar();
    }

    selectDate(dateStr) {
        this.selectedDate = dateStr;
        this.renderCalendar();
        this.loadHistoryList(dateStr);
    }

    // 履歴リストを読み込み
    async loadHistoryList(filterDate = null) {
        try {
            const url = filterDate ? `api/meal_record.php?date=${filterDate}` : 'api/meal_record.php?history=1';
            const response = await fetch(url);
            const responseText = await response.text();
            
            // Debug: PHPエラーをチェック
            if (responseText.includes('<br />') || responseText.includes('Warning:') || responseText.includes('Fatal error:')) {
                console.error('PHPエラー:', responseText);
                return;
            }
            
            const data = JSON.parse(responseText);
            
            if (filterDate) {
                this.renderDayHistory(data, filterDate);
            } else {
                this.renderFullHistory();
            }
        } catch (error) {
            console.error('履歴取得エラー:', error);
        }
    }

    // 特定日の履歴を表示
    async renderDayHistory(records, date) {
        const historyList = document.getElementById('history-list');
        if (records.length === 0) {
            historyList.innerHTML = '<p>この日の記録はありません。</p>';
            return;
        }
        
        const mealTypes = {
            'breakfast': '朝食',
            'lunch': '昼食', 
            'dinner': '夕食',
            'snack': '間食'
        };
        
        // 食事タイプごとにグループ化
        const groupedRecords = {};
        records.forEach(record => {
            if (!groupedRecords[record.meal_type]) {
                groupedRecords[record.meal_type] = [];
            }
            groupedRecords[record.meal_type].push(record);
        });
        
        // 日付文字列を直接パースして一貫した表示形式にする
        const dateParts = date.split('-');
        const year = parseInt(dateParts[0]);
        const month = parseInt(dateParts[1]);
        const day = parseInt(dateParts[2]);
        const monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
        const formattedDate = `${year}年${monthNames[month - 1]}${day}日`;
        
        let html = `<div class="history-list">
            <div class="date-group">
                <div class="date-header">
                    <span>${formattedDate}</span>
                    <span>${records.length}件</span>
                </div>`;
        
        Object.keys(groupedRecords).forEach(mealType => {
            html += `<div class="meal-group">
                <div class="meal-header">
                    <span class="meal-type-label">${mealTypes[mealType]}</span>
                    <span>${groupedRecords[mealType].length}件</span>
                </div>`;
            
            groupedRecords[mealType].forEach(record => {
                const totalCalories = (record.energy_kcal * record.quantity_g / 100).toFixed(1);
                html += `<div class="record-item">
                    <div class="food-info">
                        <div class="food-name">${record.food_name}</div>
                        <div class="food-details">${record.quantity_g}g (${totalCalories}kcal)</div>
                    </div>
                    <div class="record-actions">
                        <button class="btn-edit" onclick="app.editRecord(${record.record_id})">編集</button>
                        <button class="btn-delete" onclick="app.deleteRecord(${record.record_id})">削除</button>
                    </div>
                </div>`;
            });
            
            html += '</div>';
        });
        
        html += '</div></div>';
        historyList.innerHTML = html;
    }

    // 全履歴を表示
    async renderFullHistory() {
        const historyList = document.getElementById('history-list');
        historyList.innerHTML = '<p>日付を選択すると、その日の記録が表示されます。</p>';
    }

    // 記録編集
    async editRecord(recordId) {
        try {
            const response = await fetch(`api/meal_record.php?record_id=${recordId}`);
            const record = await response.json();
            
            // 編集モーダルに値を設定
            document.getElementById('edit-date').value = record.meal_date;
            document.getElementById('edit-meal-type').value = record.meal_type;
            
            // 編集モーダルを表示
            document.getElementById('edit-modal').style.display = 'block';
            
        } catch (error) {
            console.error('記録取得エラー:', error);
            alert('記録の取得に失敗しました');
        }
    }

    // 記録削除
    async deleteRecord(recordId) {
        if (!confirm('この記録を削除しますか？')) return;
        
        try {
            const response = await fetch('api/meal_record.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ record_id: recordId })
            });
            
            const result = await response.json();
            
            if (result.error) {
                alert('削除エラー: ' + result.error);
                return;
            }
            
            alert('記録が削除されました');
            
            // 履歴を再読み込み
            await this.loadRecordDates();
            this.renderCalendar();
            if (this.selectedDate) {
                this.loadHistoryList(this.selectedDate);
            }
            
            // グラフも更新
            this.loadDailyNutrition();
            this.loadWeeklyTrend();
            
        } catch (error) {
            alert('削除エラー: ' + error.message);
        }
    }

    // 食事入力モーダル開閉
    openMealModal() {
        document.getElementById('meal-modal').style.display = 'block';
        this.initializeDateInputs();
        this.setupFoodInputEventListeners();
        this.updatePreview();
    }

    closeMealModal() {
        document.getElementById('meal-modal').style.display = 'none';
        this.resetMealForm();
    }

    resetMealForm() {
        document.getElementById('meal-form').reset();
        const foodEntries = document.querySelector('.food-entries');
        foodEntries.innerHTML = `
            <div class="food-entry">
                <div class="input-group">
                    <label for="food-name">食品名:</label>
                    <input type="text" id="food-name" name="food_name[]" placeholder="食品名を入力" required>
                    <div class="suggestions" id="suggestions"></div>
                </div>
                
                <div class="input-group">
                    <label for="quantity">分量 (g):</label>
                    <input type="number" id="quantity" name="quantity[]" min="1" required>
                </div>
            </div>
        `;
        this.setupFoodInputEventListeners();
    }

    // プレビュー機能
    async updatePreview() {
        const date = document.getElementById('date').value;
        if (!date) return;

        try {
            // 選択した日付のデータを取得
            const response = await fetch(`api/nutrition_summary.php?date=${date}`);
            const data = await response.json();
            
            // 日別栄養チャートの日付を更新
            document.getElementById('daily-chart-date').value = date;
            
            // 現在の入力中のデータを含めてプレビュー
            this.previewData = data;
            this.loadDailyNutritionForDate();
            
        } catch (error) {
            console.error('プレビューデータ取得エラー:', error);
        }
    }

    // 食品入力フィールドのイベントリスナーを設定
    setupFoodInputEventListeners() {
        const foodInputs = document.querySelectorAll('input[name="food_name[]"]');
        const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
        
        foodInputs.forEach((input, index) => {
            // 既存のイベントリスナーを削除
            input.removeEventListener('input', this.handleFoodInput);
            input.removeEventListener('keydown', this.handleFoodKeydown);
            
            // 新しいイベントリスナーを追加
            input.addEventListener('input', (e) => this.handleFoodInput(e, index));
            input.addEventListener('keydown', (e) => this.handleFoodKeydown(e, index));
        });

        quantityInputs.forEach((input) => {
            input.removeEventListener('input', this.handleQuantityInput);
            input.addEventListener('input', () => this.handleQuantityInput());
        });
    }

    // 食品名入力のハンドラー
    async handleFoodInput(e, index) {
        const query = e.target.value.trim();
        const suggestionsElement = e.target.nextElementSibling;
        
        if (query.length < 2) {
            suggestionsElement.innerHTML = '';
            suggestionsElement.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`api/food_search.php?q=${encodeURIComponent(query)}&limit=50`);
            const foods = await response.json();
            
            if (foods.length > 0) {
                const html = foods.map(food => `
                    <div class="suggestion-item" data-food-id="${food.food_id}" data-food-name="${food.food_name}">
                        <strong>${food.food_name}</strong>
                        <span class="category">${food.category}</span>
                        <span class="calories">${food.energy_kcal}kcal/100g</span>
                    </div>
                `).join('');
                
                suggestionsElement.innerHTML = html;
                suggestionsElement.style.display = 'block';
                
                // 候補クリック時のイベントリスナー
                suggestionsElement.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', (event) => {
                        e.target.value = item.dataset.foodName;
                        e.target.dataset.foodId = item.dataset.foodId;
                        suggestionsElement.style.display = 'none';
                        this.updatePreviewWithCurrentInputs();
                    });
                });
            } else {
                suggestionsElement.innerHTML = '<div class="no-results">該当する食品が見つかりません</div>';
                suggestionsElement.style.display = 'block';
            }
        } catch (error) {
            console.error('食品検索エラー:', error);
        }
    }

    // キーボードナビゲーション
    handleFoodKeydown(e, index) {
        const suggestionsElement = e.target.nextElementSibling;
        const items = suggestionsElement.querySelectorAll('.suggestion-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                items[0].focus();
            }
        } else if (e.key === 'Escape') {
            suggestionsElement.style.display = 'none';
        }
    }

    // 分量入力のハンドラー
    handleQuantityInput() {
        this.updatePreviewWithCurrentInputs();
    }

    // 現在の入力内容でプレビューを更新
    updatePreviewWithCurrentInputs() {
        // 入力中の食品データを取得してプレビューに反映
        const inputs = document.querySelectorAll('input[name="food_name[]"]');
        const quantities = document.querySelectorAll('input[name="quantity[]"]');
        
        const inputFoods = [];
        inputs.forEach((input, index) => {
            const foodId = input.dataset.foodId;
            const quantity = parseFloat(quantities[index].value) || 0;
            
            if (foodId && quantity > 0) {
                inputFoods.push({ food_id: foodId, quantity: quantity });
            }
        });

        // プレビューデータに入力中の食品を追加してチャートを更新
        if (inputFoods.length > 0) {
            this.calculatePreviewWithInputs(inputFoods);
        }
    }

    // 入力データを含むプレビューを計算
    async calculatePreviewWithInputs(inputFoods) {
        // 実装は複雑になるため、簡単なバージョンとして
        // 現在の日付のデータを再読み込みして更新
        this.loadDailyNutritionForDate();
    }
}

// グローバル参照用
let app;

// アプリケーション初期化
document.addEventListener('DOMContentLoaded', () => {
    app = new NutritionApp();
});