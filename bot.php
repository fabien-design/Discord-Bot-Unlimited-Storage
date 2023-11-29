<?php
include 'vendor/autoload.php';

include "src/DotEnv.php";
(new DotEnv(__DIR__ . '/.env'))->load();

// // Import classes, install a LSP such as Intelephense to auto complete imports
use Discord\DiscordCommandClient;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Channel;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use function React\Async\coroutine;


// Create a $discord BOT
$discord = new DiscordCommandClient([
    'token' => getenv('BOT_TOKEN'), // Put your Bot token here from https://discord.com/developers/applications/
]);
// Create a $browser with same loop as $discord
$browser = new Browser(null, $discord->getLoop());




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
