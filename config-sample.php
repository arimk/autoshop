<?php
// Authentication credentials
define('AUTH_USERNAME', 'your_username_here');
define('AUTH_PASSWORD', 'your_password_here');

// Gemini API configuration
define('GEMINI_API_KEY', 'your_api_key_here');
define('GEMINI_MODEL', 'gemini-2.5-flash-image-preview'); // Can use gemini-2.0-flash-exp-image-generation for older model
define('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/');

// Application settings
define('MAX_UPLOAD_SIZE', 5 * 2024 * 2024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', __DIR__ . '/uploads');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1); 