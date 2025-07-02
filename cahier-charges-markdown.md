# Cahier des Charges

## Système de Gestion d'Attribution des Tuteurs de Stage

# 1. Introduction

## 1.1 Contexte du projet

Dans le cadre du Master 2 MIAGE - Systèmes d'Information Nomades à Distance, notre groupe a décidé de concevoir et de développer une application spécifique pour gérer l'attribution des tuteurs enseignants en charge du suivi des stages étudiants. Ce choix repose sur plusieurs éléments clés. Il représente une opportunité de moderniser et d'optimiser les processus pédagogiques et administratifs de l'université, de réduire les tâches répétitives et les risques d'erreurs, et de favoriser un environnement de travail collaboratif et transparent entre les différents acteurs concernés.

L'automatisation de l'enregistrement et du traitement des informations relatives aux stages offrira aux responsables une plus grande flexibilité pour ajuster manuellement les affectations en fonction des contraintes spécifiques.

Nous formons un binôme composé de **DANSIA Toussaint** et **BELLE BELLE Isaac**, et nous avons sélectionné ce projet afin d'appliquer notre expertise en ingénierie des systèmes d'information dans un cadre concret et exigeant. Ce cahier des charges a pour mission de définir précisément l'ensemble des besoins fonctionnels et non fonctionnels, l'architecture technique ainsi que la méthodologie de gestion du projet. Le développement démarre immédiatement et doit être achevé et livré au plus tard le **31 mai**.

## 1.2 Enjeux du système d'information

La gestion manuelle de l'attribution des tuteurs pose plusieurs défis majeurs :

- Des difficultés dans l'obtention et le traitement des préférences des enseignants.
- Une complexité accrue dans l'optimisation des affectations tout en respectant l'ensemble des contraintes.
- Un temps considérable investi dans la résolution des conflits d'attribution.
- Un risque accru d'insatisfaction parmi les différentes parties prenantes.

La mise en place d'un système d'information dédié permettra de surmonter ces défis en automatisant le processus d'attribution tout en maintenant la flexibilité nécessaire pour effectuer des ajustements manuels lorsque cela s'avère nécessaire.

## 1.3 Importance d'une solution performante

L'adoption d'une solution performante pour la gestion des attributions apporte plusieurs avantages concrets et stratégiques pour l'ensemble des parties prenantes :

- **Gain de temps significatif pour les responsables des stages**

L'automatisation de la collecte des données et de l'analyse des préférences permet de réduire considérablement le temps consacré aux tâches administratives. Les responsables peuvent ainsi se concentrer sur des missions à plus forte valeur ajoutée, tout en disposant d'outils d'aide à la décision pour une gestion plus fluide et efficace des affectations.

- **Amélioration de la satisfaction des enseignants**

La prise en compte systématique des préférences pédagogiques dans le processus d'attribution garantit une meilleure correspondance entre les attentes des enseignants et les affectations. Ce mode de gestion favorise un climat de confiance et renforce l'engagement des enseignants dans le suivi pédagogique des stages.

- **Transparence accrue du processus d'attribution**

La solution offre une visibilité complète sur les critères utilisés et les étapes du processus d'affectation. Cette transparence permet aux parties prenantes de mieux comprendre les décisions prises et d'accéder à un outil de suivi en temps réel, favorisant ainsi une communication fluide et limitant les incompréhensions.

- **Réduction des conflits et des réaffectations tardives**

Grâce à une gestion optimisée et à l'automatisation des propositions d'affectation, le système anticipe les conflits potentiels et identifie rapidement les points de tension. Cela permet d'effectuer des ajustements en amont, réduisant ainsi les perturbations et les insatisfactions qui pourraient survenir en fin de processus.

- **Optimisation de la répartition de la charge de travail des enseignants**

En équilibrant les affectations selon les contraintes (telles que le nombre maximum d'étudiants par enseignant), la solution garantit une répartition plus équitable des tâches. Cela permet aux enseignants de mieux encadrer leurs étudiants sans risque de surcharge, optimisant ainsi leur efficacité pédagogique.

# 2. Objectifs et Périmètre du Projet

## 2.1 Objectifs principaux

### 2.1.1 Optimisation des affectations

Afin d'assurer une gestion efficace des attributions et répondre aux attentes spécifiques des enseignants tout en respectant les contraintes institutionnelles, il est essentiel d'adopter une approche systématique d'optimisation.

• **Développement d'un algorithme optimisé d'attribution**

L'objectif est de concevoir un algorithme performant capable de générer des propositions d'affectation en intégrant l'ensemble des contraintes établies ainsi que les préférences exprimées par les enseignants. Cet algorithme devra être suffisamment adaptable pour prendre en compte des paramètres évolutifs et ajuster ses propositions en temps réel, garantissant une répartition optimale des ressources pédagogiques.

• **Réduction du nombre d'enseignants insatisfaits**

En intégrant un mécanisme de pondération basé sur les préférences individuelles, l'algorithme vise à minimiser les cas d'insatisfaction. Il s'agira d'analyser et de comparer les souhaits des enseignants avec les contraintes opérationnelles afin de proposer des affectations correspondant au mieux aux attentes de chacun.

• **Équilibrage de la charge de travail entre les enseignants**

Une répartition équitable des affectations est essentielle pour garantir une qualité de suivi homogène et éviter la surcharge de certains enseignants. L'algorithme devra ainsi intégrer des critères de distribution équilibrée des étudiants, tout en respectant les capacités individuelles des enseignants.

### 2.1.2 Gestion des contraintes

Une attribution précise et équitable requiert de mettre en place une gestion rigoureuse des contraintes spécifiques à chaque enseignant et à chaque stage. À cet effet, les aspects suivants doivent être pris en compte :

• **Mise en place d'un système de saisie et de gestion des contraintes pour les enseignants**

Développer une interface dédiée permettant aux enseignants de spécifier leurs limites et préférences en matière de charge de travail. Ce système devra être suffisamment flexible pour intégrer des paramètres variables et personnalisables en fonction des profils individuels.

• **Respect des quotas d'encadrement définis par les enseignants**

S'assurer que l'algorithme prenne rigoureusement en compte le nombre maximal d'étudiants que chaque enseignant est disposé à encadrer. Cette mesure vise à prévenir toute surcharge et à garantir un suivi pédagogique de qualité.

• **Prise en compte des incompatibilités et des spécificités des stages**

Intégrer des règles de gestion permettant d'identifier et de traiter les incompatibilités potentielles, qu'il s'agisse de conflits d'emploi du temps ou de besoins spécifiques liés à certains stages. Cette approche garantit une correspondance optimale entre les exigences des stages et les compétences des enseignants.

### 2.1.3 Satisfaction des parties prenantes

Le succès du projet repose sur la satisfaction de l'ensemble des acteurs impliqués. Pour y parvenir, il est essentiel de mettre en place des outils et des interfaces ergonomiques qui favorisent une communication fluide et transparente. Les aspects suivants doivent être développés :

• **Proposer une interface intuitive pour la gestion des stages**

Concevoir une interface ergonomique qui facilite la saisie, le suivi et la mise à jour des informations liées aux stages. Cette interface doit permettre une gestion rapide et efficace, réduisant ainsi la charge administrative et optimisant le processus d'affectation.

• **Faciliter l'expression des préférences et la visualisation des attributions par les enseignants**

Mettre en place un module interactif dédié aux enseignants, leur permettant d'indiquer leurs préférences, de consulter leurs affectations et d'accéder à un retour visuel clair sur le processus d'attribution. Cette fonctionnalité garantit une meilleure transparence et une prise en compte optimisée des attentes individuelles.

• **Améliorer la communication entre les parties prenantes**

Intégrer des outils collaboratifs tels que des messageries internes, des notifications automatiques et des tableaux de bord partagés pour fluidifier les échanges et garantir une transparence totale dans le suivi des affectations. Cette approche permet de limiter les conflits, d'améliorer la coordination entre les responsables de stages et les enseignants, et de favoriser un environnement de travail harmonieux.

## 2.2 Périmètre fonctionnel et technique

### 2.2.1 Modules de saisie des données

Pour garantir une collecte efficace et précise des informations nécessaires au processus d'affectation, il est essentiel de disposer de modules de saisie performants et ergonomiques. Les éléments suivants doivent être mis en place :

- **Interface de saisie des sujets de stage**

Permettre l'enregistrement détaillé du titre, de la description, de l'entreprise concernée et des technologies utilisées afin de constituer un dossier complet pour chaque stage.

- **Interface de saisie des informations sur les étudiants**

Recueillir les informations essentielles, telles que le nom, le parcours académique et les coordonnées, afin d'assurer un suivi individualisé et pertinent.

- **Interface de saisie des informations sur les enseignants**

Intégrer les spécialités, disponibilités et contraintes propres à chaque enseignant, afin de disposer d'un profil détaillé facilitant le processus d'affectation.

- **Système d'import/export de données**

Assurer le chargement initial des informations et permettre des sauvegardes régulières, garantissant ainsi la continuité et la sécurité des données.

### 2.2.2 Traitement et stockage des données

Une gestion centralisée et sécurisée des données est indispensable pour garantir l'intégrité et la fiabilité du système. Les actions suivantes sont recommandées :

- **Base de données sécurisée**

Mettre en place une base de données robuste pour stocker l'ensemble des informations collectées, avec des mesures de sécurité avancées afin de protéger les données sensibles.

- **Mécanismes de validation et de contrôle des données**

Implémenter des processus automatiques de validation pour assurer la cohérence et l'exactitude des informations saisies.

- **Historisation des modifications**

Enregistrer toutes les modifications apportées aux données afin de permettre un suivi détaillé afin de faciliter les audits et les retours d'expérience.

### 2.2.3 Algorithme d'affectation

L'optimisation de l'attribution des tuteurs repose sur le développement d'un algorithme intelligent capable de prendre en compte l'ensemble des paramètres du système. Cet algorithme devra :

- **Intégrer toutes les contraintes et préférences**

Prendre en compte les contraintes opérationnelles ainsi que les préférences exprimées afin de générer des propositions d'affectation équilibrées.

- **Permettre l'exécution de plusieurs simulations**

Offrir la possibilité de réaliser différentes simulations en ajustant les paramètres, permettant de comparer les résultats et de sélectionner la solution la plus adaptée.

- **Mécanisme de scoring**

Évaluer la qualité des propositions d'affectation à l'aide d'un système de notation pour faciliter la prise de décision du responsable des stages.

### 2.2.4 Interfaces utilisateur

L'ergonomie des interfaces est un élément clé pour garantir la qualité et l'efficacité du système.

- **Interface destinée au responsable des stages**

Développer une interface intuitive et complète permettant de gérer, modifier et valider les attributions de manière efficace.

- **Interface pour les enseignants**

Proposer un espace dédié où les enseignants peuvent saisir leurs préférences, consulter leurs affectations et suivre leur évolution pour garantir une meilleure transparence et implication.

- **Interface de reporting et de visualisation des résultats d'affectation**

Mettre en place des outils de reporting dynamiques permettant d'analyser les résultats des simulations et des affectations afin de faciliter l'identification des axes d'optimisation et la détection d'éventuelles anomalies.

## 2.3 Bénéficiaires

### 2.3.1 Responsable des stages

En tant qu'administrateur principal du système, le responsable des stages joue un rôle central dans la gestion et la supervision du processus d'affectation. Ses missions incluent :

- **Gestion complète des données**
  - Assurer la saisie, la mise à jour et la suppression des informations relatives aux stages, aux étudiants et aux enseignants.
  - Garantir l'intégrité et la sécurité des données collectées pour éviter toute incohérence ou perte d'informations.

- **Lancement des algorithmes d'affectation et analyse des résultats**
  - Déclencher l'exécution des algorithmes en prenant en compte les contraintes et préférences définies.
  - Disposer d'un tableau de bord ergonomique pour visualiser rapidement les attributions proposées et détecter d'éventuelles anomalies.

- **Ajustements manuels des affectations**
  - Modifier manuellement les affectations en cas de besoins spécifiques ou de situations exceptionnelles avant la validation finale.
  - Utiliser des outils de contrôle facilitant la réattribution en temps réel.

- **Communication avec les parties prenantes**
  - Diffuser les résultats et les décisions aux enseignants et aux étudiants via la plateforme dédiée.
  - Assurer une communication claire pour expliquer les critères d'attribution et répondre aux éventuelles interrogations des utilisateurs.

### 2.3.2 Enseignants

Les enseignants, en tant qu'acteurs clés du processus d'affectation, disposent d'outils interactifs leur permettant de s'impliquer activement :

- **Consultation des stages disponibles**
  - Accéder à une liste actualisée des stages, comprenant une description détaillée de chaque offre.
  - Identifier rapidement les opportunités correspondant à leur domaine d'expertise et à leurs préférences pédagogiques.

- **Expression des préférences et contraintes**
  - Indiquer leurs choix et spécifier leurs contraintes (disponibilités, nombre maximum d'étudiants, spécialisation).
  - Modifier leurs préférences en fonction de l'évolution de leur charge de travail ou d'éventuelles contraintes nouvelles.

- **Visualisation des affectations**
  - Consulter de manière interactive les stages qui leur sont attribués et accéder aux détails complémentaires.
  - Obtenir un retour visuel permettant de mieux comprendre l'impact de leurs préférences sur le processus d'affectation.

- **Communication avec le responsable des stages**
  - Utiliser les outils intégrés (messagerie, notifications) pour échanger directement avec le responsable et clarifier les attentes.
  - Participer activement au processus de validation en cas de réajustement des affectations.

### 2.3.3 Étudiants

Bien que les étudiants soient des bénéficiaires indirects du processus d'affectation, ils disposent d'un accès simplifié leur permettant de suivre leur affectation et de récupérer les informations nécessaires :

- **Identification du tuteur assigné**
  - Accéder aux informations relatives au tuteur chargé du suivi de leur stage.
  - Recevoir des notifications dès que l'affectation est finalisée.

- **Accès aux coordonnées du tuteur**
  - Consulter les coordonnées et informations de contact de leur tuteur via la plateforme.
  - Faciliter la communication et l'organisation des rendez-vous pour le suivi pédagogique.

# 3. Analyse des Besoins

## 3.1 Besoins fonctionnels

### 3.1.1 Module de saisie des informations

Ce module constitue la base de la collecte des données essentielles pour le système et doit offrir les fonctionnalités suivantes :

- **Gestion des stages**
  - Permettre la création, la modification et la suppression des sujets de stage.
  - Enregistrer toutes les caractéristiques des stages, telles que le titre, la description, le nom de l'entreprise, le lieu, les dates et les compétences requises.

- **Gestion des étudiants**
  - Enregistrer les informations personnelles des étudiants.
  - Associer chaque étudiant au stage qui lui a été attribué, afin d'assurer un suivi personnalisé.

- **Gestion des enseignants :**
  - Collecter et enregistrer les compétences, les disponibilités ainsi que les contraintes spécifiques de chaque enseignant.
  - Mettre à jour ces informations en fonction des évolutions de leur planning et de leurs préférences.

- **Import/Export de données :**
  - Offrir la possibilité d'importer des données depuis des fichiers externes (CSV, Excel) afin de faciliter la phase de chargement initial.
  - Permettre l'exportation des résultats et des données afin de faciliter les analyses ultérieures et les sauvegardes.

### 3.1.2 Interface de préférences des enseignants

Pour garantir que les préférences des enseignants soient correctement prises en compte, cette interface doit être conçue de manière intuitive et complète.

- **Visualisation des stages disponibles**

Afficher une liste complète des stages avec des options de filtres et de recherche pour permettre aux enseignants de trouver les opportunités correspondant à leurs domaines d'expertise.

- **Sélection et classement des préférences**

Offrir la possibilité de sélectionner les stages et de les classer par ordre de préférence.

- **Gestion du nombre maximum d'étudiants**

Permettre aux enseignants d'indiquer clairement le nombre maximal d'étudiants qu'ils sont disposés à suivre.

- **Ajout de commentaires**

Proposer un champ dédié pour que les enseignants puissent ajouter des commentaires ou des précisions sur leurs choix, ce qui pourra être pris en compte lors de l'analyse globale des préférences.

### 3.1.3 Algorithmes d'affectation implémentés

Le système intègre trois algorithmes d'affectation optimisés pour différents contextes d'utilisation :

- **Algorithme Glouton (Greedy Algorithm)**
  - Complexité : O(n² log n) où n est le nombre d'étudiants
  - Usage : Instances petites à moyennes (< 200 étudiants)
  - Avantages : Rapidité d'exécution, simplicité de mise en œuvre

- **Algorithme Hongrois (Hungarian Algorithm)**
  - Complexité : O(n³)
  - Usage : Optimisation globale garantie
  - Avantages : Solution mathématiquement optimale, respect strict des contraintes

- **Algorithme Génétique (Genetic Algorithm)**
  - Configuration adaptative selon la taille du problème
  - Usage : Grandes instances (200+ étudiants) avec +25% qualité vs glouton
  - Fonctionnalités avancées : Logging complet, métriques temps réel, convergence intelligente
  - Stratégies d'initialisation diversifiées (30% aléatoire, 30% départements, 40% glouton)

- **Génération de rapports et métriques**

Produire des rapports détaillés avec indicateurs de performance, temps d'exécution, et scores de satisfaction pour chaque algorithme utilisé.

### 3.1.4 Interface d'administration et de validation

Cette interface est destinée au responsable des stages et doit fournir des outils de gestion complets pour le suivi et la validation des affectations.

- **Tableau de bord synthétique**

Proposer une vue d'ensemble claire et détaillée permettant de suivre l'état des affectations et d'identifier les points nécessitant des ajustements.

- **Visualisation graphique**

Offrir des outils de visualisation graphique (graphiques et diagrammes) pour faciliter l'analyse des données et des résultats d'affectation.

- **Modification manuelle**

Intégrer des outils de modification permettant au responsable d'ajuster manuellement les affectations proposées avant la validation finale.

- **Système de notification :**

Mettre en place un système de notifications automatiques pour informer les enseignants des attributions finales.

## 3.2 Besoins non fonctionnels

Pour garantir la robustesse, la sécurité et la convivialité du système, il est indispensable de répondre aux exigences suivantes :

### 3.2.1 Performance

- **Temps de réponse optimal**

Assurer un temps de réponse inférieur à 2 secondes pour les opérations courantes afin de garantir une interaction fluide avec le système.

- **Efficacité des algorithmes**

Les performances mesurées en production garantissent :
  - Algorithme glouton : ~0.3s pour 100 étudiants
  - Algorithme hongrois : ~2s pour 100 étudiants  
  - Algorithme génétique : ~1.5s pour 100 étudiants (+15% qualité)
  - Cache hit ratio : >85% avec Redis

- **Haute disponibilité**

Maintenir une disponibilité du système de 99,9% durant les périodes critiques, afin d'éviter toute interruption dans le processus d'attribution.

### 3.2.2 Sécurité

- **Authentification sécurisée**

Mettre en place des mécanismes d'authentification robustes pour tous les utilisateurs, assurant l'accès sécurisé aux fonctionnalités du système.

- **Gestion des droits d'accès**

Implémenter une gestion des droits d'accès précise et adaptée aux différents profils afin de protéger les informations sensibles.

- **Protection des données personnelles**

Mettre en place des mesures de protection des données personnelles conformes au RGPD, incluant le droit à l'oubli, la gestion des consentements, et la minimisation des données collectées.

- **Journalisation des actions sensibles**

Enregistrer de manière sécurisée toutes les actions sensibles pour permettre une traçabilité complète en cas d'audit ou d'incident.

### 3.2.3 Scalabilité

- **Adaptabilité de l'architecture**

Concevoir une architecture capable de gérer un grand nombre de stages et d'enseignants sans dégradation de la performance, tout en assurant une réponse rapide aux demandes croissantes.

- **Extension à d'autres formations**

Prévoir la possibilité d'étendre le système à d'autres formations ou départements, afin de maximiser son utilité à l'échelle de l'université.

- **Évolutivité**

Garantir que le système puisse intégrer de nouvelles fonctionnalités et évolutions à moindre coût et avec une mise en œuvre rapide, pour répondre aux besoins futurs.

### 3.2.4 Ergonomie

- **Interface intuitive**

Développer une interface utilisateur simple et ergonomique qui nécessite un minimum de formation pour faciliter son adoption par tous les utilisateurs.

- **Design responsive**

Assurer que le design de l'application soit adaptable à différents supports (PC, tablette, smartphone) pour garantir une expérience utilisateur cohérente et efficace.

- **Accessibilité**

Respecter les normes d'accessibilité pour permettre à tous les utilisateurs, y compris ceux en situation de handicap, d'utiliser le système sans difficulté.

# 4. Description du Système et Architecture

## 4.1 Architecture générale

L'architecture proposée suit un modèle modulaire basé sur une approche en couches. Elle garantit une séparation des responsabilités et facilite l'évolution du système.

Chaque couche est dédiée à un ensemble de fonctions précises :

- La couche de présentation gère les interfaces
- La couche application orchestre les services et les contrôleurs
- La couche métier se concentre sur la logique et les algorithmes
- La couche Données est responsable du stockage et de la gestion des informations

Cette organisation facilite la maintenance et la compréhension du système.

Grâce à la modularité, chaque couche peut être testée de manière isolée, permettant une détection précoce des erreurs et une validation efficace des fonctionnalités avant leur intégration dans l'ensemble du système.

## 4.2 Composants techniques

### 4.2.1 Logiciels

- **Backend** :
  - Langage PHP 8.2+ avec architecture MVC personnalisée
  - API REST pour la communication avec le frontend
  - Documentation des API avec Swagger/OpenAPI 3.0 intégré
  - Système d'authentification basé sur JWT et sessions sécurisées

- **Frontend** :
  - HTML5, CSS3, JavaScript ES6+ pour interfaces utilisateur modernes
  - Framework Bootstrap 5.3 pour la conception responsive
  - Stimulus.js pour les interactions dynamiques
  - Chart.js pour les visualisations graphiques temps réel

- **Base de données et cache** :
  - MySQL 8.0 / MariaDB 10.6+ pour le stockage relationnel des données
  - Redis 6.0+ avec fallback automatique pour l'amélioration des performances
  - Configuration adaptative des TTL par type de données

### 4.2.2 Technologies implémentées

- **Développement** :
  - Git pour le versionnement du code
  - Composer 2.0+ pour la gestion des dépendances PHP
  - GitHub Actions pour l'intégration continue et le déploiement
  - PHPUnit 10+ pour les tests unitaires et d'intégration

- **Monitoring et observabilité** :
  - Métriques Prometheus avec interfaces visuelles
  - Logging structuré PSR-3 avec rotation automatique
  - Health checks automatisés (/api/monitoring/health.php)
  - Rate limiting configurable par endpoint

- **Sécurité** :
  - Protection CSRF sur tous les formulaires
  - Authentification JWT avec sessions sécurisées
  - Contrôle d'accès basé sur les rôles (RBAC)
  - Audit trail et journalisation des actions sensibles

## 4.3 Interface et Communication

### 4.3.1 Protocoles d'échange

- API REST sécurisée par HTTPS pour les échanges entre frontend et backend
- Documentation interactive Swagger/OpenAPI 3.0 accessible via /api/swagger.php
- Système de notifications intégré avec messagerie interne
- Authentification JWT avec sessions PHP sécurisées et protection CSRF

### 4.3.2 Maquettes et navigation

Des maquettes détaillées seront fournies pour les principales interfaces :

- Page d'accueil et tableau de bord
- Formulaires de saisie des stages, étudiants et enseignants
- Interface de sélection des préférences pour les enseignants
- Visualisation des attributions proposées
- Écrans d'administration et de paramétrage

Des sessions de test utilisateur précoces seront organisées avec des enseignants et responsables pour valider l'ergonomie des interfaces dès la phase de conception.

# 5. Méthodologie et Gestion de Projet

## 5.1 Organisation de l'équipe

L'équipe de projet est constituée d'un binôme avec les rôles suivants :

- **Chef de projet junior** : Responsable de la coordination, de la communication avec le tuteur et du respect des délais. Participe également au développement.

- **Développeur principal** : Responsable de l'architecture technique, du développement des composants critiques et de la qualité du code.

Les deux membres travailleront en étroite collaboration, avec des réunions régulières pour synchroniser leurs avancées et résoudre les difficultés rencontrées.

### 5.1.1 Interaction avec le tuteur

Le tuteur du projet jouera le rôle du commanditaire et non celui d'un conseiller technique. Cette distinction est importante car :

- Le tuteur répondra aux questions concernant les fonctionnalités souhaitées
- Les choix techniques seront entièrement à la charge de l'équipe projet
- Le tuteur évaluera la pertinence et la justification de ces choix dans le rapport final
- Le tuteur fournira des retours sur le contenu du rapport et le diaporama de soutenance

La communication avec le tuteur se fera exclusivement par email, avec :

- Un compte-rendu d'avancement envoyé toutes les 2 semaines
- Un partage mensuel de l'avancement de la rédaction du rapport
- Une première version du diaporama de soutenance au moins 2 mois avant la date prévue

## 5.2 Planning et Phases du Projet

Le projet a adopté une approche Agile avec des sprints de 2-3 semaines. Le développement s'est déroulé avec succès selon les phases suivantes :

| **Phase** | **Période** | **Statut** | **Livrables réalisés** |
|-----------|-------------|------------|------------------------|
| **Étude préliminaire** | Mai 2024 | ✅ Complété | Analyse de l'existant, spécifications fonctionnelles |
| **Cahier des charges** | Mai-Juin 2024 | ✅ Complété | Document de spécifications validé et mis à jour |
| **Conception et développement des algorithmes** | Juin-Septembre 2024 | ✅ Complété | 3 algorithmes opérationnels (Glouton, Hongrois, Génétique) |
| **Développement système complet** | Septembre-Novembre 2024 | ✅ Complété | Application complète avec interfaces utilisateur, API REST, monitoring |
| **Tests et validation** | Novembre-Décembre 2024 | ✅ Complété | Tests unitaires/intégration, benchmarks performance, validation sécurité |
| **Déploiement et optimisation** | Décembre 2024 | ✅ Complété | Cache Redis, monitoring production, pipeline CI/CD |
| **Documentation et finalisation** | Décembre 2024-Janvier 2025 | ✅ Complété | Documentation technique complète, guides utilisateur |

Certaines phases pourront se chevaucher pour optimiser les délais, notamment le début du développement de certains modules pendant que la conception se poursuit.

Un diagramme de Gantt détaillé sera fourni en annexe, avec l'identification précise des jalons de communication avec le tuteur.

## 5.3 Méthodes de suivi et de gestion

### 5.3.1 Outils de gestion de projet utilisés

- **GitHub** : Gestion du code source, versioning et pipeline CI/CD automatisé
- **GitHub Actions** : Intégration continue avec tests automatisés
- **Composer** : Gestion des dépendances PHP et scripts d'automatisation
- **PHPUnit** : Framework de tests avec couverture de code
- **Documentation intégrée** : README.md, documentation technique dans le projet

### 5.3.2 Communication et suivi

- Élaboration de fiches d'interview pour recueillir les besoins
- Réunions internes bihebdomadaires pour faire le point sur l'avancement
- Communication par email avec le tuteur
- Suivi des retours du tuteur
- Mise en place d'un échéancier détaillé pour les livrables intermédiaires destinés au tuteur
- Organisation de revues de sprint toutes les 2-3 semaines pour valider les fonctionnalités développées

# 6. Critères de Qualité et d'Évaluation

## 6.1 Qualité de l'ingénierie du SI

### 6.1.1 Respect des contraintes fonctionnelles

- Établir des procédures de vérification systématique pour s'assurer que chaque fonctionnalité respecte les spécifications définies dans le cahier des charges.
- Réaliser des tests fonctionnels exhaustifs couvrant l'ensemble des cas d'utilisation afin de valider le comportement du système dans toutes les situations prévues.
- Organiser des sessions de validation avec des utilisateurs représentatifs de divers profils pour garantir l'adéquation aux besoins réels.

### 6.1.2 Flexibilité de l'architecture

- Opter pour une conception modulaire pour faciliter l'ajout ou la modification de fonctionnalités sans perturber l'ensemble du système.
- Élaborer une documentation technique détaillée et structurée afin de permettre une maintenance aisée et une prise en main rapide.
- Intégrer des design patterns éprouvés afin de favoriser l'évolutivité et l'adaptabilité du système.

## 6.2 Qualité de la démarche projet

### 6.2.1 Conformité au cahier des charges

- Mettre en place une matrice de traçabilité reliant chaque exigence aux fonctionnalités développées
- Organiser des revues régulières pour vérifier l'adéquation entre la réalisation technique et les spécifications initiales.
- Justifier tout écart constaté et établir un processus de validation rigoureux pour corriger toute déviation par rapport aux exigences définies.

### 6.2.2 Respect des délais

- Suivi rigoureux du planning avec indicateurs d'avancement
- Identification précoce des risques de dépassement
- Plan d'action en cas de retard constaté, incluant la priorisation des fonctionnalités essentielles

### 6.2.3 Démarche qualité

- Revues de code systématiques et analyse statique du code
- Tests unitaires pour tous les composants critiques
- Tests d'intégration pour valider les interactions entre modules
- Tests de sécurité spécifiques basés sur les recommandations OWASP Top 10
- Documentation complète et à jour, incluant un wiki technique pour faciliter la maintenance future

# 7. Analyse des Risques et Plan de Contingence

| **Risque** | **Probabilité** | **Impact** | **Stratégie de mitigation** | **Plan de contingence** |
|------------|-----------------|------------|----------------------------|------------------------|
| Retard dans le développement | Moyenne | Élevé | Planning avec marges, priorisation des fonctionnalités, méthode Agile pour livraisons incrémentales | Réduction du périmètre, concentration sur les fonctionnalités essentielles |
| Complexité de l'algorithme d'affectation | Élevée | Moyen | Prototype précoce dès la phase de conception, approche progressive avec version simple améliorée par itérations | Simplification de l'algorithme, possibilité d'ajustements manuels plus importants |
| Problèmes techniques | Moyenne | Moyen | Choix de technologies maîtrisées, veille technologique | Solutions alternatives identifiées, support technique externe |
| Indisponibilité d'un membre de l'équipe | Faible | Élevé | Documentation continue, partage des connaissances | Réallocation des tâches, ajustement du planning |
| Évolution des besoins | Moyenne | Moyen | Validation régulière avec les parties prenantes, approche Agile pour intégrer les changements | Gestion des changements avec impact sur le planning |
| Mauvaise compréhension des attentes du commanditaire | Moyenne | Élevé | Communication claire et régulière, demande de clarifications | Réunion de recadrage, ajustement des spécifications |
| Retards dans les retours sur les livrables intermédiaires | Faible | Moyen | Planification anticipée, rappels courtois | Poursuite du développement sur la base des hypothèses validées précédemment |
| Problèmes d'adoption par les utilisateurs | Moyenne | Élevé | Tests d'ergonomie précoces, implication des utilisateurs dès la conception | Sessions de formation supplémentaires, ajustements de l'interface basés sur les retours |

# 8. Livrables et Documentation

## 8.1 Liste des livrables

### 8.1.1 Documents de gestion de projet

- Cahier des charges complet et validé
- Planning détaillé (diagramme de Gantt)
- Comptes-rendus de réunions
- Rapports d'avancement hebdomadaires
- Backlog produit et backlogs de sprint (conformément à la méthodologie Agile)

### 8.1.2 Documents de conception

- Diagrammes UML (cas d'utilisation, classes, séquences)
- Maquettes des interfaces utilisateur
- Documentation de l'architecture technique
- Spécifications détaillées de l'algorithme d'affectation
- Résultats des tests d'utilisabilité précoces

### 8.1.3 Éléments techniques

- Code source documenté et versionné (GitHub)
- Scripts de création et d'initialisation de la base de données (database/tutoring_system.sql)
- Jeux de données de test et scripts de validation (test_*.php)
- Tests unitaires et d'intégration (tests/Algorithm/, tests/Integration/)
- Benchmarks de performance (tests/Algorithm/GeneticAlgorithmBenchmark.php)
- Configuration Redis et cache (config/cache.php, config/genetic_algorithm.php)
- Pipeline CI/CD avec GitHub Actions (.github/workflows/ci.yml)
- Monitoring et métriques (api/monitoring/, includes/Monitor.php)

### 8.1.4 Documentation utilisateur

- Manuel d'utilisation pour le responsable des stages
- Guide de prise en main pour les enseignants
- Procédures d'installation et de déploiement
- Support de formation (incluant vidéos tutorielles)
- Wiki technique pour faciliter la maintenance future

## 8.2 Modalités de validation et soutenance

### 8.2.1 Validation des livrables

- Revues formelles à la fin de chaque phase majeure du projet
- Validation du rapport par le tuteur via les partages mensuels
- Tests d'acceptation avec le responsable des stages (tuteur dans son rôle de commanditaire)
- Démonstrations régulières des fonctionnalités développées (à chaque fin de sprint)

### 8.2.2 Préparation de la soutenance

- Élaboration du diaporama de présentation au moins 1 mois avant la date de soutenance
- Intégration rapide des retours du tuteur sur la première version du diaporama
- Préparation de démonstrations concrètes du système
- Répartition équilibrée du temps de parole entre les membres du binôme

### 8.2.3 Soutenance finale

- Présentation formelle du projet, de ses objectifs et des résultats obtenus
- Démonstration complète du système développé
- Justification des choix techniques effectués
- Session de questions-réponses

# 9. Annexes et Références

## 9.1 Annexes

- Modèle de fiche d'interview
- Diagramme de Gantt détaillé
- Maquettes préliminaires
- Format des données d'import/export
- Exemples de cas d'utilisation complets

## 9.2 Références

- Méthodologie Agile pour la gestion de projet itérative
- Documentation PHP 8.2+ pour le développement backend
- Documentation Bootstrap 5.3 pour le développement frontend responsive
- Documentation Stimulus.js pour les interactions JavaScript
- Documentation Chart.js pour les visualisations graphiques
- Documentation Redis 6.0+ pour la mise en cache distribuée
- Articles académiques sur les algorithmes d'optimisation (Hongrois, Génétique)
- Standard PSR-3 pour le logging structuré
- Métriques Prometheus pour le monitoring applicatif
- Documentation OpenAPI/Swagger 3.0 pour la documentation d'API
- Normes ISO/IEC 25010 pour l'évaluation de la qualité logicielle
- Guide OWASP Top 10 pour la sécurité des applications web
- Réglementation RGPD pour la protection des données personnelles