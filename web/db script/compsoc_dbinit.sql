create database [mysql_database];
grant all privileges on [mysql_database].* to [mysql_user]@localhost identified by '[mysql_password]';
use [mysql_database];
CREATE TABLE access (id INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY, timestamp TIMESTAMP);