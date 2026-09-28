CREATE DATABASE IF NOT EXISTS `testing`;
GRANT ALL PRIVILEGES ON `testing`.* TO 'laravel'@'%';
GRANT ALL PRIVILEGES ON `testing_test_%`.* TO 'laravel'@'%';
GRANT CREATE, DROP ON *.* TO 'laravel'@'%';
FLUSH PRIVILEGES;
