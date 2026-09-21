## EPIC 6 — Autorisations fines (Voters)

S'assurer qu'un utilisateur n'accède qu'aux données de sa société.

- [x] #29 `Voter` sur `Order` : un `ROLE_BUYER` ne voit que les commandes de sa `Company` — `OrderVoter` (`src/Ordering/Infrastructure/Security/`), attribut `ORDER_VIEW`, utilisé par `OrderController` (404 si refusé)
- [x] #30 `Voter` : seul un `ROLE_VALIDATOR` peut faire passer une commande de `draft` à `submitted` → `validated` — même `OrderVoter`, un attribut par transition (`ORDER_VALIDATE`/`PREPARE`/`SHIP`/`INVOICE` réservés au validateur ; `ORDER_SUBMIT`/`CANCEL` aussi ouverts à la société propriétaire, l'acheteur envoie son propre panier)
- [x] #31 Tests unitaires sur les Voters (cas autorisé / refusé) — `tests/Unit/Ordering/OrderVoterTest.php`

---

