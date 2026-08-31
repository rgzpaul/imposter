<?php

/**
 * Configurazione e helper per l'invio di eventi realtime tramite Pusher.
 *
 * Il secret NON e' pubblico e NON va mai committato: viene letto dalla
 * variabile d'ambiente PUSHER_SECRET oppure dal file assets/pusher-secret.php,
 * che e' in .gitignore e viene creato durante il deploy a partire dal
 * GitHub Actions secret PUSHER_SECRET.
 * Se il secret manca, pusherTrigger() non fa nulla e i client ricadono
 * automaticamente sul polling.
 */

// Il secret viene letto, in ordine, da:
//   1. la variabile d'ambiente PUSHER_SECRET
//   2. il file assets/pusher-secret.php (NON versionato, generato in fase di deploy,
//      contenuto: <?php return 'IL_SECRET';)
$__pusher_secret = getenv('PUSHER_SECRET') ?: '';
if ($__pusher_secret === '' && file_exists(__DIR__ . '/pusher-secret.php')) {
    $__pusher_secret = (string) (include __DIR__ . '/pusher-secret.php');
}

define('PUSHER_APP_ID', getenv('PUSHER_APP_ID') ?: '2190399');
define('PUSHER_KEY', getenv('PUSHER_KEY') ?: '725f7abcf8f9a470386f');
define('PUSHER_SECRET', trim($__pusher_secret));
define('PUSHER_CLUSTER', getenv('PUSHER_CLUSTER') ?: 'eu');
unset($__pusher_secret);

define('PUSHER_ADMIN_CHANNEL', 'imposter-admin');
define('PUSHER_EVENT', 'update');

function pusherEnabled()
{
    return PUSHER_SECRET !== '';
}

/**
 * Nome del canale di una stanza. Deve restare allineato a quello calcolato
 * lato client in index.html.
 */
function pusherChannelForStanza($id_stanza)
{
    $id = preg_replace('/[^0-9]/', '', (string) $id_stanza);
    return 'imposter-stanza-' . ($id === '' ? 'default' : $id);
}

/**
 * Pubblica un evento su uno o piu' canali (max 100 per chiamata).
 * Gli errori vengono loggati e ignorati: il gioco continua a funzionare
 * grazie al polling di fallback dei client.
 */
function pusherTrigger($channels, $data = [])
{
    if (!pusherEnabled()) {
        return false;
    }

    $channels = array_values(array_unique(array_filter((array) $channels)));
    if (empty($channels)) {
        return false;
    }
    $channels = array_slice($channels, 0, 100);

    $body = json_encode([
        'name' => PUSHER_EVENT,
        'channels' => $channels,
        'data' => json_encode($data)
    ]);

    $path = '/apps/' . PUSHER_APP_ID . '/events';
    $params = [
        'auth_key' => PUSHER_KEY,
        'auth_timestamp' => time(),
        'auth_version' => '1.0',
        'body_md5' => md5($body)
    ];
    ksort($params);
    $query = http_build_query($params);

    $signature = hash_hmac('sha256', "POST\n" . $path . "\n" . $query, PUSHER_SECRET);
    $url = 'https://api-' . PUSHER_CLUSTER . '.pusher.com' . $path . '?' . $query . '&auth_signature=' . $signature;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 3
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        error_log('Pusher trigger fallito (' . $status . '): ' . ($error !== '' ? $error : $response));
        return false;
    }

    return true;
}

/**
 * Notifica le stanze indicate (array di id_stanza) e il pannello admin.
 */
function pusherNotifyStanze($stanze, $reason = '')
{
    $channels = [PUSHER_ADMIN_CHANNEL];
    foreach ((array) $stanze as $stanza) {
        $channels[] = pusherChannelForStanza($stanza);
    }
    return pusherTrigger($channels, ['reason' => $reason, 'ts' => time()]);
}

/**
 * Notifica solo il pannello admin.
 */
function pusherNotifyAdmin($reason = '')
{
    return pusherTrigger([PUSHER_ADMIN_CHANNEL], ['reason' => $reason, 'ts' => time()]);
}
