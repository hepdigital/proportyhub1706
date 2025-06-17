<?php
// agent/register.php (Acenteye Özel Versiyon)
require_once '../includes/init.php';

$user = new User();
if ($user->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Form validasyonları...
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = 'Lütfen tüm alanları doldurun.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Girdiğiniz şifreler eşleşmiyor.';
    } else {
        // YENİ: register metoduna 'acente' rolünü gönderiyoruz.
        $result = $user->register($name, $email, $password, 'acente');
        
        if ($result === true) {
            // Kayıt başarılı, otomatik giriş yap ve panele yönlendir
            $user_id = $user->login($email, $password);
            if ($user_id) {
                 header('Location: index.php?status=registered');
                 exit();
            }
        } else {
            $error_message = 'Bu e-posta adresi zaten kayıtlı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <title>Acente Kaydı - Property Hub</title>
    <link rel="stylesheet" href="assets/css/agent-style.css?v=<?php echo time(); ?>">
    </head>
<body class="login-page-wrapper">
    <div class="login-card">
        <a href="#" class="logo">Property<span>Hub</span></a>
        <p class="tagline">Yeni acente hesabı oluşturun.</p>
        <?php if ($error_message): ?>
            <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <button type="submit" class="btn btn-primary w-full btn-lg mt-4">Hesap Oluştur</button>
        </form>
        <div class="text-center text-sm mt-6 text-gray-500">
            Zaten bir hesabınız var mı? <a href="login.php" class="font-medium text-primary-600 hover:underline">Giriş Yapın</a>
        </div>
    </div>
</body>
</html>