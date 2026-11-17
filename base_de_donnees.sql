-- ============================================================
--  base_de_donnees.sql — Salle de sport ERS (v2 — 2026)
--  Importer dans phpMyAdmin : Importer > Choisir ce fichier > Exécuter
-- ============================================================

CREATE DATABASE IF NOT EXISTS salle_sport
    CHARACTER SET utf8
    COLLATE utf8_general_ci;

USE salle_sport;

-- ── Table 1 : membres (inscription + connexion) ──────────────
CREATE TABLE IF NOT EXISTS membres (
    id               INT          NOT NULL AUTO_INCREMENT,
    pseudo           VARCHAR(10)  NOT NULL,
    nom              VARCHAR(50)  NOT NULL DEFAULT '',
    prenom           VARCHAR(50)  NOT NULL DEFAULT '',
    email            VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    date_inscription DATETIME     DEFAULT NOW(),
    PRIMARY KEY(id)
);

-- ── Table 2 : abonnements ────────────────────────────────────
CREATE TABLE IF NOT EXISTS abonnements (
    id               INT          NOT NULL AUTO_INCREMENT,
    membre_id        INT          DEFAULT NULL,
    activite         VARCHAR(50)  NOT NULL,
    age              VARCHAR(10)  NOT NULL,
    duree_mois       INT          NOT NULL,
    prix             DECIMAL(8,2) NOT NULL,
    statut           ENUM('payé','non payé') DEFAULT 'payé',
    date_abonnement  DATETIME     DEFAULT NOW(),
    PRIMARY KEY(id),
    FOREIGN KEY(membre_id) REFERENCES membres(id) ON DELETE SET NULL
);

-- ── Table 3 : imc ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS imc (
    id                  INT  NOT NULL AUTO_INCREMENT,
    membre_id           INT  DEFAULT NULL,
    sexe                VARCHAR(6)   NOT NULL,
    taille              FLOAT        NOT NULL,
    poids               FLOAT        NOT NULL,
    imc                 FLOAT        NOT NULL,
    masse_graisse       FLOAT,
    masse_sans_graisse  FLOAT,
    masse_musculaire    FLOAT,
    masse_eau           FLOAT,
    date_calcul         DATETIME     DEFAULT NOW(),
    PRIMARY KEY(id),
    FOREIGN KEY(membre_id) REFERENCES membres(id) ON DELETE SET NULL
);

-- ── Données de test (optionnel) ──────────────────────────────
-- Mot de passe = "Test123" (md5 = 68eacb97d86f0c4621fa2b0e17cabd8c)
INSERT INTO membres (pseudo, nom, prenom, email, mot_de_passe)
VALUES ('admin1', 'Ben Ali', 'Mohamed', 'admin@ers.tn', '68eacb97d86f0c4621fa2b0e17cabd8c')
ON DUPLICATE KEY UPDATE pseudo = pseudo;
