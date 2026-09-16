<?php // CONTRÔLEUR : enregistrer_suppression_annonce.php
// Rôle : Supprimer définitivement une annonce encore autorisée et ses photographies associées.
// Paramètres : 
//      - POST fournit l'identifiant de l'annonce et le jeton CSRF
//      - la session fournit l'utilisateur connecté (le vendeur)
// Retour : Une redirection vers l'accueil en cas de réussite ou vers le détail en cas de refus



// Étape 1 : La suppression modifie définitivement les données et accepte uniquement le formulaire POST.
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Cette action attend une confirmation de suppression.');
}
// Étape 2 : On charge la session, la sécurité, les validations et les modèles nécessaires.
require_once 'core/init.php';
require_once 'library/csrf.php';
require_once 'library/validation_annonce.php';
require_once 'model/annonce.php';
require_once 'model/photo.php';
require_once 'model/utilisateur.php';

// Étape 3 : Supprimer une annonce est une action privée, donc un compte existant est obligatoire.
$compte_connecte = exiger_compte_connecte();

// Étape 4 : On lit l'identifiant et le jeton, puis on accepte seulement un identifiant entier positif.
$annonce_id = lire_entier_positif($_POST, 'annonce_id');
$jeton_recu = lire_champ_annonce('jeton_csrf');
if ($annonce_id < 1) {
    header('Location: afficher_detail_annonce.php?id=0');
    exit;
}
// Étape 5 : Un jeton incorrect refuse l'action avant de commencer la transaction.
if (!verifier_jeton_csrf($jeton_recu)) {
    ajouter_message_flash('detail_annonce_erreur', 'La confirmation a expiré ou la suppression n’est pas autorisée.');
    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}
// Étape 6 : La transaction garantit que la suppression réussit complètement ou ne change rien.
$modele_annonce = new annonce();
if (!$modele_annonce->commencer_transaction()) {
    ajouter_message_flash('detail_annonce_erreur', 'L’annonce ne peut pas être supprimée pour le moment.');
    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}
// Étape 7 : On bloque brièvement l'annonce puis on revérifie le propriétaire, la fin et les enchères.
$situation = $modele_annonce->verrouiller_situation_action($annonce_id);
$refus = determiner_refus_action_annonce($situation, idConnected());
if ($refus !== '') {
    $modele_annonce->annuler_transaction();
    if ($refus !== 'introuvable') {
        ajouter_message_flash('detail_annonce_erreur', 'La suppression est refusée : l’annonce doit être en cours, sans enchère et vous appartenir.');
    }
    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}
// Étape 8 : On mémorise les chemins des photographies avant de supprimer leurs lignes dans la base.
$modele_photo = new photo();
$photographies = $modele_photo->lister_par_annonce($annonce_id);
$chemins_photos = [];
foreach ($photographies as $photographie) {
    $chemin_photo = preparer_chemin_photo_annonce($photographie->get('nom'));
    if ($chemin_photo !== '') {
        $chemins_photos[] = $chemin_photo;
    }
}
// Étape 9 : On supprime l'annonce ; les lignes PHOTO et les suivis liés disparaissent avec elle.
$annonce_a_supprimer = new annonce($annonce_id);
$suppression_reussie = $annonce_a_supprimer->is() && $annonce_a_supprimer->delete();
if (!$suppression_reussie) {
    $modele_annonce->annuler_transaction();
    ajouter_message_flash('detail_annonce_erreur', 'L’annonce ne peut pas être supprimée pour le moment.');
    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}
// Étape 10 : La suppression doit être validée définitivement avant de toucher aux fichiers.
if (!$modele_annonce->valider_transaction()) {
    $modele_annonce->annuler_transaction();
    ajouter_message_flash('detail_annonce_erreur', 'L’annonce ne peut pas être supprimée pour le moment.');
    header('Location: afficher_detail_annonce.php?id=' . $annonce_id);
    exit;
}
// Étape 11 : Les fichiers sont retirés seulement après la réussite définitive de la base.
foreach ($chemins_photos as $chemin_photo) {
    if (is_file($chemin_photo)) {
        unlink($chemin_photo);
    }
}
// Étape 12 : Un message confirme la réussite après le retour vers l'accueil.
ajouter_message_flash('accueil_succes', 'L’annonce a été supprimée définitivement.');
header('Location: afficher_accueil.php');
exit;
