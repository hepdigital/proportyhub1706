<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Veritabanı bağlantı seçenekleri
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları exception olarak fırlat (EN ÖNEMLİSİ)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Sonuçları varsayılan olarak associatve array olarak al
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek prepared statement'lar kullan
        ];

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Hata durumunda ekrana detaylı bilgi basmak yerine, loglayıp genel bir mesaj vermek daha güvenlidir.
            error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
            // Production ortamında kullanıcıya genel bir hata mesajı gösterilebilir.
            die("Sistemsel bir hata oluştu. Lütfen daha sonra tekrar deneyin.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }

    // Olası transaction sorunlarını yönetmek için bu metodları eklemek iyi bir pratiktir,
    // ancak şu anki kod yapımızda doğrudan kullanılmıyorlar. 
    // İleride karmaşık işlemler için gerekebilirler.
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollBack() {
        return $this->connection->rollBack();
    }
}