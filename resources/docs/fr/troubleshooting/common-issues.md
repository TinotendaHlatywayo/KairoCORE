---
title: Dépannage des problèmes courants
summary: Corrections des problèmes les plus rencontrés par les écoles : problèmes de connexion, élèves manquants, bulletins scolaires vierges et envoi d'e-mails.
category: troubleshooting
slug: common-issues
order: 1
published: true
updated: 2026-10-27
---

# Dépannage des problèmes courants

Vérifications rapides des problèmes qui surviennent le plus souvent. Si quelque chose ici ne résout pas le problème, contactez l'assistance via le site Web de la plateforme.

## Je ne peux pas me connecter

- Confirmez que vous êtes sur l'URL de l'espace de travail de votre école (par exemple `your-school.kairo.co.zw/workspace`), et non sur l'adresse de la plateforme.
- Utilisez **Mot de passe oublié** sur la page de connexion pour réinitialiser.
- Si vous n'avez jamais reçu l'e-mail d'activation, vérifiez le spam, puis demandez à un administrateur de le renvoyer depuis **Administration système → Gestion des utilisateurs → Comptes d'utilisateurs**.

## Un élève n'apparaît pas dans les listes

- Consultez le tableau **filtres** : un formulaire restant ou un filtre d'année masque les étudiants qui correspondent par ailleurs.
- Recherchez par numéro d'admission ou numéro d'identification d'étudiant plutôt que par nom (l'orthographe diffère plus que vous ne le pensez).
- Si l'étudiant a été admis via les candidatures, confirmez que la candidature a effectivement atteint le statut d'admission.

## Le moteur de facturation automatique n'a rien créé

- Vérifiez que les étudiants ont une **inscription** (année universitaire + formulaire) : la facturation cible les inscriptions, et non les seules lignes d'étudiants.
- Vérifiez que des **structures tarifaires** existent pour ce formulaire et cette année universitaire.
- Si des factures existent déjà pour cette étendue et sont toujours impayées, le moteur ignore volontairement ces étudiants pour éviter une double facturation.

## Les bulletins s'impriment avec des marques d'objet vides

- Confirmez que les **notes ont été capturées** par rapport à l'évaluation pour ce terme et ce sujet.
- Confirmez que les rapports ont été **générés après** la saisie des notes — régénérez-les si les notes sont arrivées plus tard.
- Vérifiez que vous imprimez le bon **terme**.

## Les e-mails d'annonce ne sont pas arrivés

- Vérifiez que le **Canal de livraison** était **E-mail uniquement** ou **Système et e-mail**, et non Système uniquement.
- Confirmez que l'audience inclut réellement les destinataires (le ciblage basé sur les rôles correspond au stockage réel des rôles).
- Vérifiez le dossier spam de l'école ; demandez au service informatique d'autoriser le domaine d'envoi de la plateforme.

## Un paiement est sur la mauvaise facture

- Enregistrez les paiements uniquement via **Enregistrer le paiement** sur la ligne de facture, ce qui garantit la cohérence des soldes et des reçus.
- Si un paiement a été capturé sur une mauvaise facture, contactez l'assistance plutôt que de modifier la base de données : l'historique des paiements alimente les reçus et les relevés.

## Une importation CSV du personnel signale des erreurs

- Utilisez **Télécharger le modèle Excel** sur le tableau Employés afin que les noms de colonnes correspondent exactement.
- **Prénom, nom de famille, e-mail, numéro de téléphone, pièce d'identité nationale et désignation** sont requis.
- La carte d'identité nationale est la clé de correspondance lors de la réimportation : une carte d'identité nationale identique met à jour l'employé existant au lieu de créer un duplicata. Modifiez l'e-mail pour le mettre à jour.
- Le personnel contractuel doit avoir une **Date de fin de contrat**.
- Corrigez les lignes rejetées et téléchargez uniquement celles-ci — voir [Importer le personnel à partir d'une feuille de calcul] (/docs/hr/importing-staff-from-spreadsheet).
