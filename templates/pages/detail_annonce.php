<?php // TEMPLATE : detail_annonce.php

// Rôle : Afficher le détail public d'une annonce, ses photographies et les actions autorisées.
// Paramètres : 
//      Le contrôleur fournit l'annonce, les photos, l'état de connexion et les messages fonctionnels
/**
        * @var mixed $est_connecte : Adapte le menu de navigation

        * @var mixed $annonce_detail : Infos publiques de l'annonce
        * @var mixed $photographies_detail : Photos autorisées à afficher
        * @var mixed $historique_encheres : Enchères visibles autorisées
        * @var mixed $est_vendeur : Statut vendeur de l'annonce
        * @var mixed $est_participant : Statut enchérisseur actif
        * @var mixed $est_plus_offrant : Statut de meilleur enchérisseur
        * @var mixed $peut_encherir : Droit de placer une enchère
        * @var mixed $peut_modifier : Droit d'éditer l'annonce
        * @var mixed $peut_suivre : Droit de suivre la vente
        * @var mixed $peut_arreter_suivi : Droit d'arrêter le suivi
        * @var mixed $montant_minimum : Seuil minimal de surenchère
        * @var mixed $gagnant : Identité du vainqueur
        * @var mixed $historique_autorise : Accès autorisé à l'historique

        * @var mixed $message_action : Message sur l'action en cours
        * @var mixed $message_succes : Confirmation du succès actif

        * @var mixed $message_erreur : Alerte de l'échec actif
        * @var mixed $erreur_detail : Alerte si annonce manquante
        * @var string $jeton_csrf : Jeton de sécurité anti-fraude
*/
// Retour : Une page HTML complète consultable avec ou sans connexion.


// Étape 1 : On prépare un titre général avant de savoir si l'annonce existe.
$titre_page = 'Détail d’une annonce — QuiDitMieux';
// Étape 2 : Si l'annonce existe, son titre devient aussi le titre de l'onglet du navigateur.
if ($annonce_detail !== null) {
    $titre_page = $annonce_detail['titre'] . ' — QuiDitMieux';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // On charge le fichier qui contient les réglages invisibles et la feuille de styles.
    require_once 'templates/fragments/head.php'; ?>
    <!-- Ce fichier JavaScript permet de changer la grande photographie sans recharger la page. -->
    <script src="public/assets/js/detail_annonce.js" defer></script>
</head>
<body>
    <div class="cadre-application">
        <?php // On charge le menu du haut adapté à la présence ou à l'absence d'une connexion.
        require_once 'templates/fragments/header.php'; ?>
        <main id="contenu-principal" class="page-detail-annonce">
            <?php // Étape 3 : On choisit entre deux affichages possibles.
            // Si l'annonce est absente, on montre seulement un message d'erreur.
            // Si elle existe, on montre ses photographies, ses informations et les actions autorisées.
            ?>
            <?php if ($erreur_detail !== '') { ?>
                <section class="message message--erreur detail-annonce__introuvable" role="alert">
                    <p class="message__titre">Annonce indisponible</p>
                    <p><?= echapper_html($erreur_detail) ?></p>
                    <a class="bouton detail-annonce__retour" href="afficher_accueil.php">Revenir à l’accueil</a>
                </section>
            <?php } else { ?>
                <!-- PARTIE 1 : LES PHOTOGRAPHIES -->
                <?php // La galerie utilise uniquement les fichiers validés par le contrôleur.
                // Elle affiche un message simple lorsqu'aucune photographie n'est disponible.
                ?>
                <section class="galerie" aria-label="Photographies de l’annonce">
                    <?php if (empty($photographies_detail)) { ?>
                        <div class="galerie__photo-absente">
                            <span>Aucune photographie disponible</span>
                        </div>
                    <?php } else { ?>
                        <img class="galerie__image-principale" src="<?= echapper_html($photographies_detail[0]['url']) ?>" alt="<?= echapper_html($photographies_detail[0]['alt']) ?>" data-galerie-principale >
                        <?php // La boucle crée un bouton miniature pour chaque photographie de l'annonce. ?>
                        <div class="galerie__miniatures" aria-label="Choisir une photographie">
                            <?php foreach ($photographies_detail as $index => $photographie) { ?>
                                <button class="galerie__commande<?php if ($index === 0) { ?> est-active<?php } ?>" type="button" aria-label="Afficher la photographie <?= (int) $photographie['position'] ?>" aria-pressed="<?php if ($index === 0) { ?>true<?php } else { ?>false<?php } ?>" data-galerie-miniature ><img class="galerie__miniature" src="<?= echapper_html($photographie['url']) ?>" alt="<?= echapper_html($photographie['alt']) ?>" ></button>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </section>
                <!-- PARTIE 2 : LES INFORMATIONS PUBLIQUES -->
                <?php // Le titre, le statut, la description et les informations de vente sont visibles par tous. ?>
                <article class="detail-annonce__informations">
                    <!-- Le titre et le statut permettent de reconnaître rapidement l'annonce et l'état de la vente. -->
                    <div class="detail-annonce__titre-et-statut">
                        <h1><?= echapper_html($annonce_detail['titre']) ?></h1>
                        <span class="etiquette"><?= echapper_html($annonce_detail['statut']) ?></span>
                    </div>
                    <p class="detail-annonce__description"><?= nl2br(echapper_html($annonce_detail['description'])) ?></p>
                    <!-- Le prix courant correspond au prix de départ ou à la meilleure enchère déjà placée. -->
                    <div class="detail-annonce__prix">
                        <span>Prix courant</span>
                        <strong><?= echapper_html($annonce_detail['prix_courant']) ?> €</strong>
                    </div>
                    <!-- Cette liste regroupe les autres informations utiles pour comprendre la vente. -->
                    <dl class="detail-annonce__liste">
                        <dt>Vendeur</dt>
                        <dd><?= echapper_html($annonce_detail['vendeur']) ?></dd>
                        <dt>Fin de la vente</dt>
                        <dd><?= echapper_html($annonce_detail['fin_vente']) ?></dd>
                        <dt>Nombre d’enchères</dt>
                        <dd><?= (int) $annonce_detail['nombre_encheres'] ?></dd>
                        <dt>État de l’objet</dt>
                        <dd><?= echapper_html($annonce_detail['etat']) ?></dd>
                        <dt>Catégorie</dt>
                        <dd><?= echapper_html($annonce_detail['categorie']) ?></dd>
                    </dl>
                </article>
                <!-- PARTIE 3 : LES ACTIONS ET L'HISTORIQUE -->
                <?php // Le contrôleur a calculé le rôle de la personne : visiteur, vendeur, participant ou plus-offrant.
                // Le template affiche seulement les actions autorisées pour ce rôle et pour l'état de la vente.
                ?>
                <aside class="detail-annonce__actions" aria-labelledby="titre-actions-annonce">
                    <h2 id="titre-actions-annonce">Participer à la vente</h2>
                    <?php // Les messages temporaires confirment le résultat du dernier formulaire sans conserver sa soumission. ?>
                    <?php if ($message_succes !== '') { ?>
                        <div class="message message--succes detail-annonce__message-action" role="status">
                            <p class="message__titre">Action réussie</p>
                            <p><?= echapper_html($message_succes) ?></p>
                        </div>
                    <?php } ?>
                    <?php if ($message_erreur !== '') { ?>
                        <div class="message message--erreur detail-annonce__message-action" role="alert">
                            <p class="message__titre">Action impossible</p>
                            <p><?= echapper_html($message_erreur) ?></p>
                        </div>
                    <?php } ?>
                    <?php // Ce message explique la situation actuelle de l'utilisateur dans la vente. ?>
                    <div class="detail-annonce__situation<?php if ($est_plus_offrant === true) { ?> detail-annonce__situation--favorable<?php } elseif ($est_participant === true && $est_plus_offrant === false) { ?> detail-annonce__situation--depassee<?php } ?>">
                        <p><?= echapper_html($message_action) ?></p>
                    </div>
                    <?php // Le vendeur voit ce lien uniquement lorsque l'annonce peut encore être modifiée ou supprimée. ?>
                    <?php if ($peut_modifier === true) { ?>
                        <a class="bouton detail-annonce__action-principale" href="afficher_modification_annonce.php?id=<?= (int) $annonce_detail['id'] ?>">
                            Modifier ou supprimer l’annonce
                        </a>
                    <?php } ?>
                    <?php // Un utilisateur connecté peut commencer ou arrêter le suivi d'une annonce qui ne lui appartient pas.
                    // Chaque formulaire POST contient l'identifiant de l'annonce et le jeton de sécurité.
                    ?>
                    <?php if ($peut_suivre === true) { ?>
                        <form class="detail-annonce__formulaire-suivi" action="enregistrer_suivi.php" method="post">
                            <input type="hidden" name="annonce_id" value="<?= (int) $annonce_detail['id'] ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                            <button class="bouton bouton--contour" type="submit">Suivre cette annonce</button>
                        </form>
                    <?php } elseif ($peut_arreter_suivi === true) { ?>
                        <form class="detail-annonce__formulaire-suivi" action="enregistrer_suppression_suivi.php" method="post">
                            <input type="hidden" name="annonce_id" value="<?= (int) $annonce_detail['id'] ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                            <button class="bouton bouton--contour" type="submit">Ne plus suivre</button>
                        </form>
                    <?php } ?>
                    <?php // Un visiteur reçoit un lien de connexion lorsque la vente est ouverte.
                    // Un utilisateur autorisé reçoit à la place le formulaire permettant de proposer un montant.
                    ?>
                    <?php if ($est_connecte === false && $annonce_detail['statut'] === 'En cours') { ?>
                        <a class="bouton detail-annonce__action-principale" href="afficher_connexion.php">Se connecter</a>
                    <?php } elseif ($est_connecte === true && $est_vendeur === false && $annonce_detail['statut'] === 'En cours' && $montant_minimum !== '') { ?>
                        <?php // Le montant doit dépasser le prix courant indiqué sous le champ.
                        // Le formulaire reste visible mais désactivé lorsque l'utilisateur est déjà le plus-offrant.
                        ?>
                        <form class="formulaire detail-annonce__formulaire-enchere" action="enregistrer_enchere.php" method="post">
                            <?php // Ces champs cachés désignent l'annonce et protègent l'envoi du formulaire. ?>
                            <input type="hidden" name="annonce_id" value="<?= (int) $annonce_detail['id'] ?>">
                            <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                            <div class="champ">
                                <label class="champ__libelle" for="montant-enchere">Votre enchère</label>
                                <div class="detail-annonce__champ-montant">
                                    <input class="champ__saisie" id="montant-enchere" name="montant" type="number" min="<?= echapper_html($montant_minimum) ?>" max="9999.99" step="0.01" inputmode="decimal" required <?php if ($est_plus_offrant === true) { ?>disabled<?php } ?> aria-describedby="aide-montant-enchere" >
                                    <span aria-hidden="true">€</span>
                                </div>
                                <p class="champ__aide" id="aide-montant-enchere">Montant strictement supérieur à <?= echapper_html($annonce_detail['prix_courant']) ?> €.</p>
                            </div>
                            <button class="bouton" type="submit" <?php if ($peut_encherir === false) { ?>disabled<?php } ?>>Enchérir</button>
                        </form>
                    <?php } ?>
                    <?php // Le gagnant est affiché après la fin seulement aux personnes qui ont le droit de le connaître. ?>
                    <?php if ($gagnant !== '') { ?>
                        <div class="message message--succes detail-annonce__gagnant">
                            <p class="message__titre">Vente remportée</p>
                            <p>Gagnant : <strong><?= echapper_html($gagnant) ?></strong></p>
                        </div>
                    <?php } ?>
                    <?php // L'historique est réservé au vendeur et aux utilisateurs ayant déjà enchéri.
                    // Selon la situation, cette zone affiche un refus, une liste vide ou les enchères autorisées.
                    ?>
                    <section class="historique-encheres" aria-labelledby="titre-historique-encheres">
                        <h2 id="titre-historique-encheres">Historique des enchères</h2>
                        <?php if ($historique_autorise === false) { ?>
                            <p>L’historique détaillé est réservé au vendeur et aux participants.</p>
                        <?php } elseif (empty($historique_encheres)) { ?>
                            <p>Aucune enchère n’a encore été placée.</p>
                        <?php } else { ?>
                            <?php // La boucle affiche le pseudo, la date et le montant de chaque enchère. ?>
                            <ol class="historique-encheres__liste">
                                <?php foreach ($historique_encheres as $ligne_historique) { ?>
                                    <li class="historique-encheres__ligne">
                                        <div>
                                            <strong><?= echapper_html($ligne_historique['pseudo']) ?></strong>
                                            <time><?= echapper_html($ligne_historique['date_heure']) ?></time>
                                        </div>
                                        <span><?= echapper_html($ligne_historique['montant']) ?> €</span>
                                    </li>
                                <?php } ?>
                            </ol>
                        <?php } ?>
                    </section>
                </aside>
            <?php } ?>
        </main>
        <?php // On charge le pied de page commun qui termine la page.
        require_once 'templates/fragments/footer.php';
        ?>
    </div>
</body>
</html>
