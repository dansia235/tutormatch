# Database Migration Guide

This guide outlines the process for migrating the database schema to support all the features implemented in the tutoring system application.

## Overview of Changes

The database migration addresses several inconsistencies between the code and the database schema:

1. **Missing Tables**:
   - `user_login_history` - User login tracking
   - `user_preferences` - User-specific application settings
   - `system_settings` - Global application configuration
   - `conversations` - Group and private conversation support
   - `conversation_participants` - User participants in conversations
   - `message_recipients` - Recipients of messages (for group messaging)

2. **Missing Columns**:
   - Added to the `messages` table:
     - `conversation_id` - Link to the conversation
     - `is_group` - Flag for group messages
     - `created_at` - Timestamp for message creation

3. **Naming Inconsistencies**:
   - The code references `recipient_id` while the schema uses `receiver_id`
   - The code uses `created_at` while the schema uses `sent_at` for messages

## Migration Process

### Step 1: Backup Your Database

Before proceeding with any updates, create a complete backup of your database:

```bash
mysqldump -u [username] -p [database_name] > tutoring_system_backup_[YYYY-MM-DD].sql
```

### Step 2: Review the SQL Update Script

The updates are defined in `/database/updates.sql`. This file contains:
- CREATE TABLE statements for all missing tables
- ALTER TABLE statements for adding missing columns
- INSERT statements for default system settings
- A stored procedure to migrate existing messages to the new conversation system

### Step 3: Run the Database Update Script

1. Navigate to `/update_database.php` in your web browser
2. Review the SQL statements that will be executed
3. Check the confirmation checkbox and click "Update Database"
4. Verify that all queries were executed successfully

### Step 4: Verify the Migration

After running the update script, verify that:
- All tables were created successfully
- Columns were added to existing tables
- The message migration procedure completed without errors

### Step 5: Code Considerations

The codebase is already designed to work with both the old direct messaging system and the new conversation-based system. However, you should be aware of the following:

#### Handling Old and New Message Formats

The Message model in `/models/Message.php` includes methods for both:
- Direct messages (sender_id, receiver_id)
- Conversation-based messages (conversation_id, message_recipients)

The `getConversationsByUserId()` method creates virtual conversations from direct messages for backward compatibility.

#### API Endpoints

The messaging API endpoints in `/api/messages/` are designed to work with both systems. When sending new messages, they will use the conversation system.

## Troubleshooting

### Common Issues

1. **DELIMITER Errors**:
   - The update script uses DELIMITER statements for stored procedures
   - If you run the script manually in phpMyAdmin, ensure you execute it as a whole script

2. **Foreign Key Constraints**:
   - The update adds foreign key constraints that require existing data to be valid
   - If you encounter constraint errors, check for orphaned records in your database

3. **Duplicate Entries**:
   - When migrating messages to conversations, duplicate participant entries might occur
   - The migration procedure handles this with UNIQUE constraints

### Manual Recovery

If the automated update fails:

1. Run individual CREATE TABLE statements from the update script
2. Add columns to the messages table manually
3. Run the migration procedure separately if needed

```sql
-- Run the migration procedure manually
CALL MigrateMessagesToConversations();
```

## Future Considerations

### Code Updates

Consider updating the Message model to use consistent field names:
- Either use `receiver_id` throughout the code to match the schema
- Or rename the column in the schema to `recipient_id` to match the code

### Performance Optimizations

For large databases, consider:
- Adding indexes to commonly queried fields
- Partitioning message tables by date for faster access to recent messages
- Implementing message archiving for older messages

## Support

If you encounter issues during migration, please contact the development team or create an issue in the project repository.

---

Document created on: June 6, 2025