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
    if (flock($fp, LOCK_EX)) { // Blocca il file per la scrittura
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
        flock($fp, LOCK_UN); // Rilascia il blocco
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action == 'join') {
        $nickname = trim($_POST['nickname']);
        $configData = caricaJson($config_file);

        $giocatore_presente = false;
        foreach ($configData['giocatori'] as $giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $giocatore_presente = true;
                break;
            }
        }

        if (!$giocatore_presente) {
            array_push($configData['giocatori'], [
                'nickname' => $nickname,
                'ruolo' => '',
                'numero' => null,
                'login_time' => time()
            ]);
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

    if ($action == 'start_game') {
        $configData = caricaJson($config_file);

        if (count($configData['giocatori']) > 0) {
            $configData['giocatori'] = assegnaNumeriCasuali($configData['giocatori']);

            $indice_impostore = array_rand($configData['giocatori']);

            if ($configData['brutal_mode']) {
                $giocatoreConNumero1 = null;
                foreach ($configData['giocatori'] as $index => &$giocatore) {
                    if ($giocatore['numero'] == 1) {
                        $giocatoreConNumero1 = $index;
                        break;
                    }
                }
                if ($giocatoreConNumero1 !== null && rand(0, 1) === 1) {
                    $indice_impostore = $giocatoreConNumero1;
                }
            } else {
                do {
                    $indice_impostore = array_rand($configData['giocatori']);
                } while ($configData['giocatori'][$indice_impostore]['numero'] == 1);
            }

            // First assign all players as "buono"
            foreach ($configData['giocatori'] as $index => &$giocatore) {
                $giocatore['ruolo'] = ($index == $indice_impostore) ? 'impostore' : 'buono';
            }

            // Randomly select one good player to be the fool
            $goodPlayersIndices = array_keys(array_filter($configData['giocatori'], function ($g) {
                return $g['ruolo'] === 'buono';
            }));

            if (!empty($goodPlayersIndices)) {
                $foolIndex = $goodPlayersIndices[array_rand($goodPlayersIndices)];
                $configData['giocatori'][$foolIndex]['ruolo'] = 'fool';
            }

            unset($giocatore);

            $parole = caricaJson($parole_file)['parole'];
            $configData['parola'] = $parole[array_rand($parole)];

            $configData['game_id'] = generaGameId();
            salvaJson($config_file, $configData);

            echo json_encode([
                'status' => 'success',
                'first_player' => $configData['giocatori'][$indice_impostore]['nickname'],
                'game_id' => $configData['game_id']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nessun giocatore disponibile']);
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

    // **Nuove Azioni Combinata**
    if ($action == 'get_all_data') {
        $nickname = trim($_GET['nickname']);
        $configData = caricaJson($config_file);

        // Trova la parola e il ruolo per il nickname
        $word = '';
        $role = '';
        foreach ($configData['giocatori'] as $giocatore) {
            if (strcasecmp($giocatore['nickname'], $nickname) == 0) {
                $role = $giocatore['ruolo'];
                // Both fool and buono players see the word
                $word = ($giocatore['ruolo'] === 'impostore') ? '???' : $configData['parola'];
                break;
            }
        }

        echo json_encode([
            'giocatori' => $configData['giocatori'],
            'game_id' => $configData['game_id'],
            'word' => $word,
            'role' => $role
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