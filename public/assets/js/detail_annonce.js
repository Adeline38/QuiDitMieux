// Rôle : Changer localement la photographie principale de la galerie d'une annonce.
// Paramètres : Le script utilise les images et commandes préparées par detail_annonce.php.
// Retour : La photographie choisie apparaît en grand sans requête au serveur.

document.addEventListener('DOMContentLoaded', function () {
    const imagePrincipale = document.querySelector('[data-galerie-principale]');
    const commandes = document.querySelectorAll('[data-galerie-miniature]');

    if (!imagePrincipale || commandes.length === 0) {
        return;
    }

    // Cette fonction réunit le changement d'image et l'état accessible de la miniature active.
    function activerPhotographie(commande) {
        const miniature = commande.querySelector('img');

        if (!miniature) {
            return;
        }

        imagePrincipale.src = miniature.src;
        imagePrincipale.alt = miniature.alt;

        commandes.forEach(function (autreCommande) {
            autreCommande.classList.remove('est-active');
            autreCommande.setAttribute('aria-pressed', 'false');
        });

        commande.classList.add('est-active');
        commande.setAttribute('aria-pressed', 'true');
    }

    commandes.forEach(function (commande, indexCommande) {
        commande.addEventListener('click', function () {
            activerPhotographie(commande);
        });

        // Les flèches permettent de parcourir les miniatures sans multiplier les pressions sur Tab.
        commande.addEventListener('keydown', function (evenement) {
            let nouvelIndex = indexCommande;

            if (evenement.key === 'ArrowRight') {
                nouvelIndex = indexCommande + 1;
            } else if (evenement.key === 'ArrowLeft') {
                nouvelIndex = indexCommande - 1;
            } else {
                return;
            }

            if (nouvelIndex < 0) {
                nouvelIndex = commandes.length - 1;
            }

            if (nouvelIndex >= commandes.length) {
                nouvelIndex = 0;
            }

            evenement.preventDefault();
            activerPhotographie(commandes[nouvelIndex]);
            commandes[nouvelIndex].focus();
        });
    });
});
