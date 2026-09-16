<?php // CONTRÔLEUR : actualiser_annonces_suivies_2s_AJAX.php
// Rôle : Fournir en JSON les annonces suivies, enchéries et remportées pour l'actualisation à deux secondes.
// Paramètres : 
//      - la session identifie l'utilisateur connecté
// Retour : Une réponse JSON de réussite avec deux listes ou une erreur fonctionnelle simple.



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

// Étape 4 : Sans connexion valide, aucune information privée ne doit être envoyée.
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

// Étape 5 : Le modèle récupère les suivis et les participations du compte connecté.
$modele_annonce = new annonce();
$lignes = $modele_annonce->lister_tableau_acheteur(idConnected());

if ($lignes === false) {
    echo json_encode(['succes' => false, 'erreur' => 'chargement_impossible']);
    exit;
}

// Étape 6 : Les lignes reçues sont séparées entre les annonces suivies et les ventes remportées.
$cartes = preparer_cartes_acheteur($lignes);

// Étape 7 : On renvoie les deux listes à JavaScript avec une réponse de réussite.
echo json_encode([
    'succes' => true,
    'annonces_suivies' => $cartes['suivies'],
    'annonces_remportees' => $cartes['remportees']
]);
exit;
