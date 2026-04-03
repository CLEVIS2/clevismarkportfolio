<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$flash = $_SESSION['flash'] ?? null;
$old = $_SESSION['old'] ?? [];
unset($_SESSION['flash'], $_SESSION['old']);

$isAdmin = clevis_is_admin();
$feedbackMessages = [];

$services = [
    [
        'title' => 'Brand Identity Design',
        'description' => 'Logos, business cards, social banners, and polished visual systems that give brands a consistent voice.',
    ],
    [
        'title' => 'Professional Website Development',
        'description' => 'Fast, responsive portfolio sites, business websites, and landing pages with clean frontend engineering.',
    ],
    [
        'title' => 'Creative Campaign Support',
        'description' => 'Social media creatives, digital flyers, and launch assets designed to turn ideas into visible results.',
    ],
];

$highlights = [
    'Bukhalalire Primary School, 2017 to 2019.',
    'Mundika High School, 2020 to 2023.',
    'Maseno University, 2024 to date.',
];

$feedbackLookupCode = clevis_normalize_request_code($_GET['request_code'] ?? '');
$feedbackLookupRequest = null;
$feedbackMessages = [];
$feedbackLookupError = null;
$verifyLookupCode = clevis_normalize_request_code($_GET['verify_code'] ?? '');
$verifyLookupRequest = null;
$verifyLookupError = null;
$verifyLookupFiles = [
    'brief' => [],
    'logo' => [],
    'references' => [],
];
$verifyLookupMatchType = 'unknown';

if ($feedbackLookupCode !== '') {
    if (!$pdo) {
        $feedbackLookupError = $database_error ?? 'Database connection failed.';
    } else {
        $feedbackLookupRequest = clevis_get_request_by_code($pdo, $feedbackLookupCode);

        if ($feedbackLookupRequest) {
            $feedbackMessages = clevis_get_feedback_by_request_code($pdo, $feedbackLookupCode);
        } else {
            $feedbackLookupError = 'No service request was found for that code.';
        }
    }
}

if ($verifyLookupCode !== '') {
    if (!$pdo) {
        $verifyLookupError = $database_error ?? 'Database connection failed.';
    } else {
        $verifyLookupRequest = clevis_get_request_by_verification_code($pdo, $verifyLookupCode);

        if ($verifyLookupRequest) {
            $verifyLookupFiles = clevis_request_uploaded_files($verifyLookupRequest);
            $verifyLookupMatchType = clevis_get_verification_match_type($pdo, $verifyLookupCode);
        } else {
            $verifyLookupError = 'No active receipt was found for that code.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clevis Mark | Graphic Designer &amp; Web Developer</title>
    <meta name="description" content="Professional portfolio of Clevis Mark, a graphic designer and web developer offering branding, website development, and creative support.">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="site-page">
    <div class="page-shell">
        <header class="topbar">
            <a class="brand" href="#home">
                <span class="brand-mark" aria-hidden="true">CM</span>
                <span class="brand-name">Clevis Mark</span>
            </a>
            <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="site-nav">
                <span class="menu-toggle-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>
            <button class="nav-overlay" type="button" aria-label="Close navigation" tabindex="-1"></button>
            <nav class="topnav" id="site-nav">
                <a class="nav-home" href="#home">Home</a>
                <a class="nav-about" href="#about">About</a>
                <a class="nav-services" href="#services">Services</a>
                <a class="nav-portfolio" href="#portfolio">Portfolio</a>
                <a class="nav-pdf" href="portfolio_pdf.php">PDF Portfolio</a>
                <a class="nav-request" href="#request">Request Service</a>
                <a class="nav-feedback" href="#feedback">Feedback</a>
                <a class="nav-verify" href="#verify">Verify Receipt</a>
                <?php if ($isAdmin): ?>
                    <a class="nav-admin" href="admin.php">Admin Dashboard</a>
                    <form class="logout-form" action="auth_action.php" method="post">
                        <input type="hidden" name="action" value="logout">
                        <button class="link-button nav-logout" type="submit">Logout</button>
                    </form>
                <?php endif; ?>
            </nav>
        </header>

        <main>
            <section class="hero" id="home">
                <div class="hero-copy">
                    <p class="eyebrow">Graphic Designer &amp; Web Developer</p>
                    <h1>Building bold digital experiences with clean design and reliable code.</h1>
                    <p class="hero-text">
                        Clevis Mark helps brands look professional, communicate clearly, and convert visitors into clients through strong visuals and practical web solutions.
                    </p>
                    <div class="hero-actions">
                        <a class="button primary" href="#request">Request a Service</a>
                        <a class="button secondary" href="#services">View Services</a>
                    </div>
                    <div class="hero-metrics">
                        <article>
                            <strong>Design</strong>
                            <span>Brand identity, marketing creatives, and layout systems.</span>
                        </article>
                        <article>
                            <strong>Development</strong>
                            <span>Responsive websites with PHP integration and database workflows.</span>
                        </article>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="profile-card">
                        <div class="profile-header">
                            <span>Profile</span>
                            <span class="status-dot">Available for projects</span>
                        </div>
                        <img
                            class="profile-photo"
                            src="assets/images/mar.jpg"
                            alt="Portrait of Clevis Mark"
                            onerror="this.onerror=null;this.src='assets/images/mar.jpg';"
                        >
                        <div class="profile-content">
                            <h2>Clevis Mark</h2>
                            <p>Creative problem-solver delivering professional design systems and modern website experiences.</p>
                        </div>
                    </div>

                    <div class="slideshow-card" aria-label="Changing scenery of trees and houses">
                        <div class="slide active">
                            <img src="assets/images/mar1.jpg" alt="web design picture">
                        </div>
                        <div class="slide">
                            <img src="assets/images/mar2.jpg" alt="graphic design picture">
                        </div>
                        <div class="slide">
                            <img src="assets/images/mar3.jpg" alt="computer picture">
                        </div>
                        <div class="slide-controls" aria-hidden="true">
                            <span class="dot active"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                    </div>
                </div>
            </section>

                <section class="section about" id="about">
                    <div class="section-heading">
                        <p class="eyebrow">About</p>
                        <h2>About Me</h2>
                    </div>
                    <div class="about-grid">
                        <div class="about-panel">
                            <p>
                                I studied at Bukhalalire Primary School from 2017 to 2019, then joined Mundika High School in 2020 and completed my studies in 2023. I passed KCSE and joined Maseno University in 2024, where I am currently pursuing my degree.
                            </p>
                        </div>
                        <div class="about-list">
                            <?php foreach ($highlights as $highlight): ?>
                                <article>
                                    <span></span>
                                    <p><?= htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8'); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="section services" id="services">
                    <div class="section-heading">
                        <p class="eyebrow">Services</p>
                        <h2>Solutions tailored for personal brands, startups, and businesses.</h2>
                    </div>
                    <div class="card-grid">
                        <?php foreach ($services as $service): ?>
                            <article class="service-card">
                                <h3><?= htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section portfolio" id="portfolio">
                    <div class="section-heading">
                        <p class="eyebrow">Portfolio Focus</p>
                        <h2>Work designed to feel sharp, modern, and client-ready.</h2>
                    </div>
                    <div class="portfolio-grid">
                        <article class="portfolio-card accent">
                            <span>Brand Systems</span>
                            <h3>Logos and identity packages for clear recognition.</h3>
                        </article>
                        <article class="portfolio-card dark">
                            <span>Responsive Web Design</span>
                            <h3>Business websites that look strong on mobile and desktop.</h3>
                        </article>
                        <article class="portfolio-card light">
                            <span>Launch Materials</span>
                            <h3>Digital posters, banners, and social creatives for campaigns.</h3>
                        </article>
                    </div>
                </section>

                <section class="section request" id="request">
                    <div class="section-heading">
                        <p class="eyebrow">Request Service</p>
                        <h2>Send a project brief directly from the portfolio.</h2>
                    </div>

                    <?php if ($flash): ?>
                        <div class="flash <?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($database_error): ?>
                        <div class="flash info">
                            <?= htmlspecialchars($database_error, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <div class="request-layout">
                        <div class="request-copy">
                            <h3>Start a new project</h3>
                            <p>Use the form to request branding, graphics, web design, or website development support. Submitted requests are stored in the connected MySQL database.</p>
                            <ul>
                                <li>Professional response workflow</li>
                                <li>Organized database storage</li>
                                <li>Clear service selection and project details</li>
                            </ul>
                        </div>

                        <form class="request-form" action="submit_request.php" method="post" enctype="multipart/form-data">
                            <label>
                                Client Name
                                <input type="text" name="client_name" placeholder="Your full name" value="<?= htmlspecialchars($old['client_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </label>

                            <label>
                                Email Address
                                <input type="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </label>

                            <label>
                                Phone Number
                                <input type="text" name="phone" placeholder="+254..." value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </label>

                            <label>
                                Service Needed
                                <select name="service_type" required>
                                    <option value="">Select a service</option>
                                    <?php
                                    $options = [
                                        'Graphic Design',
                                        'Logo & Branding',
                                        'Web Design',
                                        'Website Development',
                                        'Creative Campaign Support',
                                    ];
                                    foreach ($options as $option):
                                        $selected = ($old['service_type'] ?? '') === $option ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                Budget Range
                                <input type="text" name="budget_range" placeholder="Example: KES 20,000 - 50,000" value="<?= htmlspecialchars($old['budget_range'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </label>

                            <label>
                                Preferred Contact
                                <select name="preferred_contact">
                                    <?php
                                    $contacts = ['Email', 'Phone', 'WhatsApp'];
                                    foreach ($contacts as $contact):
                                        $selected = ($old['preferred_contact'] ?? 'Email') === $contact ? 'selected' : '';
                                    ?>
                                        <option value="<?= htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>><?= htmlspecialchars($contact, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>
                                Project Details
                                <textarea name="project_details" rows="6" placeholder="Describe the kind of service you need, timeline, and goals." required><?= htmlspecialchars($old['project_details'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </label>

                            <label>
                                Brief / Project File
                                <input type="file" name="brief_file" accept=".pdf,.doc,.docx,.txt,.rtf,.png,.jpg,.jpeg,.webp">
                            </label>

                            <label>
                                Logo File
                                <input type="file" name="logo_file" accept=".pdf,.png,.jpg,.jpeg,.webp,.svg">
                            </label>

                            <label>
                                Reference Files
                                <input type="file" name="reference_files[]" multiple accept=".pdf,.png,.jpg,.jpeg,.webp,.svg,.zip,.rar,.doc,.docx">
                            </label>

                            <button class="button primary full" type="submit">Send Request</button>
                        </form>
                    </div>
                </section>

                <section class="section feedback" id="feedback">
                    <div class="section-heading">
                        <p class="eyebrow">Feedback</p>
                        <h2>Enter your request code to view feedback.</h2>
                    </div>

                    <div class="feedback-lookup">
                        <form class="feedback-lookup-form" action="index.php#feedback" method="get">
                            <label>
                                Request Code
                                <input type="text" name="request_code" placeholder="Example: JOHNDOE-ABC123" value="<?= htmlspecialchars($feedbackLookupCode, ENT_QUOTES, 'UTF-8'); ?>">
                            </label>
                            <button class="button primary" type="submit">View Feedback</button>
                        </form>

                        <?php if ($feedbackLookupError): ?>
                            <div class="flash error">
                                <?= htmlspecialchars($feedbackLookupError, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php elseif ($feedbackLookupRequest): ?>
                            <article class="feedback-card">
                                <div class="feedback-card-head">
                                    <strong><?= htmlspecialchars($feedbackLookupRequest['client_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="request-code-pill"><?= htmlspecialchars($feedbackLookupRequest['request_code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <p><strong>Service:</strong> <?= htmlspecialchars($feedbackLookupRequest['service_type'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="feedback-meta">
                                    Use this code to return here and check for new replies.
                                </p>
                            </article>
                        <?php endif; ?>
                    </div>

                    <div class="feedback-list">
                        <?php if ($feedbackMessages !== []): ?>
                            <?php foreach ($feedbackMessages as $feedback): ?>
                                <article class="feedback-card">
                                    <div class="feedback-card-head">
                                        <strong><?= htmlspecialchars($feedback['service_type'] ?? 'General', ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?= htmlspecialchars(clevis_format_east_africa_datetime($feedback['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($feedback['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    <p class="feedback-meta">
                                        From <?= htmlspecialchars($feedback['admin_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($feedback['request_code'])): ?>
                                            | Code <?= htmlspecialchars($feedback['request_code'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        <?php elseif ($feedbackLookupCode !== '' && !$feedbackLookupError): ?>
                            <article class="feedback-card feedback-empty">
                                <p>No feedback has been sent for this request yet. Please check back later.</p>
                            </article>
                        <?php else: ?>
                            <article class="feedback-card feedback-empty">
                                <p>Enter the request code from your submission confirmation to see the admin reply.</p>
                            </article>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="section verify" id="verify">
                    <div class="section-heading">
                        <p class="eyebrow">Receipt Verification</p>
                        <h2>Check a forwarded receipt code.</h2>
                    </div>

                    <div class="feedback-lookup">
                        <form class="feedback-lookup-form verify-form" action="index.php#verify" method="get">
                            <label>
                                Receipt or Request Code
                                <input type="text" name="verify_code" placeholder="Example: JOHNDOE-ABC123 or RCPT-20260327-00008" value="<?= htmlspecialchars($verifyLookupCode, ENT_QUOTES, 'UTF-8'); ?>">
                            </label>
                            <button class="button primary" type="submit">Verify Receipt</button>
                        </form>

                        <?php if ($verifyLookupError): ?>
                            <div class="flash error">
                                <?= htmlspecialchars($verifyLookupError, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php elseif ($verifyLookupRequest): ?>
                            <?php
                                $verifyStatus = clevis_normalize_request_status((string)($verifyLookupRequest['request_status'] ?? 'New'));
                                $verifyStatusSlug = strtolower(str_replace(' ', '-', $verifyStatus));
                            ?>
                            <article class="verify-result">
                                <div class="verify-result-head">
                                    <div>
                                        <p class="eyebrow">Verified Receipt</p>
                                        <h2><?= htmlspecialchars($verifyLookupRequest['client_name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <p class="verify-match-note">
                                            <?php if ($verifyLookupMatchType === 'receipt_number'): ?>
                                                Matched by receipt number
                                            <?php elseif ($verifyLookupMatchType === 'request_code'): ?>
                                                Matched by request code
                                            <?php else: ?>
                                                Matched by receipt data
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="status-pill status-<?= htmlspecialchars($verifyStatusSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($verifyStatus, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>

                                <div class="verify-grid">
                                    <div>
                                        <p><strong>Receipt No.</strong> <?= htmlspecialchars(clevis_receipt_number($verifyLookupRequest), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Request Code.</strong> <?= htmlspecialchars($verifyLookupCode, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Issued.</strong> <?= htmlspecialchars(clevis_format_east_africa_datetime($verifyLookupRequest['created_at']), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Service.</strong> <?= htmlspecialchars($verifyLookupRequest['service_type'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <div>
                                        <p><strong>Email.</strong> <?= htmlspecialchars($verifyLookupRequest['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Phone.</strong> <?= htmlspecialchars($verifyLookupRequest['phone'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Status.</strong> <?= htmlspecialchars($verifyStatus, ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><strong>Budget.</strong> <?= htmlspecialchars($verifyLookupRequest['budget_range'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>

                                <?php if ($verifyLookupFiles['brief'] !== [] || $verifyLookupFiles['logo'] !== [] || $verifyLookupFiles['references'] !== []): ?>
                                    <section class="receipt-attachments verify-attachments">
                                        <h3>Uploaded Files</h3>
                                        <div class="receipt-attachment-list">
                                            <?php if ($verifyLookupFiles['brief'] !== []): ?>
                                                <div class="receipt-attachment-group">
                                                    <span>Brief</span>
                                                    <?php foreach ($verifyLookupFiles['brief'] as $file): ?>
                                                        <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                            <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($verifyLookupFiles['logo'] !== []): ?>
                                                <div class="receipt-attachment-group">
                                                    <span>Logo</span>
                                                    <?php foreach ($verifyLookupFiles['logo'] as $file): ?>
                                                        <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                            <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($verifyLookupFiles['references'] !== []): ?>
                                                <div class="receipt-attachment-group">
                                                    <span>References</span>
                                                    <?php foreach ($verifyLookupFiles['references'] as $file): ?>
                                                        <a href="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                            <?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>

                                <div class="verify-actions">
                                    <a class="button primary" href="receipt.php?request_code=<?= urlencode($verifyLookupCode); ?>">Open Receipt</a>
                                    <a class="button secondary" href="receipt_pdf.php?request_code=<?= urlencode($verifyLookupCode); ?>">Download PDF</a>
                                </div>
                            </article>
                        <?php else: ?>
                            <article class="feedback-card feedback-empty">
                                <p>Enter a request code to verify the receipt and view the current status.</p>
                            </article>
                        <?php endif; ?>
                    </div>
                </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
