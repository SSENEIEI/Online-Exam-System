<?php
// Set headers
header('Content-Type: application/json');
require_once '../config/config.php';

// --- Helper Functions ---

/**
 * Sends a request to the Google Gemini API.
 *
 * @param string $url The API URL.
 * @param array $data The data to send.
 * @return array The API response decoded as an associative array.
 */
function sendApiRequest($url, $data) {
    $maxRetries = 3;
    $attempt = 0;
    $response = null;
    $httpcode = 0;

    do {
        $attempt++;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Use for local development (XAMPP)
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            return json_decode($response, true);
        }

        // Retry on 503 (Overloaded), 429 (Rate Limit), or 5xx Server Errors
        if (in_array($httpcode, [429, 500, 502, 503, 504])) {
            if ($attempt < $maxRetries) {
                // Exponential backoff: 1s, 2s, 4s
                sleep(pow(2, $attempt - 1)); 
                continue;
            }
        } else {
            // Non-retryable error (e.g. 400 Bad Request, 401 Unauthorized)
            break;
        }

    } while ($attempt < $maxRetries);

    if ($httpcode != 200) {
        // Log error or handle it appropriately
        http_response_code($httpcode);
        echo json_encode(['error' => 'API request failed', 'details' => $response]);
        exit;
    }

    return json_decode($response, true);
}

/**
 * Extracts the JSON content from the API's text response.
 *
 * @param array $apiResponse The full API response.
 * @return array|null The decoded JSON content or null if not found.
 */
function extractJsonFromResponse($apiResponse) {
    if (isset($apiResponse['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $apiResponse['candidates'][0]['content']['parts'][0]['text'];
        
        // 1. Try to find JSON within ```json ... ``` blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded) return $decoded;
        }

        // 2. Try to find JSON within generic ``` ... ``` blocks
        if (preg_match('/```\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded) return $decoded;
        }
        
        // 3. Fallback: Try to find the first '{' and last '}' to capture the main JSON object
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        
        if ($start !== false && $end !== false && $end > $start) {
            $jsonText = substr($text, $start, $end - $start + 1);
            $decoded = json_decode($jsonText, true);
            if ($decoded) return $decoded;
        }
        
        // 4. Last resort: try decoding the whole text after simple cleanup
        $cleanText = trim(str_replace(['```json', '```'], '', $text));
        return json_decode($cleanText, true);
    }
    return null;
}


// --- Main Logic ---

// Get the request body
$requestBody = json_decode(file_get_contents('php://input'), true);

if (!$requestBody) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Extract data from the request
$userPrompt = $requestBody['prompt'] ?? '';
$difficulty = $requestBody['difficulty'] ?? 'Medium';
$examType = $requestBody['exam_type'] ?? 'multiple_choice';
$mcqCount = (int)($requestBody['mcq_count'] ?? 0);
$writtenCount = (int)($requestBody['written_count'] ?? 0);

// Determine API Key based on question count
$totalQuestions = $mcqCount + $writtenCount;
$apiKey = null;

// If total questions exceed 30, use the secondary API key
if ($totalQuestions > 30) {
    $apiKey = getSecondaryGeminiApiKey();
    if (!$apiKey) {
        // Fallback to primary if secondary is not set
        $apiKey = getGeminiApiKey();
        Logger::warning("Secondary API key not found for large request ($totalQuestions questions). Using primary key.");
    } else {
        Logger::info("Using secondary API key for large request ($totalQuestions questions).");
    }
} else {
    $apiKey = getGeminiApiKey();
}

if (!$apiKey) {
    http_response_code(500);
    Logger::error('Gemini API Key not configured');
    echo json_encode(['error' => 'API configuration error']);
    exit;
}

$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey;

// Construct the system prompt
$systemPrompt = "You are an expert AI assistant for creating educational exams. Your name is \"OES-AI\". Your task is to help teachers generate high-quality exam questions based on their requests.

**Instructions:**
1.  **Analyze the teacher's request:** Understand the topic, subject, grade level, and desired number of questions.
2.  **Adhere to the difficulty level:** Generate questions that match the specified difficulty: '$difficulty'.
3.  **Generate questions in the specified format:** The output MUST be a valid JSON object. Do not include any text outside of the JSON structure.
4.  **For Multiple Choice questions:** Provide 4 options (A, B, C, D) and clearly indicate the correct answer.
5.  **For Written questions:** Provide a clear question prompt. The `options` array should be empty, and the `correct_answer` should be an empty string.
6.  **Provide suggestions:** After generating the questions, add a `suggestions` key with a brief, helpful comment for the teacher on how the exam could be improved.
7.  **Thailand Language only:** Ensure that all questions and answers are in Thai language.

**JSON Output Format:**
{
  \"exam_title\": \"Example: Grade 10 Science Quiz - Photosynthesis\",
  \"difficulty\": \"$difficulty\",
  \"questions\": [
    {
      \"question_number\": 1,
      \"type\": \"multiple_choice\",
      \"question_text\": \"...\",
      \"options\": {\"A\": \"...\", \"B\": \"...\", \"C\": \"...\", \"D\": \"...\"},
      \"correct_answer\": \"A\"
    },
    {
      \"question_number\": 2,
      \"type\": \"written\",
      \"question_text\": \"...\",
      \"options\": {},
      \"correct_answer\": \"\"
    }
  ],
  \"suggestions\": \"...\"
}";

// Add specific instructions for the exam type
$typeInstruction = "";
if ($examType === 'multiple_choice') {
    $typeInstruction = " (Important: Create exactly $mcqCount questions. All questions MUST be of type 'multiple_choice'.)";
} elseif ($examType === 'written') {
    $typeInstruction = " (Important: Create exactly $writtenCount questions. All questions MUST be of type 'written'.)";
} elseif ($examType === 'mixed' && $mcqCount > 0 && $writtenCount > 0) {
    $typeInstruction = " (Please create exactly $mcqCount multiple_choice questions and $writtenCount written questions.)";
}
$userPrompt .= $typeInstruction;


// Prepare the data for the Gemini API
$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $systemPrompt],
                ['text' => "Teacher's request: " . $userPrompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.35,
        'topK' => 1,
        'topP' => 1,
        'maxOutputTokens' => 1000000,
    ]
];

// Send the request and get the response
$apiResponse = sendApiRequest($apiUrl, $data);

// Extract and return the clean JSON content
$examData = extractJsonFromResponse($apiResponse);
$rawText = $apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

if ($examData) {
    // Normalize keys to ensure expected structure (teacher & student use same flat shape)
    $normalized = [
        'exam_title' => $examData['exam_title'] ?? ('AI Generated Exam'),
        'difficulty' => $examData['difficulty'] ?? $difficulty,
        'questions' => $examData['questions'] ?? [],
        'suggestions' => $examData['suggestions'] ?? ''
    ];
    // Include original full response text for optional editing (student practice page uses it)
    $normalized['full_response'] = $rawText;
    echo json_encode($normalized);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to parse AI response', 'raw_response' => $apiResponse]);
}
