<?php
/*
=====================================
AI CAKE IMAGE GENERATOR
Uses Pollinations.ai for free AI image generation
Generates 2 unique cake designs based on customer prompt
=====================================
*/

set_time_limit(300);
ini_set('max_execution_time', 300);
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
 * Download image from Pollinations.ai and save locally with retries
 */
function downloadImage($url, $filepath, $maxRetries = 3) {
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        error_log("generate_cake.php: Download attempt $attempt for: $url");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_CONNECTTIMEOUT => 30,
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
            if ($attempt < $maxRetries) {
                sleep(3);
                continue;
            }
            return false;
        }
        
        // Verify it's actually an image (at least 5KB and has image magic bytes)
        if ($httpCode === 200 && strlen($imageData) > 5000) {
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
        
        // Wait before retry
        if ($attempt < $maxRetries) {
            sleep(2);
        }
    }
    
    return false;
}

/**
 * Generate Pollinations.ai image URL with enhanced prompt
 */
function getPollinationsUrl($prompt, $seed, $variation = 0) {
    // Different style variations for each image
    $variations = [
        "elegant classic style, smooth fondant finish, professional bakery quality",
        "creative modern artistic design, colorful decorations, unique contemporary style"
    ];
    
    $styleVariation = isset($variations[$variation]) ? $variations[$variation] : $variations[0];
    
    $fullPrompt = "Professional photograph of a beautiful " . $prompt . 
                  " cake, " . $styleVariation . 
                  ", delicious dessert, high quality food photography, " .
                  "studio lighting, detailed frosting and decorations, appetizing appearance, " .
                  "on elegant cake stand or plate, 8k quality, sharp focus";
    
    $encodedPrompt = rawurlencode($fullPrompt);
    
    // Use Pollinations.ai API with specific parameters
    return "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512&seed={$seed}&model=flux&nologo=true";
}

$generatedImages = [];
$timestamp = time();
$uniqueId = uniqid();

error_log("generate_cake.php: Starting to generate 2 images");

// Generate 2 images with different variations
for ($i = 0; $i < 2; $i++) {
    // Wait between requests to avoid rate limiting
    if ($i > 0) {
        sleep(5); // Increased delay between requests
    }
    
    // Create unique seed for each image
    $seed = ($timestamp * 1000) + ($i * 54321) + rand(10000, 99999);
    
    // Local filename
    $filename = "cake_{$uniqueId}_{$i}.jpg";
    $filepath = $imageDir . "/" . $filename;
    
    error_log("generate_cake.php: Generating image $i with seed $seed");
    
    // Try up to 2 different seeds if first fails
    $maxSeedAttempts = 2;
    $imageGenerated = false;
    
    for ($seedAttempt = 0; $seedAttempt < $maxSeedAttempts && !$imageGenerated; $seedAttempt++) {
        $currentSeed = $seed + ($seedAttempt * 77777);
        $imageUrl = getPollinationsUrl($customerPrompt, $currentSeed, $i);
        
        error_log("generate_cake.php: Trying seed $currentSeed (attempt " . ($seedAttempt + 1) . ")");
        
        // Download and save image with retries
        if (downloadImage($imageUrl, $filepath, 2)) {
            $localUrl = "$protocol://$host$scriptPath/generated_cakes/$filename";
            $generatedImages[] = $localUrl;
            error_log("generate_cake.php: Image $i generated successfully: $localUrl");
            $imageGenerated = true;
        } else {
            error_log("generate_cake.php: Failed to generate image $i with seed $currentSeed");
            if ($seedAttempt < $maxSeedAttempts - 1) {
                sleep(3);
            }
        }
    }
}

error_log("generate_cake.php: Generated " . count($generatedImages) . " images total");

// Return response
if (count($generatedImages) >= 2) {
    // Successfully generated both images
    echo json_encode([
        "status" => "success",
        "images" => $generatedImages,
        "prompt_used" => $customerPrompt,
        "generated_count" => count($generatedImages),
        "ai_generated" => true,
        "message" => "✨ AI generated " . count($generatedImages) . " unique cake designs!"
    ]);
} else if (count($generatedImages) == 1) {
    // Only 1 image generated - try to generate a second one with different approach
    error_log("generate_cake.php: Only 1 image generated, trying alternate method for second image");
    
    $alternateSeed = time() + rand(100000, 999999);
    $alternateFilename = "cake_{$uniqueId}_alt.jpg";
    $alternateFilepath = $imageDir . "/" . $alternateFilename;
    $alternateUrl = getPollinationsUrl($customerPrompt . " alternative creative design", $alternateSeed, 1);
    
    sleep(3);
    
    if (downloadImage($alternateUrl, $alternateFilepath, 2)) {
        $localUrl = "$protocol://$host$scriptPath/generated_cakes/$alternateFilename";
        $generatedImages[] = $localUrl;
        error_log("generate_cake.php: Alternate image generated: $localUrl");
    }
    
    echo json_encode([
        "status" => "success",
        "images" => $generatedImages,
        "prompt_used" => $customerPrompt,
        "generated_count" => count($generatedImages),
        "ai_generated" => true,
        "message" => "✨ AI generated " . count($generatedImages) . " cake design(s)!"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "AI image generation is temporarily unavailable. Please try again in 30 seconds.",
        "prompt_received" => $customerPrompt
    ]);
}
?>
