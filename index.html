<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposter Game</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" sizes="192x192" href="./icon.png">
    <link rel="manifest" href="./manifest.json">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div id="nickname-container" class="container" style="display: none;">
        <h2>Inserisci il tuo nickname</h2>
        <input type="text" id="nickname" placeholder="Nickname" class="input-field">
        <button id="join-game" class="btn">Entra</button>
    </div>

    <div id="game-container" class="container" style="display: none;">
        <h4 id="game-id">ID Partita: </h4>
        <h2>Players</h2>
        <ol id="player-list" class="player-list"></ol>
        <div id="role-images">
            <img class="role-image" id="good-image" src="good.png">
            <img class="role-image" id="evil-image" src="evil.png">
            <img class="role-image" id="fool-image" src="fool.png">
        </div>
        <h3 id="word-display"></h3>
        <button id="start-game" class="btn" style="display: none;">Avvia</button>
        <button id="leave-game" class="btn btn-red">Esci</button>
    </div> 

    <p id="last-update" style="margin-top: 20px; color: #f5f5f5;">Ultimo aggiornamento: --:--</p>
    <p id="credits" style="margin-top: auto">Made by Paul</p>
    <a style="width:50%; height:10px" href="/admin"></a>

    <script>
        $(document).ready(function () {
            var nickname = '';
            var isLoggedIn = false;

            if (localStorage.getItem('nickname')) {
                nickname = localStorage.getItem('nickname');
                entraInGioco(nickname);
            } else {
                $('#nickname-container').show();
            }

            function entraInGioco(nickname) {
                $.post('server.php', { action: 'join', nickname: nickname }, function (response) {
                    if (response.status === 'success') {
                        $('#nickname-container').hide();
                        $('#game-container').show();
                        isLoggedIn = true;
                        localStorage.setItem('nickname', nickname);
                        aggiornaDati();
                    }
                }, 'json').fail(function() {
                    console.log('Errore di connessione al server.');
                });
            }

            $('#join-game').click(function () {
                nickname = $('#nickname').val().trim();
                if (nickname !== '') {
                    entraInGioco(nickname);
                }
            });

            $('#leave-game').click(function () {
                $.post('server.php', { action: 'leave', nickname: nickname }, function (response) {
                    if (response.status === 'success') {
                        $('#game-container').hide();
                        $('#nickname-container').show();
                        $('#nickname').val('');
                        isLoggedIn = false;
                        localStorage.removeItem('nickname');
                        // Reset dell'elemento "Ultimo aggiornamento"
                        $('#last-update').text('Ultimo aggiornamento: --:--');
                    }
                }, 'json').fail(function() {
                    console.log('Errore di connessione al server.');
                });
            });

            function aggiornaDati() {
                if (isLoggedIn) {
                    $.get('server.php', { action: 'get_all_data', nickname: nickname, _: new Date().getTime() }, function (response) {
                        // Ordinamento dei giocatori
                        response.giocatori.sort(function(a, b) {
                            return (a.numero || 0) - (b.numero || 0);
                        });
                        // Aggiornamento della lista dei giocatori
                        var listaGiocatori = response.giocatori.map(function(giocatore) {
                            return '<li>' + giocatore.nickname + '</li>';
                        }).join('');
                        $('#player-list').html(listaGiocatori);

                        // Determinare se mostrare il pulsante "Avvia"
                        if (response.giocatori.length > 0) {
                        var primoGiocatore = response.giocatori.reduce(function(prev, current) {
                            return (prev.login_time < current.login_time) ? prev : current;
                        });
                        if (primoGiocatore.nickname === nickname) {
                            $('#start-game').show();
                        } else {
                            $('#start-game').hide();
                        } } else {
                            $('#start-game').hide();
                        }
                        // Aggiornare l'ID della partita
                        $('#game-id').text('ID Partita: ' + response.game_id);

                        // Aggiornare la parola
                        $('#word-display').text('La parola è ').append($('<span code>').text(response.word));

            // Show or hide images based on player's role
            if (response.role === 'impostore') {
                // Player is the impostor
                $('#role-images').show();
                $('#good-image').hide();
                $('#fool-image').hide();
                $('#evil-image').show();
            } else if (response.role === 'buono') {
                // Player is not the impostor
                $('#role-images').show();
                $('#evil-image').hide();
                if (response.is_fool) {
                    // Player is the fool
                    $('#good-image').hide();
                    $('#fool-image').show();
                } else {
                    // Regular good player
                    $('#fool-image').hide();
                    $('#good-image').show();
                }
            } else {
                // Role not assigned yet or game not started
                $('#role-images').hide();
                $('#good-image').hide();
                $('#evil-image').hide();
                $('#fool-image').hide();
            }

                        // Verifica se l'utente è ancora nel gioco
                        var userStillInGame = response.giocatori.some(function(giocatore) {
                            return giocatore.nickname === nickname;
                        });

                        if (!userStillInGame) {
                            localStorage.removeItem('nickname');
                            window.location.reload();
                        }

                        // **Aggiornamento dell'ultimo aggiornamento**
                        var currentTime = new Date();
                        var formattedTime = currentTime.toLocaleTimeString('it-IT', { hour12: false });
                        $('#last-update').text('Ultimo aggiornamento: ' + formattedTime);
                    }, 'json').fail(function() {
                        console.log('Errore di connessione al server.');
                    });
                }
            }

            $('#start-game').click(function () {
                $.post('server.php', { action: 'start_game' }, function (response) {
                    if (response.status === 'success') {
                        console.log('Il primo giocatore a iniziare è: ' + response.first_player + '\nL\'impostore è stato selezionato.');
                        $('#game-id').text('ID Partita: ' + response.game_id);
                        aggiornaDati();
                    }
                }, 'json').fail(function() {
                    console.log('Errore di connessione al server.');
                });
            });

            setInterval(aggiornaDati, 2000);
        });
    </script>

    <script src="/pwa.js"></script>

</body>
</html>