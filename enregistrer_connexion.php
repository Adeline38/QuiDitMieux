<?php // CONTRÔLEUR : enregistrer_connexion.php

// Rôle : Vérifier les identifiants reçus et ouvrir une session authentifiée.
// Paramètres : 
//      - POST fournit un pseudo ou courriel, un mot de passe et le jeton CSRF (La session doit contenir le jeton correspondant)
// Retour : Une redirection vers l'accueil en cas de réussite ou connexion.php avec un message général.



// Étape 1 : On prépare des valeurs vides pour pouvoir réafficher le formulaire après une erreur.
$identifiant = '';
$erreurs = [
    'identifiant' => '',
    'mot_de_passe' => ''
];
$erreur_generale = '';
$message_information = '';
$jeton_csrf = '';
$formulaire_disponible = false;

// Étape 2 : La connexion modifie la session et accepte donc uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend l’envoi du formulaire de connexion.');
}

// Étape 3 : On charge la session, les validations, la protection CSRF et le modèle du compte.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/validation_compte.php';
require_once 'model/utilisateur.php';

// Étape 4 : Un utilisateur déjà connecté est renvoyé vers l'accueil.
if (isConnected()) {
    header('Location: afficher_accueil.php');
    exit;
}

// Étape 5 : On lit les trois valeurs envoyées par le formulaire sans conserver le mot de passe dans le template.
$jeton_csrf = obtenir_jeton_csrf();
$formulaire_disponible = true;
$identifiant = lire_texte_post('identifiant');
$mot_de_passe = lire_mot_de_passe_post('mot_de_passe');
$jeton_recu = lire_texte_post('jeton_csrf');

// Étape 6 : Le jeton doit être valide avant de rechercher le compte dans la base.
if (!verifier_jeton_csrf($jeton_recu)) {
    $erreur_generale = 'Le formulaire a expiré ou la requête n’est pas autorisée. Veuillez réessayer.';
} else {
    // Étape 7 : On vérifie que les deux champs obligatoires ne sont pas vides.
    if ($identifiant === '') {
        $erreurs['identifiant'] = 'Le pseudo ou l’adresse de courriel est obligatoire.';
    }

    if ($mot_de_passe === '') {
        $erreurs['mot_de_passe'] = 'Le mot de passe est obligatoire.';
    }

    // Étape 8 : On cherche le compte puis on compare le mot de passe avec sa version protégée.
    // Le même message est utilisé pour ne jamais révéler si le compte existe.
    if ($erreurs['identifiant'] === '' && $erreurs['mot_de_passe'] === '') {
        $modele_utilisateur = new utilisateur();
        $compte = $modele_utilisateur->trouver_par_identifiant($identifiant);
        $identifiants_valides = false;

        if ($compte !== null) {
            $identifiants_valides = password_verify(
                $mot_de_passe,
                $compte->get('mdp_hash')
            );
        }

        if (!$identifiants_valides) {
            $erreur_generale = 'Adresse de courriel, pseudo ou mot de passe incorrect.';
        } elseif (!connect($compte->id())) {
            $erreur_generale = 'La connexion ne peut pas être établie pour le moment.';
        } else {
            // Étape 9 : connect() renouvelle l'identifiant de session avant le retour vers l'accueil.
            unset($mot_de_passe);
            header('Location: afficher_accueil.php');
            exit;
        }
    }
}

// Étape 10 : Le mot de passe en clair est retiré avant de réafficher le formulaire en erreur.
unset($mot_de_passe);

require_once 'templates/pages/connexion.php';
