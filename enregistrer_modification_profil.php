<?php // CONTRÔLEUR : enregistrer_modification_profil.php

// Rôle : Valider et enregistrer les modifications du compte actuellement connecté.
// Paramètres : 
//      - POST fournit les nouvelles valeurs de pseudo, email, nouveau_mot_de_passe facultatif et jeton CSRF
//      - la session fournit l'identifiant du compte à modifier
// Retour : Une redirection vers afficher_profil ou profil.php avec les erreurs fonctionnelles.


// Étape 1 : On prépare des valeurs vides pour pouvoir réafficher le formulaire après une erreur.
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

// Étape 2 : La modification du compte est une écriture et accepte uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire du compte.');
}

// Étape 3 : On charge la session, les validations, la protection CSRF et le modèle du compte.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/validation_compte.php';
require_once 'model/utilisateur.php';

// Étape 4 : Cette action est privée, donc une connexion valide est obligatoire.
requireLogin();

// Étape 5 : On charge tout le compte avant sa modification, comme le demande le modèle parent.
$compte = new utilisateur(idConnected());

if (!$compte->is()) {
    disconnect();
    initSession();
    ajouter_message_flash('connexion', 'Votre compte n’est plus disponible. Veuillez vous reconnecter.');
    header('Location: afficher_connexion.php');
    exit;
}

$est_connecte = true;
$pseudo_connecte = $compte->get('pseudo');
$jeton_csrf = obtenir_jeton_csrf();

// Étape 6 : On lit les valeurs envoyées. Le mot de passe ne sera jamais renvoyé au template.
$valeurs_profil['pseudo'] = lire_texte_post('pseudo');
$valeurs_profil['courriel'] = lire_texte_post('courriel');
$nouveau_mot_de_passe = lire_mot_de_passe_post('nouveau_mot_de_passe');
$jeton_recu = lire_texte_post('jeton_csrf');

// Étape 7 : Le jeton est vérifié avant les contrôles et l'écriture en base.
if (!verifier_jeton_csrf($jeton_recu)) {
    $erreur_generale = 'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.';
} else {
    // Étape 8 : On contrôle les trois valeurs selon les mêmes règles que lors de l'inscription.
    $erreurs['pseudo'] = valider_pseudo_compte($valeurs_profil['pseudo']);
    $erreurs['courriel'] = valider_courriel_compte($valeurs_profil['courriel']);
    $erreurs['nouveau_mot_de_passe'] = valider_mot_de_passe_compte(
        $nouveau_mot_de_passe,
        true
    );

    // Étape 9 : L'identifiant du compte est exclu pour ne pas considérer ses valeurs actuelles comme des doublons.
    if ($erreurs['pseudo'] === '' && $compte->pseudo_existe(
        $valeurs_profil['pseudo'],
        (int) $compte->id()
    )) {
        $erreurs['pseudo'] = 'Ce pseudo est déjà utilisé.';
    }

    if ($erreurs['courriel'] === '' && $compte->courriel_existe(
        $valeurs_profil['courriel'],
        (int) $compte->id()
    )) {
        $erreurs['courriel'] = 'Cette adresse de courriel est déjà utilisée.';
    }

    // Étape 10 : Lorsque tout est valide, le nouveau mot de passe éventuel est protégé avant l'écriture.
    if (formulaire_compte_sans_erreur($erreurs)) {
        $nouveau_hash = null;

        if ($nouveau_mot_de_passe !== '') {
            $nouveau_hash = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);

            if ($nouveau_hash === false) {
                $erreur_generale = 'Les modifications ne peuvent pas être enregistrées pour le moment.';
            }
        }

        if ($erreur_generale === '') {
            if (!$compte->modifier_compte(
                $valeurs_profil['pseudo'],
                $valeurs_profil['courriel'],
                $nouveau_hash
            )) {
                $erreur_generale = 'Les modifications ne peuvent pas être enregistrées pour le moment.';
            } else {
                // Étape 11 : Un message temporaire confirmera la réussite après la redirection vers le profil.
                unset($nouveau_mot_de_passe);
                unset($nouveau_hash);
                ajouter_message_flash('profil', 'Vos informations ont été mises à jour.');
                header('Location: afficher_profil.php');
                exit;
            }
        }
    }
}

// Le mot de passe en clair est retiré avant de réafficher le formulaire en erreur.
unset($nouveau_mot_de_passe);

require_once 'templates/pages/profil.php';
