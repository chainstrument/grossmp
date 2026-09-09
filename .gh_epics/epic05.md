## EPIC 5 — Workflow de commande (statuts)

Gérer le cycle de vie d'une commande avec le composant `Workflow`.

- [ ] #24 Définir la state machine dans `config/packages/workflow.yaml` (draft → submitted → validated → preparing → shipped → invoiced), + statut `cancelled`
- [ ] #25 Implémenter les *guards* de transition (ex : impossible de passer à `preparing` si stock insuffisant)
- [ ] #26 Contrôleur/actions pour déclencher les transitions (boutons "Valider", "Expédier"...)
- [ ] #27 `EventSubscriber` sur les événements du Workflow (`workflow.order.transition.shipped` etc.)
- [ ] #28 Page de suivi de commande affichant l'historique des transitions

---

