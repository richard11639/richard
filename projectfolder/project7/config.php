<?php
// config.php - update DB & payment keys before using
define('DB_HOST','localhost');
define('DB_NAME','hotel_db');
define('DB_USER','dbuser');
define('DB_PASS','dbpass');

define('PAYSTACK_SECRET','sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxx'); // replace
define('PAYSTACK_PUBLIC','pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxx'); // replace

// base url, e.g. https://yourdomain.com
define('BASE_URL','http://localhost'); // change to production

// PDO helper
function db(){
  static $pdo = null;
  if($pdo === null){
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
  }
  return $pdo;
}
