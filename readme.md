# Gestion de commandes B2B — Plateforme grossiste

Plateforme de gestion de commandes pour un grossiste : catalogue produits, tarifs par client, workflow de commande complet (panier → validation → préparation → expédition → facturation), API REST, traitements asynchrones.

## Stack

- **Backend** : Symfony 7
- **BDD** : SQLite (fichier local dans `var/`, un fichier par environnement — projet 100% local, pas de service dédié)
- **Conteneurisation** : Docker / Docker Compose
- **Queue** : Symfony Messenger (transport Doctrine ou Redis)
- **Back-office** : Twig (admin/validateur, reporting, gestion catalogue)
- **Espace client** : React (dossier `front/`, même repo), consomme l'API en JSON
- **API** : JSON (DTO + Serializer), authentification JWT (LexikJWTAuthenticationBundle)

## Quickstart

Clone le dépôt et lance l'environnement de développement :

```bash
git clone <repo-url> .
cp .env.example .env
docker compose up -d --build
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
```

Services démarrés par `docker compose` :

| Service    | Rôle                              | Accès                                                  |
|------------|------------------------------------|---------------------------------------------------------|
| `php`      | PHP-FPM 8.3 (l'appli Symfony)      | interne uniquement                                      |
| `nginx`    | Reverse proxy front                | port hôte dynamique — `docker compose port nginx 80`    |
| `mailer`   | Mailpit (capture des emails, dev)  | UI web — `docker compose port mailer 8025`               |

Pas de service BDD : Doctrine écrit directement dans un fichier SQLite sous `var/` (`var/data_dev.db`, `var/data_test.db`...), créé automatiquement à la première migration. Ce fichier n'est pas versionné (`var/` est dans `.gitignore`).

Les ports HTTP/mail sont volontairement **non fixés** (`8080`, `8025`...) pour éviter les conflits avec d'autres projets Docker qui tournent en parallèle sur la machine ; utilise `docker compose port <service> <port>` pour retrouver le port réellement assigné, ou `docker compose ps`.

Le conteneur `php` tourne avec ton UID/GID hôte (`1000:1000` par défaut) pour que les fichiers écrits sur `var/` (dont la BDD SQLite) t'appartiennent. Si ton utilisateur n'a pas l'UID 1000, exporte `DOCKER_UID`/`DOCKER_GID` (`export DOCKER_UID=$(id -u) DOCKER_GID=$(id -g)`) avant `docker compose up`.

### Commandes utiles

```bash
docker compose exec php php bin/console <commande>      # console Symfony
docker compose exec php vendor/bin/simple-phpunit        # tests
docker compose exec php vendor/bin/php-cs-fixer fix       # lint/format
docker compose logs -f php                                # logs applicatifs
```

Les tâches détaillées (EPICs, backlog) ont été déplacées dans un fichier séparé `TASKS.md`. Voir [TASKS.md](TASKS.md).
## Architecture front

Le projet garde **deux fronts distincts** :
- `templates/` (Twig) reste utilisé pour le back-office interne (admin, validateur de commande, reporting) — pas besoin de SPA pour ces usages internes
- `front/` (React) est l'espace self-service pour les clients (catalogue, panier, suivi de commande) — consomme exclusivement l'API REST, jamais les routes Twig

Cette séparation force une vraie discipline API : le front React ne peut pas "tricher" en allant chercher une route Twig, donc l'EPIC 9 (API) doit être fonctionnel et complet avant de commencer l'EPIC 13 (front React).

## Objectif pédagogique

Ce projet sert à couvrir un maximum de briques Symfony rencontrées sur un gros projet réel : Security/Voters, Workflow, Messenger, API avec DTO/Normalizer, Commands CLI, EventSubscriber, tests.

