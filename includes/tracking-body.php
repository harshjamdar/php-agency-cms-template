<?php
// Ensure variable is available
if (!isset($google_tag_manager_id) && function_exists('getApiSetting')) {
    $google_tag_manager_id = getApiSetting('google_tag_manager_id');
}
?>
<?php if (!empty($google_tag_manager_id)): ?>
<!-- Google Tag Manager (noscript) - Place after opening <body> tag -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo htmlspecialchars($google_tag_manager_id); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>