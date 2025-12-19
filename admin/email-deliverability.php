<?php
require_once '../config.php';
require_once 'security.php';
require_once '../includes/helpers/email-helper.php';
checkLogin();

$domain = $_SERVER['HTTP_HOST'] ?? 'yourdomain.com';
$domain = str_replace('www.', '', $domain);

// Check current DNS records (simulation - actual DNS check would require external tools)
$smtp_settings = getSMTPSettings();
$sending_domain = '';
if (!empty($smtp_settings['smtp_from_email'])) {
    $parts = explode('@', $smtp_settings['smtp_from_email']);
    $sending_domain = end($parts);
}

include 'includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-white mb-2">📧 Email Deliverability Guide</h1>
    <p class="text-slate-400">Fix spam issues and improve email delivery rates</p>
</div>

<!-- Quick Checklist -->
<div class="bg-gradient-to-r from-blue-500/10 to-purple-500/10 border border-blue-500/20 rounded-xl p-6 mb-8">
    <h2 class="text-2xl font-bold text-white mb-4">🎯 Quick Checklist</h2>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="space-y-2">
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Set up SPF record</span>
            </div>
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Configure DKIM signing</span>
            </div>
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Add DMARC policy</span>
            </div>
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Use valid From address</span>
            </div>
        </div>
        <div class="space-y-2">
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Add unsubscribe link</span>
            </div>
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Avoid spam trigger words</span>
            </div>
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Include physical address</span>
            </div>
            <div class="flex items-start gap-2 text-slate-300">
                <span class="text-green-400">☐</span>
                <span>Test emails before sending</span>
            </div>
        </div>
    </div>
</div>

<!-- Current Setup -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <h2 class="text-xl font-bold text-white mb-4">Current Configuration</h2>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-slate-400 text-sm mb-1">Your Domain</div>
            <div class="text-white font-mono"><?php echo htmlspecialchars($domain); ?></div>
        </div>
        <div class="bg-slate-900 rounded-lg p-4">
            <div class="text-slate-400 text-sm mb-1">Sending Email Domain</div>
            <div class="text-white font-mono"><?php echo htmlspecialchars($sending_domain ?: 'Not configured'); ?></div>
        </div>
    </div>
    
    <?php if (strpos($sending_domain, 'main-hosting.eu') !== false): ?>
    <div class="mt-4 p-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg">
        <p class="text-yellow-400 text-sm font-medium mb-2">⚠️ Important: Using Hosting Provider Email</p>
        <p class="text-slate-300 text-sm">You're using <?php echo htmlspecialchars($sending_domain); ?>. For better deliverability, consider:</p>
        <ul class="list-disc list-inside text-slate-300 text-sm mt-2 ml-2">
            <li>Use your own domain email (e.g., noreply@<?php echo htmlspecialchars($domain); ?>)</li>
            <li>Or use a professional email service (Gmail, Outlook, SendGrid)</li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- SPF Setup -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <div class="flex items-start gap-3 mb-4">
        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <span class="text-2xl">🔐</span>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">1. SPF (Sender Policy Framework)</h2>
            <p class="text-slate-400 text-sm">Prevents email spoofing by specifying which servers can send email from your domain</p>
        </div>
    </div>
    
    <div class="bg-slate-900 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-300 font-medium">Add this TXT record to your DNS:</span>
            <button onclick="copyToClipboard('spf-record')" class="px-3 py-1 bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded text-sm">
                Copy
            </button>
        </div>
        <pre id="spf-record" class="bg-slate-950 p-3 rounded text-sm text-green-400 overflow-auto">v=spf1 include:mail.hostinger.com ~all</pre>
        <p class="text-slate-500 text-xs mt-2">💡 For Hostinger. See provider-specific examples below.</p>
    </div>
    
    <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
        <p class="text-blue-400 text-sm font-medium mb-2">Provider-Specific SPF Records:</p>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between items-center text-slate-300">
                <span><strong>Hostinger:</strong></span>
                <code class="bg-slate-900 px-2 py-1 rounded">include:mail.hostinger.com</code>
            </div>
            <div class="flex justify-between items-center text-slate-300">
                <span><strong>Gmail/Google Workspace:</strong></span>
                <code class="bg-slate-900 px-2 py-1 rounded">include:_spf.google.com</code>
            </div>
            <div class="flex justify-between items-center text-slate-300">
                <span><strong>Outlook/Office 365:</strong></span>
                <code class="bg-slate-900 px-2 py-1 rounded">include:spf.protection.outlook.com</code>
            </div>
            <div class="flex justify-between items-center text-slate-300">
                <span><strong>SendGrid:</strong></span>
                <code class="bg-slate-900 px-2 py-1 rounded">include:sendgrid.net</code>
            </div>
            <div class="flex justify-between items-center text-slate-300">
                <span><strong>Mailgun:</strong></span>
                <code class="bg-slate-900 px-2 py-1 rounded">include:mailgun.org</code>
            </div>
        </div>
    </div>
</div>

<!-- DKIM Setup -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <div class="flex items-start gap-3 mb-4">
        <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <span class="text-2xl">🔑</span>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">2. DKIM (DomainKeys Identified Mail)</h2>
            <p class="text-slate-400 text-sm">Cryptographically signs emails to verify they haven't been tampered with</p>
        </div>
    </div>
    
    <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4 mb-4">
        <p class="text-yellow-400 text-sm">⚠️ DKIM setup varies by email provider. Follow your provider's specific instructions:</p>
    </div>
    
    <div class="space-y-3">
        <details open class="bg-slate-900 rounded-lg border-2 border-blue-500/30">
            <summary class="p-4 cursor-pointer hover:bg-slate-800 transition-colors text-white font-medium">
                ⭐ Hostinger hPanel (Your Current Hosting)
            </summary>
            <div class="p-4 border-t border-white/10 text-sm text-slate-300">
                <div class="bg-green-500/10 border border-green-500/20 rounded p-3 mb-3">
                    <p class="text-green-400 text-sm">✅ Good news: Hostinger supports DKIM on most plans!</p>
                </div>
                <ol class="list-decimal list-inside space-y-2">
                    <li>Log into your <a href="https://hpanel.hostinger.com" target="_blank" class="text-blue-400 hover:underline">Hostinger hPanel</a></li>
                    <li>Go to <strong>Emails</strong> section from the sidebar</li>
                    <li>Create an email account for your domain if you haven't (e.g., noreply@yourdomain.com)</li>
                    <li>Look for <strong>Advanced</strong> or <strong>Email Authentication</strong> settings</li>
                    <li>Find the <strong>DKIM Records</strong> section - Hostinger auto-generates these</li>
                    <li>Copy the DKIM TXT record and add it to your domain's DNS:
                        <ul class="list-disc list-inside ml-4 mt-2">
                            <li><strong>Type:</strong> TXT</li>
                            <li><strong>Name:</strong> default._domainkey (or as shown by Hostinger)</li>
                            <li><strong>Value:</strong> The DKIM key provided</li>
                        </ul>
                    </li>
                    <li>Wait 1-24 hours for DNS propagation</li>
                </ol>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded p-3 mt-3">
                    <p class="text-blue-400 text-sm mb-2"><strong>💡 Can't find DKIM settings?</strong></p>
                    <p class="text-slate-300 text-sm">Contact Hostinger support - they can enable DKIM for your domain or guide you through the process. Most Hostinger plans have this enabled by default.</p>
                </div>
            </div>
        </details>

        <details class="bg-slate-900 rounded-lg">
            <summary class="p-4 cursor-pointer hover:bg-slate-800 transition-colors text-white font-medium">
                Gmail / Google Workspace
            </summary>
            <div class="p-4 border-t border-white/10 text-sm text-slate-300">
                <ol class="list-decimal list-inside space-y-2">
                    <li>Go to Google Admin Console</li>
                    <li>Navigate to Apps → Google Workspace → Gmail → Authenticate email</li>
                    <li>Click "Generate New Record"</li>
                    <li>Copy the TXT record and add it to your DNS</li>
                    <li>Wait 24-48 hours for propagation</li>
                </ol>
                <a href="https://support.google.com/a/answer/174124" target="_blank" class="text-blue-400 hover:underline mt-2 inline-block">
                    View Official Guide →
                </a>
            </div>
        </details>
        
        <details class="bg-slate-900 rounded-lg">
            <summary class="p-4 cursor-pointer hover:bg-slate-800 transition-colors text-white font-medium">
                Outlook / Office 365
            </summary>
            <div class="p-4 border-t border-white/10 text-sm text-slate-300">
                <ol class="list-decimal list-inside space-y-2">
        
        <details class="bg-slate-900 rounded-lg">
            <summary class="p-4 cursor-pointer hover:bg-slate-800 transition-colors text-white font-medium">
                Shared Hosting / cPanel
            </summary>
            <div class="p-4 border-t border-white/10 text-sm text-slate-300">
                <div class="bg-yellow-500/10 border border-yellow-500/20 rounded p-3 mb-3">
                    <p class="text-yellow-400 text-sm">⚠️ Note: Many shared hosts don't support DKIM or it's automatically configured.</p>
                </div>
                <p class="mb-2"><strong>If your host supports DKIM:</strong></p>
                <ol class="list-decimal list-inside space-y-2 mb-3">
                    <li>Log in to cPanel</li>
                    <li>Find "Email Deliverability" or "Email Authentication"</li>
                    <li>Enable DKIM for your domain</li>
                    <li>Copy the DKIM record and add it to your DNS</li>
                </ol>
                <p class="text-yellow-400 text-sm"><strong>Better Option:</strong> Use a dedicated email service (Gmail, SendGrid, Mailgun) for better control and deliverability.</p>
            </div>
        </details>
                    <li>Sign in to Microsoft 365 admin center</li>
                    <li>Go to Setup → Domains</li>
                    <li>Select your domain → DNS records</li>
                    <li>Add DKIM CNAME records provided by Microsoft</li>
                    <li>Enable DKIM signing in Exchange admin center</li>
                </ol>
                <a href="https://learn.microsoft.com/en-us/microsoft-365/security/office-365-security/email-authentication-dkim-configure" target="_blank" class="text-blue-400 hover:underline mt-2 inline-block">
                    View Official Guide →
                </a>
            </div>
        </details>
    </div>
</div>

<!-- MX Record Setup -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <div class="flex items-start gap-3 mb-4">
        <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <span class="text-2xl">📬</span>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">MX Records (Mail Exchange)</h2>
            <p class="text-slate-400 text-sm">Required for receiving emails at your domain</p>
        </div>
    </div>
    
    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-4">
        <p class="text-red-400 text-sm font-medium">⚠️ Missing MX Record Detected</p>
        <p class="text-slate-300 text-sm mt-2">Your sending domain (<?php echo htmlspecialchars($sending_domain ?: 'not configured'); ?>) doesn't have MX records. This affects your sender reputation.</p>
    </div>
    
    <div class="bg-slate-900 rounded-lg p-4 mb-4">
        <p class="text-slate-300 text-sm mb-3"><strong>Fix Options:</strong></p>
        
        <div class="space-y-3">
            <div class="p-3 bg-green-500/10 border border-green-500/20 rounded">
                <p class="text-green-400 font-medium text-sm mb-1">✅ Option 1: Use Your Domain Email (Recommended)</p>
                <p class="text-slate-300 text-sm">Change your "From Email" in <a href="api-settings.php" class="text-blue-400 hover:underline">API Settings</a> to use your actual domain (e.g., noreply@<?php echo htmlspecialchars($domain); ?>)</p>
                <p class="text-slate-400 text-xs mt-1">Then add MX records for <?php echo htmlspecialchars($domain); ?> pointing to your email provider</p>
            </div>
            
            <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded">
                <p class="text-blue-400 font-medium text-sm mb-1">✅ Option 2: Use Gmail/Google Workspace</p>
                <p class="text-slate-300 text-sm">Configure Gmail SMTP and use your Gmail address as From Email</p>
                <p class="text-slate-400 text-xs mt-1">Gmail's MX records are automatically valid</p>
            </div>
        </div>
    </div>
    
    <details class="bg-slate-900 rounded-lg">
        <summary class="p-4 cursor-pointer hover:bg-slate-800 transition-colors text-white font-medium">
            How to Add MX Records
        </summary>
        <div class="p-4 border-t border-white/10 text-sm text-slate-300">
            <p class="mb-2">Add these records to your DNS (if using Gmail):</p>
            <div class="bg-slate-950 rounded p-3 space-y-2 font-mono text-xs">
                <div>Priority: 1 | Value: ASPMX.L.GOOGLE.COM</div>
                <div>Priority: 5 | Value: ALT1.ASPMX.L.GOOGLE.COM</div>
                <div>Priority: 5 | Value: ALT2.ASPMX.L.GOOGLE.COM</div>
                <div>Priority: 10 | Value: ALT3.ASPMX.L.GOOGLE.COM</div>
                <div>Priority: 10 | Value: ALT4.ASPMX.L.GOOGLE.COM</div>
            </div>
        </div>
    </details>
</div>

<!-- DMARC Setup -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <div class="flex items-start gap-3 mb-4">
        <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <span class="text-2xl">🛡️</span>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">3. DMARC (Domain-based Message Authentication)</h2>
            <p class="text-slate-400 text-sm">Tells receiving servers what to do if SPF or DKIM checks fail</p>
        </div>
    </div>
    
    <div class="bg-slate-900 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-300 font-medium">Basic DMARC Policy (Monitoring):</span>
            <button onclick="copyToClipboard('dmarc-record')" class="px-3 py-1 bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded text-sm">
                Copy
            </button>
        </div>
        <pre id="dmarc-record" class="bg-slate-950 p-3 rounded text-sm text-green-400 overflow-auto">v=DMARC1; p=none; rua=mailto:dmarc@<?php echo htmlspecialchars($domain); ?>; pct=100; adkim=s; aspf=s</pre>
        <p class="text-slate-500 text-xs mt-2">💡 Add this as a TXT record for _dmarc.<?php echo htmlspecialchars($domain); ?></p>
    </div>
    
    <div class="bg-green-500/10 border border-green-500/20 rounded-lg p-4">
        <p class="text-green-400 text-sm font-medium mb-2">DMARC Policy Levels:</p>
        <div class="space-y-2 text-sm text-slate-300">
            <div><strong>p=none</strong> - Monitor only (recommended to start)</div>
            <div><strong>p=quarantine</strong> - Send to spam if fails</div>
            <div><strong>p=reject</strong> - Reject if fails (strict)</div>
        </div>
    </div>
</div>

<!-- Content Best Practices -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <h2 class="text-xl font-bold text-white mb-4">📝 Content Best Practices</h2>
    
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-slate-900 rounded-lg p-4">
            <h3 class="font-bold text-green-400 mb-3">✅ DO</h3>
            <ul class="space-y-2 text-sm text-slate-300">
                <li>• Use clear, relevant subject lines</li>
                <li>• Include plain text version</li>
                <li>• Add unsubscribe link</li>
                <li>• Include physical address</li>
                <li>• Use consistent From name/email</li>
                <li>• Balance text and images</li>
                <li>• Personalize emails (use names)</li>
                <li>• Test before sending</li>
            </ul>
        </div>
        
        <div class="bg-slate-900 rounded-lg p-4">
            <h3 class="font-bold text-red-400 mb-3">❌ AVOID</h3>
            <ul class="space-y-2 text-sm text-slate-300">
                <li>• ALL CAPS subject lines</li>
                <li>• Excessive punctuation!!!</li>
                <li>• Spam trigger words (FREE, BUY NOW)</li>
                <li>• Too many links</li>
                <li>• Large attachments</li>
                <li>• Misleading subject lines</li>
                <li>• Image-only emails</li>
                <li>• URL shorteners</li>
            </ul>
        </div>
    </div>
</div>

<!-- Common Spam Trigger Words -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <h2 class="text-xl font-bold text-white mb-4">⚠️ Words That Trigger Spam Filters</h2>
    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4">
        <div class="grid md:grid-cols-4 gap-2 text-sm">
            <div class="space-y-1 text-slate-300">
                <div class="text-red-400 font-medium mb-1">Money</div>
                <div>FREE</div>
                <div>$$$</div>
                <div>Cash bonus</div>
                <div>Prize</div>
            </div>
            <div class="space-y-1 text-slate-300">
                <div class="text-red-400 font-medium mb-1">Urgency</div>
                <div>Act now!</div>
                <div>Limited time</div>
                <div>Urgent</div>
                <div>Don't delete</div>
            </div>
            <div class="space-y-1 text-slate-300">
                <div class="text-red-400 font-medium mb-1">Sales</div>
                <div>Buy now</div>
                <div>Order now</div>
                <div>Click here</div>
                <div>Clearance</div>
            </div>
            <div class="space-y-1 text-slate-300">
                <div class="text-red-400 font-medium mb-1">Other</div>
                <div>100% free</div>
                <div>Guarantee</div>
                <div>No obligation</div>
                <div>Winner</div>
            </div>
        </div>
    </div>
</div>

<!-- Testing Tools -->
<div class="bg-slate-800/50 border border-white/10 rounded-xl p-6 mb-8">
    <h2 class="text-xl font-bold text-white mb-4">🔧 Testing & Verification Tools</h2>
    <div class="grid md:grid-cols-2 gap-4">
        <a href="https://www.mail-tester.com/" target="_blank" class="bg-slate-900 hover:bg-slate-800 rounded-lg p-4 transition-colors block">
            <div class="font-bold text-white mb-1">Mail-Tester</div>
            <div class="text-slate-400 text-sm">Test your email spam score (0-10)</div>
        </a>
        <a href="https://mxtoolbox.com/spf.aspx" target="_blank" class="bg-slate-900 hover:bg-slate-800 rounded-lg p-4 transition-colors block">
            <div class="font-bold text-white mb-1">MXToolbox</div>
            <div class="text-slate-400 text-sm">Check SPF, DKIM, DMARC records</div>
        </a>
        <a href="https://www.learndmarc.com/" target="_blank" class="bg-slate-900 hover:bg-slate-800 rounded-lg p-4 transition-colors block">
            <div class="font-bold text-white mb-1">Learn DMARC</div>
            <div class="text-slate-400 text-sm">DMARC record generator</div>
        </a>
        <a href="https://toolbox.googleapps.com/apps/checkmx/" target="_blank" class="bg-slate-900 hover:bg-slate-800 rounded-lg p-4 transition-colors block">
            <div class="font-bold text-white mb-1">Google Admin Toolbox</div>
            <div class="text-slate-400 text-sm">Check MX records and email setup</div>
        </a>
    </div>
</div>

<!-- Implementation Steps -->
<div class="bg-gradient-to-r from-green-500/10 to-blue-500/10 border border-green-500/20 rounded-xl p-6">
    <h2 class="text-xl font-bold text-white mb-4">🚀 Quick Implementation Steps</h2>
    <ol class="space-y-3 text-slate-300">
        <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">1</span>
            <div>
                <div class="font-medium text-white">Configure SMTP Settings</div>
                <div class="text-sm text-slate-400">Go to <a href="api-settings.php" class="text-blue-400 hover:underline">API Settings</a> and configure your email provider</div>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">2</span>
            <div>
                <div class="font-medium text-white">Add DNS Records</div>
                <div class="text-sm text-slate-400">Log in to your domain registrar (GoDaddy, Namecheap, etc.) and add SPF, DKIM, DMARC records</div>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">3</span>
            <div>
                <div class="font-medium text-white">Wait for DNS Propagation</div>
                <div class="text-sm text-slate-400">DNS changes can take 24-48 hours to fully propagate worldwide</div>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">4</span>
            <div>
                <div class="font-medium text-white">Test Your Setup</div>
                <div class="text-sm text-slate-400">Send test emails to <a href="https://www.mail-tester.com" target="_blank" class="text-blue-400 hover:underline">mail-tester.com</a> to check your spam score</div>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">5</span>
            <div>
                <div class="font-medium text-white">Monitor & Adjust</div>
                <div class="text-sm text-slate-400">Watch bounce rates and adjust content/settings as needed</div>
            </div>
        </li>
    </ol>
</div>

<div class="mt-8 flex gap-4">
    <a href="email-test.php" class="px-6 py-3 bg-primary hover:bg-primary/80 text-white rounded-lg transition-colors">
        Test Email Sending
    </a>
    <a href="api-settings.php" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
        Configure SMTP
    </a>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('bg-green-500/30', 'text-green-400');
        btn.classList.remove('bg-blue-500/20', 'text-blue-400');
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('bg-green-500/30', 'text-green-400');
            btn.classList.add('bg-blue-500/20', 'text-blue-400');
        }, 2000);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
