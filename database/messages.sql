-- Drop table if exists to avoid conflicts
DROP TABLE IF EXISTS messages;

-- Create messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_email VARCHAR(255) NOT NULL,
    receiver_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for faster queries
CREATE INDEX idx_sender_email ON messages(sender_email);
CREATE INDEX idx_receiver_email ON messages(receiver_email);
CREATE INDEX idx_message_created_at ON messages(created_at);

-- Insert sample messages
INSERT INTO messages (sender_email, receiver_email, subject, message) VALUES 
('admin@example.com', 'test@example.com', 'Welcome Message', 'Welcome to the Lost and Found system! If you have any questions, feel free to reply to this message.'),
('user1@example.com', 'test@example.com', 'About your lost item', 'Hi, I think I found the item you described in your post. Please contact me for more details.'); 