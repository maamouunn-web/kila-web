<?php
require '../inc/functions.php'; require '../inc/auth.php';
jwt_clear(ADMIN_COOKIE);
$_SESSION=[];
session_destroy();
header('Location: login.php');
