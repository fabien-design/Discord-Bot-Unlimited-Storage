<?php

include 'vendor/autoload.php';
include "src/DotEnv.php";
(new DotEnv(__DIR__ . '/.env'))->load();


$base64Binary = file_get_contents('testfile.txt'); 
file_put_contents('image.png', base64_decode($base64Binary)); 


function fsplit($file, $maxSize = 20 * 1024 * 1024) {
    // Open the file to read
    $fileHandle = fopen($file, 'rb');
    
    // Get the file size
    $fileSize = filesize($file);
    
    // Calculate the number of parts
    $numParts = ceil($fileSize / $maxSize);
    
    // Store all the file names
    $fileParts = [];
    
    // Path to write the final files
    $storePath = __DIR__ . "/splits/";
    
    // Name of input file
    $fileName = basename($file);
    
    for ($i = 0; $i < $numParts; $i++) {
        // Read a chunk from the file
        $filePart = fread($fileHandle, $maxSize);
        
        // The filename of the part
        $filePartPath = $storePath . $fileName . ".part{$i}";
        
        // Open the new file (create it) to write
        $fileNew = fopen($filePartPath, 'wb');
        
        // Write the part of the file
        fwrite($fileNew, $filePart);
        
        // Add the name of the file to part list [optional]
        $fileParts[] = $filePartPath;
        
        // Close the part file handle
        fclose($fileNew);
    }
    
    // Close the main file handle
    fclose($fileHandle);
    
    return $fileParts;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file"])) {
    $file = $_FILES["file"];

    if ($file["error"] === UPLOAD_ERR_OK) {
        // Check file size (max 25MB)
        if ($file["size"] <= 60 * 1024 * 1024) {
            // Move the uploaded file to a location accessible by your bot
            $destination = __DIR__ . "/uploads/" . basename($file["name"]);

            if (move_uploaded_file($file["tmp_name"], $destination)) {
                // Split the file into parts
                $file_parts = fsplit($destination);

                // Information about the uploaded file
                $uploadInfo = [
                    'user_id' => getUserId(),  // Replace with the actual way to get user ID
                    'file_name' => $file['name'],
                    'file_extension' => pathinfo($file['name'], PATHINFO_EXTENSION),
                    'file_parts' => $file_parts,
                ];

                // Save the information to a JSON file
                saveUploadInfo($uploadInfo);

                
                interactWithDiscordBot();

                echo "File uploaded successfully!";
                $_POST = array();

                // Rest of your code for sending a message to Discord
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

function interactWithDiscordBot() {
    $botId = getenv('BOT_ID');
    $message = "<@{$botId}> uploadfile";

    // Replace YOUR_WEBHOOK_URL with the actual webhook URL
    $webhookUrl = getenv('WEBHOOK_URL');
    //Data to be sent in the POST request
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

}

function getUserId() {
    // Replace this with the actual way to get the user ID from the db
    return '777910706476679228';
}

function saveUploadInfo($uploadInfo) {
    // Specify the path to the JSON file
    $jsonFilePath = __DIR__ . "/upload_info.json";

    // Load existing data from the JSON file if it exists
    $existingData = file_exists($jsonFilePath) ? json_decode(file_get_contents($jsonFilePath), true) : [];

    // Append the new upload information to the existing data
    $existingData[] = $uploadInfo;

    // Save the updated data back to the JSON file
    file_put_contents($jsonFilePath, json_encode($existingData, JSON_PRETTY_PRINT));
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
        <label for="file">Choose a file (max 60MB):</label>
        <input type="file" name="file" id="file">
        <button type="submit" name="submit">Upload</button>
    </form>
</body>
</html>

<a href="downloadFile.php?idFile=15">Btn Download test</a>