<?php
//// TYPE : init.php
// Rôle : Initialiser les outils communs et la connexion à la base avant le traitement d'un contrôleur.
// Paramètres : Le fichier utilise les informations locales enregistrées dans config.php.
// Retour : La session, la connexion PDO et les outils communs deviennent disponibles pour le contrôleur.

// Pendant le développement, PHP affiche les erreurs pour aider à les corriger.
// Sur le serveur de production, cette valeur devra être remplacée par '0'.
// ini_set('display_errors', '1');
// error_reporting(E_ALL);

// Charge les fonctions de session, puis démarre la session de l'utilisateur.
// __DIR__ représente toujours le dossier "core".
require_once 'session.php';
initSession();

// Charge la fonction qui permet de protéger une page privée.
require_once dirname(__DIR__) . '/library/require_login.php';

// Construit le chemin du fichier config.php placé à la racine du projet.
// Ce fichier local contient les identifiants et doit rester ignoré par Git et SFTP.
$chemin_configuration = dirname(__DIR__) . '/config.php';

// Arrête le programme si le fichier est absent ou ne peut pas être lu.
if (!is_readable($chemin_configuration)) {
    exit('L’application ne peut pas démarrer pour le moment.');
}

// Exécute config.php afin de rendre ses quatre valeurs globales disponibles.
require_once $chemin_configuration;

// Vérifie que chaque information nécessaire existe.
$cles_requises = [
    'bdd_host',
    'bdd_base',
    'bdd_user',
    'bdd_pwd'
];

foreach ($cles_requises as $cle) {
    // $cle contient successivement le nom de chaque information obligatoire.
    if (!array_key_exists($cle, $GLOBALS)) {
        exit('L’application ne peut pas démarrer pour le moment.');
    }

    // Une valeur doit être du texte pour pouvoir être utilisée par PDO.
    if (!is_string($GLOBALS[$cle])) {
        exit('L’application ne peut pas démarrer pour le moment.');
    }

    // L'hôte, la base et l'utilisateur sont indispensables pour identifier la base à ouvrir.
    // Le mot de passe peut être vide lorsqu'un serveur local MySQL est configuré ainsi.
    if ($cle !== 'bdd_pwd' && trim($GLOBALS[$cle]) === '') {
        exit('L’application ne peut pas démarrer pour le moment.');
    }
}

// Prépare la connexion à MySQL.
$dsn = 'mysql:host=' . $GLOBALS['bdd_host'];
$dsn .= ';dbname=' . $GLOBALS['bdd_base'];
$dsn .= ';charset=utf8mb4';

// Crée la connexion à la base de données.
// Les erreurs des requêtes seront signalées par false afin de rester compatibles avec le modèle parent.
// $bdd reste disponible pour les modèles qui utilisent global $bdd.
$bdd = new PDO(
    $dsn,
    $GLOBALS['bdd_user'],
    $GLOBALS['bdd_pwd'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

// Retire les identifiants de la mémoire globale après la connexion.
unset($GLOBALS['bdd_host']);
unset($GLOBALS['bdd_base']);
unset($GLOBALS['bdd_user']);
unset($GLOBALS['bdd_pwd']);

// Ces variables temporaires ne sont plus nécessaires après la connexion.
unset($chemin_configuration);
unset($cles_requises);
unset($cle);
unset($dsn);

// Charge la classe commune des modèles et les petites fonctions partagées.
require_once '_model.php';
require_once dirname(__DIR__) . '/library/fonctions.php';

// Chaque contrôleur chargera ensuite uniquement les modèles dont il a besoin.
