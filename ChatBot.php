<?php
require_once __DIR__ . '/vendor/autoload.php'; // Include Composer's autoloader

// Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class ChatBot
{
    private $endpoint;

    public function __construct()
    {
        
        // This is the correct endpoint for the chat completions using gpt-3.5-turbo model
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';
    }

    public function sendMessage(string $message): string
    {
        // Prepare the data payload for the request
        $data = [
            'model' => 'gpt-3.5-turbo', // Specify the model here
            'max_tokens' => 150,
            'messages' => [             // An array of message objects
                // You can include previous messages here for context
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
        ];

        // Set headers for the request
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
        ];

        // Initialize cURL and make the POST request to the OpenAI API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // Check for cURL errors
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Error sending the message: ' . $error);
        }

        curl_close($ch);

        // Decode the JSON response from the OpenAI API
        $arrResult = json_decode($response, true);

        // Check if JSON decoding failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON returned: ' . json_last_error_msg());
        }

        // Check if the response contains an "error" key
        if (isset($arrResult["error"])) {
            // Handle the error, e.g., log it and provide a user-friendly message
            error_log("OpenAI API Error: " . $arrResult["error"]["message"]);
            throw new Exception('OpenAI API Error: ' . $arrResult["error"]["message"]);
        }

        // Access the response data from the "choices" key
        if (isset($arrResult["choices"][0]["message"]["content"])) {
            // Return the text part of the response
            return $arrResult["choices"][0]["message"]["content"];
        } else {
            // Log the actual response for debugging purposes
            error_log("Unexpected Response: " . print_r($arrResult, true));
            throw new Exception('Expected data not found in the response');
        }
    }
}
