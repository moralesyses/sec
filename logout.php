<?php
require_once __DIR__ . '/auth.php';
log_user_out();
header('Location: login.php');
exit;