CREATE DATABASE IF NOT EXISTS katsuki_trading;
USE katsuki_trading;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    subscription_type ENUM('free', 'prop_mastery', 'inner_circle') DEFAULT 'free',
    subscription_expiry DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Table des cours
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    level ENUM('Débutant', 'Avancé', 'Élite') NOT NULL,
    icon VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) NULL,
    spots INT NULL,
    duration VARCHAR(50) NOT NULL,
    modules_count INT NOT NULL,
    full_desc TEXT NOT NULL,
    features JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des modules (leçons)
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    order_index INT NOT NULL,
    video_url VARCHAR(255) NULL,
    content TEXT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Table de progression des étudiants
CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    score INT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module_id)
);

-- Insertion des cours (données initiales)
INSERT INTO courses (title, level, icon, price, original_price, spots, duration, modules_count, full_desc, features) VALUES
('Genèse du Trader', 'Débutant', 'fa-seedling', 0.00, NULL, NULL, '4 semaines', 12, 
 'Le programme fondamental qui vous fait passer de novice complet à trader conscient. Vous apprendrez les bases indispensables que 90% des débutants ignorent.',
 '["Lecture de prix et structure de marché","Identification des zones de liquidité","Gestion du risque professionnelle","Psychologie du débutant : les pièges à éviter","Construction d''un plan de trading personnel","Premiers trades sur compte démo accompagné"]'),

('Prop Firm Mastery', 'Avancé', 'fa-trophy', 299.00, 499.00, 8, '8 semaines intensives', 24,
 'Le programme conçu spécifiquement pour réussir les challenges des Prop Firms. Stratégies optimisées pour les contraintes de drawdown.',
 '["Stratégies conformes aux règles Prop Firm","Optimisation du ratio risque/rendement","Simulations de challenges en temps réel","Gestion du drawdown psychologique","Accès à un canal privé de validation","Coaching hebdomadaire avec un trader certifié"]'),

('Inner Circle Katsuki', 'Élite', 'fa-crown', 999.00, 1499.00, 3, '12 semaines + mentorat continu', 36,
 'La formation ultime. Réservée aux traders qui ont déjà une base solide et qui visent l''excellence absolue.',
 '["Analyse de flux d''ordres et Footprint","Stratégies institutionnelles avancées","Neuropsychologie appliquée au trading","Sessions de mentorat individuelles","Accès permanent à la salle de marché privée","Intégration au réseau des traders Katsuki"]');

-- Insertion de modules pour chaque cours (exemples, à adapter selon besoins)
-- Genèse du Trader (course_id = 1)
INSERT INTO modules (course_id, title, order_index, content) VALUES
(1, 'Introduction : Pourquoi 95% des traders perdent', 1, 'Contenu de la leçon...'),
(1, 'Structure de marché : Supports et résistances', 2, 'Contenu...'),
(1, 'Les zones de liquidité expliquées', 3, 'Contenu...'),
(1, 'Gestion du risque : La règle des 1%', 4, 'Contenu...'),
(1, 'Psychologie : Les biais cognitifs', 5, 'Contenu...'),
(1, 'Construire son plan de trading', 6, 'Contenu...'),
(1, 'Backtester une stratégie', 7, 'Contenu...'),
(1, 'Premiers trades sur démo', 8, 'Contenu...'),
(1, 'Journal de trading : méthode', 9, 'Contenu...'),
(1, 'Analyse des sessions (Londres / New York)', 10, 'Contenu...'),
(1, 'Erreurs fréquentes des débutants', 11, 'Contenu...'),
(1, 'Conclusion et prochaines étapes', 12, 'Contenu...');

-- Prop Firm Mastery (course_id = 2)
INSERT INTO modules (course_id, title, order_index, content) VALUES
(2, 'Comprendre les règles des Prop Firms', 1, 'Contenu...'),
(2, 'Stratégies adaptées au drawdown', 2, 'Contenu...'),
(2, 'Gestion de la pression en challenge', 3, 'Contenu...'),
(2, 'Optimisation R/R pour Prop Firms', 4, 'Contenu...'),
(2, 'Simulation de challenge pas à pas', 5, 'Contenu...'),
(2, 'Coaching hebdomadaire', 6, 'Contenu...'),
(2, 'Analyse de trades réels', 7, 'Contenu...'),
(2, 'Préparation à la certification', 8, 'Contenu...'),
(2, 'Passer le challenge FTMO / MFF', 9, 'Contenu...'),
(2, 'Gérer un compte financé', 10, 'Contenu...'),
(2, 'Scaling et retraits', 11, 'Contenu...'),
(2, 'Conclusion', 12, 'Contenu...');

-- Inner Circle (course_id = 3)
INSERT INTO modules (course_id, title, order_index, content) VALUES
(3, 'Footprint et flux d''ordres', 1, 'Contenu...'),
(3, 'DOM et carnet d''ordres', 2, 'Contenu...'),
(3, 'Stratégies institutionnelles avancées', 3, 'Contenu...'),
(3, 'Neuropsychologie appliquée', 4, 'Contenu...'),
(3, 'Mentorat individuel #1', 5, 'Contenu...'),
(3, 'Analyse de session en direct', 6, 'Contenu...'),
(3, 'Création d''un système automatique', 7, 'Contenu...'),
(3, 'Gestion d''un portefeuille multi-actifs', 8, 'Contenu...');

-- Témoignages (table optionnelle)
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    initials VARCHAR(10) NOT NULL,
    role VARCHAR(100) NOT NULL,
    quote TEXT NOT NULL,
    display_order INT DEFAULT 0
);

INSERT INTO testimonials (name, initials, role, quote, display_order) VALUES
('Marc L.', 'ML', 'Trader Indépendant • 18 mois', 'Avant Katsuki, je perdais de l''argent tous les mois. J''avais essayé 4 formations, rien ne marchait. La différence ? Ici on m''a appris à me connaître avant de trader. Résultat : premier mois profitable après 2 ans de galère.', 1),
('Sophie M.', 'SM', 'Funded Trader • FTMO', 'La Prop Firm Mastery m''a fait gagner 6 mois. J''ai réussi mon challenge FTMO en 3 semaines, première tentative.', 2),
('Karim B.', 'KB', 'Trader Professionnel • 3 ans', 'Ce qui change tout, c''est le mental. On parle tout le temps de stratégie dans les autres formations, jamais de ce qui se passe dans la tête. Katsuki a comblé ce vide.', 3);