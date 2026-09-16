<?php // CONTRÔLEUR : enregistrer_suppression_suivi.php
// Rôle : Contrôler puis retirer le suivi volontaire d'une annonce sans supprimer les enchères de l'utilisateur.
// Paramètres : 
//      - POST fournit l'identifiant de l'annonce et le jeton CSRF
//      - la session fournit l'utilisateur connecté
// Retour : Une redirection vers le détail avec un message de réussite ou de refus.



// Étape 1 : L'arrêt du suivi modifie la base et accepte uniquement le formulaire POST prévu.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire d’arrêt du suivi.');
}

// Étape 2 : On charge la session, la protection CSRF et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'model/annonce.php';
require_once 'model/utilisateur.php';

// Étape 3 : Arrêter un suivi est une action privée, donc un compte existant est obligatoire.
$compte_connecte = exiger_compte_connecte();

// Étape 4 : On lit l'identifiant de l'annonce et on accepte seulement un nombre entier positif.
$annonce_id = lire_entier_positif($_POST, 'annonce_id');

if ($annonce_id < 1) {
    header('Location: afficher_accueil.php');
    exit;
}

$destination = 'afficher_detail_annonce.php?id=' . $annonce_id;
$jeton_recu = '';

// Étape 5 : On lit le jeton seulement s'il existe et s'il s'agit bien d'un texte.
if (isset($_POST['jeton_csrf']) && is_string($_POST['jeton_csrf'])) {
    $jeton_recu = $_POST['jeton_csrf'];
}

// Étape 6 : Le jeton empêche un autre site de retirer un suivi au nom de l'utilisateur.
if (!verifier_jeton_csrf($jeton_recu)) {
    ajouter_message_flash('detail_annonce_erreur', 'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.');
    header('Location: ' . $destination);
    exit;
}

// Étape 7 : Le modèle retire seulement le suivi volontaire et conserve toutes les enchères.
$modele_annonce = new annonce();
$resultat = $modele_annonce->supprimer_suivi($annonce_id, idConnected());

// Étape 8 : Chaque résultat du modèle reçoit un message simple qui sera affiché sur le détail.
if ($resultat === 'succes') {
    ajouter_message_flash('detail_annonce_succes', 'Vous ne suivez plus volontairement cette annonce.');
} elseif ($resultat === 'non_suivie') {
    ajouter_message_flash('detail_annonce_erreur', 'Cette annonce ne faisait pas partie de vos suivis.');
} else {
    ajouter_message_flash('detail_annonce_erreur', 'Le suivi ne peut pas être retiré pour le moment.');
}

// Étape 9 : On revient toujours au détail de l'annonce pour montrer le résultat de l'action.
header('Location: ' . $destination);
exit;
