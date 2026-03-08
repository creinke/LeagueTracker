<?php

// use App\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Otherwise, set up environment vars
$dotenv = new Symfony\Component\Dotenv\Dotenv();

// Load `.env` if it exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
	$dotenv->load($envFile);
}

// Optionally load .env.local or .env.<env>.local
if ($appEnv = ($_SERVER['APP_ENV'] ?? 'dev')) {
	$localEnvFile = dirname(__DIR__) . '/.env.local';
	if (file_exists($localEnvFile)) {
		$dotenv->load($localEnvFile);
	}
	$envSpecificFile = dirname(__DIR__) . "/.env.$appEnv.local";
	if (file_exists($envSpecificFile)) {
		$dotenv->load($envSpecificFile);
	}
}

