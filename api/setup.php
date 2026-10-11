<?php
require 'config.php';
header('Content-Type: text/plain');
$sql = <<<'LFEOF'
CREATE TABLE users (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role               ENUM('donor','admin') NOT NULL DEFAULT 'donor',
  name               VARCHAR(100) NOT NULL,
  email              VARCHAR(255) NOT NULL UNIQUE,
  password_hash      VARCHAR(255) NOT NULL,
  blood_type         ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
  city               VARCHAR(120) NULL,
  last_donation_date DATE NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE blood_banks (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  area           VARCHAR(150) NOT NULL,
  phone          VARCHAR(30)  NOT NULL,
  latitude       DECIMAL(9,6) NOT NULL,
  longitude      DECIMAL(9,6) NOT NULL,
  slot_capacity  TINYINT UNSIGNED NOT NULL DEFAULT 2,
  admin_user_id  INT UNSIGNED NULL,
  FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE blood_stock (
  bank_id     INT UNSIGNED NOT NULL,
  blood_type  ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  units       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (bank_id, blood_type),
  FOREIGN KEY (bank_id) REFERENCES blood_banks(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE appointments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  bank_id     INT UNSIGNED NOT NULL,
  slot_start  DATETIME NOT NULL,
  status      ENUM('booked','cancelled','completed') NOT NULL DEFAULT 'booked',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_slot (bank_id, slot_start, status),
  INDEX idx_user (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (bank_id) REFERENCES blood_banks(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE emergency_requests (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requester_id    INT UNSIGNED NULL,
  requester_name  VARCHAR(160) NOT NULL,
  blood_type      ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  units_needed    TINYINT UNSIGNED NOT NULL,
  urgency         ENUM('urgent','critical') NOT NULL DEFAULT 'urgent',
  status          ENUM('open','fulfilled','cancelled') NOT NULL DEFAULT 'open',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_open (status, created_at),
  FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
INSERT INTO blood_banks (id, name, area, phone, latitude, longitude) VALUES
 (1,'Bengaluru City Blood Bank','Shivajinagar, Bengaluru','080-2222-1010',12.985700,77.605700),
 (2,'St. Mary''s Hospital Blood Centre','Richmond Town, Bengaluru','080-4111-5566',12.966300,77.600000),
 (3,'Vyasa Health Blood Bank','Jayanagar, Bengaluru','080-3355-7788',12.925000,77.593800),
 (4,'Rainbow Community Blood Bank','Indiranagar, Bengaluru','080-4499-2233',12.978400,77.640800);
INSERT INTO blood_stock (bank_id, blood_type, units) VALUES
 (1,'A+',18),(1,'O+',22),(1,'O-',4),(1,'B+',11),
 (2,'A-',2),(2,'AB+',9),(2,'O+',14),(2,'B-',1),
 (3,'O+',30),(3,'A+',16),(3,'AB-',3),(3,'B+',8),
 (4,'B-',5),(4,'A+',12),(4,'O-',2),(4,'AB+',7);
INSERT INTO emergency_requests (requester_name, blood_type, units_needed, urgency, created_at) VALUES
 ('Bengaluru City Blood Bank, Shivajinagar','O-',4,'critical', NOW() - INTERVAL 12 MINUTE),
 ('St. Mary''s Hospital Blood Centre, Richmond Town','AB-',2,'critical', NOW() - INTERVAL 34 MINUTE),
 ('Rainbow Community Blood Bank, Indiranagar','B+',6,'urgent', NOW() - INTERVAL 58 MINUTE),
 ('Vyasa Health Blood Bank, Jayanagar','A-',3,'urgent', NOW() - INTERVAL 95 MINUTE);
LFEOF;
try {
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (DB_SSL) {
        $opts[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $opts);
    if ($pdo->query("SHOW TABLES LIKE 'users'")->fetch()) {
        echo "Tables already exist. Nothing changed.\n";
    } else {
        $n = 0;
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt, " \t\r\n;");
            if ($stmt === '') { continue; }
            $pdo->exec($stmt);
            $n++;
        }
        echo "Done: $n statements ran.\n";
    }
    echo "Tables:\n";
    foreach ($pdo->query('SHOW TABLES') as $r) { echo ' - ' . $r[0] . "\n"; }
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
