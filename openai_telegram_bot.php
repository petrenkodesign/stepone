<?php
header('Content-Type: text/html; charset =utf-8');

// tokens place (місце для додавання токенів)
define('BOT_TOKEN', 'telegram_bot_token');
define('OAI_KEY', 'open_ai_api_token');
define('GIPHY_API_KEY', 'giphy_api_token');
define('WEATHER_API_KEY', 'weather_api_token');
define('BOT_USERNAME', 'stepone_bot');

$sop = fopen('php://input', 'r');
$bot_msg_json = stream_get_contents($sop);
$bot_msg_obj = json_decode($bot_msg_json);

// start processor (вся магія тут)
if(!empty($bot_msg_json)) {
    // logging a message for analyse
    $log_msg = json_encode($bot_msg_obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    log_to_file($log_msg);
    // checks chats by allows group, send message for deny user
    command_processor($bot_msg_obj);
}
// end processor



// ---------------------------------------------------------- logging (журналювання)
function log_to_file ($msg) {
    $file = 'bot_logs'.DIRECTORY_SEPARATOR.date("Y_m_d").'_serj_bot.log';
    $msg = date('[Y-m-d, h:i:s]').$msg."\r\n";
    file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
}

// ---------------------------------------------------------- postmans (листоноші)
function bot_says ($chat_id, $text, $reply=false) {
    $post = [
        'chat_id' => $chat_id,
        'text' => $text,
        'reply_to_message_id' => $reply
    ];
    curl_mail($post,'sendMessage');

}

function send_gif ($q, $bot=false) {
    $bot = $bot ?: $bot_msg_obj;
    $gif_id = random_int(0, 50);
    $gif = json_decode(call_giphy($q), true);
    $gif_url = $gif['data'][$gif_id]['images']['original']['url'];
    bot_send_gif(bot_chat_id($bot), $gif_url);
}

function bot_send_gif ($chat_id, $video, $text=false, $reply=false) {
    $post = [
        'chat_id' => $chat_id,
        'video' => $video,
        'text' => $text,
        'reply_to_message_id' => $reply
    ];
    curl_mail($post,'sendVideo');

}
// telegram
function curl_mail ($post, $method=false, $file=false, $url=false, $callback=false) {
    if(!$url) {
      $url = 'https://api.telegram.org/'.BOT_TOKEN.'/'.$method;
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $result = curl_exec($ch);
    curl_close($ch);
    if($callback) return $result;
    return true;
   // var_dump($result);
}

// giphy
function call_giphy ($q) {
    $post = [
        'api_key' => GIPHY_API_KEY,
        'limit' => 50,
        'ofset' => 0,
        'q' => $q
    ];
    $ch = curl_init('https://api.giphy.com/v1/gifs/search?'.http_build_query($post));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
// openai
function call_openai ($txt, $messages=false) {
    $messages = $messages ?: [[
      'role' => 'user',
      'content' => $txt
    ]];
    $post = [
        'model' => 'gpt-4-1106-preview',
        'temperature' => 0.5,
        'max_tokens' => 3000,
        'top_p' => 0.8,
        'frequency_penalty' => 0,
        'presence_penalty' => 0,
        'stop' => ['stop'],
        'messages' => $messages

    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: Bearer '.OAI_KEY;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($result);
    return (empty($result->choices)) ? 'не знаю що сказати(' : $result->choices;
}
// weatherapi
function call_weather ($city) {
  $post = [
      'key' => WEATHER_API_KEY,
      'aqi' => 'no',
      'q' => $city
  ];
  $ch = curl_init('https://api.weatherapi.com/v1/current.json?'.http_build_query($post));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}
// ---------------------------------------------------------- bot`s data (ботодані)
function bot_chat_id ($bot=false) {
  $bot = $bot ?: $bot_msg_obj;
  return ( is_callback($bot) ) ? $bot->callback_query->message->chat->id : $bot->message->chat->id;
}

function bot_user_id ($bot=false) {
  $bot = $bot ?: $bot_msg_obj;
  return ( is_callback($bot) ) ? $bot->callback_query->from->id : $bot->message->from->id;
}

function bot_msg_id($bot=false) {
  $bot = $bot ?: $bot_msg_obj;
  return ( is_callback($bot) ) ? $bot->callback_query->message->message_id : $bot->message->message_id;
}

function is_callback ($bot=false) {
  $bot = $bot ?: $bot_msg_obj;
  return (empty($bot->callback_query->id)) ? false : $bot->callback_query->id;
}

// ---------------------------------------------------------- tools (інструменти)
function str_has_array ($str, $array) {
  $str = mb_strtolower($str, 'utf-8');
  foreach ($array as $value) {
    if( stristr($str, $value) ) return true;
  }
  return false;
}

function has_replay($bot) {
  $reply_to = ( is_callback($bot) ) ? $bot->callback_query->message->reply_to_message : $bot->message->reply_to_message;
  if( !empty($reply_to->from->username) && $reply_to->from->username == BOT_USERNAME) {
    return [[
      'role' => 'assistant',
      'content' => $reply_to->text
    ],[
      'role' => 'user',
      'content' => ( is_callback($bot) ) ? $bot->callback_query->message->text : $bot->message->text
    ]];
  }
  return false;
}

// ---------------------------------------------------------- processor (керманич)
function command_processor ($bot) {

  $ask = $bot->message->text;
  $has_replay = has_replay($bot);


  switch ($ask) {
    case str_has_array($ask, ['мені сумно', 'я сумую', 'не весело']):
      send_gif('do not be sad', $bot);
      break;

    case str_has_array($ask, ['тай таке', 'що тут скажеш']):
      send_gif('dassit', $bot);
      break;

    case str_has_array($ask, ['гарного дня', 'удачі', 'все буде добре']):
      send_gif('good day', $bot);
      break;

    case str_has_array($ask, ['мені потрібна допомога', 'мені потрібна підтримка', 'не підкажеш']):
      send_gif('here to help', $bot);
      break;

    case str_has_array($ask, ['покажи картинку']):
      $img_name = str_replace("покажи картинку", '', $ask);
      send_gif($img_name, $bot);
      break;
    // get the weather from the weather site, convert it into human text and return it to the user
    case str_has_array($ask, ['як погода', 'яка погода', 'погода в']):
      bot_says(bot_chat_id($bot), 'Дивлюсь');
      $ask = str_replace(['як погода', 'яка погода'], '', $ask);
      if( !$ask ) return bot_says(bot_chat_id($bot), 'Вкажіть місто, приклад: "Погода в Києві"');

      $pre_txt = 'Поверни назву міста, без додаткових коментарів, одним словом, англійською з наступного тексту: ';
      $ai_answer = call_openai( $pre_txt . '"' . $ask . '"' );

      if( !empty( $ai_answer[0]->message->content ) ) {
        $weather_json = call_weather ( $ai_answer[0]->message->content );
        $pre_txt = 'Зроби повний опис, резюме про погоду з наступного тексту: ';
        $ai_answer = call_openai( $pre_txt . $weather_json );
        bot_says( bot_chat_id($bot), @$ai_answer[0]->message->content ?: 'нічого не знайшов' );
      } else {
        bot_says( bot_chat_id($bot), $ai_answer );
      }
      break;
    // for calling bot use array below ['cтепан', 'cтепане', 'степанко', 'степко'] or replay on bot msg
    case (str_has_array($ask, ['cтепан', 'cтепане', 'степанко', 'степко']) || $has_replay):
      // openai functionality
      $whait_msg = ['дайте подумати...', 'момент...', 'думаю...', 'хвилинку...', 'зара оповім', 'ем...', 'я думаю...', 'трошки зайнятий...', 'я тут...' ];

      $whm_id = random_int(0, count($whait_msg));
      bot_says( bot_chat_id($bot), $whait_msg[$whm_id] );

      $ai_answer = call_openai($ask, $has_replay);

      if( !is_array($ai_answer) ) {
        bot_says( bot_chat_id($bot), $ai_answer );
      } else {
        foreach($ai_answer as $answer) {
          // bot_says ($chat_id, $text, $reply=false)
          bot_says( bot_chat_id($bot), (empty($answer->message->content)) ? 'немає що сказати' : $answer->message->content, bot_msg_id($bot) );
        }
      }
      break;
  }

}

?>
