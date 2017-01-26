# Annabot 2.x - Configuration
Il est possible de préconfigurer certaines features d'Annabot dans le but d'en faire un
véritable outil de communication.

Les fichiers de configuration se trouvent dans le dossier `Includes` d'Annabot.

## Configuration de l'environnement
Configurer l'environnement permet de simplifier la connexion au serveur.

Il faut copier le fichier `Includes/Env.inc.php.sample` vers `Includes/Env.inc.php`.

Paramètre | Valeur | Description
---|---|---
$chatProdVersion | string | Version du chat en production
$chatProdHost | string | Url du chat en production
$chatDevVersion | string | Version du chat de développement
$chatDevHost | string | Url du chat de développement

## Configuration des alertes de mise à jour
Annabot embarque un système de notification de mise à jour. Seules les mises à jour stables
sont notifiées. La notification est effectuée une fois par jour à 14h00 sur l'@ et en privé
(comptes Anomaly et Gecko).

Pour activer les mises à jour il faut disposer d'un compte GitHub qui aura accès aux sources 
d'Annabot.

Il faut copier le fichier `Includes/GitHub.inc.php.sample` vers `Include/GitHub.inc.php`.

Sur GitHub il faut créer un [token d'accès personnel](https://github.com/settings/tokens) 
disposant des droits `repo:status`, `repo_deployment`, `public_repo`.

### Paramétrer le module de mise à niveau
Paramètre | Valeur | Description
---|---|---
GitHub_username | string | Nom du compte
GitHub_usertoken | string | [token d'accès personnel](https://github.com/settings/tokens)
GitHub_reporting | bool | Le bot ouvre un ticket sur GitHub en cas d'exception levée (ignore les exceptions des modules)
GitHub_versionCheck | bool | Vérifie si une mise à jour est disponible, si oui un message est émis sur le chat

## Mises à jour
Pour des raisons de sécurité Annabot n'embarque pas de système de mise à niveau automatique.