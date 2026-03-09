CREATE TABLE IF NOT EXISTS post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_post_likes UNIQUE (post_id, user_id),
    CONSTRAINT fk_post_likes_post FOREIGN KEY (post_id)
        REFERENCES posts(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_post_likes_user FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
