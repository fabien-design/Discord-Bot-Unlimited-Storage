<?php
include 'vendor/autoload.php';

include "src/DotEnv.php";
(new DotEnv(__DIR__ . '/.env'))->load();

include "includes/connexion_db.php";

// // Import classes, install a LSP such as Intelephense to auto complete imports
use Discord\DiscordCommandClient;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Channel;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use function React\Async\coroutine;


function generateRandomPassword($length = 12) {
    // Définit tous les caractères possibles pour le mot de passe
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@!';

    // Mélange les caractères
    $shuffledCharacters = str_shuffle($characters);

    // Sélectionne la sous-chaîne de la longueur spécifiée
    $password = substr($shuffledCharacters, 0, $length);

    return $password;
}


// Create a $discord BOT
$discord = new DiscordCommandClient([
    'token' => getenv('BOT_TOKEN'), // Put your Bot token here from https://discord.com/developers/applications/
]);
// Create a $browser with same loop as $discord
$browser = new Browser(null, $discord->getLoop());

$discord->registerCommand('createAccount', function (Message $message, $params) use ($connexion, $browser, $discord) {
    coroutine(function (Message $message, $params) use ($connexion, $browser, $discord) {
        if ($message->author->bot) return;

        try {
            $idAuthor = $message->author->id;

            $CheckUser = $connexion->prepare("SELECT * FROM user WHERE discord_id = :id");
            $CheckUser->bindParam("id",$idAuthor); 
            if($CheckUser->execute()){
                $users = $CheckUser->fetch();
                if(empty($users)){
                    $message->author->sendMessage($message->author . " vous n'avez pas de compte associé" );
                    $password = generateRandomPassword();
                    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
                    $username = $message->author->username;
                    $discordId = $message->author->id;
                    $insertUser = $connexion->prepare('INSERT INTO `user` (`username`, `discord_id`, `password`) VALUES (:user, :discord, :pass)');
                    $insertUser->bindParam(":user", $username);
                    $insertUser->bindParam(":discord", $discordId);
                    $insertUser->bindParam(":pass", $passwordHash);
                    if($insertUser->execute()){

                        $message->author->sendMessage("Username : ".$username. " Password : " .$password);
                        $message->author->sendMessage("**Une fois connecté, changez le mot de passe**");

                    }else{
                        echo "Erreur lors de la création de votre compte.";
                    }

                }else{
                    $message->author->sendMessage($message->author . " vous avez déjà un compte associé sous le nom : ". $users['username']);
                }
            }else{
                $message->channel->sentMessage("Le service de vérification est indisponible");
            }

        } catch (Exception $e) { 
            $message->reply('Unable to acesss the Discord status API :(');
        }
               

    }, $message, $params);
});


$discord->registerCommand('discordstatus', function (Message $message, $params) use ($browser) {
    coroutine(function (Message $message, $params) use ($browser) {
        // Ignore messages from any Bots
        
        if ($message->author->bot) return;

        try {

            $message->reply('Discord bot status: ' );

        } catch (Exception $e) { // Request failed
            // Uncomment to debug exceptions
            //var_dump($e);
            
            // Send reply about the discord status
            $message->reply('Unable to acesss the Discord status API :(');
        }
    }, $message, $params);
});

// $filePath = file_get_contents(__DIR__ . '/bot_input.txt');

// $builder = MessageBuilder::new();
// $builder->addFile($filePath);

$discord->registerCommand('uploadfile', function (Message $message, $params) use ($browser, $discord) {
    coroutine(function () use ($message, $browser, $discord) {

        // Read the JSON file  
        $json = file_get_contents('upload_info.json'); 
        
        // Decode the JSON file 
        $json_data = json_decode($json,true); 
        
        // Display data 
        print_r($json_data[0]["file_name"]); 
        
        $filePath = file_get_contents(__DIR__ . '/bot_input.txt');
        
        $builder = MessageBuilder::new();
        $builder->addFile($filePath);
        
        // Get the channel where the command was received
        $channel = $message->channel;

        // Send the message with the file attached
        $channel->sendMessage($builder);

    }, $message, $params);
});

  

  

// Start the Bot (must be at the bottom)
$discord->run(); 
