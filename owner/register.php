<?php
// owner/register.php (Nihai Versiyon)
require_once '../includes/init.php';

$user = new User();
if ($user->isLoggedIn()) {
    header('Location: index.php'); // Zaten giriş yapmışsa panele yönlendir
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
        $error_message = 'Lütfen tüm alanları doldurun.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Şifreniz en az 6 karakter olmalıdır.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Girdiğiniz şifreler eşleşmiyor.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Lütfen geçerli bir e-posta adresi girin.';
    } else {
        $result = $user->register($name, $email, $password);
        if ($result === true) {
            // Kayıt başarılı, otomatik giriş yap ve panele yönlendir
            $user->login($email, $password);
            header('Location: index.php?status=registered');
            exit();
        } else {
            // User sınıfı, kullanıcı zaten varsa false döndürür.
            $error_message = 'Bu e-posta adresi zaten kayıtlı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Tesis Sahibi Kaydı - Property Hub</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/owner-style.css?v=<?php echo time(); ?>">
</head>
<body class="login-page-wrapper">

    <div class="login-card">
        <a href="#" class="logo">Property<span>Hub</span></a>
        <p class="tagline">Yeni tesis sahibi hesabı oluşturun.</p>

        <?php if ($error_message): ?>
            <div class="alert alert-error mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="name">Adınız Soyadınız</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-posta Adresiniz</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Şifreniz (En az 6 karakter)</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Şifreniz (Tekrar)</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-full btn-lg mt-4">Hesap Oluştur</button>
        </form>
        
        <div class="text-center text-sm mt-6 text-gray-500">
            Zaten bir hesabınız var mı? <a href="login.php" class="font-medium text-primary-600 hover:underline">Giriş Yapın</a>
        </div>
    </div>

</body>
</html>