-- seed.sql = DML (data): INSERT rows you want present in a fresh dev DB (test users, lookup tables, sample restaurants/offers).

USE waste_not_kitchen;
INSERT INTO users (username, role, password) VALUES
('testuser', 'customer', 'passwordhash1');