-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 29 jan. 2026 à 19:01
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

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
-- Structure de la table `enseignant`
--

CREATE TABLE `enseignant` (
  `ID-ENSEIGNANT` int(20) NOT NULL,
  `NOM` varchar(255) NOT NULL,
  `PRENOM` varchar(255) NOT NULL,
  `EMAIL` text NOT NULL,
  `MDP` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `ID-ETUDIANT` int(20) NOT NULL,
  `NOM` varchar(255) NOT NULL,
  `PRENOM` varchar(255) NOT NULL,
  `EMAIL` varchar(255) NOT NULL,
  `MDP` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`ID-ETUDIANT`, `NOM`, `PRENOM`, `EMAIL`, `MDP`) VALUES
(1, 'LALEYE', 'Adetutu', 'ademht@hestim.ma', '123455');

-- --------------------------------------------------------

--
-- Structure de la table `liste_enseignant`
--

CREATE TABLE `liste_enseignant` (
  `EMAIL` varchar(255) NOT NULL,
  `MDP` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `liste_enseignant`
--

INSERT INTO `liste_enseignant` (`EMAIL`, `MDP`) VALUES
('manuellamht@hestim.ma', '123456'),
('abdel@hestim.ma', '7890');

-- --------------------------------------------------------

--
-- Structure de la table `liste_etudiant`
--

CREATE TABLE `liste_etudiant` (
  `NOM` int(11) NOT NULL,
  `PRENOM` int(11) NOT NULL,
  `EMAIL` int(11) NOT NULL,
  `MDP` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD PRIMARY KEY (`ID-ENSEIGNANT`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`ID-ETUDIANT`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `enseignant`
--
ALTER TABLE `enseignant`
  MODIFY `ID-ENSEIGNANT` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `etudiant`
--
ALTER TABLE `etudiant`
  MODIFY `ID-ETUDIANT` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
