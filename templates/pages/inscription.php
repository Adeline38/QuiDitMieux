<?php // TEMPLATE : inscription.php
// Rôle : Afficher le formulaire public de création d'un compte.
// Paramètres : 
//      Le contrôleur fournit les valeurs non sensibles
/**
        * @var bool $formulaire_disponible : Droit d'afficher les champs
        * @var mixed $valeurs_inscription : 

        * @var array $erreurs : Erreurs de saisie par champ
        * @var string $erreur_generale : Message d'échec de connexion
        * @var string $jeton_csrf : Code secret de sécurité caché qui prouve que le formulaire vient bien de notre propre site web
*/
// Retour : Une page HTML complète destinée au navigateur.



// Le titre apparaît dans l'onglet du navigateur.
$titre_page = 'Inscription — QuiDitMieux';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // Le fragment contient les réglages communs et la feuille de styles. ?>
    <?php require_once 'templates/fragments/head.php'; ?>
</head>
<body>
    <main id="contenu-principal" class="page-authentification">
        <!-- Cette partie présente le service et les informations nécessaires au compte. -->
        <section class="presentation-authentification" aria-labelledby="titre-presentation">
            <a class="presentation-authentification__monogramme" href="afficher_accueil.php"
                aria-label="QuiDitMieux, revenir à l'accueil">Q</a>
            <h1 id="titre-presentation" class="presentation-authentification__titre">Rejoignez QuiDitMieux</h1>
            <p>Créez votre compte et commencez à suivre ou enchérir sur les objets qui vous intéressent.</p>
        </section>
        <!-- Le formulaire affiche seulement les valeurs et erreurs préparées par le contrôleur. -->
        <section class="zone-authentification" aria-labelledby="titre-formulaire">
            <div class="formulaire-authentification">
                <h2 id="titre-formulaire">Créer un compte</h2>
                <p class="formulaire-authentification__introduction">Pseudo, courriel et mot de passe suffisent.</p>
                <?php if ($erreur_generale !== '') { ?>
                    <div class="message message--erreur formulaire-authentification__message" role="alert">
                        <p class="message__titre">Inscription impossible</p>
                        <p><?= echapper_html($erreur_generale) ?></p>
                    </div>
                <?php } ?>
                <?php // Le formulaire est affiché seulement lorsque le contrôleur l'autorise. ?>
                <?php if ($formulaire_disponible === true) { ?>
                    <form class="formulaire" action="enregistrer_inscription.php" method="post">
                        <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                        <?php // Le pseudo est le nom public visible par les autres utilisateurs. ?>
                        <div class="champ<?php if ($erreurs['pseudo'] !== '') { ?> est-invalide<?php } ?>">
                            <label class="champ__libelle" for="pseudo">Pseudo</label>
                            <input class="champ__saisie" id="pseudo" name="pseudo" type="text" maxlength="255" autocomplete="username" value="<?= echapper_html($valeurs_inscription['pseudo']) ?>" placeholder="Saisir pseudo" aria-describedby="erreur-pseudo" <?php if ($erreurs['pseudo'] !== '') { ?>aria-invalid="true"<?php } ?> required>
                            <p class="champ__erreur" id="erreur-pseudo"><?= echapper_html($erreurs['pseudo']) ?></p>
                        </div>
                        <?php // Le courriel reste privé et sert à retrouver le compte. ?>
                        <div class="champ<?php if ($erreurs['courriel'] !== '') { ?> est-invalide<?php } ?>">
                            <label class="champ__libelle" for="courriel">Adresse de courriel</label>
                            <input class="champ__saisie" id="courriel" name="courriel" type="email" maxlength="255" autocomplete="email" value="<?= echapper_html($valeurs_inscription['courriel']) ?>" placeholder="Saisir adresse de courriel" aria-describedby="erreur-courriel" <?php if ($erreurs['courriel'] !== '') { ?>aria-invalid="true"<?php } ?> required>
                            <p class="champ__erreur" id="erreur-courriel"><?= echapper_html($erreurs['courriel']) ?></p>
                        </div>
                        <?php // Le mot de passe est masqué et doit contenir au moins huit caractères. ?>
                        <div class="champ<?php if ($erreurs['mot_de_passe'] !== '') { ?> est-invalide<?php } ?>">
                            <label class="champ__libelle" for="mot-de-passe">Mot de passe</label>
                            <input class="champ__saisie" id="mot-de-passe" name="mot_de_passe" type="password" minlength="8" autocomplete="new-password" placeholder="Saisir mot de passe" aria-describedby="erreur-mot-de-passe" <?php if ($erreurs['mot_de_passe'] !== '') { ?>aria-invalid="true"<?php } ?> required>
                            <p class="champ__erreur" id="erreur-mot-de-passe"><?= echapper_html($erreurs['mot_de_passe']) ?></p>
                        </div>
                        <!-- Le bouton envoie les informations au contrôleur d'inscription. -->
                        <button class="bouton formulaire-authentification__bouton" type="submit">Créer mon compte</button>
                    </form>
                <?php } ?>
                <p class="formulaire-authentification__navigation">Déjà inscrit ? <a href="afficher_connexion.php">Se connecter</a></p>
            </div>
        </section>
    </main>
</body>
</html>
