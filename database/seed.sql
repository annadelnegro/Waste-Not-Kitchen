
-- seed.sql
-- Populate the waste_not_kitchen database with sample/dummy data for development

USE waste_not_kitchen;
-- Users (insert only if username not already present)
INSERT INTO users (username, role, password)
SELECT 'admin', 'admin', 'adminpass' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

INSERT INTO users (username, role, password)
SELECT 'rest_owner', 'restaurant', 'restpass' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'rest_owner');

INSERT INTO users (username, role, password)
SELECT 'alice_customer', 'customer', 'alicepass' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'alice_customer');

INSERT INTO users (username, role, password)
SELECT 'bob_donor', 'donor', 'bobpass' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'bob_donor');

INSERT INTO users (username, role, password)
SELECT 'carol_needy', 'needy', 'carolpass' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'carol_needy');

-- Profiles (use the user id if the user exists and profile for that user doesn't exist)
INSERT INTO profiles (user_id, full_name, address, phone)
SELECT u.id, 'Site Administrator', '1 Admin Road, City', '+1-555-0001'
FROM users u
WHERE u.username = 'admin'
	AND NOT EXISTS (SELECT 1 FROM profiles p WHERE p.user_id = u.id);

INSERT INTO profiles (user_id, full_name, address, phone)
SELECT u.id, 'Alice Example', '123 Main St, Townsville', '+1-555-0101'
FROM users u
WHERE u.username = 'alice_customer'
	AND NOT EXISTS (SELECT 1 FROM profiles p WHERE p.user_id = u.id);

INSERT INTO profiles (user_id, full_name, address, phone)
SELECT u.id, 'Carol Needshelp', '45 Hope Ave, Smalltown', '+1-555-0202'
FROM users u
WHERE u.username = 'carol_needy'
	AND NOT EXISTS (SELECT 1 FROM profiles p WHERE p.user_id = u.id);

-- Restaurants (attach to the restaurant owner user)
INSERT INTO restaurants (user_id, restaurant_name)
SELECT u.id, 'Good Food Restaurant'
FROM users u
WHERE u.username = 'rest_owner'
	AND NOT EXISTS (SELECT 1 FROM restaurants r WHERE r.user_id = u.id AND r.restaurant_name = 'Good Food Restaurant');

-- Plates (use restaurant id found by name)
INSERT INTO plates (restaurant_id, title, description, price, quantity, available_from, available_until)
SELECT r.id, 'Half Portion Pasta', 'Leftover but fresh-tasting pasta, half portion', 4.50, 5, '2025-11-05 10:00:00', '2025-11-05 14:00:00'
FROM restaurants r
WHERE r.restaurant_name = 'Good Food Restaurant'
	AND NOT EXISTS (SELECT 1 FROM plates p WHERE p.restaurant_id = r.id AND p.title = 'Half Portion Pasta');

INSERT INTO plates (restaurant_id, title, description, price, quantity, available_from, available_until)
SELECT r.id, 'Vegetable Soup', 'Hearty vegetable soup', 3.00, 8, '2025-11-05 09:00:00', '2025-11-05 13:00:00'
FROM restaurants r
WHERE r.restaurant_name = 'Good Food Restaurant'
	AND NOT EXISTS (SELECT 1 FROM plates p WHERE p.restaurant_id = r.id AND p.title = 'Vegetable Soup');

INSERT INTO plates (restaurant_id, title, description, price, quantity, available_from, available_until)
SELECT r.id, 'Chicken Wrap', 'Grilled chicken wrap with salad', 5.00, 3, '2025-11-05 12:00:00', '2025-11-05 18:00:00'
FROM restaurants r
WHERE r.restaurant_name = 'Good Food Restaurant'
	AND NOT EXISTS (SELECT 1 FROM plates p WHERE p.restaurant_id = r.id AND p.title = 'Chicken Wrap');

-- Orders (ensure plate + buyer exist and the same created_at doesn't already exist)
INSERT INTO orders (plate_id, buyer_id, quantity, status, created_at)
SELECT p.id, u.id, 1, 'reserved', '2025-11-05 09:30:00'
FROM plates p
JOIN users u ON u.username = 'alice_customer'
WHERE p.title = 'Half Portion Pasta'
	AND NOT EXISTS (
		SELECT 1 FROM orders o WHERE o.plate_id = p.id AND o.buyer_id = u.id AND o.created_at = '2025-11-05 09:30:00'
	);

INSERT INTO orders (plate_id, buyer_id, quantity, status, created_at)
SELECT p.id, u.id, 2, 'paid', '2025-11-05 09:45:00'
FROM plates p
JOIN users u ON u.username = 'alice_customer'
WHERE p.title = 'Vegetable Soup'
	AND NOT EXISTS (
		SELECT 1 FROM orders o WHERE o.plate_id = p.id AND o.buyer_id = u.id AND o.created_at = '2025-11-05 09:45:00'
	);

-- Donations (donor gives plate(s) to a needy user or anonymous needy)
INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at)
SELECT d.id, n.id, p.id, 1, '2025-11-05 10:15:00'
FROM users d
JOIN users n ON n.username = 'carol_needy'
JOIN plates p ON p.title = 'Vegetable Soup'
WHERE d.username = 'bob_donor'
	AND NOT EXISTS (
		SELECT 1 FROM donations dn WHERE dn.donor_id = d.id AND dn.needy_id = n.id AND dn.plate_id = p.id AND dn.donated_at = '2025-11-05 10:15:00'
	);

-- donation without specific needy (needy_id NULL)
INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at)
SELECT d.id, NULL, p.id, 1, '2025-11-05 11:00:00'
FROM users d
JOIN plates p ON p.title = 'Chicken Wrap'
WHERE d.username = 'bob_donor'
	AND NOT EXISTS (
		SELECT 1 FROM donations dn WHERE dn.donor_id = d.id AND dn.needy_id IS NULL AND dn.plate_id = p.id AND dn.donated_at = '2025-11-05 11:00:00'
	);

-- Reports (create example reports if not present for the same user/year/type)
INSERT INTO reports (user_id, report_type, year, generated_at)
SELECT u.id, 'restaurant_activity', 2025, '2025-11-05 12:00:00'
FROM users u
WHERE u.username = 'admin'
	AND NOT EXISTS (SELECT 1 FROM reports r WHERE r.user_id = u.id AND r.report_type = 'restaurant_activity' AND r.year = 2025);

INSERT INTO reports (user_id, report_type, year, generated_at)
SELECT u.id, 'customer_purchases', 2025, '2025-11-05 12:05:00'
FROM users u
WHERE u.username = 'rest_owner'
	AND NOT EXISTS (SELECT 1 FROM reports r WHERE r.user_id = u.id AND r.report_type = 'customer_purchases' AND r.year = 2025);

-- End of seed file