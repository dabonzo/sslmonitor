<?php
namespace Deployer;

require 'recipe/laravel.php';

// Load environment variables from .env.deployer
if (file_exists(__DIR__ . '/.env.deployer')) {
    $lines = file(__DIR__ . '/.env.deployer', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv("$key=$value");
    }
}

// Project configuration
set('application', 'SSL Monitor v4');
set('repository', 'git@github.com:dabonzo/sslmonitor.git');
set('keep_releases', 3);
set('default_timeout', 600); // 10 minutes for long-running tasks

// Shared files/dirs between deploys
add('shared_files', [
    '.env',
]);

add('shared_dirs', [
    'storage',
    '.playwright',  // Playwright browsers (persistent!)
]);

// Writable dirs by web server
set('writable_mode', 'chmod');
set('writable_use_sudo', false);
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// Host configuration for ISPConfig
host('production')
    ->setHostname('monitor.intermedien.at')
    ->setRemoteUser('default_deploy')  // Maps to web6:client0
    ->setIdentityFile('~/.ssh/ssl-monitor-deploy')
    ->set('deploy_path', '/var/www/monitor.intermedien.at/web')
    ->set('branch', 'main')
    ->set('http_user', 'web6')
    ->set('http_group', 'client0')
    ->set('bin/php', '/usr/bin/php8.3')
    ->set('bin/composer', '/usr/local/bin/composer');

// Custom Tasks

/**
 * Task: Install Playwright browsers in shared directory
 */
task('playwright:install', function () {
    cd('{{release_path}}');

    $playwrightPath = '{{deploy_path}}/shared/.playwright';

    // Check if Firefox is already installed (using Firefox instead of Chrome to avoid crashpad issues)
    $installed = run("test -d $playwrightPath/firefox-* && echo 'yes' || echo 'no'");

    if ($installed === 'no') {
        writeln('<comment>Installing Firefox for the first time (this may take 2-3 minutes)...</comment>');
        run("PLAYWRIGHT_BROWSERS_PATH=$playwrightPath npx playwright install firefox", ['timeout' => 600]);
        writeln('<info>✓ Firefox installed to shared directory</info>');
    } else {
        writeln('<info>✓ Firefox already installed in shared directory</info>');
    }
})->desc('Install Playwright Firefox in shared directory');

/**
 * Task: Update Firefox path in .env
 */
task('playwright:update_env', function () {
    $playwrightPath = '{{deploy_path}}/shared/.playwright';

    // Find the actual Firefox binary path
    $firefoxPath = run("find $playwrightPath -name firefox -type f -path '*/firefox/firefox' | head -n 1 || echo ''");

    if (empty($firefoxPath)) {
        writeln('<comment>Firefox binary not found yet - config will auto-detect it</comment>');
        return;
    }

    // Update or add BROWSERSHOT_CHROME_PATH in .env (keeping name for compatibility)
    $envPath = '{{deploy_path}}/shared/.env';
    $hasPath = run("grep -q 'BROWSERSHOT_CHROME_PATH' $envPath && echo 'yes' || echo 'no'");

    if ($hasPath === 'yes') {
        run("sed -i 's|^BROWSERSHOT_CHROME_PATH=.*|BROWSERSHOT_CHROME_PATH=$firefoxPath|' $envPath");
    } else {
        run("echo 'BROWSERSHOT_CHROME_PATH=$firefoxPath' >> $envPath");
    }

    writeln("<info>✓ Updated BROWSERSHOT_CHROME_PATH to Firefox: $firefoxPath</info>");
})->desc('Update Firefox path in .env file');

/**
 * Task: Terminate Horizon gracefully
 */
task('horizon:terminate', function () {
    // Check if current deployment exists
    $currentExists = run("test -d {{deploy_path}}/current && echo 'yes' || echo 'no'");

    if ($currentExists === 'no') {
        writeln('<info>No current deployment - skipping Horizon termination</info>');
        return;
    }

    // Check if Horizon is running
    $isRunning = run("cd {{deploy_path}}/current && {{bin/php}} artisan horizon:status 2>/dev/null | grep -q 'running' && echo 'yes' || echo 'no'");

    if ($isRunning === 'yes') {
        writeln('<comment>Terminating Horizon gracefully...</comment>');
        run('cd {{deploy_path}}/current && {{bin/php}} artisan horizon:terminate', ['timeout' => 60]);
        writeln('<info>✓ Horizon terminated gracefully</info>');

        // Wait for Horizon to fully stop
        run('sleep 3');
    } else {
        writeln('<info>✓ Horizon is not running</info>');
    }
})->desc('Terminate Horizon gracefully');

/**
 * Task: Restart Horizon via systemd
 */
task('horizon:restart', function () {
    writeln('<comment>Restarting Horizon service via systemd...</comment>');

    try {
        run('sudo -n /usr/bin/systemctl restart ssl-monitor-horizon');

        // Wait a moment for service to start
        run('sleep 2');

        // Verify it's running
        $status = run('sudo -n /usr/bin/systemctl is-active ssl-monitor-horizon || echo "inactive"');

        if ($status === 'active') {
            writeln('<info>✓ Horizon service restarted successfully</info>');
        } else {
            writeln('<error>⚠ Horizon service may not be running. Check with: sudo systemctl status ssl-monitor-horizon</error>');
        }
    } catch (\Exception $e) {
        writeln('<error>Failed to restart Horizon: ' . $e->getMessage() . '</error>');
        throw $e;
    }
})->desc('Restart Horizon systemd service');

/**
 * Task: Build production assets
 */
task('build:assets', function () {
    cd('{{release_path}}');

    writeln('<comment>Installing NPM dependencies...</comment>');
    run('npm ci --prefer-offline --no-audit', ['timeout' => 300]);

    writeln('<comment>Building production assets...</comment>');
    run('npm run build', ['timeout' => 600]);

    writeln('<info>✓ Assets built successfully</info>');
})->desc('Build production assets with NPM');

/**
 * Task: Clear all Laravel caches
 */
task('laravel:cache_clear', function () {
    run('cd {{release_path}} && {{bin/php}} artisan cache:clear');
    run('cd {{release_path}} && {{bin/php}} artisan config:clear');
    run('cd {{release_path}} && {{bin/php}} artisan view:clear');
    run('cd {{release_path}} && {{bin/php}} artisan route:clear');
    writeln('<info>✓ All Laravel caches cleared</info>');
})->desc('Clear all Laravel caches');

/**
 * Task: Optimize Laravel caches
 */
task('laravel:optimize', function () {
    run('cd {{release_path}} && {{bin/php}} artisan config:cache');
    run('cd {{release_path}} && {{bin/php}} artisan route:cache');
    run('cd {{release_path}} && {{bin/php}} artisan view:cache');
    run('cd {{release_path}} && {{bin/php}} artisan event:cache');
    writeln('<info>✓ Laravel caches optimized</info>');
})->desc('Optimize Laravel caches for production');

/**
 * Main deployment task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'build:assets',
    'playwright:install',
    'deploy:shared',
    'playwright:update_env',
    'artisan:storage:link',
    'laravel:cache_clear',
    'laravel:optimize',
    'artisan:migrate',
    'horizon:terminate',
    'deploy:symlink',
    'horizon:restart',
    //'deploy:fix_permissions',
    'deploy:cleanup',
    'deploy:success',
])->desc('Deploy SSL Monitor v4 to production');

/**
 * Extend rollback with Horizon restart
 */
after('rollback', 'horizon:restart');

/**
 * Failure handling
 */
fail('deploy', 'deploy:failed');

task('deploy:failed', function () {
    writeln('');
    writeln('<error>═══════════════════════════════════════</error>');
    writeln('<error>  ✗ Deployment FAILED!</error>');
    writeln('<error>═══════════════════════════════════════</error>');
    writeln('');
    writeln('<comment>Attempting to restart Horizon...</comment>');

    // Try to restart Horizon even on failure
    try {
        invoke('horizon:restart');
    } catch (\Exception $e) {
        writeln('<error>Could not restart Horizon</error>');
    }

    writeln('');
    writeln('<comment>Please check the logs above to diagnose the issue.</comment>');
    writeln('<comment>Previous version should still be active.</comment>');
})->desc('Handle deployment failure');

/**
 * Success message
 */
task('deploy:success', function () {
    writeln('');
    writeln('<info>═══════════════════════════════════════</info>');
    writeln('<info>  ✓ Deployment Successful!</info>');
    writeln('<info>═══════════════════════════════════════</info>');
    writeln('');
    writeln('<comment>SSL Monitor v4 has been deployed to production.</comment>');
    writeln('');
    writeln('Next steps:');
    writeln('  1. Visit: https://monitor.intermedien.at');
    writeln('  2. Check Horizon: https://monitor.intermedien.at/horizon');
    writeln('  3. Check logs: ssh default_deploy@monitor.intermedien.at "tail -f /var/www/monitor.intermedien.at/web/shared/storage/logs/laravel.log"');
    writeln('  4. Check Horizon status: sudo systemctl status ssl-monitor-horizon');
    writeln('');
})->desc('Display success message');

/**
 * Task: Fix file permissions before cleanup
 */
task('deploy:fix_permissions', function () {
    writeln('<comment>Fixing file permissions for cleanup...</comment>');

    // Fix permissions on all releases to ensure cleanup can remove them
    run('find {{deploy_path}}/releases -type d -exec chmod 755 {} \;');
    run('find {{deploy_path}}/releases -type f -exec chmod 644 {} \;');

    // Specifically fix permissions on build assets and generated files
    run('find {{deploy_path}}/releases -name "*.js" -exec chmod 644 {} \;');
    run('find {{deploy_path}}/releases -name "*.css" -exec chmod 644 {} \;');
    run('find {{deploy_path}}/releases -name "*.ts" -exec chmod 644 {} \;');

    writeln('<info>✓ File permissions fixed for cleanup</info>');
})->desc('Fix file permissions before cleanup');

/**
 * Task: Clear all caches and restart services after deployment
 */
task('deploy:post_cleanup', function () {
    writeln('<comment>Clearing caches and restarting services...</comment>');

    // Clear all Laravel caches to prevent authentication issues
    run('cd {{release_path}} && {{bin/php}} artisan cache:clear');
    run('cd {{release_path}} && {{bin/php}} artisan config:clear');
    run('cd {{release_path}} && {{bin/php}} artisan view:clear');
    run('cd {{release_path}} && {{bin/php}} artisan route:clear');

    // Restart services to ensure fresh state
    run('sudo /usr/bin/systemctl restart php8.3-fpm');
    run('sudo /usr/bin/systemctl restart redis');
    run('sudo /usr/bin/systemctl restart apache2');

    writeln('<info>✓ All caches cleared and services restarted</info>');
})->desc('Clear caches and restart services after deployment');

// After deploy hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:success', 'deploy:unlock');
after('deploy:success', 'deploy:post_cleanup');
