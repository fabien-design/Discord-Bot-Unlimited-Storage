<?php
include 'vendor/autoload.php';
include "src/DotEnv.php";
(new DotEnv(__DIR__ . '/.env'))->load();

include "includes/connexion_db.php";


$id = $_GET['idFile'];

$selectAllFilesParts = $connexion->prepare("SELECT * FROM files_uploaded fu, files_parts_uploaded fpu WHERE fu.id = fpu.file_id AND fu.id = :id");
$selectAllFilesParts->bindParam(":id", $id);
$selectAllFilesParts->execute();
$infos = $selectAllFilesParts->fetchAll();

$webhookUrl = getenv('WEBHOOK_URL');
$botToken = getenv('BOT_TOKEN');
$indexFilePart = 1;

foreach ($infos as $info) {

    $channelId = $info['channel_id'];
    $messageId = $info['message_id'];

    // Construire l'URL pour récupérer le message
    $url = "https://discord.com/api/v10/channels/{$channelId}/messages/{$messageId}";

    // Effectuer la requête GET
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "Authorization: Bot $botToken",
    ]);

    $response = curl_exec($ch);

    // Vérifier si la requête a réussi
    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
        $messageData = json_decode($response, true);

        // Télécharger l'attachement
        $attachments = $messageData['attachments'];
        if (!empty($attachments)) {
            $attachmentUrl = $attachments[0]['url'];
            $attachmentContents = file_get_contents($attachmentUrl);

            // Envoi des en-têtes HTTP pour indiquer que le contenu est un téléchargement
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $info['name'] . "_" . $indexFilePart . "." . $info['extension'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($attachmentContents));
            if($indexFilePart > 1){var_dump($info);die;}
            // Sortie du contenu de l'attachement
            echo $attachmentContents;

            // Incrementer l'index pour le prochain fichier
            $indexFilePart += 1;
        } else {
            echo 'Aucun attachement trouvé dans le message.';
        }
    } else {
        echo 'Échec de la requête : ' . $response;
    }

    curl_close($ch);
}
error_reporting(E_ALL);
ini_set('display_errors', '1');

die;

// Fin du script
?>
