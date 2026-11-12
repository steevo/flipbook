# com_flipbook — Liseuse

Composant Joomla 5/6 pour lire en ligne des livres et des revues page par page, avec l'effet d'une page qui tourne.

- **Feuilletage** avec [StPageFlip](https://github.com/Nodlik/StPageFlip) (MIT) : double page sur ordinateur, une page à la fois sur téléphone, coin qui se relève au survol, couverture et dos rigides.
- **Chargement progressif** : les pages sont des images (WebP, JPEG ou PNG) chargées seulement autour de la page affichée. Ouvrir un numéro coûte environ 1 Mo au lieu du PDF entier.
- **Barre d'outils** : première, précédente, suivante, dernière page, aller à une page, vignettes, zoom en grande taille, plein écran, flèches du clavier. L'adresse suit la page affichée (`?page=31`), ce qui permet de lier une page précise.
- **Contrôle d'accès** : chaque livre a un niveau d'accès. Les pages sont servies par le composant après vérification des droits, jamais directement. Sans le niveau d'accès, on ne voit que les pages en libre accès (la couverture par défaut) et une invitation à se connecter.
- **Administration** : liste des livres (titre, dossier des pages, niveau d'accès, pages en libre accès, décalage de numérotation). Le nombre de pages et leurs proportions sont lus dans le dossier.
- **Site** : vue d'un livre et étagère de tous les livres. Un lien de menu « Liseuse : tous les livres » donne aux livres des adresses comme `/liseuse/bulletin-64`.

## Stockage des pages

Les pages sont dans un dossier protégé (par défaut `flipbook/` à la racine du site, avec un `.htaccess` qui interdit l'accès direct, créé à l'installation). Chaque livre a son dossier avec trois tailles :

```
flipbook/<dossier du livre>/
    t/001.webp   vignettes (~300 px de haut)
    m/001.webp   lecture   (~1200 px de haut)
    l/001.webp   zoom      (~2400 px de haut)
```

## Avec Collector

Le composant [Collector](https://github.com/steevo/collector) (0.9.3 et plus) a un type de champ « Liseuse » qui affiche un bouton « Lire en ligne » vers un livre.
