# Annabot 2.x - Modules 
Annabot dispose d'une gestion des fonctionalités sous forme de module.  
Il est fortement déconseillé de toucher au noyau du bot. 
 
## Créer un module 
Dans un premier temps il faut créer un nouveau dossier dans le dossier `Modules`. Ensuite 
un fichier Main.php est requis avec une structure de base (voir `Modules/Example/Main.example.php`). 
 
Le fichier Main.php est celui qui est censé recevoir les listeners. Si le traitement est trop 
lourd des sous-classes dédiées peuvent êtres créées afin de faciliter le maintien du code. 
 
Le module créé dépend du Namespace `Module\NomDuModule`, si ce namespace est ajouté en début de 
fichier dans les classes enfants, elles peuvent être chargées sans utiliser les fonctions `require` et `include`. 
 
Le bot utilise le chargement des classes en suivant le modèle Autoload PSR-4. 
 
## Events 
 
Annabot dispose d'une gestion asynchrone des évènements; pour les utiliser il suffit de créer des `listeners` 
et éventuellement des `emitters` spécifiques. 
 
### Ecouter un event 
 
Dans la majorité des cas les modules seront basés sur l'écoute d'évènements, l'écoute est assez simple 
à appréhender, voici un exemple :
 
```php 
// On écoute la connexion des membres 
$bot->on('user.connected', function($e) { 
  
  // Quand un membre se connecte on envoie un message en salon 
  $this->tell('Un membre vient de se connecter'); 
  
}); 
``` 
 
Certains events, à cause de la rapidité des requêtes (2-3/s), peuvent nécessiter l'utilisation de `once` qui 
garantit qu'on aura pas deux fois cet event, attention c'est mutualisé entre les modules.
 
```php 
// On écoute la connexion du bot 
$bot->once('self.connected', function($e) { 
 
  // Quand le bot est connecté on envoie un message en salon 
  $this->say('Salut les noobs!'); 
  
}); 
``` 
 
### Emettre un event 
 
Dans certains cas les modules peuvent être amenés à émettre un event pour d'autres modules 
ou tout simplement pour simplifier le code en séparant certaines couches. 
 
Le second paramètre contient les données de l'event, ça doit toujours être un tableau (array). 
 
**Tous les events émis depuis un module sont préfixé par `mod.`** 
 
```php 
// On émet un évènement 
$bot->emit('dire.pwet',[]);
```

### Ecouter un event provenant d'un module

Pour éviter tout problème les events des modules sont automatiquement préfixés par `:`.

Pour remprendre l'exemple au dessus, écouter l'event `dire.pwet` devra être effectué comme ceci:
```PHP
$bot->on(':dire.pwet', function($data) {

  // On affiche un message
  $bot->say('Event dire pwet émise!');
  
});
```
 
### Events prédéfinis 
Voici la liste des events disponibles, certains events ne sont disponibles que si le bot dispose d'un compte 
de niveau suffisant. 
 
Event | Description | Etat
----|----|----
self.connected | Le bot est connecté au chat mais pas prêt | Stable
self.online | Le bot est visible des autres membres mais pas encore prêt | Stable
self.ready | Le bot est prêt à l'utilisation | Stable
self.shutdown | Le bot s'arrête | Stable
self.away | Le bot a changé de statut | Stable 
self.nopm | Le bot a changé l'état de l'option "pas de MP" | Stable 
self.color | Le bot a changé de couleur | Stable 
self.afk | Le bot a changé l'état du mode A | Stable
self.voice | Le bot a reçu ou s'est vu retiré le droit de parler | Stable 
self.level | Le bot a été promu ou rétrogradé | Stable
self.join | Le bot a changé de salon | Stable
----|----|----
bot.online | Un autre bot est déjà en ligne lors de l'initialisation | Stable
bot.connected | Un autre bot vient de se connecter | Stable
bot.disconnected | Un autre bot vient de se déconnecter | Stable 
bot.away | Un autre bot a changé de statut | Stable 
bot.nopm | Le bot a changé l'état de l'option "pas de MP" | Stable 
bot.color | Le bot a changé de couleur | Stable 
bot.afk | Le bot a changé l'état du mode A | Stable
bot.voice | Un autre a reçu ou s'est vu retiré le droit de parler | Stable 
bot.level | Le bot a été promu ou rétrogradé | Stable
bot.join | Le bot a changé de salon | Stable
----|----|----
user.connected | L'utilisateur vient de se connecter | Stable
user.disconnected | L'utilisateur vient de se déconnecter | Stable 
user.away | L'utilisateur a changé de statut | Stable 
user.nopm | L'utilisateur a changé l'état de l'option "pas de MP" | Stable 
user.color | L'utilisateur vient de changer de couleur | Stable 
user.afk | L'utilisateur a changé l'état de l'option "Non disponible" | Stable 
user.voice | L'utilisateur a reçu ou s'est vu retiré le droit de parler | Stable 
user.level | L'utilisateur a été promu ou rétrogradé | Stable 
user.join | L'utilisateur a changé de salon | Stable 
----|----| ----
msg.received | Message reçu en salon | Testing 
msg.sent | Message envoyé en salon | Testing 
----|----|----
pm.received | Message privé reçu | Stable
pm.sent | Message privé envoyé | Stable
----|----|----
notice.received | /NOTICE reçue | TODO
notice.sent | /NOTICE envoyée | TODO
----|----|----
room.created | Un salon à été créé | Stable
room.destroyed | Un salon à été détruit | Stable
room.name | Le nom du salon à changé | Issue #6
room.desc | La description du salon à changée | Issue #6
room.dice | Le /DICE à été (des)activé | Stable
room.mute | Le salon à été (dé)muté | Stable
room.published | Le salon est (in)accessible depuis la page de connexion | Stable
room.private | Le salon (ne) nécessite (pas) une invitation | Stable
 
_Les events `bot.voice` ne seront jamais émises, ne vous reposez pas dessus_ 

## Bangs

Un gestionnaire de bangs est intégré, il permet d'écouter des intructions simplifiées. Libre à vous d'effectuer un traitement dessus.
Les bangs retournent un tableau contenant les utilisateurs et le reste de l'instruction sous forme de chaine (string). Chaque bang enregistré requiert la création d'un manuel 
dans le dossier Man de votre module. Le fichier texte doit respecter certaines choses. Se référrer à la création des manuels pour plus d'infos.

### Enregistrer un bang

```PHP
$bot->registerBang('monbang', Annabot::LEVEL_MEMBER, 'pm');
```

## Tâches CRON 
 
Annabot dispose d'une gestion des tâches CRON, ces tâches sont internes au bot et font référence 
à un event custom qui sera émis au(x) moment(s) voulu(s). Les events des tâches CRON sont préfixés 
par `cron.*` 
 
Ce gestionnaire simplifie la création de tâches récursives en proposant un système similaire à Linux. 
 
### Expression 
 
Une expression CRON est une chaîne représentant le calendrier pour une commande à exécuter. Les paramètres d'un programme CRON sont les suivants: 
 
    *    *    *    *    *    *  
    -    -    -    -    -    -  
    |    |    |    |    |    |  
    |    |    |    |    |    + année [optionnel]  
    |    |    |    |    +----- jour de la semaine (0 - 7) (Dimanche = 0 ou 7)  
    |    |    |    +---------- mois (1 - 12)  
    |    |    +--------------- jour du mois (1 - 31)  
    |    +-------------------- heure (0 - 23)  
    +------------------------- minute (0 - 59)  
      
### Créer une tâche CRON 
 
Pour créer une tâche CRON, c'est facile : 
 
```php 
// On crée un event qui sera émis tous les jours à 12h 
$bot->addCronjob('midi', '0 12 * * * *'); 
``` 
 
Désormais tous les jours à 12h00 l'event `cron.midi` sera émis, il ne vous reste plus qu'à  
effectuer le traitement.

## Commandes bot

Pour qu'un membre puisse gérer ses abonnements ou executer des actions Annabot gère la commande
`/BOT`. Cette commande doit être enregistrée au sein de votre module pour qu'il puisse l'écouter,
une commande non enregistrée sera tout simplement ignorée.

### Enregistrer une commande

Pour enregistrer une commande il faut faire appel à la méthode `registerCmd`. Le premier paramètre 
sera le nom de l'event, le second est le type de compte requis.

```PHP
$bot->registerCmd('salut', Annabot::ADMIN); // on déclare la commande salut, compte admin requis
```

### Ecouter les commandes reçues

Pour des raisons de gestion, il est strictement interdit d'écouter une commande déjà déclarée.
Les commandes sont émises avec le préfixe `cmd.`.

```PHP
// Un admin envoie: /BOT ping
$bot->on('cmd.salut', function($data) {
   $this->tell(key($data['user']), 'hey! ça va?!'); // renvoie pong à l'admin qui viens d'envoyer la commande ping
});
```

## Commandes du chat

Toutes les commandes utiles sont portées sous forme de méthode native.

Méthode | Description
---|---
alert(string $message) | Envoie une alerte aux modérateurs
away(string $away) | Change le statut du bot
awayna(string $away) | Change le statut du bot en mode non disponible
