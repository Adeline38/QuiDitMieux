<?php // TEMPLATE : profil.php

// Rôle : Afficher les informations privées et le formulaire de modification du compte connecté.
// Paramètres : 
//      Le contrôleur fournit le pseudo, le courriel, les messages, les erreurs et le jeton CSRF
/**
         * @var mixed $valeurs_profil : contient le pseudo et le courriel affichés dans le formulaire
         * @var mixed $message_succes : contient la confirmation d'une modification réussie
 
         * @var mixed $erreurs : contient les erreurs placées près des champs
         * @var mixed $erreur_generale : contient le message général si la modification échoue
         * @var mixed $jeton_csrf : contient la protection du formulaire contre un envoi non autorisé
*/
// Retour : Une page HTML complète réservée à l'utilisateur authentifié


// Étape 1 : On prépare le titre de l'onglet et on indique que le lien « Mon compte » est actif.
$titre_page = 'Mon compte — QuiDitMieux';
$navigation_active = 'compte';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // On charge le fichier qui contient les réglages invisibles et la feuille de styles.
    require_once 'templates/fragments/head.php';
    ?>
</head>
<body>
    <div class="cadre-application">
        <?php // On charge le menu du haut adapté à l'utilisateur connecté.
        require_once 'templates/fragments/header.php';
        ?>
        <main id="contenu-principal" class="page-profil">
            <!-- PARTIE 1 : LA PRÉSENTATION DU COMPTE -->
            <!-- Cette introduction rappelle que le courriel et le mot de passe restent privés. -->
            <header class="page-profil__entete">
                <h1>Mon compte</h1>
                <p class="texte-secondaire">Modifiez vos informations privées. Seul votre pseudo est visible par les autres utilisateurs.</p>
            </header>
            <!-- PARTIE 2 : LE FORMULAIRE DE MODIFICATION -->
            <?php // Ce panneau affiche les informations qui peuvent être modifiées.
            // Le mot de passe enregistré dans la base n'est jamais envoyé dans le template.
            ?>
            <section class="formulaire-profil" aria-label="Informations du compte">
                <?php // Ce message confirme que les nouvelles informations ont bien été enregistrées. ?>
                <?php if ($message_succes !== '') { ?>
                    <div class="message message--succes formulaire-profil__message" role="status">
                        <p class="message__titre">Modification enregistrée</p>
                        <p><?= echapper_html($message_succes) ?></p>
                    </div>
                <?php } ?>
                <?php // Ce message apparaît lorsqu'un problème concerne toute la modification du compte. ?>
                <?php if ($erreur_generale !== '') { ?>
                    <div class="message message--erreur formulaire-profil__message" role="alert">
                        <p class="message__titre">Modification impossible</p>
                        <p><?= echapper_html($erreur_generale) ?></p>
                    </div>
                <?php } ?>
                <?php // Le formulaire envoie les nouvelles informations au contrôleur avec la méthode POST. ?>
                <form class="formulaire" action="enregistrer_modification_profil.php" method="post">
                    <?php // Le jeton caché protège la modification contre une demande faite depuis un autre site. ?>
                    <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                    <!-- CHAMP 1 : LE PSEUDO -->
                    <?php // Le pseudo peut être modifié mais doit rester unique. ?>
                    <div class="champ<?php if ($erreurs['pseudo'] !== '') { ?> est-invalide<?php } ?>">
                        <label class="champ__libelle" for="pseudo">Pseudo</label>
                        <input class="champ__saisie" id="pseudo" name="pseudo" type="text" maxlength="255" autocomplete="username" value="<?= echapper_html($valeurs_profil['pseudo']) ?>" aria-describedby="erreur-pseudo"<?php if ($erreurs['pseudo'] !== '') { ?> aria-invalid="true"<?php } ?> required >
                        <p class="champ__erreur" id="erreur-pseudo"><?= echapper_html($erreurs['pseudo']) ?></p>
                    </div>
                    <!-- CHAMP 2 : L'ADRESSE DE COURRIEL -->
                    <?php // Le courriel reste privé et doit également rester unique. ?>
                    <div class="champ<?php if ($erreurs['courriel'] !== '') { ?> est-invalide<?php } ?>">
                        <label class="champ__libelle" for="courriel">Adresse de courriel</label>
                        <input class="champ__saisie" id="courriel" name="courriel" type="email" maxlength="255" autocomplete="email" value="<?= echapper_html($valeurs_profil['courriel']) ?>" aria-describedby="erreur-courriel"<?php if ($erreurs['courriel'] !== '') { ?> aria-invalid="true"<?php } ?> required >
                        <p class="champ__erreur" id="erreur-courriel"><?= echapper_html($erreurs['courriel']) ?></p>
                    </div>
                    <!-- CHAMP 3 : LE NOUVEAU MOT DE PASSE -->
                    <?php // Ce champ peut rester vide lorsque l'utilisateur ne veut pas changer son mot de passe. ?>
                    <div class="champ<?php if ($erreurs['nouveau_mot_de_passe'] !== '') { ?> est-invalide<?php } ?>">
                        <label class="champ__libelle" for="nouveau-mot-de-passe">Nouveau mot de passe</label>
                        <input class="champ__saisie" id="nouveau-mot-de-passe" name="nouveau_mot_de_passe" type="password" minlength="8" autocomplete="new-password" placeholder="Saisir nouveau mot de passe" aria-describedby="aide-nouveau-mot-de-passe erreur-nouveau-mot-de-passe"<?php if ($erreurs['nouveau_mot_de_passe'] !== '') { ?> aria-invalid="true"<?php } ?> >
                        <p class="champ__aide" id="aide-nouveau-mot-de-passe">Laissez ce champ vide pour conserver le mot de passe actuel. L’ancien mot de passe n’est pas demandé.</p>
                        <p class="champ__erreur" id="erreur-nouveau-mot-de-passe"><?= echapper_html($erreurs['nouveau_mot_de_passe']) ?></p>
                    </div>
                    <!-- Ce bouton envoie les nouvelles informations au contrôleur. -->
                    <button class="bouton formulaire-profil__bouton" type="submit">Enregistrer les modifications</button>
                </form>
            </section>
        </main>
        <?php // On charge le pied de page commun qui termine la page.
        require_once 'templates/fragments/footer.php';
        ?>
    </div>
</body>
</html>
