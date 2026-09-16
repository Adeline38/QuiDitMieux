<?php // TEMPLATE : annonce.php

// Rôle : Afficher le formulaire qui permet de créer ou de modifier une annonce.
// Paramètres :
/**
        * @var int $annonce_id : Identifiant unique de l'annonce

        * @var bool $publication_disponible : Autorisation de publication
        * @var bool $mode_modification : Mode édition actif ou non

        * @var mixed $est_connecte : Adapte le menu de navigation
        * @var mixed $pseudo_connecte : Identifiant du compte actif

        * @var mixed $titre_page : 
        * @var string $titre_formulaire : Grand titre du formulaire
        * @var mixed $valeurs_annonce : Données saisies du formulaire
        * @var mixed $categories : Liste des catégories d'achat
        * @var mixed $etats_annonce : Liste des états de l'objet
        * @var mixed $date_minimale : Date minimale autorisée

        * @var string $photo_principale_choisie : Nom de la photo principale
        * @var array $photographies_existantes : Photos déjà enregistrées
        * @var array $identifiants_photos_supprimees : ID des photos à supprimer

        * @var string $libelle_action : Texte du bouton de validation (ex: "Créer" ou "Modifier")
        * @var string $action_formulaire : URL d'envoi du formulaire

        * @var string $titre_erreur : Message d'erreur ciblé
        * @var mixed $erreurs_annonce : Erreurs de validation par champ
        * @var mixed $erreur_generale : contient le message général si la modification échoue

        * @var string $jeton_csrf : Code secret de sécurité caché qui prouve que le formulaire vient bien de notre propre site web
        * @var mixed $navigation_active : Lien du menu actif (facultative)
*/
// Retour : Une page HTML permet de publier ou de modifier une annonce selon les droits vérifiés


// --- ÉTAPE 1 : LE MODE CREATION (PREMIÈRE PRÉPARATION) ---
// On prépare les textes et l'adresse utilisés pour créer une nouvelle annonce
// Il prépare donc des tiroirs avec des textes comme "Publier une annonce" et prépare l'adresse du fichier de traitement "enregistrer_creation_annonce.php" pour enregistrer la nouvelle histoire
// Donc ces valeurs sont utilisées par défaut lorsque le formulaire est ouvert en mode création
$titre_page = 'Publier une annonce — QuiDitMieux';
$titre_formulaire = 'Publier une annonce';
$action_formulaire = 'enregistrer_creation_annonce.php';
$libelle_action = 'Publier l’annonce';
$titre_erreur = 'Publication indisponible';
$navigation_active = 'publication';

// --- ÉTAPE 2 : LE MODE MODIFICATION ---
// Ici, on fait un test avec un interrupteur VRAI ou FAUX appelé "$mode_modification"
if ($mode_modification === true) {
    // Si c'est VRAI (ce qui signifie que l'objet existe déjà et qu'on veut juste corriger une ligne), on efface les textes de l'ÉTAPE 1 pour les remplacer par un vocabulaire conformé à la modification
    $titre_page = 'Modifier l’annonce — QuiDitMieux';
    $titre_formulaire = 'Modifier l’annonce';
    $action_formulaire = 'enregistrer_modification_annonce.php';
    $libelle_action = 'Enregistrer les modifications';
    $titre_erreur = 'Modification indisponible';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // --- ÉTAPE 3 : LES STYLES ---
    // Le fragment contient les réglages communs et la feuille de styles 
    // Juste après, on charge le JavaScript ("annonce.js") qui va surveiller en direct l'album photo ?>
    <?php // On charge le fichier qui contient les réglages invisibles de la page et la feuille de styles
    require_once 'templates/fragments/head.php'; ?>
    <?php // Ce fichier JavaScript gère les photographies et la fenêtre de confirmation ?>
    <script src="public/assets/js/annonce.js" defer></script>
</head>
<body>
    <div class="cadre-application">
        
        <?php // --- ÉTAPE 4 : BANDEAU DE NAVIGATION EN HAUT --- 
        // Le menu change selon l'état de connexion préparé par le contrôleur ?>
        <?php require_once 'templates/fragments/header.php'; ?>
        <main id="contenu-principal">

            <?php // --- ÉTAPE 5 : APPELER LE FORMULAIRE ---
            // Plutôt que de tout réécrire ici, On appelle le fichier "formulaire_annonce.php"
            // Le formulaire envoie les informations de l'annonce et les photographies facultatives.
            // Son adresse dépend du mode création ou modification préparé au début du fichier ?>
            <?php
            require_once 'templates/fragments/formulaire_annonce.php';
            ?>

            <!-- --- ÉTAPE 6 : LA CONFIRMATION AVANT UNE SUPPRESSION --- -->
            <!-- C'est une sécurité très importante. Si l'interrupteur "$mode_modification" est sur VRAI, On 
            prépare une fenêtre cachée ("<dialog>"). 
            // Cette fenêtre demande une confirmation avant la suppression définitive. Elle existe seulement pendant la modification et utilise un 
            formulaire POST protégé par un jeton secret contre les tricheurs. -->
            <?php if ($mode_modification === true) { ?>
                <dialog class="confirmation-suppression" data-confirmation-suppression aria-labelledby="titre-confirmation-suppression">
                    <form class="formulaire" action="enregistrer_suppression_annonce.php" method="post">
                        <input type="hidden" name="annonce_id" value="<?= (int) $annonce_id ?>">
                        <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                        <h2 id="titre-confirmation-suppression">Supprimer définitivement l’annonce ?</h2>
                        <p>Cette action est définitive. Les informations et les photographies de l’annonce seront supprimées.</p>
                        <div class="annonce__actions">
                            <button class="bouton bouton--contour" type="button" data-fermer-suppression>Annuler</button>
                            <button class="bouton bouton--danger" type="submit">Confirmer la suppression</button>
                        </div>
                    </form>
                </dialog>
            <?php } ?>
        </main>

        <?php 
        // --- LE FOOTER --- 
        // Le pied de page commun termine la page
        require_once 'templates/fragments/footer.php'; ?>
    </div>
</body>
</html>
