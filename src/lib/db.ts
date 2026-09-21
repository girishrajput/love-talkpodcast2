import mysql from 'mysql2/promise';

// Create a connection pool to MySQL
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || process.env.DB_DATABASE || 'lovetalkpodcast2',
  waitForConnections: true,
  connectionLimit: parseInt(process.env.DB_CONNECTION_LIMIT || '10', 10),
  maxIdle: parseInt(process.env.DB_MAX_IDLE || '10', 10),
  idleTimeout: parseInt(process.env.DB_IDLE_TIMEOUT || '60000', 10),
  queueLimit: 0,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0,
  ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: false } : undefined,
});

let tablesInitialized = false;

export async function ensureTablesExist() {
  if (tablesInitialized) return;
  try {
    tablesInitialized = true;

    // 1. Profiles Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`profiles\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`auth_user_id\` VARCHAR(255) UNIQUE NOT NULL,
        \`email\` VARCHAR(255) UNIQUE NOT NULL,
        \`name\` VARCHAR(255) NOT NULL,
        \`avatar_url\` TEXT,
        \`role\` ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user',
        \`status\` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`last_login\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_profiles_email\` (\`email\`),
        INDEX \`idx_profiles_role\` (\`role\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 2. Membership Plans Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`membership_plans\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`name\` VARCHAR(255) NOT NULL,
        \`slug\` VARCHAR(255) UNIQUE NOT NULL,
        \`target_audience\` VARCHAR(255) NOT NULL,
        \`price\` DECIMAL(10, 2) NOT NULL,
        \`currency\` VARCHAR(10) DEFAULT 'INR',
        \`billing_period\` VARCHAR(20) DEFAULT 'year',
        \`discounted_price\` DECIMAL(10, 2),
        \`is_active\` BOOLEAN DEFAULT TRUE,
        \`is_featured\` BOOLEAN DEFAULT FALSE,
        \`badge\` VARCHAR(100),
        \`benefits\` JSON,
        \`razorpay_plan_id\` VARCHAR(255),
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX \`idx_plans_slug\` (\`slug\`),
        INDEX \`idx_plans_active\` (\`is_active\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 3. Memberships Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`memberships\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`user_id\` VARCHAR(36) NOT NULL,
        \`plan_id\` VARCHAR(36),
        \`status\` ENUM('active', 'pending', 'expired', 'cancelled', 'failed', 'paused') NOT NULL DEFAULT 'pending',
        \`razorpay_customer_id\` VARCHAR(255),
        \`razorpay_subscription_id\` VARCHAR(255),
        \`start_date\` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`end_date\` DATETIME NOT NULL,
        \`auto_renew\` BOOLEAN DEFAULT TRUE,
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX \`idx_memberships_user_id\` (\`user_id\`),
        INDEX \`idx_memberships_status\` (\`status\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 4. Payments Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`payments\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`user_id\` VARCHAR(36) NOT NULL,
        \`membership_id\` VARCHAR(36),
        \`razorpay_payment_id\` VARCHAR(255) UNIQUE NOT NULL,
        \`razorpay_order_id\` VARCHAR(255) NOT NULL,
        \`razorpay_subscription_id\` VARCHAR(255),
        \`amount\` DECIMAL(10, 2) NOT NULL,
        \`currency\` VARCHAR(10) DEFAULT 'INR',
        \`status\` ENUM('captured', 'failed', 'refunded', 'pending') NOT NULL,
        \`payment_method\` VARCHAR(50) DEFAULT 'upi',
        \`paid_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_payments_order\` (\`razorpay_order_id\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 5. Membership Orders Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`membership_orders\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`user_id\` VARCHAR(36) NOT NULL,
        \`plan_id\` VARCHAR(36) NOT NULL,
        \`razorpay_order_id\` VARCHAR(255) UNIQUE NOT NULL,
        \`amount\` DECIMAL(10, 2) NOT NULL,
        \`currency\` VARCHAR(10) DEFAULT 'INR',
        \`status\` ENUM('created', 'pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'created',
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX \`idx_membership_orders_user\` (\`user_id\`),
        INDEX \`idx_membership_orders_rzp\` (\`razorpay_order_id\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 6. Webhook Events Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`webhook_events\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`event_id\` VARCHAR(255) UNIQUE NOT NULL,
        \`event_type\` VARCHAR(100) NOT NULL,
        \`payload\` JSON NOT NULL,
        \`processed\` BOOLEAN DEFAULT FALSE,
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_webhook_event\` (\`event_id\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 7. Episodes Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`episodes\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`title\` VARCHAR(255) NOT NULL,
        \`episode_number\` INT UNIQUE NOT NULL,
        \`slug\` VARCHAR(255) UNIQUE NOT NULL,
        \`description\` TEXT NOT NULL,
        \`audio_url\` TEXT NOT NULL,
        \`audio_duration\` INT NOT NULL,
        \`cover_image\` TEXT NOT NULL,
        \`language\` ENUM('english', 'hindi', 'bilingual') DEFAULT 'english',
        \`tags\` JSON,
        \`publish_date\` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`is_published\` BOOLEAN DEFAULT TRUE,
        \`access_type\` ENUM('FREE', 'PREMIUM') NOT NULL DEFAULT 'FREE',
        \`preview_duration\` INT DEFAULT 60,
        \`transcript_en\` MEDIUMTEXT,
        \`transcript_hi\` MEDIUMTEXT,
        \`listens_count\` INT DEFAULT 0,
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX \`idx_episodes_slug\` (\`slug\`),
        INDEX \`idx_episodes_access_type\` (\`access_type\`),
        INDEX \`idx_episodes_published\` (\`is_published\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 8. Comments Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`comments\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`episode_id\` VARCHAR(36) NOT NULL,
        \`user_name\` VARCHAR(255) NOT NULL,
        \`user_email\` VARCHAR(255) NOT NULL,
        \`user_avatar\` TEXT,
        \`rating\` INT DEFAULT 5,
        \`content\` TEXT NOT NULL,
        \`likes_count\` INT DEFAULT 0,
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_comments_episode\` (\`episode_id\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 9. Premium Benefits Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`premium_benefits\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`title\` VARCHAR(255) NOT NULL,
        \`description\` TEXT,
        \`icon\` VARCHAR(100) DEFAULT 'Sparkles',
        \`is_enabled\` BOOLEAN DEFAULT TRUE,
        \`sort_order\` INT DEFAULT 0,
        \`plans\` JSON,
        \`created_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_benefits_enabled\` (\`is_enabled\`),
        INDEX \`idx_benefits_sort\` (\`sort_order\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 10. Site Settings Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`site_settings\` (
        \`setting_key\` VARCHAR(100) PRIMARY KEY,
        \`setting_value\` JSON NOT NULL,
        \`updated_at\` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);

    // 11. Subscribers Table
    await pool.query(`
      CREATE TABLE IF NOT EXISTS \`subscribers\` (
        \`id\` VARCHAR(36) PRIMARY KEY,
        \`email\` VARCHAR(255) UNIQUE NOT NULL,
        \`name\` VARCHAR(255),
        \`subscribed_at\` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX \`idx_subscribers_email\` (\`email\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `);
  } catch (err) {
    console.warn('Auto table initialization warning:', err);
  }
}

/**
 * Execute a MySQL query with parameters
 */
export async function query<T = any>(sql: string, params: any[] = []): Promise<T> {
  try {
    const isProd = process.env.NODE_ENV === 'production';
    const enableAutoMigrate = process.env.ENABLE_AUTO_MIGRATION === 'true';
    if (!isProd || enableAutoMigrate) {
      await ensureTablesExist();
    }
    const [rows] = await pool.execute(sql, params);
    return rows as T;
  } catch (error: any) {
    console.error('MySQL Query Error:', error);
    throw new Error(error.message || 'Database query execution failed');
  }
}

export default pool;
