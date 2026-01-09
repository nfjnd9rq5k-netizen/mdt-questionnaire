<?php
define('ADMIN_USERNAME', 'admin');

define('ADMIN_PASSWORD_HASH', '$argon2id$v=19$m=65536,t=4,p=1$WC9MbnJhaU5zUmFoRUphLg$+1LGwuCS+8AMCQ57GJs3h5wXjSZKyYYvjePbJ6WEiAg');

define('ENCRYPTION_KEY_FILE', __DIR__ . '/secure_data/.encryption_key');

function getEncryptionKey() {
    $keyFile = ENCRYPTION_KEY_FILE;
    $keyDir = dirname($keyFile);
    
    if (!file_exists($keyDir)) {
        mkdir($keyDir, 0700, true);
        file_put_contents($keyDir . '/.htaccess', "Deny from all\n");
    }
    
    if (!file_exists($keyFile)) {
        $key = bin2hex(random_bytes(32));
        file_put_contents($keyFile, $key);
        chmod($keyFile, 0600);
    }
    
    return file_get_contents($keyFile);
}

define('ENCRYPTION_KEY', getEncryptionKey());

define('DATA_DIR', __DIR__ . '/secure_data/');
define('DATA_FILE', DATA_DIR . 'responses.enc');
define('BACKUP_DIR', DATA_DIR . 'backups/');

define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);

define('STUDY_ID', 'SHARK_INHOME_JAN2026');
define('STUDY_NAME', 'Étude Shark - In Home');
define('TARGET_PARTICIPANTS', 5);

if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0700, true);
}
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0700, true);
}

$htaccess = DATA_DIR . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\nOptions -Indexes\n");
}

$indexFile = DATA_DIR . 'index.php';
if (!file_exists($indexFile)) {
    file_put_contents($indexFile, "<?php http_response_code(403); exit('Access Denied');");
}
