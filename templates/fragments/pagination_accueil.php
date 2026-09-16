<?php // FRAGMENT : pagination_accueil.php 

// Rôle : Afficher les liens permettant de parcourir les pages d'une recherche.
// Paramètres : 
//      - le template fournit le mode, les pages et les adresses précédente et suivante
/**
        * @var mixed $mode_recherche : Type de recherche actif
        * @var mixed $nombre_pages : Nombre total de pages
        * @var mixed $erreur_accueil : Message d'erreur de la page
        * @var mixed $pages_pagination : Liste des numéros de page
        * @var mixed $url_page_precedente : Lien vers la page précédente
        * @var mixed $url_page_suivante : Lien vers la page suivante
*/
// Retour : Une navigation HTML (ou aucun affichage si une seule page suffit).
?>
                    <?php // La pagination apparaît seulement lorsque la recherche possède plusieurs pages. ?>
                    <?php if ($mode_recherche === true && $nombre_pages > 1 && $erreur_accueil === '') { ?>
                        <nav class="pagination" aria-label="Pagination des résultats">
                            <?php if ($url_page_precedente !== '') { ?>
                                <a class="pagination__lien" href="<?= echapper_html($url_page_precedente) ?>">Précédente</a>
                            <?php } else { ?>
                                <span class="pagination__lien est-desactive" aria-disabled="true">Précédente</span>
                            <?php } ?>
                            <?php foreach ($pages_pagination as $page) { ?>
                                <?php if ($page['active'] === true) { ?>
                                    <span class="pagination__lien est-active" aria-current="page"><?= (int) $page['numero'] ?></span>
                                <?php } else { ?>
                                    <a class="pagination__lien" href="<?= echapper_html($page['url']) ?>"><?= (int) $page['numero'] ?></a>
                                <?php } ?>
                            <?php } ?>
                            <?php if ($url_page_suivante !== '') { ?>
                                <a class="pagination__lien" href="<?= echapper_html($url_page_suivante) ?>">Suivante</a>
                            <?php } else { ?>
                                <span class="pagination__lien est-desactive" aria-disabled="true">Suivante</span>
                            <?php } ?>
                        </nav>
                    <?php } ?>
