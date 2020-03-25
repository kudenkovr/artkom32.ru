<?php
mb_internal_encoding('utf-8');

// Version
define('VERSION', '3.0.3.2');
define('RK_VERSION', '0.6.9-pa');

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

start('catalog');