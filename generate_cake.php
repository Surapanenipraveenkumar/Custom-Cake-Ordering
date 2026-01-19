<?php
/*
=====================================
AI CAKE IMAGE GENERATOR
Uses Pollinations.ai for free AI image generation
Generates 2 unique cake designs based on customer prompt
=====================================
*/

set_time_limit(180);
ini_set('max_execution_time', 180);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

error_log("generate_cake.php: Started");

// Get prompt from request
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$customerPrompt = isset($data['prompt']) ? trim($data['prompt']) : '';

if (empty($customerPrompt)) {
    echo json_encode(["status" => "error", "message" => "Please describe your cake"]);
    exit;
}

error_log("generate_cake.php: Prompt received: $customerPrompt");

// Create folder for generated images
$imageDir = __DIR__ . "/generated_cakes";
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}

// Build base URL for local images
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['REQUEST_URI']);

// Clean old images (older than 1 hour)
$files = glob($imageDir . "/cake_*.jpg");
foreach ($files as $file) {
    if (time() - filemtime($file) > 3600) {
        @unlink($file);
    }
}

/**
 * Download image from Pollinations.ai and save locally
 */
function downloadImage($url, $filepath) {
    error_log("generate_cake.php: Downloading from: $url");
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: image/jpeg,image/png,image/*',
            'Accept-Language: en-US,en;q=0.9'
        ]
    ]);
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("generate_cake.php: HTTP $httpCode, Content-Type: $contentType, Size: " . strlen($imageData));
    
    if ($error) {
        error_log("generate_cake.php: cURL error: $error");
        return false;
    }
    
    // Verify it's actually an image (at least 10KB and has image magic bytes)
    if ($httpCode === 200 && strlen($imageData) > 10000) {
        $isJpeg = (substr($imageData, 0, 2) === "\xFF\xD8");
        $isPng = (substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n");
        
        if ($isJpeg || $isPng) {
            if (file_put_contents($filepath, $imageData)) {
                error_log("generate_cake.php: Image saved to $filepath");
                return true;
            }
        } else {
            error_log("generate_cake.php: Not a valid image - magic bytes don't match");
        }
    }
    
    return false;
}

/**
 * Generate Pollinations.ai image URL
 */
function getPollinationsUrl($prompt, $seed) {
    $fullPrompt = "Professional bakery photograph of a " . $prompt . 
                  " cake, beautiful decorated cake, delicious dessert, high quality food photography, " .
                  "studio lighting, detailed frosting, appetizing, on elegant cake stand, 8k quality";
    
    $encodedPrompt = rawurlencode($fullPrompt);
    
    // Use Pollinations.ai API with specific parameters
    return "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512&seed={$seed}&model=flux&nologo=true";
}

$generatedImages = [];
$timestamp = time();
$uniqueId = uniqid();

// Style variations for 2 different designs
$styles = [
    " elegant classic style with smooth fondant",
    " creative modern artistic design with colorful decorations"
];

error_log("generate_cake.php: Starting to generate 2 images");

// Generate 2 images
for ($i = 0; $i < 2; $i++) {
    // Wait between requests to avoid rate limiting
    if ($i > 0) {
        sleep(3);
    }
    
    // Create unique seed for each image
    $seed = ($timestamp * 1000) + ($i * 54321) + rand(10000, 99999);
    
    // Modify prompt with style variation
    $styledPrompt = $customerPrompt . $styles[$i];
    
    // Get Pollinations URL
    $imageUrl = getPollinationsUrl($styledPrompt, $seed);
    
    // Local filename
    $filename = "cake_{$uniqueId}_{$i}.jpg";
    $filepath = $imageDir . "/" . $filename;
    
    error_log("generate_cake.php: Generating image $i with seed $seed");
    
    // Download and save image
    if (downloadImage($imageUrl, $filepath)) {
        $localUrl = "$protocol://$host$scriptPath/generated_cakes/$filename";
        $generatedImages[] = $localUrl;
        error_log("generate_cake.php: Image $i generated successfully: $localUrl");
    } else {
        error_log("generate_cake.php: Failed to generate image $i");
        
        // Retry once with different seed
        sleep(2);
        $seed = $seed + 99999;
        $imageUrl = getPollinationsUrl($styledPrompt, $seed);
        
        if (downloadImage($imageUrl, $filepath)) {
            $localUrl = "$protocol://$host$scriptPath/generated_cakes/$filename";
            $generatedImages[] = $localUrl;
            error_log("generate_cake.php: Image $i retry succeeded: $localUrl");
        }
    }
}

error_log("generate_cake.php: Generated " . count($generatedImages) . " images total");

// Return response
if (count($generatedImages) > 0) {
    echo json_encode([
        "status" => "success",
        "images" => $generatedImages,
        "prompt_used" => $customerPrompt,
        "generated_count" => count($generatedImages),
        "ai_generated" => true,
        "message" => "✨ AI generated " . count($generatedImages) . " unique cake designs!"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "AI image generation is temporarily unavailable. Please try again in 30 seconds.",
        "prompt_received" => $customerPrompt
    ]);
}
?>
