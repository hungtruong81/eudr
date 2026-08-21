-- EUDR handover demo seed. Safe synthetic data only.
-- Demo password for all accounts: ChangeMe-EUDR-2026!
-- Legacy API hash: md5(md5(password + salt)); change every password immediately.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

INSERT INTO eudr_roles (role_id, name, description, sort_order) VALUES
  (6712, 'admin', 'Quản trị viên', 1),
  (6713, 'farmer', 'Nông hộ', 2),
  (87423, 'purchaser', 'Thu mua', 3),
  (87425, 'factory', 'Nhà máy', 4),
  (87426, 'sales', 'Bán hàng', 5),
  (87427, 'trader', 'Trader', 6)
ON DUPLICATE KEY UPDATE description=VALUES(description), sort_order=VALUES(sort_order);

INSERT INTO eudr_companies
  (company_id, company_code, company_name, short_name, tax_code, address, status, created_at, created_by, generate_default)
VALUES
  (90001, 'DEMO-ADMIN', 'EUDR Demo Administration', 'Demo Admin', NULL, 'Demo only', 'active', NOW(), 0, 1),
  (90002, 'DEMO-FARM', 'EUDR Demo Farm', 'Demo Farm', NULL, 'Demo only', 'active', NOW(), 0, 1),
  (90003, 'DEMO-BUY', 'EUDR Demo Purchasing', 'Demo Purchasing', NULL, 'Demo only', 'active', NOW(), 0, 1),
  (90004, 'DEMO-FACT', 'EUDR Demo Factory', 'Demo Factory', NULL, 'Demo only', 'active', NOW(), 0, 1),
  (90005, 'DEMO-TRADE', 'EUDR Demo Trader', 'Demo Trader', NULL, 'Demo only', 'active', NOW(), 0, 1)
ON DUPLICATE KEY UPDATE company_name=VALUES(company_name), status='active';

INSERT INTO eudr_users
  (user_id, user_code, company_id, parent_user_id, full_name, email, phone, password, salt, register_type, created_at, created_by, is_approved, is_active)
VALUES
  (90001, 'demo-admin', 90001, 0, 'Demo Administrator', 'admin@eudr-demo.local', '0900000001', 'cb8bf3509b161256bee7ce1f333440bb', '123456', 'admin', NOW(), 0, 1, 1),
  (90002, 'demo-farmer', 90002, 0, 'Demo Farmer', 'farmer@eudr-demo.local', '0900000002', 'cb8bf3509b161256bee7ce1f333440bb', '123456', 'farmer', NOW(), 0, 1, 1),
  (90003, 'demo-purchaser', 90003, 0, 'Demo Purchaser', 'purchaser@eudr-demo.local', '0900000003', 'cb8bf3509b161256bee7ce1f333440bb', '123456', 'purchaser', NOW(), 0, 1, 1),
  (90004, 'demo-factory', 90004, 0, 'Demo Factory Operator', 'factory@eudr-demo.local', '0900000004', 'cb8bf3509b161256bee7ce1f333440bb', '123456', 'factory', NOW(), 0, 1, 1),
  (90005, 'demo-trader', 90005, 0, 'Demo Trader', 'trader@eudr-demo.local', '0900000005', 'cb8bf3509b161256bee7ce1f333440bb', '123456', 'trader', NOW(), 0, 1, 1)
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), email=VALUES(email), is_approved=1, is_active=1;

INSERT INTO eudr_user_roles (user_role_id, user_id, role_id) VALUES
  (90001, 90001, 6712),
  (90002, 90002, 6713),
  (90003, 90003, 87423),
  (90004, 90004, 87425),
  (90005, 90005, 87427)
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id), user_id=VALUES(user_id);

INSERT INTO eudr_company_groups
  (company_group_id, company_group_code, default_name, company_id, name, description, status, is_default, created_by, created_at)
VALUES
  (90001, 'DEMO-FARM-GRP', 'farmer', 90002, 'Demo Farmers', 'Synthetic demo group', 'active', 1, 0, NOW()),
  (90002, 'DEMO-BUY-GRP', 'purchaser', 90003, 'Demo Purchasers', 'Synthetic demo group', 'active', 1, 0, NOW()),
  (90003, 'DEMO-TRADE-GRP', 'trader', 90005, 'Demo Traders', 'Synthetic demo group', 'active', 1, 0, NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), status='active';

INSERT INTO eudr_company_group_members
  (company_group_member_id, company_group_id, user_id, assigned_by, assigned_at)
VALUES
  (90001, 90001, 90002, 90001, NOW()),
  (90002, 90002, 90003, 90001, NOW()),
  (90003, 90003, 90005, 90001, NOW())
ON DUPLICATE KEY UPDATE company_group_id=VALUES(company_group_id), user_id=VALUES(user_id);

SET FOREIGN_KEY_CHECKS=1;
