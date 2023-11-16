# Степан  # v.1.0.0
Степан (stepone) - simple telegram chatbot which use chatGPT - open AI generative model
![Stepan-640x640](https://raw.githubusercontent.com/petrenkodesign/stepone/37e0ce0fc36e83182f3c262af01d83385e87197d/avatar.jpg)

### Requirements
PHP 7.1.x+\

### Installation
Clone project:

``git clone https://github.com/petrenkodesign/stepone.git``

go to project directory:

``cd  stepone``

open in the editor script openai_telegram_bot.php, and edit constant records:

```
define('BOT_TOKEN', 'telegram_bot_token');
define('OAI_KEY', 'open_ai_api_token');
define('GIPHY_API_KEY', 'giphy_api_token');
define('BOT_USERNAME', 'stepone_bot');
```

BOT_TOKEN - telegram bot token. Get it from @BotFather: /mybots -> @bot_name -> API Token.
[Howto create telegram bot](https://chat.openai.com/share/be4a2d34-4006-4e59-b057-c5f15f4ffeb6)

OAI_KEY - open ai API token.
[Howto create open AI account and use chatbot](https://chat.openai.com/share/6bf0a9ae-1171-4d21-8427-17959a8f4b2d)

GIPHY_API_KEY - giphy.com API key. [How to create giphy API key](https://chat.openai.com/share/a51c3b22-8fd7-4c8f-bb3f-3ee96ec3a462)

BOT_USERNAME - any username for your telegram bot that you define when you create it.

Сonfigure [nginx](https://chat.openai.com/share/84bfc407-0403-4d80-a7ba-0a7b08598c9a) or [apache](https://chat.openai.com/share/5bb2fb4a-9dcf-4630-9a6d-911fba1d0d7c) WEB Server

Create Webhook for bot

```
curl --location 'https://api.telegram.org/telegram_bot_token/setWebhook' \
--form 'url="https://your.domain/openai_telegram_bot.php"'
```

### Usage
Call the bot in telegram by nickname "Степан", "Степане", "Степанко", "Степко".

``Степко, what is the speed of light?``

you can change bot nicnames in array on 199 line in open_telegram_bot.php script

``case (str_has_array($ask, ['cтепан', 'cтепане', 'степанко', 'степко']) || $has_replay):``

use the nicknames you want with a lowercase letter