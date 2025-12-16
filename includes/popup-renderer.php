<?php
// Fetch active popups
try {
    if (!isset($pdo)) {
        require_once __DIR__ . '/../../config.php';
    }
    
    $stmt = $pdo->query("SELECT * FROM popups WHERE is_active = 1");
    $activePopups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $activePopups = [];
}

if (!empty($activePopups)):
?>
<!-- Popup Container -->
<div id="popup-overlay" class="fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div id="popup-content" class="bg-slate-900 border border-white/10 rounded-2xl max-w-lg w-full shadow-2xl transform scale-95 transition-transform duration-300 relative overflow-hidden">
        <!-- Close Button -->
        <button id="popup-close" class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors z-10">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        
        <!-- Dynamic Content -->
        <div id="popup-body" class="p-8 text-white prose prose-invert max-w-none">
            <!-- Content injected via JS -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popups = <?php echo json_encode($activePopups); ?>;
    const overlay = document.getElementById('popup-overlay');
    const content = document.getElementById('popup-content');
    const body = document.getElementById('popup-body');
    const closeBtn = document.getElementById('popup-close');
    
    let hasShownPopup = false;

    // Helper to check cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Helper to set cookie (expires in 1 day)
    function setCookie(name, value) {
        const d = new Date();
        d.setTime(d.getTime() + (24*60*60*1000));
        document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/`;
    }

    function showPopup(popup) {
        if (hasShownPopup || getCookie(`popup_seen_${popup.id}`)) return;
        
        body.innerHTML = popup.content;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex'); // Ensure flex display for centering
        // Small delay for animation
        setTimeout(() => {
            overlay.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
        
        hasShownPopup = true;
        setCookie(`popup_seen_${popup.id}`, 'true');
    }

    function closePopup() {
        overlay.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => {
            overlay.classList.remove('flex'); // Remove flex
            overlay.classList.add('hidden');
        }, 300);
    }

    closeBtn.addEventListener('click', closePopup);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closePopup();
    });

    // Initialize Triggers
    popups.forEach(popup => {
        if (popup.trigger_type === 'exit') {
            document.addEventListener('mouseleave', (e) => {
                if (e.clientY < 0) showPopup(popup);
            });
        } else if (popup.trigger_type === 'timer') {
            setTimeout(() => showPopup(popup), popup.trigger_value * 1000);
        } else if (popup.trigger_type === 'scroll') {
            window.addEventListener('scroll', () => {
                const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
                if (scrollPercent >= popup.trigger_value) showPopup(popup);
            });
        }
    });
});
</script>
<?php endif; ?>
