<?php // CONTRÔLEUR : afficher_tableau_de_bord.php
// Rôle : Préparer les trois sections privées du tableau de bord de l'utilisateur connecté.
// Paramètres : 
//      - la session fournit l'identifiant du compte (aucune donnée GET ou POST n'est attendue)
// Retour : Le template tableau_de_bord.php reçoit les annonces vendues, suivies, enchéries et remportées.



// Étape 1 : Le tableau de bord consulte des données et accepte donc uniquement la méthode GET.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    exit('Cette page est accessible uniquement en consultation.');
}

// Étape 2 : On charge la session, la sécurité, la préparation des cartes et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/preparation_tableau_de_bord.php';
require_once 'model/annonce.php';
require_once 'model/utilisateur.php';

// Étape 3 : Le tableau de bord est privé, donc une connexion et un compte existant sont obligatoires.
$compte_connecte = exiger_compte_connecte();

// Étape 4 : On prépare les informations communes utilisées par le menu et les formulaires de la page.
$est_connecte = true;
$pseudo_connecte = $compte_connecte->get('pseudo');
$jeton_csrf = obtenir_jeton_csrf();
$erreur_tableau = '';
$modele_annonce = new annonce();

// Étape 5 : Le modèle récupère les ventes du compte puis ses suivis et ses participations.
$lignes_vendeur = $modele_annonce->lister_tableau_vendeur(idConnected());
$lignes_acheteur = $modele_annonce->lister_tableau_acheteur(idConnected());

if ($lignes_vendeur === false || $lignes_acheteur === false) {
    // Si une lecture échoue, on prépare un message général et des listes vides.
    $erreur_tableau = 'Le tableau de bord ne peut pas être chargé pour le moment.';
    $annonces_vendeur = [];
    $annonces_suivies = [];
    $annonces_remportees = [];
} else {
    // Si les lectures réussissent, les lignes sont transformées en cartes pour les trois sections.
    $annonces_vendeur = preparer_cartes_vendeur($lignes_vendeur);
    $cartes_acheteur = preparer_cartes_acheteur($lignes_acheteur);
    $annonces_suivies = $cartes_acheteur['suivies'];
    $annonces_remportees = $cartes_acheteur['remportees'];
}

// Étape 6 : Le template affiche le premier état avant que JavaScript commence les actualisations automatiques.
require_once 'templates/pages/tableau_de_bord.php';
