<?php
// owner/login.php (Nihai Versiyon)
require_once '../includes/init.php';

$user = new User();

// Eğer "Beni Hatırla" çerezi varsa ve geçerliyse, direkt panele yönlendir
if ($user->validateToken()) {
    header('Location: index.php');
    exit();
}

// Eğer normal bir oturum varsa, yine panele yönlendir
if ($user->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user_id = $user->login($email, $password);
    if ($user_id) {
        // Giriş başarılı, "Beni Hatırla" seçili mi diye kontrol et
        if (!empty($_POST['remember_me'])) {
            $user->generateAndStoreToken($user_id);
        }
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Geçersiz e-posta veya şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tesis Paneli Girişi - Property Hub</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/owner-style.css?v=<?php echo time(); ?>">
</head>
<body class="login-page-wrapper">

    <div class="login-card">
        <a href="#" class="logo">Property<span>Hub</span></a>
        <p class="tagline">Tesis yönetimi paneline hoş geldiniz.</p>

        <?php if ($error_message): ?>
            <div class="alert alert-error mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="login-options">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <span>Beni Hatırla</span>
                </label>
                </div>

            <button type="submit" class="btn btn-primary w-full btn-lg">Giriş Yap</button>
        </form>
        
        <div class="text-center text-sm mt-6 text-gray-500">
            Hesabınız yok mu? <a href="register.php" class="font-medium text-primary-600 hover:underline">Kaydolun</a>
        </div>
    </div>

</body>
</html>