<?php
/**
 * WP Customer Cleanup Script
 * 
 * Bulk deletes WordPress users with the 'customer' role who have NO orders.
 * This is useful for cleaning up spam accounts or guest-checkout-only users.
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your WordPress root directory (e.g., public_html/)
 *    OR to wp-content/ (adjust the require_once path below if needed).
 * 2. Visit: https://yourdomain.com/cleanup-customers.php (or wherever you uploaded it)
 * 3. Watch the output. It will delete users in batches.
 * 4. DELETE THIS FILE IMMEDIATELY AFTER RUNNING.
 * 
 * WARNING: This script is destructive. It will permanently delete users.
 * It only deletes users with the 'customer' role who have ZERO orders.
 * 
 * @author Maven (OpenClaw)
 * @license MIT
 */

// 1. SHOW ALL ERRORS (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. DISABLE OUTPUT BUFFERING (force immediate output)
if (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);

// 3. LOAD WORDPRESS
// Adjust path if needed. If this file is in the root (public_html), use:
require_once(__DIR__ . '/wp-load.php');

// If this file is in wp-content, use:
// require_once(__DIR__ . '/../../wp-load.php');

// 4. CONFIGURATION
$batch_size = 100;      // Process 100 users per batch
$max_iterations = 200;  // Safety limit (200 * 100 = 20,000 users)
$role_to_clean = 'customer'; // Target role: 'customer', 'subscriber', etc.

echo "<!DOCTYPE html><html><head><title>WP Customer Cleanup</title>";
echo "<style>
    body { font-family: monospace; background: #111; color: #0f0; padding: 20px; }
    .log { margin: 5px 0; border-bottom: 1px solid #333; padding: 5px; }
    .done { color: #0f0; }
    .warn { color: #ff0; }
    .err { color: #f00; }
    h2 { color: #0f0; }
</style>";
echo "</head><body>";
echo "<h2>🧹 WP Customer Cleanup Script</h2>";
echo "<p>Role: {$role_to_clean} | Batch Size: {$batch_size} | Max Iterations: {$max_iterations}</p>";
echo "<hr>";

$deleted_count = 0;
$skipped_count = 0;
$iteration = 0;
$start_time = time();

// Loop until no more users are found or max iterations reached
while ($iteration < $max_iterations) {
    $iteration++;
    
    // Get users with the specific role (paged)
    $args = array(
        'role' => $role_to_clean,
        'number' => $batch_size,
        'offset' => ($iteration - 1) * $batch_size,
        'orderby' => 'ID',
        'order' => 'ASC'
    );
    
    $users = get_users($args);
    
    if (empty($users)) {
        echo "<div class='done'><p>✅ No more {$role_to_clean} users found. Done!</p></div>";
        break;
    }
    
    echo "<div class='log'><strong>Batch {$iteration}: Processing " . count($users) . " users...</strong></div>";
    
    foreach ($users as $user) {
        $user_id = $user->ID;
        $email = $user->user_email;
        
        // Skip administrators (safety)
        if (in_array('administrator', $user->roles)) {
            $skipped_count++;
            continue;
        }
        
        // Check if user has ANY orders (direct SQL query for speed/reliability)
        global $wpdb;
        $order_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}posts 
            WHERE post_type = 'shop_order' 
            AND meta_key = '_customer_user' 
            AND meta_value = %d",
            $user_id
        ));
        
        if ($order_count > 0) {
            // Has orders -> SKIP
            $skipped_count++;
        } else {
            // No orders -> DELETE (Direct SQL to avoid wp_delete_user crashes)
            try {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->users} WHERE ID = %d", $user_id));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE user_id = %d", $user_id));
                $deleted_count++;
                
                // Print every 10th to keep output clean
                if ($deleted_count % 10 == 0) {
                    echo "<div class='log'> 🗑️ Deleted: {$email} (ID: {$user_id})</div>";
                }
            } catch (Exception $e) {
                echo "<div class='err'> ❌ Failed to delete: {$email} (ID: {$user_id}) - " . $e->getMessage() . "</div>";
                $skipped_count++;
            }
        }
    }
    
    echo "<div class='log'>Batch complete. Total Deleted: {$deleted_count} | Total Skipped (Have Orders): {$skipped_count}</div>";
    echo "<hr>";
    
    // Flush output so you see progress in browser
    if (ob_get_length()) ob_end_flush();
    flush();
    
    // Sleep briefly to prevent server overload
    usleep(50000); // 0.05 seconds
}

$end_time = time();
$duration = $end_time - $start_time;

echo "<h2>🏁 Cleanup Finished</h2>";
echo "<p><strong>Total Deleted:</strong> {$deleted_count}</p>";
echo "<p><strong>Total Skipped (Have Orders):</strong> {$skipped_count}</p>";
echo "<p><strong>Duration:</strong> {$duration} seconds</p>";
echo "<p style='color:red; font-weight:bold; font-size:1.2em;'>⚠️ IMPORTANT: DELETE THIS FILE NOW: cleanup-customers.php</p>";
echo "<p style='color:red;'>Do not leave this file on your server.</p>";
echo "</body></html>";
