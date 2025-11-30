-- Demo seed: keep the existing users and add a moderate amount of dummy data
USE waste_not_kitchen;

-- Ensure demo users exist (do not delete existing users)
INSERT IGNORE INTO users (username, password, role, security_question, security_answer)
VALUES
  ('annas_rest', '$2y$10$gzAmL.wivUPBjFB0rYl6QuX0g/EnXPTKOr9tIm06R7fEZ1ckL0Iiq', 'restaurant', 'Q', 'A'),
  ('john_customer', '$2y$10$e2GIeb78.CWq5YPAkY1H6uggfvbjzQSF9rOC295zLEht1d/9m5tgm', 'customer', 'Q', 'A'),
  ('mary_donor', '$2y$12$PPlyDMuoagkiolV3XxT3yu8pt6OLePaDTGr66wCHFEg1/yK6dPSci', 'donor', 'Q', 'A'),
  ('paul_needy', '$2y$12$0TqOYa9cXSLHYdZyyfG5d.Iph473f0LLfgVPm3DtbkeIN93XfI5Hi', 'needy', 'Q', 'A');

-- Resolve user ids for later inserts
SET @annas_user_id = (SELECT id FROM users WHERE username='annas_rest' LIMIT 1);
SET @cust_user_id  = (SELECT id FROM users WHERE username='john_customer' LIMIT 1);
SET @donor_user_id = (SELECT id FROM users WHERE username='mary_donor' LIMIT 1);
SET @needy_user_id = (SELECT id FROM users WHERE username='paul_needy' LIMIT 1);

-- Ensure profiles exist (insert if missing)
INSERT INTO profiles (user_id, full_name, address, phone)
SELECT @annas_user_id, 'Anna''s Final Rest', '12 Test Blvd', '111-222-3333' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM profiles WHERE user_id = @annas_user_id);

INSERT INTO profiles (user_id, full_name, address, phone)
SELECT @cust_user_id, 'John Doe', '456 Oak Ave', '321-555-1212' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM profiles WHERE user_id = @cust_user_id);

INSERT INTO profiles (user_id, full_name, address, phone)
SELECT @donor_user_id, 'Mary Donor', '789 Pine Rd', '999-888-7777' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM profiles WHERE user_id = @donor_user_id);

INSERT INTO profiles (user_id, full_name, address, phone)
SELECT @needy_user_id, 'Paul Needy', 'No Fixed Address', '000-000-0000' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM profiles WHERE user_id = @needy_user_id);

-- Ensure restaurants row exists for annas_rest
INSERT INTO restaurants (user_id, restaurant_name)
SELECT @annas_user_id, 'Anna''s Final Rest' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM restaurants WHERE user_id = @annas_user_id);

SET @annas_rest_id = (SELECT id FROM restaurants WHERE user_id = @annas_user_id LIMIT 1);

-- Add a moderate set of plates for the restaurant (20 items)
INSERT INTO plates (restaurant_id, title, description, price, quantity, available_from, available_until)
VALUES
  (@annas_rest_id, 'Burger Deluxe', 'Juicy beef burger with cheese', 6.00, 30, '2025-11-01 08:00:00', '2026-01-01 23:59:59'),
  (@annas_rest_id, 'Hearty Soup', 'Warm vegetable soup', 4.50, 25, '2025-11-01 08:00:00', '2026-01-01 21:00:00'),
  (@annas_rest_id, 'Veggie Wrap', 'Fresh seasonal veggies in a wrap', 5.25, 20, '2025-11-01 08:00:00', '2026-01-01 21:00:00'),
  (@annas_rest_id, 'Chicken Salad', 'Grilled chicken over mixed greens', 7.00, 18, '2025-11-01 08:00:00', '2026-01-01 21:00:00'),
  (@annas_rest_id, 'Pasta Bolognese', 'Classic pasta with meat sauce', 8.50, 22, '2025-11-01 10:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Fish & Chips', 'Crispy fried fish with fries', 9.00, 16, '2025-11-01 11:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Grilled Cheese', 'Melted cheddar on sourdough', 4.00, 28, '2025-11-01 08:00:00', '2026-01-01 20:00:00'),
  (@annas_rest_id, 'Tomato Bruschetta', 'Toasted bread with fresh tomato', 3.75, 26, '2025-11-01 12:00:00', '2026-01-01 20:00:00'),
  (@annas_rest_id, 'Caesar Salad', 'Romaine with Caesar dressing', 6.50, 24, '2025-11-01 08:00:00', '2026-01-01 20:00:00'),
  (@annas_rest_id, 'Steak Sandwich', 'Sliced steak in a roll', 10.00, 12, '2025-11-01 12:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Veg Curry', 'Spicy vegetable curry with rice', 7.50, 20, '2025-11-01 09:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Sushi Platter', 'Assorted sushi pieces', 14.00, 10, '2025-11-01 12:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Beef Tacos', 'Three tacos with beef and salsa', 6.75, 20, '2025-11-01 11:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Falafel Bowl', 'Falafel with hummus and salad', 6.25, 22, '2025-11-01 10:00:00', '2026-01-01 21:00:00'),
  (@annas_rest_id, 'Chicken Curry', 'Mild chicken curry with rice', 8.25, 18, '2025-11-01 10:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Pancake Stack', 'Stack of fluffy pancakes', 5.50, 30, '2025-11-01 07:00:00', '2026-01-01 14:00:00'),
  (@annas_rest_id, 'Fruit Salad', 'Seasonal fruit mix', 3.50, 40, '2025-11-01 07:00:00', '2026-01-01 20:00:00'),
  (@annas_rest_id, 'Minestrone', 'Hearty Italian soup', 4.75, 24, '2025-11-01 09:00:00', '2026-01-01 20:00:00'),
  (@annas_rest_id, 'BBQ Ribs', 'Slow-cooked ribs with BBQ sauce', 12.00, 8, '2025-11-01 12:00:00', '2026-01-01 22:00:00'),
  (@annas_rest_id, 'Chocolate Cake', 'Rich chocolate slice', 3.95, 35, '2025-11-01 08:00:00', '2026-01-01 22:00:00');

-- Insert a set of orders (randomized quantities/status) for testing (50 orders)
-- Orders assigned to john_customer; use random selection from plates of this restaurant
INSERT INTO orders (plate_id, buyer_id, quantity, status, created_at)
SELECT p.id, @cust_user_id, FLOOR(1 + RAND()*4), ELT(1 + FLOOR(RAND()*3), 'reserved','paid','picked_up'), NOW() - INTERVAL FLOOR(RAND()*30) DAY
FROM plates p
WHERE p.restaurant_id = @annas_rest_id
ORDER BY RAND()
LIMIT 50;

-- Insert donations from mary_donor to paul_needy or available (30 donations)
INSERT INTO donations (donor_id, needy_id, plate_id, quantity, status, donated_at)
SELECT @donor_user_id,
  CASE WHEN RAND() < 0.6 THEN @needy_user_id ELSE NULL END,
  p.id,
  FLOOR(1 + RAND()*3),
  ELT(1 + FLOOR(RAND()*3), 'available','reserved','claimed'),
  NOW() - INTERVAL FLOOR(RAND()*20) DAY
FROM plates p
WHERE p.restaurant_id = @annas_rest_id
ORDER BY RAND()
LIMIT 30;

-- Optional: a few payment_info rows to exercise payment flows (masked numbers)
INSERT INTO payment_info (user_id, card_number, cvc, expiration_date, cardholder_name)
VALUES
  (@cust_user_id, '************1111', NULL, '12/25', 'John Doe'),
  (@donor_user_id, '************2222', NULL, '11/26', 'Mary Donor');

-- Completed: demo seed has users, profiles, restaurant, ~20 plates, orders and donations for testing.

