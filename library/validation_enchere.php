<?php
//// TYPE : name.php
// Rôle : Lire et valider les données reçues par le formulaire de placement d'une enchère.
// Paramètres : Les fonctions reçoivent un nom de champ ou un montant provenant de POST.
// Retour : Les fonctions retournent un texte nettoyé ou un montant prêt pour la base de données.

// Rôle : Lire un champ texte envoyé par le formulaire d'enchère.
// Paramètres : $nom_champ contient le nom exact du champ attendu dans POST.
// Retour : Le texte sans espaces extérieurs, ou une chaîne vide si le champ est absent ou incorrect.
function lire_champ_enchere($nom_champ)
{
    if (!isset($_POST[$nom_champ]) || !is_string($_POST[$nom_champ])) {
        return '';
    }

    return trim($_POST[$nom_champ]);
}

// Rôle : Vérifier puis normaliser un montant d'enchère compatible avec DECIMAL(6,2).
// Paramètres : $montant contient la saisie en euros, avec une virgule ou un point facultatif.
// Retour : Le montant avec exactement deux décimales, ou null si la saisie est invalide.
function preparer_montant_enchere($montant)
{
    if (!is_string($montant)) {
        return null;
    }

    $montant = trim($montant);
    $montant = str_replace(',', '.', $montant);

    // Le format refuse les signes, les exposants et plus de deux chiffres après le séparateur décimal.
    if (!preg_match('/^[0-9]{1,4}(\.[0-9]{1,2})?$/', $montant)) {
        return null;
    }

    $valeur = (float) $montant;

    if ($valeur <= 0 || $valeur > 9999.99) {
        return null;
    }

    return number_format($valeur, 2, '.', '');
}
