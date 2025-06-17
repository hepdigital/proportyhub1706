<?php
class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function register($name, $email, $password, $role = 'tesis_sahibi') { // $role parametresi eklendi
    if ($this->findByEmail($email)) {
        return false; // Kullanıcı zaten var
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // SQL sorgusuna 'role' sütunu eklendi
    $stmt = $this->db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)");
    
    // execute içine :role parametresi eklendi
    return $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $password_hash,
        ':role' => $role
    ]);
}

    public function login($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->createSession($user);
            return $user['id']; // Başarılı girişte user_id döndür
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function logout() {
        $this->clearAuthToken();
        $_SESSION = [];
        session_destroy();
    }
    
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
    }

    // --- BENİ HATIRLA METOTLARI ---

    public function generateAndStoreToken($user_id) {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $validator_hash = password_hash($validator, PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30); // 30 gün geçerli

        // Eski token'ları sil
        $stmt_delete = $this->db->prepare("DELETE FROM auth_tokens WHERE user_id = :user_id");
        $stmt_delete->execute([':user_id' => $user_id]);

        // Yeni token'ı ekle
        $stmt_insert = $this->db->prepare("INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at) VALUES (:user_id, :selector, :validator_hash, :expires_at)");
        $stmt_insert->execute([
            ':user_id' => $user_id,
            ':selector' => $selector,
            ':validator_hash' => $validator_hash,
            ':expires_at' => $expires_at
        ]);

        // Çerezi oluştur
        setcookie('remember_me', $selector . ':' . $validator, time() + 86400 * 30, "/");
    }

    public function validateToken() {
        if (empty($_COOKIE['remember_me'])) {
            return false;
        }

        list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);
        if (!$selector || !$validator) {
            return false;
        }

        $stmt = $this->db->prepare("SELECT * FROM auth_tokens WHERE selector = :selector AND expires_at >= NOW()");
        $stmt->execute([':selector' => $selector]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($token_data && password_verify($validator, $token_data['validator_hash'])) {
            $user_data = $this->findById($token_data['user_id']);
            if ($user_data) {
                $this->createSession($user_data);
                return true;
            }
        }
        
        // Geçersiz token ise çerezi temizle
        $this->clearAuthToken();
        return false;
    }
    
    public function clearAuthToken() {
        if (isset($_COOKIE['remember_me'])) {
            // Çerezi geçmişe ayarlayarak sil
            setcookie('remember_me', '', time() - 3600, '/');

            // DB'den de temizle
            list($selector, ) = explode(':', $_COOKIE['remember_me'], 2);
            if($selector){
                $stmt = $this->db->prepare("DELETE FROM auth_tokens WHERE selector = :selector");
                $stmt->execute([':selector' => $selector]);
            }
        }
    }
    
        public function updateAgentProfile($agent_id, $data) {
        $sql = "
            UPDATE users SET
                agency_name = :agency_name,
                agency_province_id = :agency_province_id,
                agency_district_id = :agency_district_id
            WHERE id = :agent_id AND role = 'acente'
        ";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':agency_name' => $data['agency_name'],
            ':agency_province_id' => $data['agency_province_id'],
            ':agency_district_id' => $data['agency_district_id'],
            ':agent_id' => $agent_id
        ]);
    }
}