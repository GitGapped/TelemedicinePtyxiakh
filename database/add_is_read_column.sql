-- Add is_read column to chat_messages table
ALTER TABLE chat_messages
ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0;

-- Update existing messages to be marked as read
UPDATE chat_messages SET is_read = 1; 