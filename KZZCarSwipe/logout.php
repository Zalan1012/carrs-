<?php
require 'includes/auth.php';

$_SESSION = [];
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
session_destroy();

header('Location: login.php');
exit;
