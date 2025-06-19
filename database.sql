
CREATE DATABASE IF NOT EXISTS library;
USE library;


CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);


INSERT INTO admins (username, password) VALUES ('admin', 'admin123');


CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    borrow_date DATE NOT NULL,
    return_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE admins MODIFY COLUMN password VARCHAR(255) NOT NULL;


UPDATE admins SET password = 'admin123' WHERE username = 'admin';
