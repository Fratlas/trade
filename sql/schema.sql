CREATE DATABASE IF NOT EXISTS katsuki_trading;
USE katsuki_trading;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,
    bio TEXT NULL,
    avatar_url VARCHAR(255) NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    subscription_type ENUM('free', 'prop_mastery', 'inner_circle') DEFAULT 'free',
    subscription_expiry DATE NULL,
    status ENUM('active', 'disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Table des contenus administrables (vidéo, livre, photo, post, webinar, article, podcast, report)
CREATE TABLE contents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('video', 'book', 'photo', 'post', 'webinar', 'article', 'podcast', 'report') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    media_url VARCHAR(255) NULL,
    thumbnail_url VARCHAR(255) NULL,
    price DECIMAL(10,2) NULL,
    meta JSON NULL,
    created_by INT NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
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

-- Table des matières et liens de cours
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE course_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_subject (course_id, subject_id)
);

-- Table des examens et questions
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    passing_score INT NOT NULL DEFAULT 70,
    duration_minutes INT NOT NULL DEFAULT 60,
    instructions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question TEXT NOT NULL,
    options JSON NOT NULL,
    correct_option VARCHAR(50) NOT NULL,
    points INT NOT NULL DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    score INT NOT NULL,
    max_score INT NOT NULL,
    percentage INT NOT NULL,
    passed TINYINT(1) NOT NULL DEFAULT 0,
    answers JSON NOT NULL,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
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

-- Matières de trading
INSERT INTO subjects (name, description) VALUES
('Fondamentaux du Trading', 'Les bases indispensables pour comprendre les marchés, le risque et la psychologie du trading.'),
('Prop Firm & Gestion du Risque', 'Tout ce qu''il faut savoir pour réussir les challenges des Prop Firms et protéger son capital.'),
('Trading Institutionnel', 'Approfondissement sur les stratégies avancées, le flux d''ordres et le mindset des traders pro.');

INSERT INTO course_subjects (course_id, subject_id) VALUES
(1, 1),
(2, 2),
(3, 3);

-- Examens liés aux cours
INSERT INTO exams (course_id, title, passing_score, duration_minutes, instructions) VALUES
(1, 'Quiz de validation : Genèse du Trader', 70, 45, 'Répondez aux questions pour valider les fondamentaux du trading. Vous devez obtenir au moins 70%.'),
(2, 'Examen Prop Firm Mastery', 75, 60, 'Ce quiz vérifie votre compréhension des règles et des stratégies Prop Firm.'),
(3, 'Examen Inner Circle', 80, 75, 'Testez vos connaissances sur les stratégies institutionnelles et le mindset avancé.');

INSERT INTO exam_questions (exam_id, question, options, correct_option, points) VALUES
(1, 'Quel est le premier principe de gestion du risque ?', '["Risque fixe de 10% par trade","Risque variable selon l''intuition","Risque inférieur à 1% du capital","Risque inexistant sur un compte démo"]', 'Risque inférieur à 1% du capital', 2),
(1, 'Qu''est-ce qu''une zone de liquidité ?', '["Un niveau de prix avec beaucoup d''ordres","Un type d''indicateur technique","Un ordre limité seulement","Un stop loss mal placé"]', 'Un niveau de prix avec beaucoup d''ordres', 2),
(1, 'Quel est le signe d''une gestion émotionnelle saine ?', '["Prendre plus de trades qu''il n''en faut","Suivre son plan de trading","Vérifier les graphiques toutes les 5 minutes","Changer de stratégie en cours de session"]', 'Suivre son plan de trading', 1),
(2, 'Quelle pratique est essentielle pour réussir un challenge Prop Firm ?', '["Augmenter la taille des positions à chaque gain","Respecter strictement les règles de drawdown","Ignorer les pertes de moins de 5%","Changer de stratégie tous les jours"]', 'Respecter strictement les règles de drawdown', 2),
(2, 'Comment doit-on gérer un drawdown en challenge ?', '["Étendre le risque pour récupérer","Réduire la taille des positions","Fermer toutes les positions immédiatement","Prendre plus de trades à haut risque"]', 'Réduire la taille des positions', 2),
(3, 'Qu''est-ce que le Footprint ?', '["Un type de graphique de prix","Une analyse de flux d''ordres","Une méthode de journalisation","Une stratégie de news trading"]', 'Une analyse de flux d''ordres', 2),
(3, 'Quel élément est clé dans le trading institutionnel ?', '["Travailler seul sans mentor","Comprendre le carnet d''ordres","Suivre des signaux gratuits","Trader seulement les weekends"]', 'Comprendre le carnet d''ordres', 2);

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