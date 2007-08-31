CREATE TABLE sf_guard_user_permission(
 created_at DATETIME,
 updated_at DATETIME,
 user_id INTEGER,
 permission_id INTEGER,
 PRIMARY KEY(user_id,
 permission_id));

CREATE TABLE sf_guard_user_group(
 created_at DATETIME,
 updated_at DATETIME,
 group_id INTEGER,
 user_id INTEGER,
 PRIMARY KEY(group_id,
 user_id));

CREATE TABLE sf_guard_user(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 created_at DATETIME,
 updated_at DATETIME,
 username VARCHAR(128)
NOT NULL,
 algorithm VARCHAR(128)
DEFAULT 'sha1' NOT NULL,
 salt VARCHAR(128)
NOT NULL,
 password VARCHAR(128)
NOT NULL,
 last_login DATETIME,
 is_active INTEGER DEFAULT 1 NOT NULL,
 is_super_admin INTEGER DEFAULT 0 NOT NULL);

CREATE TABLE sf_guard_remember_key(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 created_at DATETIME,
 updated_at DATETIME,
 user_id INTEGER,
 remember_key VARCHAR(32),
 ip_address VARCHAR(15));

CREATE TABLE sf_guard_permission(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 created_at DATETIME,
 updated_at DATETIME,
 name VARCHAR(255)
NOT NULL,
 description VARCHAR(4000));

CREATE TABLE sf_guard_group_permission(
 created_at DATETIME,
 updated_at DATETIME,
 group_id INTEGER,
 permission_id INTEGER NOT NULL,
 PRIMARY KEY(group_id,
 permission_id));

CREATE TABLE sf_guard_group(
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 created_at DATETIME,
 updated_at DATETIME,
 name VARCHAR(255)
NOT NULL,
 description VARCHAR(4000));
