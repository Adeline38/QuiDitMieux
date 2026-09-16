<?php // CONTRÔLEUR : enregistrer_enchere.php
// Rôle : Contrôler puis enregistrer une enchère sur l'annonce d'un autre utilisateur.
// Paramètres : 
//      - POST fournit l'identifiant de l'annonce, le montant et le jeton CSRF
//      - la session fournit l'utilisateur connecté (l'enchérisseur)
// Retour : Une redirection vers le détail avec un message fonctionnel de réussite ou de refus



// Étape 1 : Une enchère modifie la base et accepte uniquement le formulaire POST prévu.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire d’enchère.');
}

// Étape 2 : On charge la session, les validations, la protection CSRF et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/validation_enchere.php';
require_once 'model/enchere.php';
require_once 'model/utilisateur.php';

// Étape 3 : Enchérir est une action privée, donc une connexion et un compte existant sont obligatoires.
$compte_connecte = exiger_compte_connecte();

// Étape 4 : L'identifiant reçu doit être un nombre entier positif avant toute utilisation.
$annonce_id = filter_var(
    lire_champ_enchere('annonce_id'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($annonce_id === false) {
    header('Location: afficher_accueil.php');
    exit;
}

$annonce_id = (int) $annonce_id;
$destination = 'afficher_detail_annonce.php?id=' . $annonce_id;
$jeton_recu = lire_champ_enchere('jeton_csrf');

// Étape 5 : Le jeton est contrôlé avant de lire ou d'enregistrer une enchère.
if (!verifier_jeton_csrf($jeton_recu)) {
    ajouter_message_flash(
        'detail_annonce_erreur',
        'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.'
    );
    header('Location: ' . $destination);
    exit;
}

// Étape 6 : Le montant est préparé avec deux décimales et doit rester dans les limites de la base.
$montant = preparer_montant_enchere(lire_champ_enchere('montant'));

if ($montant === null) {
    ajouter_message_flash(
        'detail_annonce_erreur',
        'Saisissez un montant positif avec au maximum deux décimales.'
    );
    header('Location: ' . $destination);
    exit;
}

// Étape 7 : Le modèle revérifie la vente, le rôle, le prix et le plus-offrant au moment de l'écriture.
$modele_enchere = new enchere();
$resultat = $modele_enchere->enregistrer_enchere(
    $annonce_id,
    idConnected(),
    $montant
);

// Étape 8 : Chaque résultat du modèle reçoit un message simple qui sera affiché sur le détail.
if ($resultat === 'succes') {
    ajouter_message_flash('detail_annonce_succes', 'Votre enchère a bien été enregistrée.');
} elseif ($resultat === 'annonce_introuvable') {
    ajouter_message_flash('detail_annonce_erreur', 'L’annonce demandée est introuvable.');
} elseif ($resultat === 'annonce_personnelle') {
    ajouter_message_flash('detail_annonce_erreur', 'Vous ne pouvez pas enchérir sur votre propre annonce.');
} elseif ($resultat === 'vente_terminee') {
    ajouter_message_flash('detail_annonce_erreur', 'Cette vente est terminée. Aucune nouvelle enchère n’est acceptée.');
} elseif ($resultat === 'deja_plus_offrant') {
    ajouter_message_flash('detail_annonce_erreur', 'Vous êtes déjà le plus-offrant de cette vente.');
} elseif ($resultat === 'montant_insuffisant') {
    ajouter_message_flash('detail_annonce_erreur', 'Votre montant doit être strictement supérieur au prix courant.');
} else {
    ajouter_message_flash('detail_annonce_erreur', 'Votre enchère ne peut pas être enregistrée pour le moment.');
}

// Étape 9 : On revient toujours au détail de l'annonce pour montrer le résultat de l'action.
header('Location: ' . $destination);
exit;
