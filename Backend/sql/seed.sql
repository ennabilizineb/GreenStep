-- GreenStep seed data.
-- Run this AFTER schema.sql to populate the emission factor catalogue and tip library.
-- Emission factors are sourced from DEFRA (UK Government GHG Conversion Factors 2023)
-- and IPCC AR6 (2022) where DEFRA does not cover the category.
--
-- OWNERSHIP: Backend & API Lead (Mohammed Mohsen Alsakkaf — A23CS4026).
-- The schema tables themselves are owned by Member 3 (Database & Security Lead).

USE greenstep;

-- ============================================================
-- 1. Activity Types (emission factor catalogue)
--    kg_co2_per_unit = how many kg of CO2-equivalent one unit
--    of this activity produces. Negative values indicate a
--    net saving (e.g., recycling offsets landfill emissions).
-- ============================================================

INSERT INTO activity_types (category, name, unit, kg_co2_per_unit) VALUES

-- Transport (DEFRA 2023 — passenger vehicle averages)
('transport', 'Car',              'km',   0.1700),   -- average petrol/diesel car, 1 passenger
('transport', 'Public Transport', 'km',   0.0435),   -- average bus (city service)
('transport', 'Walking',          'km',   0.0000),   -- zero direct emissions
('transport', 'Cycling',          'km',   0.0000),   -- zero direct emissions

-- Energy (DEFRA 2023 — UK grid average electricity)
('energy',    'Electricity',      'kWh',  0.2120),   -- UK grid emission intensity (kgCO2e/kWh)

-- Food (IPCC AR6 2022 — lifecycle averages)
('food',      'Red Meat Meal',    'meal', 2.5000),   -- beef/lamb dominant meal
('food',      'Mixed Meal',       'meal', 1.2000),   -- mixed protein (chicken, fish, etc.)
('food',      'Vegetarian Meal',  'meal', 0.5000),   -- plant-based meal

-- Recycling (DEFRA 2023 — avoided landfill emissions, averaged across materials)
('recycling', 'Recycling Item',   'item', -0.1000);  -- offset per item diverted from landfill


-- ============================================================
-- 2. Eco-Tips library (sample entries for daily tip feature)
--    Admins can add more via POST /api/admin/tips.
-- ============================================================

INSERT INTO tips (title, body, category) VALUES

('Walk or Cycle Short Distances',
 'For trips under 2 km, walking or cycling produces zero carbon emissions and also improves cardiovascular health. Leave the car at home.',
 'transport'),

('Choose Public Transport',
 'A city bus emits roughly 4× less CO2 per passenger-km than a solo car journey. Choosing the bus or train even twice a week can meaningfully reduce your annual footprint.',
 'transport'),

('Try One Vegetarian Meal Today',
 'Replacing a single red-meat meal with a vegetarian alternative saves approximately 2 kg of CO2 — equivalent to leaving a 60 W light bulb off for over a week.',
 'food'),

('Unplug Chargers and Idle Devices',
 'Chargers and appliances left on standby account for up to 10 % of a typical household electricity bill. Unplugging them when not in use costs nothing and cuts your energy footprint.',
 'energy'),

('Recycle Consistently',
 'Recycling one aluminium can saves enough energy to power a television for 3 hours. Make recycling a non-negotiable daily habit rather than an afterthought.',
 'recycling'),

('Reduce Your Shower Time by 2 Minutes',
 'Cutting your shower from 8 minutes to 6 saves roughly 20 litres of hot water per day, reducing both your water and energy footprint — around 15 kg CO2 saved per month.',
 'energy'),

('Eat Seasonal and Locally Grown Food',
 'Locally grown, seasonal produce requires far less refrigerated transport than imported alternatives. Choosing it reduces the supply-chain emissions embedded in every meal.',
 'food'),

('Combine Car Trips',
 'Cold-engine starts produce disproportionately high emissions. Batching errands into one round trip instead of several short drives can cut transport-related CO2 by up to 30 %.',
 'transport'),

('Air-Dry Laundry Instead of Tumble Drying',
 'A typical tumble dryer uses 4–5 kWh per cycle — roughly 1 kg of CO2 per load on the average grid. Air-drying costs nothing and extends the life of your clothes.',
 'energy'),

('Carry a Reusable Bag and Bottle',
 'Producing a single-use plastic bag generates about 1.6 kg of CO2 over its lifecycle. A reusable bag used 50 times offsets that cost and eliminates the landfill burden.',
 'recycling');
