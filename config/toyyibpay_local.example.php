<?php
/**
 * ToyyibPay local credentials — copy to toyyibpay_local.php and fill in.
 * Never commit toyyibpay_local.php (listed in .gitignore).
 */

/** User Secret Key from ToyyibPay merchant profile. */
define('TOYYIBPAY_SECRET_KEY', 'your-user-secret-key-here');

/**
 * true  = https://dev.toyyibpay.com (sandbox / test account)
 * false = https://toyyibpay.com (live production)
 */
define('TOYYIBPAY_USE_SANDBOX', true);

/**
 * Public site URL for ToyyibPay return/callback (no trailing slash).
 * Live InfinityFree:
 *   https://scholarhub.infinityfree.me
 * Local XAMPP:
 *   http://localhost/project_Group13
 */
// define('TOYYIBPAY_APP_BASE_URL', 'https://scholarhub.infinityfree.me');
