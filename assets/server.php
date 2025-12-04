<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

$parole_file = 'list.json';
$config_file = 'config.json';

function caricaJson($file)
{
    if (!file_exists($file)) {
        return ['giocatori' => [], 'parola' => '', 'brutal_mode' => false, 'game_id' => ''];
    }
    $content = file_get_contents($file);
    if ($content === false) {
        return ['giocatori' => [], 'parola' => '', 'brutal_mode' => false, 'game_id' => ''];
    }
    return json_decode($content, true);
}

function salvaJson($file, $data)
{
    $fp = fopen($file, 'w');
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN);
        fclose($fp);
        error_log($file . ' salvato correttamente.');
    } else {
        error_log('Impossibile bloccare il file ' . $file);
    }
}

function generaGameId()
{
    return chr(random_int(65, 90)) . random_int(1, 9);
}

function assegnaNumeriCasuali($giocatori)
{
    $numeriDisponibili = range(1, count($giocatori));
    shuffle($numeriDisponibili);

    foreach ($giocatori as $index => &$giocatore) {
        $giocatore['numero'] = array_pop($numeriDisponibili);
    }
    unset($giocatore);

    return $giocatori;
}

function logPlayer($nickname, $display_name)
{
    $log_file = 'players.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] Nickname: {$nickname} | Name: {$display_name}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Remove players inactive for more than 5 minutes
function cleanupInactivePlayers(&$configData, $config_file)
{
    $timeout = 5 * 60; // 5 minutes
    $now = time();
    $originalCount = count($configData['giocatori']);

    $configData['giocatori'] = array_values(array_filter($configData['giocatori'], function ($g) use ($now, $timeout) {
        $lastSeen = isset($g['last_seen']) ? $g['last_seen'] : (isset($g['login_time']) ? $g['login_time'] : 0);
        return ($now - $lastSeen) < $timeout;
    }));

    if (count($configData['giocatori']) < $originalCount) {
        salvaJson($config_file, $configData);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action == 'join') {
        $nickname = trim($_POST['nickname']); // email come ID
        $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : $nickname;
        $configData = caricaJson($config_file);

        $giocatore_presente = false;
        $giocatore_index = -1;
        foreach ($configData['giocatori'] as $index => $giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $giocatore_presente = true;
                $giocatore_index = $index;
                break;
            }
        }

        if (!$giocatore_presente) {
            $id_stanza = isset($_POST['id_stanza']) ? trim($_POST['id_stanza']) : '';
            array_push($configData['giocatori'], [
                'nickname' => $nickname,
                'display_name' => $display_name,
                'ruolo' => '',
                'numero' => null,
                'login_time' => time(),
                'last_seen' => time(),
                'id_stanza' => $id_stanza,
                'id_stanza_time' => $id_stanza !== '' ? time() : null
            ]);
            salvaJson($config_file, $configData);

            // Log del nuovo giocatore
            logPlayer($nickname, $display_name);
        } else {
            // Aggiorna display_name, id_stanza e last_seen se già presente
            $configData['giocatori'][$giocatore_index]['display_name'] = $display_name;
            $configData['giocatori'][$giocatore_index]['last_seen'] = time();
            if (isset($_POST['id_stanza'])) {
                $configData['giocatori'][$giocatore_index]['id_stanza'] = trim($_POST['id_stanza']);
            }
            salvaJson($config_file, $configData);
        }

        echo json_encode(['status' => 'success']);
    }

    if ($action == 'leave') {
        $nickname = trim($_POST['nickname']);
        $configData = caricaJson($config_file);

        $configData['giocatori'] = array_filter($configData['giocatori'], function ($giocatore) use ($nickname) {
            return strcasecmp($giocatore['nickname'], $nickname) != 0;
        });
        $configData['giocatori'] = array_values($configData['giocatori']);
        salvaJson($config_file, $configData);
        echo json_encode(['status' => 'success']);
    }

    if ($action == 'reset') {
        $configData = ['giocatori' => [], 'parola' => '', 'brutal_mode' => false, 'game_id' => ''];
        salvaJson($config_file, $configData);

        echo json_encode(['status' => 'success', 'message' => 'Sessione resettata con successo.']);
    }

    if ($action == 'set_brutal_mode') {
        $brutal_mode = ($_POST['brutal_mode'] === 'true');
        $configData = caricaJson($config_file);
        $configData['brutal_mode'] = $brutal_mode;
        salvaJson($config_file, $configData);

        echo json_encode(['status' => 'success']);
    }

    if ($action == 'set_id_stanza') {
        $nickname = trim($_POST['nickname']);
        $id_stanza = trim($_POST['id_stanza']);
        $configData = caricaJson($config_file);

        foreach ($configData['giocatori'] as $index => &$giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $oldIdStanza = isset($giocatore['id_stanza']) ? $giocatore['id_stanza'] : '';
                $giocatore['id_stanza'] = $id_stanza;
                // Update id_stanza_time only if the ID changed to a new value
                if ($id_stanza !== $oldIdStanza) {
                    $giocatore['id_stanza_time'] = $id_stanza !== '' ? time() : null;
                }
                break;
            }
        }
        unset($giocatore);
        salvaJson($config_file, $configData);

        echo json_encode(['status' => 'success']);
    }

    if ($action == 'start_game') {
        $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
        $configData = caricaJson($config_file);

        // Find starter's id_stanza
        $starterIdStanza = '';
        foreach ($configData['giocatori'] as $giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $starterIdStanza = isset($giocatore['id_stanza']) ? $giocatore['id_stanza'] : '';
                break;
            }
        }

        // Get indices of players with same id_stanza
        $stanzaPlayerIndices = [];
        foreach ($configData['giocatori'] as $index => $giocatore) {
            $playerIdStanza = isset($giocatore['id_stanza']) ? $giocatore['id_stanza'] : '';
            if ($playerIdStanza === $starterIdStanza) {
                $stanzaPlayerIndices[] = $index;
            }
        }

        if (count($stanzaPlayerIndices) >= 3) {
            // Assign random numbers only to stanza players
            $numeriDisponibili = range(1, count($stanzaPlayerIndices));
            shuffle($numeriDisponibili);
            foreach ($stanzaPlayerIndices as $i => $playerIndex) {
                $configData['giocatori'][$playerIndex]['numero'] = array_pop($numeriDisponibili);
            }

            // Pick imposter from stanza players
            $indice_impostore = $stanzaPlayerIndices[array_rand($stanzaPlayerIndices)];

            if ($configData['brutal_mode']) {
                $giocatoreConNumero1 = null;
                foreach ($stanzaPlayerIndices as $playerIndex) {
                    if ($configData['giocatori'][$playerIndex]['numero'] == 1) {
                        $giocatoreConNumero1 = $playerIndex;
                        break;
                    }
                }
                if ($giocatoreConNumero1 !== null && rand(0, 1) === 1) {
                    $indice_impostore = $giocatoreConNumero1;
                }
            } else {
                do {
                    $indice_impostore = $stanzaPlayerIndices[array_rand($stanzaPlayerIndices)];
                } while ($configData['giocatori'][$indice_impostore]['numero'] == 1);
            }

            // Assign roles only to stanza players
            foreach ($stanzaPlayerIndices as $playerIndex) {
                $configData['giocatori'][$playerIndex]['ruolo'] = ($playerIndex == $indice_impostore) ? 'impostore' : 'buono';
            }

            // Pick fool from good players in stanza
            $goodPlayersIndices = array_filter($stanzaPlayerIndices, function ($idx) use ($configData) {
                return $configData['giocatori'][$idx]['ruolo'] === 'buono';
            });

            if (!empty($goodPlayersIndices)) {
                $foolIndex = $goodPlayersIndices[array_rand($goodPlayersIndices)];
                $configData['giocatori'][$foolIndex]['ruolo'] = 'fool';
            }

            // Generate word and game_id for this stanza
            $parole = caricaJson($parole_file)['parole'];
            $stanzaWord = $parole[array_rand($parole)];
            $stanzaGameId = generaGameId();

            // Store word and game_id per player in stanza
            foreach ($stanzaPlayerIndices as $playerIndex) {
                $configData['giocatori'][$playerIndex]['stanza_parola'] = $stanzaWord;
                $configData['giocatori'][$playerIndex]['stanza_game_id'] = $stanzaGameId;
            }

            salvaJson($config_file, $configData);

            echo json_encode([
                'status' => 'success',
                'first_player' => $configData['giocatori'][$indice_impostore]['display_name'] ?? $configData['giocatori'][$indice_impostore]['nickname'],
                'game_id' => $stanzaGameId
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Servono almeno 3 giocatori per iniziare']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];

    if ($action == 'get_players') {
        echo json_encode(caricaJson($config_file));
    }

    if ($action == 'get_players_admin') {
        echo json_encode(caricaJson($config_file));
    }

    if ($action == 'get_brutal_mode') {
        $configData = caricaJson($config_file);
        echo json_encode(['brutal_mode' => $configData['brutal_mode']]);
    }

    if ($action == 'get_game_id') {
        $configData = caricaJson($config_file);
        echo json_encode(['game_id' => $configData['game_id']]);
    }

    if ($action == 'get_word') {
        $nickname = trim($_GET['nickname']);
        $configData = caricaJson($config_file);

        foreach ($configData['giocatori'] as $giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $word = ($giocatore['ruolo'] === 'impostore') ? '???' : $configData['parola'];
                echo json_encode(['word' => $word]);
                break;
            }
        }
    }

    if ($action == 'get_all_data') {
        $nickname = trim($_GET['nickname']);
        $configData = caricaJson($config_file);

        // Cleanup inactive players
        cleanupInactivePlayers($configData, $config_file);

        // Update last_seen for current player
        $playerFound = false;
        foreach ($configData['giocatori'] as $index => &$giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $giocatore['last_seen'] = time();
                $playerFound = true;
                break;
            }
        }
        unset($giocatore);
        if ($playerFound) {
            salvaJson($config_file, $configData);
        }

        $word = '';
        $role = '';
        $currentPlayerIdStanza = '';
        $stanzaGameId = '';
        foreach ($configData['giocatori'] as $giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $role = $giocatore['ruolo'];
                $stanzaParola = isset($giocatore['stanza_parola']) ? $giocatore['stanza_parola'] : '';
                $word = ($giocatore['ruolo'] === 'impostore') ? '???' : $stanzaParola;
                $currentPlayerIdStanza = isset($giocatore['id_stanza']) ? $giocatore['id_stanza'] : '';
                $stanzaGameId = isset($giocatore['stanza_game_id']) ? $giocatore['stanza_game_id'] : '';
                break;
            }
        }

        // Filter players by id_stanza - only show players with the same id_stanza
        $filteredPlayers = array_values(array_filter($configData['giocatori'], function($g) use ($currentPlayerIdStanza) {
            $playerIdStanza = isset($g['id_stanza']) ? $g['id_stanza'] : '';
            return $playerIdStanza === $currentPlayerIdStanza;
        }));

        echo json_encode([
            'giocatori' => $filteredPlayers,
            'game_id' => $stanzaGameId,
            'word' => $word,
            'role' => $role,
            'id_stanza' => $currentPlayerIdStanza
        ]);
    }

    if ($action == 'get_admin_data') {
        $configData = caricaJson($config_file);
        echo json_encode([
            'giocatori' => $configData['giocatori'],
            'brutal_mode' => $configData['brutal_mode'],
            'game_id' => $configData['game_id'],
            'game_word' => $configData['parola']
        ]);
    }
}
