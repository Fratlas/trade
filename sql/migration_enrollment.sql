-- Inscriptions payantes + medias modules

CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    proof_url VARCHAR(500) NULL,
    payment_note TEXT NULL,
    admin_note TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_course (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Colonnes modules (ignore si deja presentes via setup-enrollment.php)
-- ALTER TABLE modules ADD COLUMN audio_url VARCHAR(500) NULL AFTER video_url;
-- ALTER TABLE modules ADD COLUMN pdf_url VARCHAR(500) NULL AFTER audio_url;
