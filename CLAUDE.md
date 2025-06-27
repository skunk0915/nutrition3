# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Database Operations
- **Import food database**: Execute `foods-0-5k.sql` to populate the comprehensive food nutrition database (5,000+ Japanese foods)
- **Setup database schema**: Execute `database.sql` to create tables, views, and constraints
- **Fix foreign key issues**: If encountering foreign key constraint violations, ensure the `foods` table exists and has the correct `id` column

### Development Workflow
- **Test APIs**: Access API endpoints directly via browser or curl for testing
- **View database**: No built-in admin interface - use phpMyAdmin or direct MySQL client
- **Deploy**: Simple file upload - no build process required

## Architecture Overview

### Database Design
The system uses three core tables with specific relationships:
- **`foods`** table contains 5,000+ Japanese food items with 100+ nutrition columns
- **`meal_records`** references `foods(id)` via foreign key constraint `meal_records_ibfk_1`
- **`nutrition_targets`** stores daily nutritional goals per user
- Two database views provide aggregated nutrition summaries (`daily_nutrition_summary`, `meal_nutrition_summary`)

### API Architecture
RESTful PHP APIs with consistent patterns:
- **Search API** (`food_search.php`): Intelligent food search with Japanese/English support
- **Recording API** (`meal_record.php`): Transaction-based meal recording with validation
- **Analytics API** (`nutrition_summary.php`): Multi-timeframe nutrition analysis (daily/weekly/monthly)

All APIs use PDO prepared statements, consistent error handling via `jsonResponse()`, and CORS headers.

### Frontend Architecture
Single-page application using vanilla JavaScript with `NutritionApp` class:
- Event-driven architecture with clean separation of concerns
- Three Chart.js visualizations: daily achievement bars, weekly trend lines, monthly intake bars
- Dynamic form management with real-time food search autocomplete
- Responsive CSS Grid layout with mobile-first design

## Key Implementation Details

### Food Search System
- Uses LIKE queries with intelligent ranking (exact match → starts with → contains)
- Debounced input with minimum 2-character requirement
- Returns basic nutrition data alongside search results

### Meal Recording Flow
1. Validate date format (YYYY-MM-DD) and meal type (breakfast/lunch/dinner/snack)
2. Validate food selections against database
3. Use database transactions for atomic operations
4. Calculate nutrition values based on quantity (per 100g base)

### Chart Data Processing
- Daily charts show achievement rates vs nutritional targets
- Weekly charts fill missing dates with zero values for consistent 7-day display
- Monthly charts aggregate by calendar month
- All charts destroy and recreate on data updates to prevent memory leaks

## Database Table Relationships

**Critical**: The system recently migrated from `food_nutrition` table to `foods` table. Ensure all references use `foods` table:
- Foreign key: `meal_records.food_id` → `foods.id`
- All JOINs use `foods f ON mr.food_id = f.id`
- Views reference `foods` table in calculations

## Configuration Notes

- Database config in `api/config.php` points to external MySQL server
- No environment variables - direct configuration in PHP
- UTF-8 encoding required for Japanese food names
- Error reporting enabled - disable for production

## Common Issues

### Foreign Key Constraint Violations
If encountering "Cannot add or update a child row: a foreign key constraint fails", check:
1. The `foods` table exists and is populated
2. The `food_id` being inserted exists in `foods.id`
3. The foreign key constraint references `foods(id)` not `food_nutrition(food_id)`

### Food Search Not Working
- Verify `foods` table has data with Japanese characters in `食品名` column
- Check that API returns `food_id` field (mapped from `id` column)
- Ensure autocomplete validation prevents submission of non-existent foods

### Chart Display Issues
- Charts require data in specific format with numeric values
- Missing dates in weekly view are automatically filled with zeros
- Achievement rates calculated as (consumed/target) * 100