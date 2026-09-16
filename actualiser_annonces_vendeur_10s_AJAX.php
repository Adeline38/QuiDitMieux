<?php // CONTRÔLEUR : actualiser_annonces_vendeur_10s_AJAX.php
// Rôle : Fournir en JSON l'état actuel des annonces du vendeur connecté pour l'actualisation à dix secondes.
// Paramètres : 
//      - la session identifie l'utilisateur connecté (le vendeur)
// Retour : Une réponse JSON de réussite avec les cartes ou une erreur fonctionnelle simple.



// Étape 1 : On indique au navigateur que ce contrôleur renvoie des données JSON et non une page HTML.
header('Content-Type: application/json; charset=UTF-8');

// Étape 2 : Cette actualisation lit seulement des données et accepte donc uniquement la méthode GET.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['succes' => false, 'erreur' => 'methode_invalide']);
    exit;
}

// Étape 3 : On charge la session, la préparation des cartes et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/preparation_tableau_de_bord.php';
require_once 'model/annonce.php';
require_once 'model/utilisateur.php';

// Étape 4 : Sans connexion valide, aucune annonce privée ne doit être envoyée.
if (!isConnected()) {
    echo json_encode(['succes' => false, 'erreur' => 'session_expiree']);
    exit;
}

$compte_connecte = userConnected();

// Une session peut encore contenir l'identifiant d'un compte qui n'existe plus.
// Dans ce cas, on ferme cette session et on demande une nouvelle connexion.
if ($compte_connecte === null) {
    disconnect();
    echo json_encode(['succes' => false, 'erreur' => 'session_expiree']);
    exit;
}

// Étape 5 : Le modèle récupère seulement les annonces appartenant au vendeur connecté.
$modele_annonce = new annonce();
$lignes = $modele_annonce->lister_tableau_vendeur(idConnected());

if ($lignes === false) {
    echo json_encode(['succes' => false, 'erreur' => 'chargement_impossible']);
    exit;
}

// Étape 6 : On prépare les cartes puis on les renvoie à JavaScript avec une réponse de réussite.
echo json_encode([
    'succes' => true,
    'annonces' => preparer_cartes_vendeur($lignes)
]);
exit;
