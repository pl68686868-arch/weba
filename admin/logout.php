<?php
declare(strict_types=1);

/**
 * Admin Logout
 * 
 * @package Weba
 * @author Danny Duong
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->logout();

redirect('/admin/login.php');
