# Plan de développement XMS : package CMS Filament v5 pilotable par IA

Document de référence pour Claude Code. Chaque phase est autonome, testable et livrable. Ne pas passer à la phase suivante sans que les tests de la phase courante passent.

---

## 0. Contexte projet et environnements

### Les deux repos

| Rôle | Repo GitHub | Chemin local |
|---|---|---|
| Package CMS (XMS) | https://github.com/OursBlanc-io/xms.oursblanc.io.git (public) | `/Users/olivier/OursBlanc/Dev/oursblanc.io/xms.oursblanc.io` |
| Site vitrine OursBlanc (app hôte) | https://github.com/OursBlanc-io/oursblanc.io.git | `/Users/olivier/OursBlanc/Dev/oursblanc.io/oursblanc.io` |

Le développement se fait en parallèle : le package est la brique produit, le site vitrine oursblanc.io est la première implémentation réelle et sert de terrain d'intégration permanent. Le contenu actuel du repo oursblanc.io est jetable : le vider (en conservant `.git`) et repartir sur une app Laravel fraîche.

### Environnements

| | Local | Production |
|---|---|---|
| URL | `http://oursblanc.test` (Valet) | `https://v2.oursblanc.io` (bascule ultérieure sur oursblanc.io) |
| Base MySQL | `oursblanc_io` | définie dans `.env.production` |
| Env file | `.env` | `.env.production` (jamais commité, un `.env.production.example` versionné) |

- Local : macOS, Laravel Valet, MySQL local, PHP 8.3+.
- Le package est lié à l'app en local via un repository Composer de type `path` (symlink), ce qui permet de développer les deux en simultané sans publier :

```json
// composer.json de oursblanc.io
"repositories": [
    { "type": "path", "url": "../xms.oursblanc.io", "options": { "symlink": true } }
],
"require": {
    "oursblanc-io/xms": "*"
}
```

- En production, le package sera installé soit via VCS repository pointant sur le repo GitHub public (tag), soit via Packagist plus tard. Prévoir le passage en tags sémantiques dès la v0.1.0.

### Naming et conventions du package

- Nom Composer : `oursblanc-io/xms`
- Namespace PHP : `OursBlanc\Xms`
- Préfixes : tables `xms_`, config `config/xms.php`, namespace de vues `xms::`, tag d'assets `xms`, routes nommées `xms.*`
- Le repo public implique : pas de secrets, pas de références clients dans le code ou les tests, LICENSE (MIT sauf décision contraire), README soigné en anglais.

### Setup initial (à exécuter par Claude Code, phase 0)

1. Cloner les deux repos aux chemins indiqués s'ils ne sont pas déjà présents.
2. Dans `oursblanc.io` : vider le contenu (hors `.git`), scaffolder Laravel (dernière stable), commit initial "fresh Laravel app".
3. Créer la base `oursblanc_io` si absente, configurer `.env` (APP_URL=http://oursblanc.test, DB oursblanc_io, `SESSION_DRIVER` et `QUEUE_CONNECTION` en database pour le local dans un premier temps).
4. `valet link oursblanc` dans le dossier du site (puis vérifier `http://oursblanc.test`).
5. Installer Filament v5 dans l'app, créer un user admin de dev.
6. Dans `xms.oursblanc.io` : squelette du package (voir section 2), branchement path repository dans l'app, vérifier que le service provider du package est bien découvert.
7. Créer `.env.production.example` documentant les variables attendues (DB, Cloudflare zone/token, disque média, URL).
8. Commits atomiques sur les deux repos à chaque étape. Ne jamais pousser sans validation explicite d'Olivier.

---

## 1. Vision et décisions actées

Package Laravel (Composer) fournissant un CMS de pages orienté blocs, administré via Filament v5, avec :

- Approche par blocs : chaque bloc est une classe PHP auto-décrite (champs Filament, JSON Schema, vue Blade). Pas de page builder visuel drag and drop complexe, des formulaires Filament simples.
- Multilingue : une ligne de page par locale, reliées par un groupe de traduction. Slug, SEO et statut de publication indépendants par langue.
- Pages plates : pas de hiérarchie en base, mais les slugs peuvent contenir des `/` pour simuler une arborescence d'URL.
- Thèmes : un thème est un dossier versionné dans l'app hôte. Pour les blocs génériques du package, le thème ne surcharge que CSS, JS et design tokens. Un mécanisme de blocs custom permet à l'app hôte ou au thème d'apporter des blocs complets avec leur propre Blade (du code versionné, jamais de Blade uploadé en runtime).
- Médias : attachés à la page (pas de médiathèque centralisée), optimisation WebP automatique, support vidéo avec poster.
- Rendu : Blade direct via route catch-all, cache HTTP compatible Cloudflare avec invalidation automatique par URL à la publication ou mise à jour.
- Pilotage IA : serveur MCP exposant des outils CRUD complets sur les pages, avec authentification par token API simple. Claude (chat ou Code) doit pouvoir créer, modifier, traduire et publier des pages entières sans documentation externe grâce à l'auto-description des blocs.
- Single site pour la v1.
- Sécurité IA : système de drafts et révisions obligatoire dans le core. Une IA n'écrit jamais directement en production sans passage par un draft et une preview.

### Stack cible

- PHP 8.3+, Laravel 12+ (compatible 13), Filament v5, MySQL 8.
- spatie/laravel-medialibrary pour les médias.
- laravel/mcp pour le serveur MCP (si le package officiel ne couvre pas le besoin, implémentation HTTP streamable maison en fallback).
- Pest + orchestra/testbench pour les tests package.

---

## 2. Arborescence du package

```
xms.oursblanc.io/
├── composer.json                  # oursblanc-io/xms
├── LICENSE
├── README.md
├── config/xms.php
├── database/migrations/
├── resources/
│   ├── views/
│   │   ├── blocks/                # vues Blade des blocs génériques
│   │   ├── layouts/               # layout de rendu par défaut
│   │   └── components/            # picture, video, seo-head
│   ├── css/xms.css                # styles neutres des blocs génériques
│   └── js/xms.js
├── routes/
│   ├── web.php                    # catch-all frontend
│   └── mcp.php                    # endpoint MCP
├── src/
│   ├── XmsServiceProvider.php
│   ├── Blocks/
│   │   ├── Block.php              # classe abstraite
│   │   ├── BlockRegistry.php
│   │   ├── SchemaGenerator.php
│   │   └── Generic/               # HeroBlock, TextBlock, ImageBlock, etc.
│   ├── Models/                    # Page, PageRevision, TranslationGroup, ApiToken
│   ├── Filament/                  # PageResource, pages, actions, tokens
│   ├── Http/Controllers/          # RenderController, PreviewController
│   ├── Rendering/                 # PageRenderer, ThemeManager, ViewResolver
│   ├── Cache/                     # CacheInvalidator (interface), CloudflareInvalidator, NullInvalidator
│   ├── Media/                     # PageMediaManager, conversions
│   ├── Mcp/                       # serveur, outils, validation
│   ├── Seo/                       # SeoData (DTO), head, hreflang, sitemap
│   └── Support/                   # SlugService, scopes
└── tests/
```

---

## 3. Modèle de données

Migrations dans l'ordre :

### xms_translation_groups
- `id`
- timestamps

### xms_pages
- `id`
- `translation_group_id` FK nullable (créé automatiquement à la création de la première page)
- `locale` string(10), indexé
- `slug` string(500), peut contenir des `/`, sans slash de tête ni de fin, unique par (locale, slug)
- `title` string
- `blocks` json (structure décrite en section 4)
- `seo` json (title, description, canonical, og_title, og_description, og_image_media_id, robots, structured_data)
- `template` string nullable (layout Blade alternatif du thème)
- `status` enum: draft, published
- `published_at` datetime nullable
- `created_by`, `updated_by` nullable (user id ou identifiant de token API)
- timestamps, softDeletes

Index : (locale, slug) unique, (translation_group_id, locale) unique, status.

### xms_page_revisions
- `id`
- `page_id` FK
- `blocks` json, `seo` json, `title`, `slug` (snapshot complet)
- `author_type` enum: user, api_token
- `author_id` string
- `created_at`

Rétention configurable (défaut : 50 révisions par page).

### xms_api_tokens
- `id`
- `name`
- `token` hashé (sha256), affiché une seule fois à la création
- `abilities` json (ex : ["pages:read", "pages:write", "pages:publish"])
- `last_used_at`
- timestamps

### Médias
Table `media` de spatie/medialibrary, publiée par le package si absente. Collections attachées au modèle Page, nommées par UUID de bloc (voir section 7).

---

## 4. Contrat du système de blocs

C'est la brique la plus structurante, à faire en premier après le squelette.

### Classe abstraite Block

```php
abstract class Block
{
    abstract public static function name(): string;        // identifiant machine, ex : 'hero'
    abstract public static function label(): string;       // libellé admin
    abstract public static function fields(): array;       // composants de formulaire Filament
    abstract public static function view(): string;        // ex : 'xms::blocks.hero'

    public static function schema(): array;                // JSON Schema, dérivé de fields() par défaut, surchargeable
    public static function description(): string;          // description pour l'IA, ex : "Bannière pleine largeur avec titre, texte et image de fond"
    public static function mediaFields(): array;           // liste des champs contenant des IDs de média, défaut []
    public static function rules(): array;                 // règles de validation Laravel du payload data
}
```

Point critique : `schema()` doit être généré automatiquement par introspection des composants Filament (TextInput → string, Select → enum, MarkdownEditor → string avec format markdown, Toggle → boolean, champ média → integer avec annotation `x-media: true`). Écrire un `SchemaGenerator` dédié avec tests exhaustifs par type de composant. Permettre la surcharge manuelle. La qualité de ce schema conditionne directement la fiabilité du pilotage par IA.

### Format JSON de la colonne blocks

```json
[
  {
    "uuid": "9b2f...",
    "type": "hero",
    "data": {
      "title": "SmartSkin",
      "content": "...",
      "image": 42,
      "alignment": "left"
    }
  }
]
```

- `uuid` généré à la création du bloc, stable dans le temps (sert de clé pour les médias et pour les updates ciblés par l'IA).
- `type` doit exister dans le registry, sinon rejet en validation et rendu d'un commentaire HTML en debug / rien en prod.
- `data` validé contre `rules()` du bloc à chaque écriture (admin comme MCP).

### BlockRegistry

- Singleton enregistré dans le container.
- `register(string $blockClass)`, `all()`, `find(string $name)`, `schemas()`.
- Les blocs génériques du package sont enregistrés par défaut, désactivables via config.
- L'app hôte ou un thème enregistre ses blocs custom dans un service provider : `BlockRegistry::register(MyCustomBlock::class)`. Un bloc custom apporte sa propre vue Blade (`view()` pointe vers un namespace de l'app ou du thème). C'est le mécanisme "blocs custom avec leur propre Blade" : du code versionné, chargé au boot, jamais de compilation Blade depuis la base.

### Blocs génériques v1

Livrer 8 blocs qui couvrent 90 % des besoins d'une page vitrine :

1. `heading` (niveau h1 à h4, texte, ancre optionnelle)
2. `text` (markdown)
3. `hero` (titre, sous-titre, image de fond, CTA optionnel, alignement)
4. `image` (média, alt, légende, largeur : contenu / large / pleine)
5. `gallery` (repeater d'images, colonnes)
6. `video` (média vidéo uploadé OU URL YouTube/Vimeo, poster)
7. `cta` (titre, texte, bouton libellé + URL, style)
8. `columns` (2 ou 3 colonnes, chaque colonne : titre, markdown, image optionnelle)

Chaque bloc : classe + vue Blade neutre + entrée dans les tests de schema.

---

## 5. Admin Filament

- `PageResource` avec :
  - Table : titre, slug, locale (badge), statut (badge), updated_at, filtres par locale et statut.
  - Form : onglet Contenu (TextInput title, TextInput slug avec validation regex `^[a-z0-9]+(?:[-/][a-z0-9]+)*$` et unicité par locale, champ Builder alimenté par le registry), onglet SEO (champs du DTO SeoData), onglet Réglages (locale, template, statut, published_at).
  - Le Builder Filament mappe chaque bloc du registry : `Builder\Block::make($block::name())->label($block::label())->schema($block::fields())`.
- Actions :
  - Publier / dépublier (avec déclenchement invalidation cache).
  - Prévisualiser (URL signée, voir section 8).
  - Dupliquer vers une autre locale : copie la structure de blocs et le SEO, rattache au même translation_group, statut draft. Ne pas traduire automatiquement dans l'admin (c'est le rôle de l'IA via MCP).
  - Historique des révisions : liste, diff simple (comparaison JSON côté PHP, affichage des blocs ajoutés/supprimés/modifiés), restauration.
- Sauvegarde : chaque update crée une révision AVANT écriture (snapshot de l'état précédent).
- Gestion des tokens API : une page Filament simple (liste, création avec affichage unique du token, révocation, abilities par checkboxes).

---

## 6. Rendu frontend et thèmes

### Routing

- Route catch-all enregistrée en dernier : `Route::get('{locale}/{slug}', RenderController::class)->where('slug', '.*')` avec locale contrainte aux locales configurées. Option config `locale_in_url: true|false` et `default_locale_hidden: true` (la locale par défaut sans préfixe : `/produits/smartskin` en FR, `/en/products/smartskin` en EN).
- Résolution : page publiée par (locale, slug), sinon 404. Prévoir un hook de redirection (table `xms_redirects` en v1.1, pas dans la v1).

### PageRenderer

- Itère sur les blocs, résout la vue de chaque bloc via le ViewResolver, injecte `$data` (cast en DTO léger ou array) et `$page`.
- Layout : `xms::layouts.default` surchargeable par le thème et par page via le champ `template`.
- Composant `<x-xms::seo-head>` : title, meta description, canonical, OG, hreflang (générés depuis les autres pages publiées du même translation_group), JSON-LD si présent.

### ThemeManager

- Config `xms.theme` : nom du thème actif ou null.
- Un thème = dossier `resources/themes/{name}/` dans l'app hôte contenant :
  - `theme.json` (manifest : nom, description, assets)
  - `css/`, `js/` : assets intégrés au build Vite de l'app hôte (recommandé : convention Vite, le manifest liste les entrées à inclure)
  - `views/` : uniquement pour les blocs custom du thème et les layouts, PAS pour surcharger le Blade des blocs génériques
- ViewResolver pour un bloc générique : toujours la vue du package. La personnalisation visuelle des blocs génériques passe par CSS/JS du thème (les vues génériques exposent des classes stables `xms-block xms-block--hero` et des data-attributes).
- ViewResolver pour un bloc custom : la vue déclarée par la classe du bloc (namespace du thème ou de l'app).
- Design tokens : le thème peut fournir `tokens.css` (variables CSS custom properties) injecté avant le CSS des blocs.
- Premier thème réel : le thème `oursblanc` dans l'app hôte, construit en phase 3 pour styler le site vitrine. Il sert de référence documentaire.

### Sitemap

- Route `/sitemap.xml` générée depuis les pages publiées, avec alternates hreflang. Cacheable, invalidée avec le reste.

---

## 7. Médias

- Le modèle Page implémente `HasMedia` (spatie).
- Convention de collection : `block-{uuid}` (un bloc peut avoir plusieurs médias, distingués par le nom de champ en custom property).
- Champ Filament custom `PageMediaUpload` : wrapper de FileUpload/SpatieMediaLibraryFileUpload qui attache le fichier à la page courante avec la collection du bloc courant et stocke l'ID du média dans `data`. Point d'attention : dans un Builder, récupérer l'UUID du bloc courant demande un peu de plomberie Filament, prévoir du temps sur ce composant, c'est le plus délicat de la partie admin.
- Conversions images : `webp` (qualité 82) en 3 tailles (480, 960, 1920) + conservation de l'original. Composant Blade `<x-xms::picture :media-id="$data['image']">` qui rend un `<picture>` avec srcset WebP et fallback.
- Vidéos : stockage direct, génération de poster si ffmpeg disponible (détection binaire, sinon skip silencieux avec log), composant `<x-xms::video>`. Pas de transcodage en v1, prévoir une interface `VideoProcessor` vide pour plus tard.
- Stockage : disque configurable (`xms.media_disk`). Local en dev (`public`), Scaleway Object Storage en production (S3 compatible). Documenter la config CORS Scaleway : méthode `aws s3api put-bucket-cors --endpoint-url https://s3.fr-par.scw.cloud --profile scaleway`, le client `mc` échoue avec erreur EOF sur les buckets à points.
- Nettoyage : à la sauvegarde d'une page, détecter les médias attachés dont l'UUID de bloc n'existe plus dans `blocks` et les supprimer (avec délai de grâce de 24h via job planifié plutôt que suppression immédiate, pour survivre aux allers-retours de draft).

---

## 8. Cache et invalidation Cloudflare

- Interface `CacheInvalidator { purgeUrls(array $urls): void; }` + implémentations `CloudflareInvalidator` (API purge by URL, zone ID + token en config/env) et `NullInvalidator` (défaut, utilisé en local).
- Middleware `SetCacheHeaders` sur la route de rendu : si page publiée et pas de session/preview, `Cache-Control: public, s-maxage={config}, max-age={config}`. Sinon `no-store`.
- `Page::urlsToPurge()` : URL de la page, URL des autres locales du groupe (les hreflang changent), sitemap. Hook événement `xms.purge_urls` pour que l'app hôte ajoute des URLs (listings, home).
- Événements `PageSaved`, `PagePublished`, `PageUnpublished` → listener → job `PurgeCdnCache` (queue).
- Preview : route `/{locale}/{slug}?preview={signature}` signée (URL signée Laravel, TTL 30 min), bypasse le statut published, force `no-store`, rend le dernier état draft.
- Production v2.oursblanc.io : zone Cloudflare d'oursblanc.io, Cache Rule "cache everything" sur le sous-domaine, variables `XMS_CLOUDFLARE_ZONE_ID` et `XMS_CLOUDFLARE_TOKEN` dans `.env.production`. Après purge, la vérification peut nécessiter un cache busting manuel (comportement Cloudflare connu, à documenter dans le README).

---

## 9. Serveur MCP

### Transport et auth

- Endpoint HTTP streamable `POST /mcp/xms` (route configurable), package laravel/mcp si adapté.
- Auth : header `Authorization: Bearer {token}`, vérifié contre `xms_api_tokens` (hash), abilities vérifiées par outil. Middleware dédié + rate limiting.
- Toute écriture via MCP crée une révision avec `author_type=api_token`.
- Cible d'usage : connecteur MCP dans Claude chat pointant sur `https://v2.oursblanc.io/mcp/xms` (et `http://oursblanc.test/mcp/xms` en local via le client MCP de dev).

### Outils

Lecture (`pages:read`) :
- `list_block_types` → pour chaque bloc : name, label, description, JSON Schema, liste des champs média. C'est le contrat d'auto-découverte.
- `list_pages(locale?, status?, search?)` → id, title, slug, locale, status, updated_at, translation_group_id.
- `get_page(id)` → objet complet : blocks (avec uuid), seo, meta, urls (publique + preview signée), locales sœurs du groupe.

Écriture (`pages:write`) :
- `create_page(locale, title, slug, blocks, seo?, translation_group_id?)` → validation stricte : chaque bloc validé contre le schema/rules de son type, slug validé et unique. Statut draft imposé. Retourne la page + URL de preview.
- `update_page(id, title?, slug?, blocks?, seo?)` → remplacement complet des champs fournis. Les UUIDs de blocs existants sont préservés s'ils sont renvoyés, les nouveaux blocs sans uuid en reçoivent un.
- `patch_blocks(id, operations)` → opérations ciblées : `insert(position, block)`, `update(uuid, data)`, `remove(uuid)`, `move(uuid, position)`. Évite de renvoyer toute la page pour une retouche.
- `attach_media_from_url(page_id, block_uuid, field, url, alt?)` → télécharge (avec limites : taille max, content-types autorisés, timeout, blocage IP privées / SSRF), attache, optimise, retourne l'ID média et le place lui-même dans le champ du bloc.
- `translate_page(id, target_locale, blocks_translated, seo_translated, slug)` → helper qui crée la page sœur dans le groupe : Claude fournit le contenu traduit, l'outil garantit le rattachement au groupe et la cohérence de structure (mêmes types de blocs dans le même ordre, sinon erreur explicite).

Publication (`pages:publish`) :
- `publish_page(id)`, `unpublish_page(id)` → déclenche l'invalidation CDN.

Erreurs : toujours structurées et actionnables (ex : `blocks[2].data.title: required`, avec rappel du schema du type concerné). Une IA corrige bien quand l'erreur est précise.

### Test d'intégration cible

Scénario Pest de bout en bout : un client MCP simulé appelle `list_block_types`, construit une page de 5 blocs, la crée, la modifie via `patch_blocks`, attache une image par URL, la publie, vérifie que le rendu HTTP contient le contenu et que l'invalidateur (mock) a reçu les bonnes URLs. Ce test est la définition du succès du projet.

---

## 10. Phases de développement

Chaque phase se termine par : tests verts dans le package ET vérification manuelle dans oursblanc.test.

### Phase 0 : environnements et squelette (1 j)
- Setup complet décrit en section 0 (clone, Laravel frais dans oursblanc.io, base, Valet, Filament, path repository).
- Squelette du package : composer.json, XmsServiceProvider, config, testbench + Pest opérationnels, CI GitHub Actions (tests + pint) sur le repo du package.
- Livrable : `composer test` vert dans le package, `oursblanc.test` qui répond, panel Filament accessible.

### Phase 1 : blocs (2 j)
- Block abstrait, BlockRegistry, SchemaGenerator avec tests par type de champ, les 8 blocs génériques (classes + schemas), validation de payload.
- Livrable : tests unitaires registry + schemas + validation.

### Phase 2 : modèle et admin (2,5 j)
- Migrations, modèles, PageResource complet avec Builder, révisions à la sauvegarde, actions publier/dupliquer/historique, page tokens API.
- Livrable : création d'une vraie page dans l'admin de oursblanc.test.

### Phase 3 : rendu et thème oursblanc (2 j)
- Route catch-all, RenderController, PageRenderer, vues des 8 blocs, layout, seo-head, hreflang, sitemap, ThemeManager, preview signée.
- Création du thème `oursblanc` dans l'app hôte (tokens, CSS, JS) : première vraie page du site vitrine visible et stylée sur oursblanc.test.
- Livrable : tests HTTP de rendu + page d'accueil draft du site vitrine.

### Phase 4 : médias (2 j)
- Intégration spatie, composant PageMediaUpload (prévoir la moitié du temps dessus), conversions WebP, composants picture/video, nettoyage différé.
- Livrable : upload dans un bloc depuis l'admin, rendu picture avec srcset, test du nettoyage.

### Phase 5 : cache (1 j)
- Interface, CloudflareInvalidator (client HTTP testé contre des fakes), middleware headers, événements + job, urlsToPurge.
- Livrable : tests des headers et des URLs purgées, NullInvalidator actif en local.

### Phase 6 : MCP (3 j)
- Transport + auth + abilities, les 10 outils, validation et erreurs structurées, le test d'intégration de bout en bout de la section 9.
- Livrable : connexion réelle depuis Claude (connecteur MCP) sur oursblanc.test, création d'une page complète par prompt.

### Phase 7 : contenu et mise en production (1,5 j)
- README complet du package (installation, config, création d'un bloc custom, création d'un thème, setup Cloudflare, setup MCP), CHANGELOG, tag v0.1.0.
- Construction des pages réelles du site vitrine (FR d'abord, EN via `translate_page`).
- Déploiement v2.oursblanc.io : `.env.production`, migration, disque média Scaleway, Cloudflare (Cache Rule + token de purge), token MCP de production.
- Livrable : v2.oursblanc.io en ligne, purge Cloudflare vérifiée après une modification de page.

Total : environ 15 jours équivalent dev humain. Ordre strict, chaque phase mergée avec tests verts.

---

## 11. Points de vigilance pour Claude Code

1. Le SchemaGenerator (phase 1) et le PageMediaUpload dans le Builder (phase 4) sont les deux vraies difficultés techniques. Ne pas les sous-estimer, écrire les tests d'abord.
2. Toujours valider les payloads MCP côté serveur avec les rules des blocs, jamais faire confiance au client.
3. Ne jamais compiler de Blade provenant de la base ou d'un upload.
4. Préfixer toutes les tables, routes, vues et config par `xms_` / `xms::` / `xms.` pour éviter les collisions dans l'app hôte.
5. Sur `attach_media_from_url` : protections SSRF obligatoires (résolution DNS, blocage des plages privées, taille max, timeout).
6. Filament v5 : vérifier les signatures exactes de l'API Builder/FileUpload dans la doc à jour avant d'écrire, l'API a bougé entre v3 et v5.
7. Les vues des blocs génériques exposent des classes CSS stables et documentées : c'est le contrat avec les thèmes, ne pas les changer sans version majeure.
8. Le repo du package est public : aucun secret, aucune donnée client, messages de commit en anglais, code et docblocks en anglais. Le site oursblanc.io peut rester en français.
9. Ne jamais `git push` sans validation explicite d'Olivier. Commits locaux atomiques librement.
10. Deux repos, deux cycles de commit : une feature qui touche package + site fait deux commits distincts, chacun dans son repo.