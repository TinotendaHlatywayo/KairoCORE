---
title: Importer du personnel à partir d'une feuille de calcul
summary: Téléchargez le modèle d'employé, remplissez-le et téléchargez-le. Le nouveau téléchargement avec les mêmes identifiants nationaux met à jour le personnel existant au lieu de le dupliquer.
category: hr
slug: importing-staff-from-spreadsheet
order: 2
published: true
updated: 2026-10-27
---

# Importer du personnel à partir d'une feuille de calcul

L'importateur crée des dossiers de personnel en masse, avec les mêmes champs que l'assistant **Nouvel employé**. Il est également possible d'exécuter plusieurs tâches en toute sécurité : le personnel est associé à une **carte d'identité nationale**, donc un nouveau téléchargement met à jour les employés existants au lieu de créer des doublons.

## 1. Téléchargez le modèle

1. Ouvrez **RH et paie → Répertoire du personnel et RH → Employés**.
2. Cliquez sur **Importer des employés (Excel/CSV)**.
3. Dans l'assistant, cliquez sur **Télécharger le modèle Excel**.

Le modèle est livré avec les en-têtes de colonne corrects et cinq exemples de lignes montrant à quel point les données doivent être variées. Supprimez les exemples avant de saisir votre véritable portée, ou conservez-les et corrigez les valeurs.

## 2. Remplissez-le

Seules ces six colonnes doivent être présentes avec des valeurs :

| Colonne obligatoire | Remarques |
| --- | --- |
| Prénom | |
| Nom de famille | |
| Courriel | Doit être unique parmi les comptes du personnel et du portail |
| Numéro de téléphone | |
| Carte d'identité nationale | La clé correspondante — ne la changez jamais pour une personne existante |
| Désignation | Titre du poste, par ex. "Professeur de mathématiques" |

Tout le reste est facultatif et a une valeur par défaut raisonnable :

| Colonne | Par défaut lorsqu'il est vide |
| --- | --- |
| Genre | Femme |
| Date de naissance | 1990-01-01 |
| État civil | Célibataire |
| Adresse physique | Non fourni |
| Nom/téléphone du contact d'urgence | Non fourni |
| Département | Vide (voir départements ci-dessous) |
| Rôle | Personnel enseignant |
| Type d'emploi | Permanente |
| Date d'adhésion | Aujourd'hui |
| Niveau de salaire | Vierge |
| Statut | Actif |

**La date de fin du contrat est requise lorsque le type d'emploi est Contrat.** Les dates doivent être des dates Excel ou `YYYY-MM-DD`.

### Listes et paires

Certaines colonnes contiennent plusieurs valeurs. Utilisez deux points entre le nom et la valeur, et un point-virgule entre les entrées :

- `Individual Allowances` → `Teaching Allowance: 150; Transport: 80`
- `Spouse Details` → `Spouse: Rudo Maphosa; Occupation: Nurse`
- `Dependents` → `Child 1: Tadiwa (8); Child 2: Kuda (5)`
- `Next of Kin` → `Name: Rudo Maphosa; Phone: 0771234567; Relationship: Spouse`
- `Custom Fields` → `TSC Number: 123456; Pension Number: PN-88`

## 3. Téléchargez-le

1. Cliquez sur **Importer des employés (Excel/CSV)** et choisissez le fichier complété. Les colonnes sont automatiquement mises en correspondance à partir des noms d'en-tête — il n'y a pas d'écran de mappage.
2. Consultez le résumé et confirmez l’importation.

## 4. Que se passe-t-il lors du nouveau téléchargement

C’est la partie importante pour mettre à jour le personnel en masse :

- **Même carte d'identité nationale, détails modifiés** → l'employé existant est mis à jour. Aucun nouvel enregistrement, aucun compte de portail en double et aucun deuxième e-mail d'activation.
- **Même pièce d'identité nationale avec un nouvel e-mail** → l'e-mail figurant sur le dossier de l'employé et son compte de connexion associé sont tous deux mis à jour.
- **Nouvelle carte d'identité nationale** → un nouvel employé est créé et un compte de portail mis à disposition.
- **L'e-mail appartient à quelqu'un d'autre** → la ligne est rejetée avec un message clair ; corrigez l'e-mail et téléchargez à nouveau.

Donc, pour actualiser une liste d'employés, téléchargez le modèle (ou votre dernière exportation), modifiez uniquement les e-mails ou autres détails dont vous avez besoin, puis téléchargez à nouveau le même fichier.

## Départements et niveaux de salaire

- **Département** correspond à un département existant par son nom. S'il n'existe pas, l'importateur demande s'il doit le créer ou ignorer cette valeur. Sa création génère également un code départemental.
- **Échelle de salaire** correspond à un échelon existant par son nom. Laissez-le vide si la personne n’a pas encore de note.

## Lorsque les lignes échouent

L'importation n'enregistre jamais à moitié : les lignes présentant des problèmes sont répertoriées dans les résultats avec la raison exacte (par exemple, une date de fin de contrat manquante, un sexe invalide, un rôle inconnu ou une date mal formée). Téléchargez les lignes rejetées au format CSV, corrigez-les et importez uniquement ce fichier. Les lignes valides du même téléchargement sont toujours importées.

> Gardez la carte d'identité nationale stable pour le personnel existant : cette seule colonne est ce qui fait d'un nouveau téléchargement une mise à jour plutôt qu'un doublon.
