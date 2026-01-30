-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : ven. 30 jan. 2026 à 00:25
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `hestimsmart`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id` int(10) UNSIGNED NOT NULL,
  `NOM` varchar(255) NOT NULL,
  `PRENOM` varchar(255) NOT NULL,
  `EMAIL` varchar(255) NOT NULL,
  `MDP` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id`, `NOM`, `PRENOM`, `EMAIL`, `MDP`, `created_at`) VALUES
(1, 'ADMIN', 'HESTIM', 'admin@hestim.ma', '123456', '2026-01-29 23:46:04');

-- --------------------------------------------------------

--
-- Structure de la table `cours`
--

CREATE TABLE `cours` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `cours`
--

INSERT INTO `cours` (`id`, `code`, `nom`, `description`) VALUES
(1, 'ALGO1', 'Algorithmes et structures de données', NULL),
(2, 'BD1', 'Base de données', NULL),
(3, 'RESEAUX1', 'Réseaux informatiques', NULL),
(4, 'PG', 'Programmation', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `document_demandes`
--

CREATE TABLE `document_demandes` (
  `id` int(10) UNSIGNED NOT NULL,
  `etudiant_id` int(20) NOT NULL,
  `type_document` enum('homologation','bulletin','certificat_scolarite','autre') NOT NULL,
  `autre_document` varchar(255) DEFAULT NULL,
  `commentaire` varchar(500) DEFAULT NULL,
  `statut` enum('non_traite','traite') NOT NULL DEFAULT 'non_traite',
  `date_demande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_traitement` datetime DEFAULT NULL,
  `demandeur_nom` varchar(255) NOT NULL,
  `demandeur_prenom` varchar(255) NOT NULL,
  `demandeur_email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `document_demandes`
--

INSERT INTO `document_demandes` (`id`, `etudiant_id`, `type_document`, `autre_document`, `commentaire`, `statut`, `date_demande`, `date_traitement`, `demandeur_nom`, `demandeur_prenom`, `demandeur_email`) VALUES
(8, 1, 'homologation', NULL, 'C\'est urgent !!!', 'non_traite', '2026-01-29 21:16:34', NULL, 'LALEYE', 'Adetutu', 'ademht@hestim.ma');

-- --------------------------------------------------------

--
-- Structure de la table `enseignant`
--

CREATE TABLE `enseignant` (
  `ID-ENSEIGNANT` int(20) NOT NULL,
  `NOM` varchar(255) NOT NULL,
  `PRENOM` varchar(255) NOT NULL,
  `EMAIL` text NOT NULL,
  `MDP` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `enseignant`
--

INSERT INTO `enseignant` (`ID-ENSEIGNANT`, `NOM`, `PRENOM`, `EMAIL`, `MDP`) VALUES
(1, 'MEHINTO', 'Manuella', 'manuellamht@hestim.ma', '123456'),
(2, 'ISSIFOU', 'Abdel', 'abdel@hestim.ma', '7890');

--
-- Déclencheurs `enseignant`
--
DELIMITER $$
CREATE TRIGGER `after_insert_enseignant` AFTER INSERT ON `enseignant` FOR EACH ROW BEGIN
    INSERT INTO liste_enseignant (email, mdp)
    VALUES (NEW.email, NEW.mdp);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `enseignant_affectations`
--

CREATE TABLE `enseignant_affectations` (
  `id` int(10) UNSIGNED NOT NULL,
  `enseignant_id` int(20) NOT NULL,
  `filiere_id` int(10) UNSIGNED NOT NULL,
  `niveau_id` tinyint(3) UNSIGNED NOT NULL,
  `cours_id` int(10) UNSIGNED NOT NULL,
  `annee_scolaire` varchar(9) NOT NULL,
  `groupe` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `enseignant_affectations`
--

INSERT INTO `enseignant_affectations` (`id`, `enseignant_id`, `filiere_id`, `niveau_id`, `cours_id`, `annee_scolaire`, `groupe`) VALUES
(1, 1, 1, 1, 1, '2025-2026', 'G1'),
(2, 2, 4, 4, 4, '2025-2026', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `ID-ETUDIANT` int(20) NOT NULL,
  `NOM` varchar(255) NOT NULL,
  `PRENOM` varchar(255) NOT NULL,
  `EMAIL` varchar(255) NOT NULL,
  `MDP` varchar(255) NOT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`ID-ETUDIANT`, `NOM`, `PRENOM`, `EMAIL`, `MDP`, `date_inscription`) VALUES
(1, 'LALEYE MODIF', 'Adetutu', 'ademht@hestim.ma', '123455', '2026-01-29 23:23:18');

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

CREATE TABLE `filieres` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id`, `code`, `nom`) VALUES
(1, 'INFO', 'Informatique'),
(2, 'GC', 'Génie Civil'),
(3, 'GI', 'Ingénierie Industrielle'),
(4, 'FIN', 'Finance'),
(5, 'BIOTECHNOL', 'Biotechnologie');

-- --------------------------------------------------------

--
-- Structure de la table `filiere_cours`
--

CREATE TABLE `filiere_cours` (
  `id` int(10) UNSIGNED NOT NULL,
  `filiere_id` int(10) UNSIGNED NOT NULL,
  `cours_id` int(10) UNSIGNED NOT NULL,
  `niveau_id` tinyint(3) UNSIGNED NOT NULL,
  `semestre` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `filiere_cours`
--

INSERT INTO `filiere_cours` (`id`, `filiere_id`, `cours_id`, `niveau_id`, `semestre`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 2, 1, 1),
(3, 1, 3, 1, 1),
(4, 4, 4, 4, 2);

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions`
--

CREATE TABLE `inscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `etudiant_id` int(20) NOT NULL,
  `filiere_id` int(10) UNSIGNED NOT NULL,
  `niveau_id` tinyint(3) UNSIGNED NOT NULL,
  `annee_scolaire` varchar(9) NOT NULL,
  `groupe` varchar(20) DEFAULT NULL,
  `date_inscription` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `inscriptions`
--

INSERT INTO `inscriptions` (`id`, `etudiant_id`, `filiere_id`, `niveau_id`, `annee_scolaire`, `groupe`, `date_inscription`) VALUES
(1, 1, 1, 1, '2025-2026', 'G1', '2026-01-29 22:42:04');

-- --------------------------------------------------------

--
-- Structure de la table `niveaux`
--

CREATE TABLE `niveaux` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `libelle` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `niveaux`
--

INSERT INTO `niveaux` (`id`, `libelle`) VALUES
(1, '1ère année'),
(2, '2ème année'),
(3, '3ème année'),
(4, '4ème année');

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--

CREATE TABLE `presences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enseignant_id` int(20) NOT NULL,
  `affectation_id` int(10) UNSIGNED NOT NULL,
  `cours_id` int(10) UNSIGNED NOT NULL,
  `etudiant_id` int(20) NOT NULL,
  `annee_scolaire` varchar(9) NOT NULL,
  `presence_at` datetime NOT NULL,
  `present` tinyint(1) NOT NULL DEFAULT '0',
  `commentaire` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `presences`
--

INSERT INTO `presences` (`id`, `enseignant_id`, `affectation_id`, `cours_id`, `etudiant_id`, `annee_scolaire`, `presence_at`, `present`, `commentaire`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, '2025-2026', '2026-01-30 01:16:00', 1, NULL, '2026-01-30 01:16:06', '2026-01-30 01:16:06');

-- --------------------------------------------------------

--
-- Structure de la table `reservations_salles`
--

CREATE TABLE `reservations_salles` (
  `id` int(10) UNSIGNED NOT NULL,
  `enseignant_id` int(20) NOT NULL,
  `salle_id` int(10) UNSIGNED NOT NULL,
  `cours_nom` varchar(255) NOT NULL,
  `niveau` varchar(50) NOT NULL,
  `effectif` smallint(6) NOT NULL,
  `motif` enum('cours','td','tp','reunion','examen','autre') NOT NULL DEFAULT 'cours',
  `motif_autre` varchar(255) DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `statut` enum('en_attente','validee','rejetee','annulee') NOT NULL DEFAULT 'en_attente',
  `rejet_motif` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `reservations_salles`
--

INSERT INTO `reservations_salles` (`id`, `enseignant_id`, `salle_id`, `cours_nom`, `niveau`, `effectif`, `motif`, `motif_autre`, `date_debut`, `date_fin`, `statut`, `rejet_motif`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'info', '1ere anne', 30, 'cours', NULL, '2026-02-06 13:17:00', '2026-02-06 17:00:00', 'annulee', NULL, '2026-01-29 22:00:55', '2026-01-29 22:01:50'),
(2, 1, 12, 'Algo', '1ère année', 24, 'autre', 'Rattrapage', '2026-01-28 00:05:00', '2026-01-28 00:22:00', 'validee', NULL, '2026-01-30 00:05:51', '2026-01-30 00:06:19'),
(3, 1, 5, 'FB', '1ère année', 23, 'td', NULL, '2026-01-01 00:08:00', '2026-01-01 00:20:00', 'rejetee', 'Occupé', '2026-01-30 00:07:34', '2026-01-30 00:07:52'),
(4, 1, 1, 'info', '1ère année', 12, 'td', NULL, '2026-01-08 03:18:00', '2026-01-30 03:18:00', 'validee', NULL, '2026-01-30 00:18:33', '2026-01-30 00:18:51');

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
  `id` int(10) UNSIGNED NOT NULL,
  `batiment` enum('A','B') NOT NULL,
  `nom` varchar(50) NOT NULL,
  `type` enum('amphi','salle') NOT NULL,
  `etage` tinyint(4) NOT NULL,
  `taille` enum('Standard','Grande') NOT NULL,
  `capacite` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`id`, `batiment`, `nom`, `type`, `etage`, `taille`, `capacite`) VALUES
(1, 'A', 'Amphi A', 'amphi', 1, 'Grande', 36),
(2, 'A', 'Salle B', 'salle', 2, 'Standard', 28),
(3, 'A', 'Salle F', 'salle', 3, 'Grande', 32),
(4, 'A', 'Salle E', 'salle', 3, 'Standard', 30),
(5, 'A', 'Amphi D', 'amphi', 2, 'Grande', 64),
(6, 'A', 'Salle C', 'salle', 2, 'Grande', 36),
(7, 'B', 'Amphi A', 'amphi', 1, 'Grande', 36),
(8, 'B', 'Salle B', 'salle', 2, 'Standard', 28),
(9, 'B', 'Salle F', 'salle', 3, 'Grande', 32),
(10, 'B', 'Salle E', 'salle', 3, 'Standard', 30),
(11, 'B', 'Amphi D', 'amphi', 2, 'Grande', 64),
(12, 'B', 'Salle C', 'salle', 2, 'Grande', 36);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- Index pour la table `cours`
--
ALTER TABLE `cours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `document_demandes`
--
ALTER TABLE `document_demandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_etudiant_id` (`etudiant_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_demande` (`date_demande`);

--
-- Index pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD PRIMARY KEY (`ID-ENSEIGNANT`);

--
-- Index pour la table `enseignant_affectations`
--
ALTER TABLE `enseignant_affectations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_affect` (`enseignant_id`,`filiere_id`,`niveau_id`,`cours_id`,`annee_scolaire`,`groupe`),
  ADD KEY `fk_aff_filiere` (`filiere_id`),
  ADD KEY `fk_aff_niveau` (`niveau_id`),
  ADD KEY `fk_aff_cours` (`cours_id`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`ID-ETUDIANT`);

--
-- Index pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `filiere_cours`
--
ALTER TABLE `filiere_cours`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_filiere_cours` (`filiere_id`,`cours_id`,`niveau_id`),
  ADD KEY `fk_fc_cours` (`cours_id`),
  ADD KEY `fk_fc_niveau` (`niveau_id`);

--
-- Index pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_insc_unique` (`etudiant_id`,`annee_scolaire`),
  ADD KEY `idx_insc_filiere_niveau` (`filiere_id`,`niveau_id`),
  ADD KEY `fk_insc_niveau` (`niveau_id`);

--
-- Index pour la table `niveaux`
--
ALTER TABLE `niveaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `libelle` (`libelle`);

--
-- Index pour la table `presences`
--
ALTER TABLE `presences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_presence` (`affectation_id`,`etudiant_id`,`presence_at`),
  ADD KEY `idx_presence_affect_date` (`affectation_id`,`presence_at`),
  ADD KEY `idx_presence_etudiant` (`etudiant_id`),
  ADD KEY `fk_presence_cours` (`cours_id`),
  ADD KEY `fk_presence_enseignant` (`enseignant_id`);

--
-- Index pour la table `reservations_salles`
--
ALTER TABLE `reservations_salles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enseignant` (`enseignant_id`),
  ADD KEY `idx_salle` (`salle_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_debut` (`date_debut`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_salle` (`batiment`,`nom`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `cours`
--
ALTER TABLE `cours`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `document_demandes`
--
ALTER TABLE `document_demandes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `enseignant`
--
ALTER TABLE `enseignant`
  MODIFY `ID-ENSEIGNANT` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `enseignant_affectations`
--
ALTER TABLE `enseignant_affectations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `etudiant`
--
ALTER TABLE `etudiant`
  MODIFY `ID-ETUDIANT` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `filiere_cours`
--
ALTER TABLE `filiere_cours`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `niveaux`
--
ALTER TABLE `niveaux`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `presences`
--
ALTER TABLE `presences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `reservations_salles`
--
ALTER TABLE `reservations_salles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `salles`
--
ALTER TABLE `salles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `document_demandes`
--
ALTER TABLE `document_demandes`
  ADD CONSTRAINT `fk_doc_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiant` (`ID-ETUDIANT`) ON DELETE CASCADE;

--
-- Contraintes pour la table `enseignant_affectations`
--
ALTER TABLE `enseignant_affectations`
  ADD CONSTRAINT `fk_aff_cours` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`),
  ADD CONSTRAINT `fk_aff_ens` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignant` (`ID-ENSEIGNANT`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_aff_filiere` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`),
  ADD CONSTRAINT `fk_aff_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`);

--
-- Contraintes pour la table `filiere_cours`
--
ALTER TABLE `filiere_cours`
  ADD CONSTRAINT `fk_fc_cours` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fc_filiere` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fc_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`);

--
-- Contraintes pour la table `inscriptions`
--
ALTER TABLE `inscriptions`
  ADD CONSTRAINT `fk_insc_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiant` (`ID-ETUDIANT`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_insc_filiere` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`),
  ADD CONSTRAINT `fk_insc_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`);

--
-- Contraintes pour la table `presences`
--
ALTER TABLE `presences`
  ADD CONSTRAINT `fk_presence_affect` FOREIGN KEY (`affectation_id`) REFERENCES `enseignant_affectations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_presence_cours` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`),
  ADD CONSTRAINT `fk_presence_enseignant` FOREIGN KEY (`enseignant_id`) REFERENCES `enseignant` (`ID-ENSEIGNANT`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_presence_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiant` (`ID-ETUDIANT`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations_salles`
--
ALTER TABLE `reservations_salles`
  ADD CONSTRAINT `fk_res_salle` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
