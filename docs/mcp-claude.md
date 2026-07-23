# Utiliser XMS avec Claude (MCP)

XMS expose un serveur MCP (`Model Context Protocol`) qui permet à Claude de lire et modifier le contenu du site directement en conversation : lister les blocs disponibles, créer/modifier des pages, gérer les médias, etc.

## Pré-requis

- Un accès admin à `https://app.oursblanc.io/admin`
- Claude Desktop (ou tout client compatible MCP supportant les connecteurs personnalisés)

## 1. Générer un token API

1. Connecte-toi sur `https://app.oursblanc.io/admin`
2. Ouvre la ressource **API Tokens**
3. Clique sur **Créer**, donne un nom au token (ex. `Claude Desktop`) et sélectionne les *abilities* nécessaires (ex. `pages:read`, `pages:write`)
4. **Copie le token affiché immédiatement** — il n'est montré qu'une seule fois et ne peut pas être récupéré ensuite (comme les tokens Sanctum)

> Crée un token dédié par usage/personne plutôt que de partager un token existant, et limite les *abilities* au strict nécessaire.

## 2. Ajouter le connecteur dans Claude Desktop

1. Dans Claude Desktop : **Réglages → Connecteurs → Ajouter un connecteur personnalisé**
2. Renseigne :
   - **Nom** : `XMS` (libre)
   - **URL** :
     ```
     https://app.oursblanc.io/mcp/xms?token=VOTRE_TOKEN
     ```
3. Laisse l'authentification OAuth désactivée/vide — l'authentification se fait via le paramètre `?token=` dans l'URL
4. Valide. Claude doit maintenant lister les tools XMS disponibles dans une nouvelle conversation

## Pourquoi `?token=` dans l'URL ?

Le dialogue "connecteur personnalisé" de Claude Desktop ne permet pas de configurer un header `Authorization` personnalisé (seulement OAuth ou aucune authentification). XMS accepte donc le token soit via header `Authorization: Bearer <token>` (recommandé pour les intégrations qui le supportent), soit via le paramètre de requête `?token=` (nécessaire pour Claude Desktop).

**Compromis à connaître** : un token passé dans l'URL peut se retrouver dans les logs d'accès du serveur et l'historique du navigateur. Pour limiter le risque :
- Utilise un token dédié avec des *abilities* restreintes plutôt qu'un token "admin complet"
- Révoque le token depuis l'admin (`API Tokens`) si tu penses qu'il a été exposé

## Utilisation

Une fois le connecteur actif, tu peux simplement demander à Claude en langage naturel, par exemple :

- « Liste les types de blocs disponibles sur XMS »
- « Crée une page `/fr/offres` avec un bloc héro et un bloc CTA »
- « Ajoute une image à la page `accueil` depuis cette URL »

Claude appelle les tools MCP exposés par XMS (`list_block_types`, `create_page`, `update_page`, etc.) pour réaliser l'action, dans la limite des *abilities* du token utilisé.

## Dépannage

| Symptôme | Cause probable |
|---|---|
| `401 Missing bearer token` | Le token n'est pas présent dans l'URL (vérifie `?token=...`) |
| `401 Invalid API token` | Token mal copié, expiré ou révoqué — regénère-en un |
| Erreur sur une action précise (ex. "pages:write required") | Le token n'a pas l'*ability* nécessaire — édite le token dans l'admin pour l'ajouter |
| Claude ne voit aucun tool | Vérifie que l'URL du connecteur est bien `https://app.oursblanc.io/mcp/xms?token=...` (pas d'espace, token complet) |
