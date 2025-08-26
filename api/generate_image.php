<?php
session_start();
require_once '../config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400'); // 24 hours cache

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check authentication
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$requestData = json_decode(file_get_contents('php://input'), true);

if (!$requestData || !isset($requestData['contents']) || !is_array($requestData['contents'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Validate the contents structure
foreach ($requestData['contents'] as $content) {
    if (!isset($content['role']) || !isset($content['parts']) || !is_array($content['parts'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid content structure']);
        exit;
    }
}

// Prepare the request to Gemini API
$geminiRequest = [
    'generationConfig' => [
        'temperature' => 1,
        'topK' => 40,
        'topP' => 0.95,
        'maxOutputTokens' => 8192,
        'response_modalities' => ['Text', 'Image']
    ],
    'contents' => $requestData['contents']
];

// Initialize cURL session
$ch = curl_init(GEMINI_API_ENDPOINT . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY);

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($geminiRequest),
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 30
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to connect to Gemini API: ' . $error]);
    exit;
}

curl_close($ch);

// Check if the response is valid JSON
$decodedResponse = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from Gemini API']);
    exit;
}

// Check for specific API error responses
if (!isset($decodedResponse['candidates']) || !is_array($decodedResponse['candidates'])) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Invalid response structure from Gemini API',
        'details' => $decodedResponse['error'] ?? 'Unknown error'
    ]);
    exit;
}

// Handle IMAGE_SAFETY and other finish reasons
if (isset($decodedResponse['candidates'][0]['finishReason'])) {
    $finishReason = $decodedResponse['candidates'][0]['finishReason'];
    if ($finishReason === 'IMAGE_SAFETY') {
        http_response_code(422); // Unprocessable Entity
        echo json_encode([
            'error' => 'Content safety violation',
            'details' => 'The image could not be generated due to safety concerns',
            'candidates' => $decodedResponse['candidates']
        ]);
        exit;
    }
}

// Set response headers
header('Content-Type: application/json');
http_response_code($httpCode);

// Return the Gemini API response
echo $response; 