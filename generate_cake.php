<?php
/*
=====================================
AI CAKE IMAGE GENERATOR
Uses Pollinations.ai for free AI image generation
Only returns successfully generated images (no padding)
=====================================
*/

set_time_limit(180);
ini_set('max_execution_time', 180);
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Get prompt from request
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$customerPrompt = isset($data['prompt']) ? trim($data['prompt']) : (isset($_POST['prompt']) ? trim($_POST['prompt']) : trim($rawInput));

if ($customerPrompt === "") {
    echo json_encode(["status" => "error", "message" => "Please describe your cake"]);
    exit;
}

// Create folder for generated images
$imageDir = __DIR__ . "/generated_cakes";
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0755, true);
}

// Build protocol and host for URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['REQUEST_URI']);

// Download image from URL and save locally
function downloadAndSaveImage($url, $filepath) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CakeOrderingApp/1.0');
    
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if it's valid image data (check magic bytes)
    if ($httpCode === 200 && strlen($imageData) > 5000) {
        $isImage = (
            substr($imageData, 0, 2) === "\xFF\xD8" || // JPEG
            substr($imageData, 0, 8) === "\x89PNG\r\n\x1a\n" // PNG
        );
        
        if ($isImage) {
            file_put_contents($filepath, $imageData);
            return true;
        }
    }
    return false;
}

// Generate image URL
function getImageUrl($prompt, $seed) {
    $fullPrompt = "A beautiful professional photograph of " . $prompt . " cake, delicious bakery product, high quality food photography, studio lighting, detailed, appetizing, white background";
    $encodedPrompt = urlencode($fullPrompt);
    return "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512&seed={$seed}&model=flux&nologo=true";
}

$generatedImages = [];
$timestamp = time();

// Style variations
$styles = [
    " elegant sophisticated design",
    " colorful festive celebration"
];

// Generate 2 images with proper delays
for ($i = 0; $i < 2; $i++) {
    if ($i > 0) {
        sleep(5);
    }
    
    $seed = $timestamp * 1000 + $i * 12345 + rand(10000, 99999);
    $modifiedPrompt = $customerPrompt . $styles[$i];
    
    $imageUrl = getImageUrl($modifiedPrompt, $seed);
    $filename = "cake_" . $timestamp . "_" . $i . ".jpg";
    $filepath = $imageDir . "/" . $filename;
    
    if (downloadAndSaveImage($imageUrl, $filepath)) {
        $localUrl = "$protocol://$host$scriptPath/generated_cakes/$filename";
        $generatedImages[] = $localUrl;
    }
}

// Return only successfully generated images (no padding!)
if (count($generatedImages) > 0) {
    echo json_encode([
        "status" => "success",
        "images" => $generatedImages,
        "prompt_used" => $customerPrompt,
        "generated_count" => count($generatedImages),
        "ai_generated" => true,
        "message" => "✨ AI generated " . count($generatedImages) . " cake designs"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Please wait 30 seconds and try again.",
        "prompt_received" => $customerPrompt
    ]);
}
?>
