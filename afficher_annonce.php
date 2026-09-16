<?php // CONTRÔLEUR : afficher_annonce.php
// Rôle : Préparer le formulaire permettant à un utilisateur connecté de publier une annonce.
// Paramètres : 
//      - la session fournit l'identifiant de l'utilisateur
// Retour : Le template annonce.php reçoit un formulaire vide, les catégories et les protections nécessaires.



// Les listes communes évitent de recopier les mêmes champs dans plusieurs contrôleurs.
require_once 'library/validation_annonce.php';

// Étape 1 : On prépare toutes les valeurs attendues par le formulaire de création.
$est_connecte = false;
$pseudo_connecte = '';
$jeton_csrf = '';
$categories = [];
$publication_disponible = false;
$erreur_generale = '';
$methode_http = '';
$mode_modification = false;
$annonce_id = 0;
$photographies_existantes = [];
$identifiants_photos_supprimees = [];
$photo_principale_choisie = '';
$date_minimale = date('Y-m-d');
$etats_annonce = obtenir_etats_annonce();
$valeurs_annonce = obtenir_valeurs_annonce_vides();
$erreurs_annonce = obtenir_erreurs_annonce_vides();

// Étape 2 : On charge la fonction qui protège les textes affichés dans le template.
require_once 'library/fonctions.php';

// Étape 3 : On lit la méthode utilisée pour demander la page.
if (isset($_SERVER['REQUEST_METHOD'])) {
    $methode_http = $_SERVER['REQUEST_METHOD'];
}

// Étape 4 : Ce contrôleur affiche seulement le formulaire et accepte donc uniquement la méthode GET.
if ($methode_http !== 'GET') {
    $erreur_generale = 'Cette page est accessible uniquement en consultation.';
    require_once 'templates/pages/annonce.php';
    exit;
}

// Étape 5 : On charge la session, la protection CSRF et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/categorie.php';
require_once 'model/utilisateur.php';

// Étape 6 : Publier une annonce est une action privée, donc une connexion valide est obligatoire.
// Une session liée à un compte supprimé ne donne pas accès à une page privée.
$compte_connecte = exiger_compte_connecte();

$est_connecte = true;
$pseudo_connecte = $compte_connecte->get('pseudo');
$jeton_csrf = obtenir_jeton_csrf();

// Étape 7 : Le modèle interroge l'API en arrière-plan pour obtenir les catégories autorisées.
$modele_categorie = new categorie();
$categories_recues = $modele_categorie->lister();

if ($categories_recues === null) {
    $erreur_generale = 'Les catégories ne peuvent pas être chargées pour le moment.';
} else {
    $categories = $categories_recues;
    $publication_disponible = true;
}

// Étape 8 : Le template reçoit le formulaire vide, les catégories et le jeton de sécurité.
require_once 'templates/pages/annonce.php';
