<?php // CONTRÔLEUR : afficher_inscription.php
// Rôle : Préparer et afficher le formulaire public de création d'un compte.
// Paramètres : 
//      - GET ne fournit aucune donnée personnelle
//      - la session sert à vérifier que le visiteur n’est pas déjà connecté
// Retour : Le template reçoit des champs vides et un jeton CSRF.



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

// Ces valeurs vides sont aussi utilisées après une erreur d'inscription.
$valeurs_inscription = ['pseudo' => '', 'courriel' => ''];
$erreurs = ['pseudo' => '', 'courriel' => '', 'mot_de_passe' => ''];
$erreur_generale = '';
$jeton_csrf = obtenir_jeton_csrf();
$formulaire_disponible = true;

// Le template affiche les valeurs préparées sans traiter de données.
require_once 'templates/pages/inscription.php';
