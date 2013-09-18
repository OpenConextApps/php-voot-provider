CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(64) NOT NULL,
    display_name TEXT DEFAULT NULL,
    mail TEXT DEFAULT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS groups (
    id VARCHAR(64) NOT NULL,
    title TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS roles (
    id INT(11) NOT NULL,
    voot_membership_role VARCHAR(64) NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS users_groups_roles (
    user_id VARCHAR(64) NOT NULL,
    group_id VARCHAR(64) NOT NULL,
    role_id INT(11) NOT NULL,
    FOREIGN KEY (user_id)
        REFERENCES users (id),
    FOREIGN KEY (group_id)
        REFERENCES groups (id),
    FOREIGN KEY (role_id)
        REFERENCES roles (id)
);

INSERT INTO roles VALUES (10, 'member');
INSERT INTO roles VALUES (20, 'manager');
INSERT INTO roles VALUES (50, 'admin');
