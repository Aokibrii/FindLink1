-- Drop table if exists to avoid conflicts
DROP TABLE IF EXISTS notifications;

-- Create notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster queries
CREATE INDEX idx_user_email ON notifications(user_email);
CREATE INDEX idx_created_at ON notifications(created_at);

-- Insert sample notifications
INSERT INTO notifications (user_email, message) VALUES 
('test@example.com', 'Welcome to Lost and Found! Start posting your items.'),
('test@example.com', 'Your post "Lost Phone" has received a new comment.'),
('test@example.com', 'Someone found an item that matches your lost item description.'); 