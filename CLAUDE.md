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

### Authentication Testing
- **Register new user**: POST to `/api/auth.php?action=register` with `{email, password, age, gender}`
- **Login existing user**: POST to `/api/auth.php?action=login` with `{email, password}`
- **Test protected APIs**: Include `Authorization: Bearer {token}` header for authenticated requests
- **Quick test token**: Can manually set token in browser console: `localStorage.setItem('authToken', 'your_token_here')`

## Architecture Overview

### Database Design
The system uses four core tables with specific relationships:
- **`users`** table stores authentication and profile data (email, password_hash, age, gender, auth_token)
- **`foods`** table contains 5,000+ Japanese food items with 100+ nutrition columns
- **`meal_records`** references `foods(id)` and `users(user_id)` via foreign key constraints
- **`nutrition_targets`** stores personalized daily nutritional goals based on user age/gender
- Two database views provide aggregated nutrition summaries (`daily_nutrition_summary`, `meal_nutrition_summary`)

### Authentication System
Token-based authentication with the following flow:
- **Registration**: Creates user with age/gender-based nutrition targets and generates 32-char hex token
- **Login**: Validates credentials and returns existing auth token
- **API Protection**: All protected endpoints use `getAuthenticatedUserId()` with Bearer token validation
- **Frontend Integration**: `NutritionApp` class manages authentication state and shows login/register interface

### API Architecture
RESTful PHP APIs with consistent patterns:
- **Auth API** (`auth.php`): User registration and login with token generation
- **Search API** (`food_search.php`): Intelligent food search with Japanese/English support
- **Recording API** (`meal_record.php`): Transaction-based meal recording with validation
- **Analytics API** (`nutrition_summary.php`): Multi-timeframe nutrition analysis (daily/weekly/monthly)

All APIs use PDO prepared statements, consistent error handling via `jsonResponse()`, and CORS headers.

### Frontend Architecture
Single-page application using vanilla JavaScript with `NutritionApp` class:
- **Authentication Flow**: Login/registration modal system with localStorage token management
- **Event-driven architecture** with clean separation of concerns
- **Chart System**: Three Chart.js visualizations with real-time preview capabilities
- **Dynamic form management** with real-time food search autocomplete and preview calculations
- **Modal-based UI**: Authentication, meal input, history browsing, and edit interfaces

## Key Implementation Details

### Authentication Integration
- **Token Storage**: Uses `localStorage.getItem('authToken')` for session persistence
- **API Headers**: All protected API calls include `Authorization: Bearer {token}` header via `getAuthHeaders()`
- **UI State Management**: `checkAuthentication()` shows login interface or main app based on token presence
- **Auto-targeting**: Registration automatically creates personalized nutrition targets based on age/gender

### Food Search System
- Uses LIKE queries with intelligent ranking (exact match → starts with → contains)
- Debounced input with minimum 2-character requirement
- Returns comprehensive nutrition data alongside search results
- Supports both Japanese and English food names

### Meal Recording Flow
1. **Authentication Check**: Verify user token before allowing data entry
2. **Food Selection**: Real-time search with autocomplete and nutrition preview
3. **Validation**: Date format (YYYY-MM-DD) and meal type validation
4. **Transaction Processing**: Atomic database operations with rollback on failure
5. **Chart Updates**: Automatic refresh of all visualizations after successful recording

### Chart Data Processing
- **Daily charts**: Show achievement rates vs personalized nutritional targets
- **Weekly charts**: Fill missing dates with zero values for consistent 7-day display
- **Preview system**: Real-time chart updates while user inputs meal data
- **Memory management**: All charts destroy and recreate on data updates to prevent memory leaks

## Database Table Relationships

**Critical**: The system recently migrated from `food_nutrition` table to `foods` table. Ensure all references use `foods` table:
- Foreign key: `meal_records.food_id` → `foods.id`
- Foreign key: `meal_records.user_id` → `users.user_id`
- All JOINs use `foods f ON mr.food_id = f.id`
- Views reference `foods` table in calculations

## Configuration Notes

- Database config in `api/config.php` points to external MySQL server (mysql80.mizy.sakura.ne.jp)
- No environment variables - direct configuration in PHP (consider moving to .env for security)
- UTF-8 encoding required for Japanese food names
- Error reporting enabled - disable for production
- CORS headers configured for cross-origin requests
- No build process - direct file deployment to server

## Common Issues

### Authentication Issues
If encountering 401 Unauthorized errors:
1. Verify user has valid token in localStorage (`authToken`)
2. Check that `getAuthHeaders()` is being called for protected API requests
3. Ensure the auth token exists in the `users` table
4. Verify the token is being sent as `Authorization: Bearer {token}` header

### Foreign Key Constraint Violations
If encountering "Cannot add or update a child row: a foreign key constraint fails", check:
1. The `foods` table exists and is populated
2. The `food_id` being inserted exists in `foods.id`
3. The `user_id` being inserted exists in `users.user_id`
4. The foreign key constraint references `foods(id)` not `food_nutrition(food_id)`

### Food Search Not Working
- Verify `foods` table has data with Japanese characters in `食品名` column
- Check that API returns `food_id` field (mapped from `id` column)
- Ensure autocomplete validation prevents submission of non-existent foods
- Verify authentication token is included in search requests

### Chart Display Issues
- Charts require data in specific format with numeric values
- Missing dates in weekly view are automatically filled with zeros
- Achievement rates calculated as (consumed/target) * 100
- Charts automatically refresh after successful meal recording

### Modal Interface Issues
- Modal elements require proper initialization in `NutritionApp.init()`
- Authentication modal shows/hides based on `localStorage.authToken` presence
- Food search autocomplete may not work if modal DOM elements are not properly loaded
- History modal requires calendar initialization and event listeners