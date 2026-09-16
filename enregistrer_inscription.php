<?php // CONTRÔLEUR : enregistrer_inscription.php

// Rôle : Valider un formulaire d'inscription et enregistrer un nouveau compte utilisateur.
// Paramètres : 
//      - POST fournit le pseudo, le courriel, le mot de passe et le jeton CSRF
// Retour : Une redirection vers l'accueil en cas de réussite ou inscription.php avec des erreurs.



// Étape 1 : On prépare des valeurs vides pour pouvoir réafficher le formulaire après une erreur.
$valeurs_inscription = [
    'pseudo' => '',
    'courriel' => ''
];
$erreurs = [
    'pseudo' => '',
    'courriel' => '',
    'mot_de_passe' => ''
];
$erreur_generale = '';
$jeton_csrf = '';
$formulaire_disponible = false;

// Étape 2 : L'inscription modifie la base et accepte donc uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire d’inscription.');
}

// Étape 3 : On charge la session, les validations, la protection CSRF et le modèle du compte.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/validation_compte.php';
require_once 'model/utilisateur.php';

// Étape 4 : Un utilisateur connecté ne peut pas créer un second compte depuis sa session actuelle.
if (isConnected()) {
    header('Location: afficher_accueil.php');
    exit;
}

$jeton_csrf = obtenir_jeton_csrf();
$formulaire_disponible = true;

// Étape 5 : On lit les valeurs envoyées. Seuls le pseudo et le courriel pourront être réaffichés.
$valeurs_inscription['pseudo'] = lire_texte_post('pseudo');
$valeurs_inscription['courriel'] = lire_texte_post('courriel');
$mot_de_passe = lire_mot_de_passe_post('mot_de_passe');
$jeton_recu = lire_texte_post('jeton_csrf');

// Étape 6 : Le jeton doit être valide avant toute recherche ou écriture concernant le compte.
if (!verifier_jeton_csrf($jeton_recu)) {
    $erreur_generale = 'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.';
} else {
    // Étape 7 : Chaque valeur est contrôlée et chaque erreur est placée près du champ concerné.
    $erreurs['pseudo'] = valider_pseudo_compte($valeurs_inscription['pseudo']);
    $erreurs['courriel'] = valider_courriel_compte($valeurs_inscription['courriel']);
    $erreurs['mot_de_passe'] = valider_mot_de_passe_compte($mot_de_passe);

    $modele_utilisateur = new utilisateur();

    // Étape 8 : Un pseudo et un courriel déjà utilisés sont refusés après leur première validation.
    if ($erreurs['pseudo'] === '' && $modele_utilisateur->pseudo_existe($valeurs_inscription['pseudo'])) {
        $erreurs['pseudo'] = 'Ce pseudo est déjà utilisé.';
    }

    if ($erreurs['courriel'] === '' && $modele_utilisateur->courriel_existe($valeurs_inscription['courriel'])) {
        $erreurs['courriel'] = 'Cette adresse de courriel est déjà utilisée.';
    }

    // Étape 9 : Lorsque tout est valide, le mot de passe est protégé avant la création du compte.
    if (formulaire_compte_sans_erreur($erreurs)) {
        $mdp_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

        if ($mdp_hash === false) {
            $erreur_generale = 'Le compte ne peut pas être créé pour le moment.';
        } elseif (!$modele_utilisateur->creer_compte(
            $valeurs_inscription['pseudo'],
            $valeurs_inscription['courriel'],
            $mdp_hash
        )) {
            $erreur_generale = 'Le compte ne peut pas être créé pour le moment.';
        } else {
            // Étape 10 : La redirection évite de créer deux comptes lors d'une actualisation du navigateur.
            unset($mot_de_passe);
            unset($mdp_hash);
            header('Location: afficher_accueil.php');
            exit;
        }
    }
}

// Le mot de passe en clair est retiré avant de réafficher le formulaire en erreur.
unset($mot_de_passe);

require_once 'templates/pages/inscription.php';
