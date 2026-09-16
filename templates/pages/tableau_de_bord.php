<?php // TEMPLATE : tableau_de_bord.php

// Rôle : Afficher les trois sections du tableau de bord privé et les informations actualisées par JavaScript
// Paramètres : 
//      Le contrôleur fournit les cartes, l'utilisateur connecté, le jeton CSRF et une éventuelle erreur
/**
        * @var mixed $est_connecte : Adapte le menu de navigation
        * @var mixed $pseudo_connecte : Identifiant du compte actif

        * @var mixed $annonces_vendeur : contient les annonces publiées par l'utilisateur connecté
        * @var mixed $annonces_suivies : contient les annonces suivies ou celles auxquelles il a participé
        * @var mixed $annonces_remportees : contient les ventes terminées qu'il a gagnées

        * @var mixed $erreur_tableau : contient le message général si les données ne peuvent pas être chargées
        * @var mixed $jeton_csrf : contient la protection des formulaires contre un envoi non autorisé
*/
// Retour : Une page HTML complète conforme à l'état initial prévu par la maquette.


// Étape 1 : On prépare le titre de l'onglet et on indique que le lien « Tableau de bord » est actif.
$titre_page = 'Tableau de bord — QuiDitMieux';
$navigation_active = 'tableau_de_bord';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php // On charge le fichier qui contient les réglages invisibles et la feuille de styles.
    require_once 'templates/fragments/head.php';
    ?>
    <!-- Ce fichier JavaScript actualise automatiquement les prix et déplace les ventes remportées. -->
    <script src="public/assets/js/tableau_de_bord.js" defer></script>
</head>
<body>
    <div class="cadre-application">
        <?php // On charge le menu du haut adapté à l'utilisateur connecté.
        require_once 'templates/fragments/header.php';
        ?>
        <?php // Le jeton placé sur la page sera lu par JavaScript pour protéger ses demandes au serveur. ?>
        <main id="contenu-principal" class="page-tableau-de-bord" data-jeton-csrf="<?= echapper_html($jeton_csrf) ?>">
            <?php // Cette introduction annonce le titre et les deux rythmes de mise à jour automatique.
            // Le reste de la page est séparé en trois listes pour aider l'utilisateur à retrouver ses ventes.
            ?>
            <header class="page-tableau-de-bord__entete">
                <h1>Tableau de bord</h1>
                <p class="texte-secondaire">Actualisation : mes annonces 10 s · suivies et enchéries 2 s</p>
            </header>
            <?php // Ce message apparaît si le contrôleur n'a pas réussi à préparer les listes d'annonces. ?>
            <?php if ($erreur_tableau !== '') { ?>
                <div class="message message--erreur page-tableau-de-bord__erreur" role="alert">
                    <p class="message__titre">Chargement impossible</p>
                    <p><?= echapper_html($erreur_tableau) ?></p>
                </div>
            <?php } ?>
            <!-- JavaScript utilise cette zone pour annoncer une mise à jour ou une erreur sans recharger la page. -->
            <p class="page-tableau-de-bord__actualisation" data-message-actualisation role="status" aria-live="polite"></p>
            <!-- PARTIE 1 : LES ANNONCES PUBLIÉES -->
            <?php // Cette première section regroupe les annonces dont l'utilisateur connecté est le vendeur. ?>
            <section class="section-tableau" aria-labelledby="titre-mes-annonces">
                <h2 id="titre-mes-annonces" class="section-tableau__titre">Mes annonces</h2>
                <div class="section-tableau__cartes" data-cartes-vendeur>
                    <?php // Si la liste est vide, un message et un lien proposent de publier une première annonce. ?>
                    <?php if (empty($annonces_vendeur)) { ?>
                        <div class="section-tableau__etat-vide">
                            <p>Vous n’avez publié aucune annonce.</p>
                            <a class="bouton" href="afficher_annonce.php">Publier une annonce</a>
                        </div>
                    <?php } else { ?>
                        <?php // La boucle crée une carte pour chaque annonce publiée par l'utilisateur. ?>
                        <?php foreach ($annonces_vendeur as $carte) { ?>
                            <?php // La couleur de la carte indique la situation préparée par le contrôleur. ?>
                            <article class="carte-tableau<?php if ($carte['couleur'] === 'positive') { ?> carte-tableau--positive<?php } elseif ($carte['couleur'] === 'negative') { ?> carte-tableau--negative<?php } ?>" data-annonce-id="<?= (int) $carte['id'] ?>">
                                <?php // La photographie est affichée lorsqu'elle existe, sinon un cadre explique son absence. ?>
                                <?php if ($carte['photo_url'] !== '') { ?>
                                    <img class="carte-tableau__photo" src="<?= echapper_html($carte['photo_url']) ?>" alt="Photographie de <?= echapper_html($carte['titre']) ?>">
                                <?php } else { ?>
                                    <div class="carte-tableau__photo carte-tableau__photo--absente">Aucune photo</div>
                                <?php } ?>
                                <?php // Cette partie regroupe le statut, le titre, le prix et la fin de la vente. ?>
                                <div class="carte-tableau__informations">
                                    <span class="etiquette<?php if ($carte['couleur'] === 'positive') { ?> etiquette--positive<?php } elseif ($carte['couleur'] === 'negative') { ?> etiquette--negative<?php } else { ?> etiquette--neutre<?php } ?>"><?= echapper_html($carte['statut']) ?></span>
                                    <h3 class="carte-tableau__titre"><a href="afficher_detail_annonce.php?id=<?= (int) $carte['id'] ?>"><?= echapper_html($carte['titre']) ?></a></h3>
                                    <strong class="carte-tableau__prix"><?= echapper_html($carte['prix_courant']) ?> €</strong>
                                    <p class="carte-tableau__metadonnees"><?= (int) $carte['nombre_encheres'] ?> enchère(s) · fin <?= echapper_html($carte['fin_vente']) ?></p>
                                    <?php // Les boutons de modification et de suppression apparaissent seulement si ces actions restent autorisées. ?>
                                    <?php if ($carte['peut_modifier'] === true) { ?>
                                        <div class="carte-tableau__actions">
                                            <a href="afficher_modification_annonce.php?id=<?= (int) $carte['id'] ?>">Modifier</a>
                                            <form action="enregistrer_suppression_annonce.php" method="post" data-formulaire-suppression>
                                                <input type="hidden" name="annonce_id" value="<?= (int) $carte['id'] ?>">
                                                <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                                                <!-- La suppression utilise POST car elle modifie définitivement les données. -->
                                                <button type="submit">Supprimer</button>
                                            </form>
                                        </div>
                                    <?php } ?>
                                </div>
                            </article>
                        <?php } ?>
                    <?php } ?>
                </div>
            </section>
            <!-- PARTIE 2 : LES ANNONCES SUIVIES OU ENCHÉRIES -->
            <?php // Cette section réunit les suivis et les participations, sans répéter les ventes déjà remportées. ?>
            <section class="section-tableau" aria-labelledby="titre-annonces-suivies">
                <h2 id="titre-annonces-suivies" class="section-tableau__titre">Suivies ou enchéries</h2>
                <div class="section-tableau__cartes" data-cartes-suivies>
                    <?php // Si la liste est vide, un lien propose de découvrir les annonces publiques. ?>
                    <?php if (empty($annonces_suivies)) { ?>
                        <div class="section-tableau__etat-vide">
                            <p>Vous ne suivez aucune annonce et n’avez encore placé aucune enchère.</p>
                            <a class="bouton" href="afficher_accueil.php">Découvrir les annonces</a>
                        </div>
                    <?php } else { ?>
                        <?php // La boucle crée une carte pour chaque annonce suivie ou enchérie. ?>
                        <?php foreach ($annonces_suivies as $carte) { ?>
                            <?php // Le vert indique une situation favorable et le rouge indique que l'utilisateur a été dépassé. ?>
                            <article class="carte-tableau<?php if ($carte['couleur'] === 'positive') { ?> carte-tableau--positive<?php } elseif ($carte['couleur'] === 'negative') { ?> carte-tableau--negative<?php } ?>" data-annonce-id="<?= (int) $carte['id'] ?>">
                                <?php // La photographie est affichée lorsqu'elle existe, sinon un cadre explique son absence. ?>
                                <?php if ($carte['photo_url'] !== '') { ?>
                                    <img class="carte-tableau__photo" src="<?= echapper_html($carte['photo_url']) ?>" alt="Photographie de <?= echapper_html($carte['titre']) ?>">
                                <?php } else { ?>
                                    <div class="carte-tableau__photo carte-tableau__photo--absente">Aucune photo</div>
                                <?php } ?>
                                <?php // Cette partie regroupe le statut, le titre, le prix et la fin de la vente. ?>
                                <div class="carte-tableau__informations">
                                    <span class="etiquette<?php if ($carte['couleur'] === 'positive') { ?> etiquette--positive<?php } elseif ($carte['couleur'] === 'negative') { ?> etiquette--negative<?php } else { ?> etiquette--neutre<?php } ?>"><?= echapper_html($carte['statut']) ?></span>
                                    <h3 class="carte-tableau__titre"><a href="afficher_detail_annonce.php?id=<?= (int) $carte['id'] ?>"><?= echapper_html($carte['titre']) ?></a></h3>
                                    <strong class="carte-tableau__prix"><?= echapper_html($carte['prix_courant']) ?> €</strong>
                                    <p class="carte-tableau__metadonnees"><?= (int) $carte['nombre_encheres'] ?> enchère(s) · fin <?= echapper_html($carte['fin_vente']) ?></p>
                                    <?php // Le bouton apparaît seulement pour un suivi volontaire que l'utilisateur peut retirer. ?>
                                    <?php if ($carte['suivi_volontaire'] === true) { ?>
                                        <form class="carte-tableau__suivi" action="enregistrer_suppression_suivi.php" method="post">
                                            <input type="hidden" name="annonce_id" value="<?= (int) $carte['id'] ?>">
                                            <input type="hidden" name="jeton_csrf" value="<?= echapper_html($jeton_csrf) ?>">
                                            <button type="submit">Ne plus suivre</button>
                                        </form>
                                    <?php } ?>
                                </div>
                            </article>
                        <?php } ?>
                    <?php } ?>
                </div>
            </section>
            <!-- PARTIE 3 : LES ENCHÈRES REMPORTÉES -->
            <?php // Les ventes terminées gagnées sont isolées ici et ne sont pas répétées dans la section précédente. ?>
            <section class="section-tableau" aria-labelledby="titre-annonces-remportees">
                <h2 id="titre-annonces-remportees" class="section-tableau__titre">Enchères remportées</h2>
                <div class="section-tableau__cartes" data-cartes-remportees>
                    <?php // Un message simple remplace les cartes tant qu'aucune vente n'a été gagnée. ?>
                    <?php if (empty($annonces_remportees)) { ?>
                        <div class="section-tableau__etat-vide">
                            <p>Vous n’avez encore remporté aucune enchère.</p>
                        </div>
                    <?php } else { ?>
                        <?php // La boucle crée une carte verte pour chaque vente remportée. ?>
                        <?php foreach ($annonces_remportees as $carte) { ?>
                            <article class="carte-tableau carte-tableau--positive" data-annonce-id="<?= (int) $carte['id'] ?>">
                                <?php // La photographie est affichée lorsqu'elle existe, sinon un cadre explique son absence. ?>
                                <?php if ($carte['photo_url'] !== '') { ?>
                                    <img class="carte-tableau__photo" src="<?= echapper_html($carte['photo_url']) ?>" alt="Photographie de <?= echapper_html($carte['titre']) ?>">
                                <?php } else { ?>
                                    <div class="carte-tableau__photo carte-tableau__photo--absente">Aucune photo</div>
                                <?php } ?>
                                <?php // Cette partie rappelle le titre, le prix final et que l'utilisateur est le gagnant. ?>
                                <div class="carte-tableau__informations">
                                    <span class="etiquette etiquette--positive">Remportée</span>
                                    <h3 class="carte-tableau__titre"><a href="afficher_detail_annonce.php?id=<?= (int) $carte['id'] ?>"><?= echapper_html($carte['titre']) ?></a></h3>
                                    <strong class="carte-tableau__prix"><?= echapper_html($carte['prix_courant']) ?> €</strong>
                                    <p class="carte-tableau__metadonnees"><?= (int) $carte['nombre_encheres'] ?> enchère(s) · gagnant : vous</p>
                                </div>
                            </article>
                        <?php } ?>
                    <?php } ?>
                </div>
            </section>
        </main>
        <?php // On charge le pied de page commun qui termine la page.
        require_once 'templates/fragments/footer.php';
        ?>
    </div>
</body>
</html>
