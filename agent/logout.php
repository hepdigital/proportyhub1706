<?php
// owner/logout.php
require_once '../includes/init.php';

$user = new User();
$user->logout(); // Bu metot artık çerezi de temizliyor

header('Location: login.php');
exit();