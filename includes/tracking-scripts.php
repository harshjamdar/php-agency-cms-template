<?php
/**
 * Tracking Scripts Injector
 * Loads API settings from database and generates tracking code snippets
 * for Google Analytics, Google Tag Manager, Facebook Pixel, etc.
 */

// Function to get API setting value
if (!function_exists('getApiSetting')) {
    function getApiSetting($key) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM api_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}

// Load all API settings
$google_analytics_id = getApiSetting('google_analytics_id');
$google_tag_manager_id = getApiSetting('google_tag_manager_id');
$facebook_pixel_id = getApiSetting('facebook_pixel_id');
$recaptcha_site_key = getApiSetting('recaptcha_site_key');
?>

<?php if ($google_analytics_id): ?>
<!-- Google Analytics (GA4) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($google_analytics_id); ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo htmlspecialchars($google_analytics_id); ?>');
</script>
<?php endif; ?>

<?php if ($google_tag_manager_id): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo htmlspecialchars($google_tag_manager_id); ?>');</script>
<?php endif; ?>

<?php if ($facebook_pixel_id): ?>
<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '<?php echo htmlspecialchars($facebook_pixel_id); ?>');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($facebook_pixel_id); ?>&ev=PageView&noscript=1"
/></noscript>
<?php endif; ?>

<?php if ($recaptcha_site_key): ?>
<!-- Google reCAPTCHA v3 -->
<script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptcha_site_key); ?>"></script>
<script>
// Initialize reCAPTCHA v3 when page loads
window.addEventListener('load', function() {
    grecaptcha.ready(function() {
        // Execute reCAPTCHA on page load to register activity
        grecaptcha.execute('<?php echo htmlspecialchars($recaptcha_site_key); ?>', {action: 'homepage'})
            .then(function(token) {
                console.log('reCAPTCHA v3 initialized successfully');
            });
    });
});

// Helper function to get reCAPTCHA token for forms
function getRecaptchaToken(action) {
    return grecaptcha.execute('<?php echo htmlspecialchars($recaptcha_site_key); ?>', {action: action});
}
</script>
<?php endif; ?>
