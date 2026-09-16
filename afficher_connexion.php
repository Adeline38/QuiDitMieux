<?php // CONTRÔLEUR : afficher_connexion.php
// Rôle : Préparer et afficher le formulaire public de connexion.
// Paramètres : 
//      - la session sert à vérifier que le visiteur n’est pas déjà connecté et peut contenir un message temporaire
// Retour : Le template reçoit les champs vides, le message et le jeton CSRF



// Ce contrôleur affiche seulement le formulaire avec une requête GET.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit('Cette page est accessible uniquement en consultation.');
}

// La session permet de reconnaître un membre et de protéger le formulaire.
require_once 'core/init.php';
require_once 'library/csrf.php';
if (isConnected()) {
    header('Location: afficher_accueil.php');
    exit;
}

// Ces valeurs vides sont aussi utilisées après une erreur de connexion.
$identifiant = '';
$erreurs = ['identifiant' => '', 'mot_de_passe' => ''];
$erreur_generale = '';
$message_information = lire_message_flash('connexion');
$jeton_csrf = obtenir_jeton_csrf();
$formulaire_disponible = true;

// Le template affiche les valeurs préparées sans traiter de données.
require_once 'templates/pages/connexion.php';
