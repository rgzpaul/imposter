# Imposter

A fun social game where you try to figure out who's the imposter.

## What is this?

This is a game you can play with friends. Someone is the imposter, and everyone else has to find out who it is.

## How to Play

1. Sign in with Google
2. Enter an **ID Stanza** (4-digit room code) to join a specific room
3. Wait for at least 3 players in your room
4. The first player to set the ID Stanza can start the game
5. Each player gets a role and a secret word (except the imposter)
6. Discuss and try to find who doesn't know the word!

## ID Stanza

The ID Stanza is a numeric room code (max 4 digits) that lets you play with specific people:

- Players with the same ID Stanza see only each other
- Each room has its own independent game (word, roles, game ID)
- The first player to set a specific ID becomes the room leader
- Leave it empty to join the default room

## Roles

- 😇 **Buono**: Knows the secret word and tries to find the imposter
- 😈 **Impostore**: Doesn't know the word and tries to blend in
- 🤪 **Fool**: Knows the word but actually wants to get eliminated

## Game Rules

- Minimum 3 players required to start
- Player #1 cannot be the imposter (unless Brutal Mode is enabled)
- Each player takes turns describing the word without saying it
- Vote to eliminate who you think is the imposter

## Aggiornamenti realtime (Pusher)

L'interfaccia non fa piu' polling continuo: il server pubblica un evento su
[Pusher](https://pusher.com) ad ogni cambiamento (join, uscita, cambio ID
Stanza, avvio partita, reset, cleanup) e i client si aggiornano subito.

- Canale per stanza: `imposter-stanza-<id>` (`imposter-stanza-default` senza ID)
- Canale admin: `imposter-admin`
- Evento: `update`
- Il polling resta solo come heartbeat (45s nel gioco, 60s in admin) per
  mantenere vivo `last_seen`, e torna automaticamente a 1.5s se Pusher non e'
  raggiungibile o se il server non ha il secret configurato.

### Configurazione

`app_id`, `key` e `cluster` sono pubblici e stanno in `assets/pusher.php`.
Il **secret non e' mai committato**: viene letto dalla variabile d'ambiente
`PUSHER_SECRET` oppure dal file `assets/pusher-secret.php`, che e' in
`.gitignore` e viene generato durante il deploy dal GitHub Actions secret
`PUSHER_SECRET` (Settings -> Secrets and variables -> Actions).

In locale basta creare `assets/pusher-secret.php` con
`<?php return 'IL_TUO_SECRET';`. Senza secret il gioco funziona comunque,
ricadendo sul polling.

## Session & Cleanup

- Players stay logged in until they click "Esci" or clear browser data
- Inactive players are automatically removed after 5 minutes
- No need to manually clean up abandoned sessions

## Admin Panel

Access `/admin` to:
- View all players grouped by room
- See roles and secret words for each room
- Toggle Brutal Mode
- Reset the entire session

Enjoy!

