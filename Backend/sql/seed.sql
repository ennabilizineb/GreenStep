-- GreenStep seed data — must be run AFTER schema.sql.
-- Populates: roles, categories, activity_types (DEFRA/IPCC baselines), badges, tips.
-- OWNERSHIP: Backend & API Lead (Mohammed Mohsen Alsakkaf — A23CS4026).

USE greenstep;

-- ── 1. Roles ──────────────────────────────────────────────────────────────────
INSERT INTO Role (name) VALUES
('user'),      -- role_id = 1 : standard end-user
('leader'),    -- role_id = 2 : community / group leader
('admin');     -- role_id = 3 : platform administrator

-- ── 2. Categories ─────────────────────────────────────────────────────────────
INSERT INTO Category (name, description) VALUES
('transport',  'Transportation and daily commuting activities'),   -- category_id = 1
('energy',     'Household and office energy consumption'),          -- category_id = 2
('food',       'Dietary choices and meal-type selections'),         -- category_id = 3
('recycling',  'Waste reduction and recycling activities');         -- category_id = 4

-- ── 3. Populate Initial System Admin Account ────────────────────────────────
-- Required to satisfy the 'added_by' NOT NULL foreign key constraint in the Tip table.
-- Password hash corresponds to a secure temporary password verified via password_verify()
INSERT INTO `User` (`role_id`, `name`, `email`, `password_hash`, `joined_at`) VALUES
(3, 'System Administrator', 'admin@greenstep.com', '$2y$10$e0myXaGBy5Wb88BBA9PhCOV5Z4F0Wf4L4v.V1W6TzA7bM6zV8x6t2', NOW()); -- user_id = 1
-- ── 4. Activity Types ─────────────────────────────────────────────────────────
-- Emission factors sourced from:
--   DEFRA UK GHG Conversion Factors 2023 (transport, energy)
--   IPCC AR6 Working Group III (2022)  (food)
-- Negative kg_co2_per_unit = net carbon saving (recycling offsets landfill emissions).
INSERT INTO Activity_Type (category_id, name, unit, kg_co2_per_unit) VALUES
-- Transport  (category_id = 1)
(1, 'Car',              'km',   0.1700),   -- avg petrol/diesel car, 1 passenger
(1, 'Public Transport', 'km',   0.0435),   -- avg city bus service
(1, 'Walking',          'km',   0.0000),
(1, 'Cycling',          'km',   0.0000),
-- Energy  (category_id = 2)
(2, 'Electricity',      'kWh',  0.2120),   -- UK grid avg emission intensity
-- Food  (category_id = 3)
(3, 'Red Meat Meal',    'meal', 2.5000),   -- beef / lamb dominant
(3, 'Mixed Meal',       'meal', 1.2000),   -- chicken / fish / mixed protein
(3, 'Vegetarian Meal',  'meal', 0.5000),   -- plant-based
-- Recycling  (category_id = 4)
(4, 'Recycling Item',   'item', -0.1000);  -- avg saving per item diverted from landfill

-- ── 5. Badges ─────────────────────────────────────────────────────────────────
-- criteria_json is evaluated server-side in LogController::dashboard().
-- Supported criteria types:
--   { "type": "total_logs",    "threshold": N }
--   { "type": "streak_days",   "threshold": N }
--   { "type": "category_logs", "category": "<name>", "threshold": N }
-- image_url uses empty string '' as placeholder (schema is NOT NULL; frontend treats '' as no image)
INSERT INTO Badge (name, criteria_json, image_url) VALUES
('Eco Beginner',  '{"type":"total_logs","threshold":1}',                               ''),
('Green Streak',  '{"type":"streak_days","threshold":7}',                              ''),
('Recycling Pro', '{"type":"category_logs","category":"recycling","threshold":10}',    ''),
('Carbon Cutter', '{"type":"streak_days","threshold":30}',                             ''),
('Eco Warrior',   '{"type":"total_logs","threshold":50}',                              '');

-- ── 6. Tips ───────────────────────────────────────────────────────────────────
-- added_by = NULL means pre-seeded by the system (no admin account at seed time).
-- Admins can add more via POST /api/admin/tips.
INSERT INTO Tip (category_id, added_by, title, body, source_url) VALUES
(1, 1, 'Walk or Cycle Short Distances',
 'For trips under 2 km, walking or cycling produces zero carbon emissions and improves cardiovascular health. Leave the car at home.',
 NULL),

(1, 1, 'Choose Public Transport',
 'A city bus emits roughly 4× less CO2 per passenger-km than a solo car journey. Choosing the bus or train even twice a week makes a measurable difference.',
 NULL),

(3, 1, 'Try One Vegetarian Meal Today',
 'Replacing a single red-meat meal with a vegetarian alternative saves approximately 2 kg of CO2 — equivalent to leaving a 60 W bulb off for over a week.',
 NULL),

(2, 1, 'Unplug Chargers and Idle Devices',
 'Chargers and appliances left on standby account for up to 10 % of a typical household electricity bill. Unplugging them costs nothing and cuts your energy footprint.',
 NULL),

(4, 1, 'Recycle Consistently',
 'Recycling one aluminium can saves enough energy to power a television for 3 hours. Make recycling a non-negotiable daily habit.',
 NULL),

(2, 1, 'Reduce Your Shower Time by 2 Minutes',
 'Cutting your shower from 8 minutes to 6 saves roughly 20 litres of hot water per day, reducing both water and energy footprint — about 15 kg CO2 saved per month.',
 NULL),

(3, 1, 'Eat Seasonal and Locally Grown Food',
 'Locally grown, seasonal produce requires far less refrigerated transport than imported alternatives. Choosing it reduces supply-chain emissions embedded in every meal.',
 NULL),

(1, 1, 'Combine Car Trips',
 'Cold-engine starts produce disproportionately high emissions. Batching errands into one round trip instead of several short drives can cut transport CO2 by up to 30 %.',
 NULL),

(2, 1, 'Air-Dry Laundry Instead of Tumble Drying',
 'A typical tumble dryer uses 4–5 kWh per cycle — roughly 1 kg of CO2 per load. Air-drying costs nothing and extends the life of your clothes.',
 NULL),

(4, 1, 'Carry a Reusable Bag and Bottle',
 'A reusable bag used 50 times offsets its production cost and eliminates the landfill burden of single-use plastics entirely.',
 NULL);
