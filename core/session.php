<?php
//// TYPE : session.php
// Rôle : Fournir depuis le dossier core les fonctions communes qui gèrent la session et l'utilisateur connecté.
// Paramètres : Les fonctions utilisent la session PHP et, selon le besoin, l'identifiant d'un utilisateur.
// Retour : Les fonctions retournent l'état de connexion, l'identifiant ou l'utilisateur connecté.

function initSession() {
    // Rôle : Démarrer la session si elle n'est pas déjà active.
    // Paramètres : Aucun paramètre n'est nécessaire.
    // Retour : true si une connexion utilisateur valide existe dans la session, sinon false.

    // Le contrôle évite de tenter un second démarrage lorsque la session est déjà active.
    if (session_status() === PHP_SESSION_NONE) {
        // Si le dossier prévu par PHP n'est pas accessible, le dossier temporaire de Windows est utilisé.
        // PHP peut ainsi enregistrer la session sans modifier les droits des dossiers de Laragon.
        $dossier_sessions = session_save_path();

        if ($dossier_sessions === '' || !is_writable($dossier_sessions)) {
            session_save_path(sys_get_temp_dir());
        }

        session_start();
    }

    return isConnected();
}

function connect($id) {
    // Rôle : Enregistrer dans la session l'identifiant de l'utilisateur qui vient de se connecter.
    // Paramètres : $id contient l'identifiant de l'utilisateur authentifié.
    // Retour : true si l'identifiant est valide et enregistré, sinon false.

    // Seul un nombre entier positif peut représenter la clé primaire d'un utilisateur authentifié.
    $identifiant_valide = filter_var(
        $id,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($identifiant_valide === false) {
        return false;
    }

    // Le changement d'identifiant empêche un tiers de réutiliser un identifiant de session connu avant la connexion.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    if (!session_regenerate_id(true)) {
        return false;
    }

    // Le jeton anonyme est retiré après le changement de niveau d'accès ; le prochain formulaire en créera un nouveau.
    unset($_SESSION['jeton_csrf']);

    // La session conserve seulement l'état de connexion et l'identifiant utile aux contrôleurs.
    $_SESSION['connected'] = true;
    $_SESSION['id'] = (int) $identifiant_valide;

    return true;
}

function isConnected() {
    // Rôle : Vérifier si la session contient une connexion utilisateur valide.
    // Paramètres : Aucun paramètre n'est nécessaire.
    // Retour : true si l'utilisateur est connecté avec un identifiant valide, sinon false.
    // Les deux informations sont obligatoires pour éviter de considérer une session incomplète comme connectée.
    if (!isset($_SESSION['connected'], $_SESSION['id'])) {
        return false;
    }

    // La comparaison stricte refuse par exemple le texte "1", qui ne correspond pas au booléen attendu.
    if ($_SESSION['connected'] !== true) {
        return false;
    }

    // L'identifiant doit conserver le format entier positif imposé lors de la connexion.
    if (!is_int($_SESSION['id']) || $_SESSION['id'] < 1) {
        return false;
    }

    return true;
}

function disconnect() {
    // Rôle : Supprimer les données et le cookie de la session de l'utilisateur.
    // Paramètres : Aucun paramètre n'est nécessaire.
    // Retour : true lorsque les informations de connexion ont été supprimées.

    global $userConnected;

    // Les données en mémoire sont effacées avant de détruire la session enregistrée par PHP.
    $_SESSION = [];
    $userConnected = null;

    // Si PHP utilise un cookie de session, son expiration empêche le navigateur de le renvoyer ensuite.
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (ini_get('session.use_cookies')) {
            $parametres_cookie = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parametres_cookie['path'],
                $parametres_cookie['domain'],
                $parametres_cookie['secure'],
                $parametres_cookie['httponly']
            );
        }

        // La destruction supprime les données de session conservées par le serveur.
        session_destroy();
    }

    return true;
}

function idConnected() {
    // Rôle : Obtenir l'identifiant de l'utilisateur actuellement connecté.
    // Paramètres : Aucun paramètre n'est nécessaire.
    // Retour : L'identifiant de l'utilisateur connecté, ou zéro en l'absence de connexion valide.

    // La valeur zéro représente explicitement l'absence d'un utilisateur connecté.
    if (!isConnected()) {
        return 0;
    }

    return $_SESSION['id'];
}

function userConnected() {
    // Rôle : Charger une seule fois l'objet représentant l'utilisateur connecté.
    // Paramètres : Aucun paramètre n'est nécessaire.
    // Retour : L'objet utilisateur chargé, ou null si aucune connexion ou aucun modèle utilisateur n'est disponible.

    global $userConnected;

    // Aucun objet utilisateur ne doit être créé pour un visiteur anonyme.
    if (!isConnected()) {
        return null;
    }

    // Cette vérification évite une erreur tant que le futur modèle utilisateur n'est pas encore chargé.
    if (!class_exists('utilisateur')) {
        return null;
    }

    // L'objet est chargé une seule fois, puis réutilisé pendant le reste de la requête.
    if (empty($userConnected)) {
        $userConnected = new utilisateur();

        if (!$userConnected->load(idConnected())) {
            $userConnected = null;
        }
    }

    return $userConnected;
}

function messageConnexion() {
    // Rôle : Fournir un message simple décrivant l'état de connexion courant.
    // Paramètres : Aucun paramètre n'est nécessaire.
    // Retour : Une chaîne destinée à un contrôle temporaire de l'état de connexion.

    // Le message traduit simplement le résultat booléen pour un affichage ou un contrôle pédagogique.
    if (isConnected()) {
        return 'Connecté';
    }

    return 'Pas connecté';
}

function ajouter_message_flash($destination, $message) {
    // Rôle : Conserver un court message qui sera affiché une seule fois après une redirection.
    // Paramètres : $destination désigne la page autorisée et $message contient le texte fonctionnel à transmettre.
    // Retour : true si le message est enregistré, sinon false.

    // La liste blanche empêche la création de rubriques de session imprévues depuis un appel incorrect.
    $destinations_autorisees = [
        'connexion',
        'profil',
        'accueil_succes',
        'detail_annonce_succes',
        'detail_annonce_erreur'
    ];

    if (!in_array($destination, $destinations_autorisees, true)) {
        return false;
    }

    if (!is_string($message) || trim($message) === '') {
        return false;
    }

    $_SESSION['messages_flash'][$destination] = trim($message);

    return true;
}

function lire_message_flash($destination) {
    // Rôle : Lire puis supprimer le message temporaire destiné à une page.
    // Paramètres : $destination désigne la page qui demande son message.
    // Retour : Le message enregistré ou une chaîne vide lorsqu'il n'existe pas.

    // Une destination inconnue ne doit jamais permettre de lire une autre donnée de session.
    $destinations_autorisees = [
        'connexion',
        'profil',
        'accueil_succes',
        'detail_annonce_succes',
        'detail_annonce_erreur'
    ];

    if (!in_array($destination, $destinations_autorisees, true)) {
        return '';
    }

    if (!isset($_SESSION['messages_flash'][$destination])) {
        return '';
    }

    $message = $_SESSION['messages_flash'][$destination];
    unset($_SESSION['messages_flash'][$destination]);

    if (!is_string($message)) {
        return '';
    }

    return $message;
}
