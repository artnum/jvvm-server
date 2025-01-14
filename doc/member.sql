CREATE TABLE IF NOT EXISTS member (
    id BIGINT UNSIGNED PRIMARY KEY,
    firstname VARCHAR(255),
    lastname VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    _modified INT NOT NULL DEFAULT 0,
    _deleted INT NOT NULL DEFAULT 0,
    _created INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS member_email (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255), -- name of the email
    member BIGINT UNSIGNED,
    email VARCHAR(255),
    type ENUM('personnal', 'professional') DEFAULT 'personnal',
    FOREIGN KEY (member) REFERENCES member(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS member_phone (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255), -- name of the email
    member BIGINT UNSIGNED,
    phone VARCHAR(255),
    type ENUM('personnal', 'professional') DEFAULT 'personnal',
    mobile BOOL DEFAULT FALSE,
    FOREIGN KEY (member) REFERENCES member(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS member_address (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(255), -- name of the email
    member BIGINT UNSIGNED,
    address VARCHAR(255),
    zip VARCHAR(10),
    city VARCHAR(255),
    country CHAR(2),
    type ENUM('personnal', 'professional') DEFAULT 'personnal',
    FOREIGN KEY (member) REFERENCES member(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS member_auth (
    member_id BIGINT UNSIGNED,
    identifier VARCHAR(255) NOT NULL, -- email, phone, ...
    parameter1 VARCHAR(255) NOT NULL DEFAULT '', -- password, token, ...
    parameter2 VARCHAR(255) NOT NULL DEFAULT '', -- password, token, ...
    parameter3 VARCHAR(255) NOT NULL DEFAULT '', -- password, token, ...
    parameter4 VARCHAR(255) NOT NULL DEFAULT '', -- password, token, ...
    type CHAR(8), -- describe type of authentification (password, token, ...)
    options1 VARCHAR(255) NOT NULL DEFAULT '', -- options related to auth type
    options2 VARCHAR(255) NOT NULL DEFAULT '',
    options3 VARCHAR(255) NOT NULL DEFAULT '',
    options4 VARCHAR(255) NOT NULL DEFAULT '',
    _modified INT NOT NULL DEFAULT 0,
    _deleted INT NOT NULL DEFAULT 0,
    _created INT NOT NULL DEFAULT 0,
    PRIMARY KEY(member_id, type, identifier),
    FOREIGN KEY (member_id) REFERENCES member(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX USING HASH (identifier),
    INDEX USING HASH (type)
);

CREATE TABLE IF NOT EXISTS member_session (
    session_id BIGINT UNSIGNED PRIMARY KEY,
    member_id BIGINT UNSIGNED,
    since INT DEFAULT 0,
    FOREIGN KEY (memeber_id) REFERENCES member(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX USING BTREE (since)
);