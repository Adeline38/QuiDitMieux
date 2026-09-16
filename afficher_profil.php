<?php // CONTRÔLEUR : afficher_profil.php

// Rôle : Charger et afficher les informations privées modifiables du compte connecté.
// Paramètres :  
//      - La session fournit l'identifiant du compte (aucune donnée GET ou POST n'est attendue)
// Retour : Le template profil.php reçoit le pseudo, le courriel, le jeton CSRF et un message éventuel.



// Étape 1 : On prépare des valeurs vides pour que le template reçoive toujours la même structure.
$est_connecte = false;
$pseudo_connecte = '';
$valeurs_profil = [
    'pseudo' => '',
    'courriel' => ''
];
$erreurs = [
    'pseudo' => '',
    'courriel' => '',
    'nouveau_mot_de_passe' => ''
];
$erreur_generale = '';
$message_succes = '';
$jeton_csrf = '';

// Étape 2 : Cette page consulte le compte et accepte donc uniquement la méthode GET.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit('Cette page est accessible uniquement en consultation.');
}

// Étape 3 : On charge la session, la protection CSRF et le modèle du compte.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/utilisateur.php';

// Étape 4 : Le profil est privé, donc une connexion valide est obligatoire.
requireLogin();

// Étape 5 : L'identifiant vient de la session pour empêcher de consulter le compte d'une autre personne.
$compte = new utilisateur(idConnected());

if (!$compte->is()) {
    // Une session liée à un compte absent est invalidée pour empêcher tout accès privé incohérent.
    disconnect();
    initSession();
    ajouter_message_flash('connexion', 'Votre compte n’est plus disponible. Veuillez vous reconnecter.');
    header('Location: afficher_connexion.php');
    exit;
}

// Étape 6 : On prépare seulement les informations privées nécessaires au formulaire et à son en-tête.
$est_connecte = true;
$pseudo_connecte = $compte->get('pseudo');
$valeurs_profil['pseudo'] = $pseudo_connecte;
$valeurs_profil['courriel'] = $compte->get('email');
$message_succes = lire_message_flash('profil');
$jeton_csrf = obtenir_jeton_csrf();

// Étape 7 : Le template reçoit le compte chargé et les protections préparées.
require_once 'templates/pages/profil.php';
