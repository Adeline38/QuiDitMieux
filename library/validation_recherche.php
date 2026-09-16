<?php
//// TYPE : name.php
// Rôle : Regrouper les contrôles serveur des critères de recherche d'annonces.
// Paramètres : Les fonctions reçoivent les valeurs transmises dans l'adresse par la méthode GET.
// Retour : Les fonctions fournissent des valeurs nettoyées ou signalent une donnée invalide.

// Rôle : Lire un critère de recherche sans provoquer d'erreur si sa forme est incorrecte.
// Paramètres : $nom_champ contient le nom exact attendu dans GET.
// Retour : Le texte sans espaces extérieurs, ou null si la valeur reçue n'est pas un texte.
function lire_critere_recherche($nom_champ)
{
    if (!isset($_GET[$nom_champ])) {
        return '';
    }

    if (!is_string($_GET[$nom_champ])) {
        return null;
    }

    return trim($_GET[$nom_champ]);
}

// Rôle : Contrôler et normaliser une limite de prix compatible avec DECIMAL(6,2).
// Paramètres : $prix contient la valeur minimale ou maximale reçue par le contrôleur.
// Retour : Le prix avec deux décimales, une chaîne vide si le champ est vide, ou null si la valeur est invalide.
function preparer_prix_recherche($prix)
{
    if ($prix === '') {
        return '';
    }

    if (!is_string($prix)) {
        return null;
    }

    $prix_normalise = str_replace(',', '.', $prix);

    if (!preg_match('/^[0-9]{1,4}([.][0-9]{1,2})?$/', $prix_normalise)) {
        return null;
    }

    $prix_nombre = (float) $prix_normalise;

    if ($prix_nombre < 0 || $prix_nombre > 9999.99) {
        return null;
    }

    return number_format($prix_nombre, 2, '.', '');
}

// Rôle : Transformer les mots saisis en éléments distincts utilisables par la recherche SQL.
// Paramètres : $texte contient les mots-clés nettoyés du formulaire.
// Retour : Un tableau de mots non vides qui devront tous être présents dans le titre ou la description.
function separer_mots_recherche($texte)
{
    if ($texte === '') {
        return [];
    }

    $mots = preg_split('/\s+/u', $texte);

    if ($mots === false) {
        return [];
    }

    return $mots;
}
