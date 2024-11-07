<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Imposter Game</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" sizes="192x192" href="./icon.png">
    <link rel="manifest" href="./manifest.json">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div id="admin-container" class="container">
        <h2>Gestione Giocatori</h2>
        <table id="players-table" class="players-table">
            <thead>
                <tr>
                    <th>Nickname</th>
                    <th>Ruolo</th>
                    <th>Numero</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>

        <div id="game-info" class="game-info">
            <p><strong>ID Partita:</strong> <span id="game-id">-</span></p>
            <p><strong>Parola:</strong> <span id="game-word">-</span></p>
        </div>

        <div class="brutal-mode">
            <input type="checkbox" id="brutal-mode-toggle">
            <label for="brutal-mode-toggle">Brutal Mode (il primo può essere impostore)</label>
        </div>

        <button id="reset-session" class="btn btn-red">Reset</button>
    </div>

    <script>
        $(document).ready(function () {
            function aggiornaDatiAdmin() {
                $.get('server.php', { action: 'get_admin_data' }, function (response) {
                    // Aggiornamento della tabella dei giocatori
                    var giocatoriHTML = response.giocatori.map(function(giocatore) {
                        var ruolo = giocatore.ruolo ? giocatore.ruolo : '-';
                        if (ruolo === 'buono' && giocatore.is_fool) {
                            ruolo = 'fool';  // Indica che il giocatore è il fool
                        }
                        var numero = giocatore.numero !== null ? giocatore.numero : '-';
                        return '<tr><td>' + giocatore.nickname + '</td><td>' + ruolo + '</td><td>' + numero + '</td></tr>';
                    }).join('');
                    $('#players-table tbody').html(giocatoriHTML);

                    // Aggiornamento della Brutal Mode
                    $('#brutal-mode-toggle').prop('checked', response.brutal_mode);

                    // Aggiornamento ID Partita e Parola
                    $('#game-id').text(response.game_id ? response.game_id : '-');
                    $('#game-word').text(response.game_word ? response.game_word : '-');
                    
                }, 'json').fail(function() {
                    console.log('Errore di connessione al server.');
                });
            }

            $('#brutal-mode-toggle').change(function () {
                var brutal_mode = $(this).is(':checked');
                $.post('server.php', { action: 'set_brutal_mode', brutal_mode: brutal_mode }, function (response) {
                    if (response.status === 'success') {
                        console.log('Brutal Mode aggiornata');
                    }
                }, 'json').fail(function() {
                    console.log('Errore di connessione al server.');
                });
            });

            $('#reset-session').click(function () {
                if (confirm('Sei sicuro di voler resettare la sessione?')) {
                    $.post('server.php', { action: 'reset' }, function (response) {
                        if (response.status === 'success') {
                            console.log(response.message);
                            location.reload();
                        }
                    }, 'json').fail(function() {
                        console.log('Errore di connessione al server.');
                    });
                }
            });

            aggiornaDatiAdmin();
            setInterval(aggiornaDatiAdmin, 2000);
        });
    </script>

    <script src="/pwa.js"></script>

</body>
</html>