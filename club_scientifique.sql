-- Base de données : club_scientifique
-- Script de création des tables

-- Suppression des tables si elles existent déjà
DROP TABLE IF EXISTS messages_contact;
DROP TABLE IF EXISTS informations;
DROP TABLE IF EXISTS projets;
DROP TABLE IF EXISTS membres;
DROP TABLE IF EXISTS admins;

-- Structure de la table `admins`
-- Table des administrateurs du site
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  date_creation DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Structure de la table `membres`
-- Table des membres du club (candidats et validés)
CREATE TABLE membres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL,
  prenom VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  telephone VARCHAR(20) NOT NULL,
  classe ENUM('Seconde', 'Première', 'Terminale', 'Supérieur') NOT NULL,
  serie ENUM('S', 'S1', 'S2', 'Autre') NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  statut ENUM('en_attente', 'valide', 'refuse') NOT NULL DEFAULT 'en_attente',
  date_candidature DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Structure de la table `projets`
-- Table des projets scientifiques
CREATE TABLE projets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  date_creation DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Structure de la table `informations`
-- Table des articles d'information
CREATE TABLE informations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(100) NOT NULL,
  contenu TEXT NOT NULL,
  document_url VARCHAR(255) DEFAULT NULL,
  date_publication DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Structure de la table `messages_contact`
-- Table des messages envoyés via le formulaire de contact
CREATE TABLE messages_contact (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  objet VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  lu TINYINT(1) NOT NULL DEFAULT 0,
  date_envoi DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Structure de la table `galerie`
-- Table pour stocker les images de la galerie
CREATE TABLE galerie (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(100) NOT NULL,
  description TEXT,
  categorie_id INT NOT NULL,
  fichier VARCHAR(255) NOT NULL,
  date_ajout DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Structure de la table `categories_galerie`
-- Table pour les catégories d'images
CREATE TABLE categories_galerie (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion d'un administrateur par défaut
-- Email: admin@club-scientifique.fr
-- Mot de passe: Admin123!
INSERT INTO admins (nom, prenom, email, mot_de_passe, date_creation) VALUES
('Admin', 'Super', 'admin@club-scientifique.fr', '$2y$10$Gl9EQK4A9UlCIqIJVyxJueW.EFZQQEDfGZqHXc8OL8Zh36XAdRrta', NOW());

-- Insertion des catégories de galerie par défaut
INSERT INTO categories_galerie (nom) VALUES
('Projets scientifiques'),
('Événements et sorties'),
('Réunions du club');
