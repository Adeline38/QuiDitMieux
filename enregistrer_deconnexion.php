<?php // CONTRÔLEUR : enregistrer_deconnexion.php

// Rôle : Fermer complètement la session d'un utilisateur puis revenir à l'accueil public.
// Paramètres : 
//      - POST fournit le jeton CSRF
//      - l'identité provient uniquement de la session (session connectée possédant le jeton correspondant)
// Retour : Une redirection vers l'accueil ou un message simple si le jeton est invalide



// Étape 1 : La déconnexion modifie la session et accepte uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend le formulaire de déconnexion.');
}

// Étape 2 : On charge la session et l'outil qui vérifie le jeton de sécurité.
require_once 'core/init.php';
require_once 'library/csrf.php';

// Étape 3 : Si la session est déjà absente, le résultat attendu est simplement l'accueil public.
if (!isConnected()) {
    header('Location: afficher_accueil.php');
    exit;
}

// Étape 4 : On lit le jeton seulement s'il existe et s'il s'agit bien d'un texte.
$jeton_recu = '';

if (isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])) {
    $jeton_recu = $_POST['jeton_csrf'];
}

// Étape 5 : Un jeton incorrect refuse la demande et laisse la session intacte.
if (!verifier_jeton_csrf($jeton_recu)) {
    exit('La déconnexion a été refusée. Veuillez revenir à la page précédente.');
}

// Étape 6 : On ferme la session puis on renvoie l'utilisateur vers l'accueil.
disconnect();
header('Location: afficher_accueil.php');
exit;
