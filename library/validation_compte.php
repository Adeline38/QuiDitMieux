<?php
//// TYPE : name.php
// Rôle : Regrouper les validations partagées par l'inscription et la modification du compte.
// Paramètres : Les fonctions reçoivent les textes issus des formulaires après lecture de POST.
// Retour : Les fonctions fournissent une valeur nettoyée ou un message d'erreur fonctionnel.

// Rôle : Lire un champ POST uniquement lorsqu'il contient bien du texte.
// Paramètres : $nom_champ contient le nom exact du champ attendu dans le formulaire.
// Retour : Le texte sans espaces extérieurs, ou une chaîne vide si la donnée est absente ou mal formée.
function lire_texte_post($nom_champ)
{
    // Une donnée sous forme de tableau est refusée afin d'éviter une erreur dans trim().
    if (!isset($_POST[$nom_champ]) || !is_string($_POST[$nom_champ])) {
        return '';
    }

    return trim($_POST[$nom_champ]);
}

// Rôle : Lire un mot de passe POST sans modifier ses espaces éventuels.
// Paramètres : $nom_champ contient le nom exact du champ de mot de passe attendu.
// Retour : Le texte reçu, ou une chaîne vide si la donnée est absente ou mal formée.
function lire_mot_de_passe_post($nom_champ)
{
    // Contrairement aux autres champs, les espaces peuvent faire partie du mot de passe choisi.
    if (!isset($_POST[$nom_champ]) || !is_string($_POST[$nom_champ])) {
        return '';
    }

    return $_POST[$nom_champ];
}

// Rôle : Vérifier les règles communes d'un pseudo utilisateur.
// Paramètres : $pseudo contient le pseudo nettoyé à contrôler.
// Retour : Une chaîne vide si le pseudo est valide, sinon un message destiné au formulaire.
function valider_pseudo_compte($pseudo)
{
    if ($pseudo === '') {
        return 'Le pseudo est obligatoire.';
    }

    if (mb_strlen($pseudo, 'UTF-8') > 255) {
        return 'Le pseudo ne peut pas dépasser 255 caractères.';
    }

    if (strpos($pseudo, '@') !== false) {
        return 'Le pseudo ne doit pas contenir le symbole @.';
    }

    return '';
}

// Rôle : Vérifier qu'une adresse de courriel est présente, valide et compatible avec la base.
// Paramètres : $courriel contient l'adresse nettoyée à contrôler.
// Retour : Une chaîne vide si le courriel est valide, sinon un message destiné au formulaire.
function valider_courriel_compte($courriel)
{
    if ($courriel === '') {
        return 'L’adresse de courriel est obligatoire.';
    }

    if (mb_strlen($courriel, 'UTF-8') > 255) {
        return 'L’adresse de courriel ne peut pas dépasser 255 caractères.';
    }

    if (filter_var($courriel, FILTER_VALIDATE_EMAIL) === false) {
        return 'L’adresse de courriel n’est pas valide.';
    }

    return '';
}

// Rôle : Vérifier la longueur d'un mot de passe obligatoire ou facultatif.
// Paramètres : $mot_de_passe contient le texte reçu et $facultatif autorise une valeur vide lors d'une modification.
// Retour : Une chaîne vide si le mot de passe est accepté, sinon un message destiné au formulaire.
function valider_mot_de_passe_compte($mot_de_passe, $facultatif = false)
{
    if ($mot_de_passe === '' && $facultatif === true) {
        return '';
    }

    if ($mot_de_passe === '') {
        return 'Le mot de passe est obligatoire.';
    }

    if (mb_strlen($mot_de_passe, 'UTF-8') < 8) {
        return 'Le mot de passe doit comporter au moins huit caractères.';
    }

    return '';
}

// Rôle : Vérifier qu'un formulaire ne contient aucun message d'erreur de champ.
// Paramètres : $erreurs contient les messages associés aux différents champs.
// Retour : true si tous les messages sont vides, sinon false.
function formulaire_compte_sans_erreur($erreurs)
{
    foreach ($erreurs as $erreur) {
        if ($erreur !== '') {
            return false;
        }
    }

    return true;
}
