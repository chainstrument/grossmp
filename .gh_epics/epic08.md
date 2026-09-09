## EPIC 8 — Traitement asynchrone (Messenger)

Génération de facture PDF sans bloquer la requête HTTP.

- [ ] #36 Configurer Symfony Messenger (transport Doctrine pour commencer, migration Redis possible ensuite)
- [ ] #37 `Message` `GenerateInvoiceMessage` + `MessageHandler` associé
- [ ] #38 Génération du PDF de facture (lib type `dompdf` ou `mpdf`)
- [ ] #39 Déclenchement du message à la transition `invoiced` du Workflow
- [ ] #40 Commande CLI pour consommer la queue (`messenger:consume`) + doc sur le lancement en dev

---

