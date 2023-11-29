<?php

include 'vendor/autoload.php';


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"];

    // Check if the file upload was successful
    if ($file["error"] === UPLOAD_ERR_OK) {
        // Check file size (max 25MB)
        if ($file["size"] <= 25 * 1024 * 1024) {
            // Move the uploaded file to a location accessible by your bot
            $destination = __DIR__ . "/uploads/" . basename($file["name"]);

            if (move_uploaded_file($file["tmp_name"], $destination)) {
                // File upload successful, now interact with your Discord bot
                interactWithDiscordBot($destination, $file);
                echo "File uploaded successfully!";

                $botId = 1179498771583877141;
                $message = "<@{$botId}> uploadfile";

                // Replace YOUR_WEBHOOK_URL with the actual webhook URL
                $webhookUrl = "https://discord.com/api/webhooks/1179514398059016323/isuAr_Q4Po9aiKy2nzlAgq5PwUIGkIFNwiBietMeHDKBvBI0ENpxlQ7aBAlUoirPKS1V";

                // Data to be sent in the POST request
                $data = [
                    "content" => $message,
                ];

                // Use cURL to send a POST request to the Discord webhook URL
                $ch = curl_init($webhookUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute the cURL request
                $response = curl_exec($ch);

                // Close cURL session
                curl_close($ch);

                // Check for errors or handle the response as needed
                if ($response === false) {
                    echo "Error sending message.";
                } else {
                    echo "Message sent successfully!";
                }

            } else {
                echo "Error moving the file.";
            }
        } else {
            echo "File size exceeds the limit (25MB).";
        }
    } else {
        echo "Error uploading file.";
    }
} 

function interactWithDiscordBot($filePath, $file) {


    file_put_contents(__DIR__ . '/bot_input.txt', $filePath);

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Discord</title>
</head>
<body>
     <h1>Discord Bot File Uploader</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="file">Choose a file (max 25MB):</label>
        <input type="file" name="file" id="file">
        <button type="submit" name="submit">Upload</button>
    </form>
</body>
</html>