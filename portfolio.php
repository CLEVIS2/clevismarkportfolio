<?php

require_once __DIR__ . '/includes/receipt.php';

$printMode = isset($_GET['print']) && $_GET['print'] !== '0';
$websiteLink = clevis_receipt_public_base_url();

$summary = [
    'Creative web developer and graphic designer focused on building responsive, polished, and practical digital experiences.',
    'Comfortable with frontend layout, PHP-based workflows, database-driven features, and branding that looks professional across devices.',
    'Passionate about turning ideas into usable websites, client-ready visuals, and reliable systems that feel clear and modern.',
    'Strengths include attention to detail, visual consistency, problem solving, and delivering work that balances design with function.',
];

$skills = [
    'HTML5 and CSS3',
    'JavaScript',
    'PHP',
    'MySQL',
    'Responsive Web Design',
    'UI Layout and Page Structure',
    'Brand Identity Design',
    'Logo Design',
    'Social Media Creatives',
    'Digital Flyers',
    'Database-Driven Features',
    'PDF and Print-Ready Documents',
];

$education = [
    [
        'institution' => 'Bukhalalire Primary School',
        'course' => 'Primary Education',
        'year' => '2017 to 2019',
    ],
    [
        'institution' => 'Mundika High School',
        'course' => 'Secondary Education / KCSE',
        'year' => '2020 to 2023',
    ],
    [
        'institution' => 'Maseno University',
        'course' => 'Undergraduate Degree Program',
        'year' => '2024 to date',
    ],
];

$projects = [
    [
        'title' => 'Personal Portfolio Website',
        'description' => 'A responsive personal website showcasing services, biography, feedback, and verification tools in a polished public interface.',
        'technologies' => 'PHP, HTML, CSS, JavaScript',
        'role' => 'Designer and full-stack developer',
    ],
    [
        'title' => 'Service Request and Receipt System',
        'description' => 'A request workflow that captures client details, stores submissions, and produces clean receipt PDFs for tracking and follow-up.',
        'technologies' => 'PHP, MySQL, HTML, CSS',
        'role' => 'Developer and system designer',
    ],
    [
        'title' => 'Branding and Creative Campaign Assets',
        'description' => 'Design work for logos, social graphics, and marketing assets that help businesses communicate clearly and look professional.',
        'technologies' => 'Graphic design tools, layout systems, digital publishing',
        'role' => 'Graphic designer and visual communicator',
    ],
];

$experience = [
    [
        'company' => 'Independent / Freelance',
        'role' => 'Web Developer and Graphic Designer',
        'duration' => '2024 to Present',
        'responsibilities' => 'Build responsive websites, create brand assets, support client presentations, and deliver practical design and development work for personal and small-business projects.',
    ],
];

$certifications = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clevis Mark Nyongesa | PDF Portfolio</title>
    <meta name="description" content="Professional PDF portfolio for Clevis Mark Nyongesa.">
    <style>
        :root {
            --paper: #f6f8fb;
            --panel: #ffffff;
            --ink: #111827;
            --muted: #556074;
            --line: #d9e1ec;
            --accent: #123a63;
            --accent-soft: #e9f0f8;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            --radius: 22px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(18, 58, 99, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 24%),
                var(--paper);
        }

        a {
            color: inherit;
        }

        .portfolio-shell {
            width: min(1100px, calc(100% - 32px));
            margin: 20px auto 36px;
        }

        .portfolio-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--panel);
            text-decoration: none;
            font-weight: 700;
            color: var(--accent);
            box-shadow: var(--shadow);
        }

        .portfolio-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .portfolio-hero {
            padding: 34px 34px 28px;
            background: linear-gradient(135deg, var(--accent) 0%, #1f4c7c 52%, #16314f 100%);
            color: #f8fbff;
        }

        .portfolio-eyebrow {
            margin: 0 0 12px;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 0.78rem;
            color: rgba(248, 251, 255, 0.78);
        }

        .portfolio-hero h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.8rem);
            line-height: 1.05;
            max-width: 12ch;
        }

        .portfolio-title {
            margin: 12px 0 0;
            font-size: 1.1rem;
            color: rgba(248, 251, 255, 0.85);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .contact-item {
            padding: 14px 15px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .contact-item span {
            display: block;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(248, 251, 255, 0.65);
            margin-bottom: 6px;
        }

        .contact-item strong {
            font-size: 0.98rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .portfolio-body {
            padding: 30px 34px 36px;
        }

        .section {
            padding: 0 0 26px;
            margin-bottom: 26px;
            border-bottom: 1px solid var(--line);
        }

        .section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: 0;
        }

        .section-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .section-header h2 {
            margin: 0;
            color: var(--accent);
            font-size: 1.18rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .section-header p {
            margin: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .summary {
            margin: 0;
            color: var(--ink);
            line-height: 1.8;
            font-size: 1.03rem;
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .chip {
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--accent-soft);
            border: 1px solid #c9d8ea;
            font-weight: 700;
            color: var(--accent);
            font-size: 0.95rem;
        }

        .education-list,
        .project-grid,
        .experience-list {
            display: grid;
            gap: 16px;
        }

        .education-item,
        .project-item,
        .experience-item {
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px 18px 16px;
            background: #fbfcfe;
        }

        .education-item h3,
        .project-item h3,
        .experience-item h3 {
            margin: 0 0 8px;
            font-size: 1.08rem;
            color: var(--ink);
        }

        .meta-line {
            margin: 0 0 8px;
            color: var(--muted);
            line-height: 1.6;
        }

        .meta-line strong {
            color: var(--accent);
        }

        .project-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .project-item.full {
            grid-column: 1 / -1;
        }

        .project-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .project-tags span {
            padding: 7px 10px;
            border-radius: 999px;
            background: #eef3f8;
            color: #344255;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .footer-note {
            padding: 18px 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, #f1f5fb 0%, #eaf1fa 100%);
            border: 1px solid var(--line);
            color: #2d3a4c;
            line-height: 1.7;
        }

        .footer-note strong {
            color: var(--accent);
        }

        .muted {
            color: var(--muted);
        }

        @media (max-width: 900px) {
            .contact-grid,
            .project-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 720px) {
            .portfolio-shell {
                width: min(100% - 18px, 1100px);
                margin-top: 12px;
            }

            .portfolio-hero,
            .portfolio-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .portfolio-hero h1 {
                max-width: none;
            }
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 14mm;
            }

            body {
                background: #ffffff !important;
            }

            .portfolio-shell {
                width: 100%;
                margin: 0;
            }

            .portfolio-actions {
                display: none !important;
            }

            .portfolio-card {
                box-shadow: none;
            }

            .portfolio-hero {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .section,
            .education-item,
            .project-item,
            .experience-item,
            .footer-note {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="portfolio-shell">
        <?php if (!$printMode): ?>
            <div class="portfolio-actions">
                <a class="action-button" href="portfolio_pdf.php">Download PDF</a>
                <a class="action-button" href="index.php">Back to Site</a>
            </div>
        <?php endif; ?>

        <article class="portfolio-card">
            <header class="portfolio-hero">
                <p class="portfolio-eyebrow">Personal Portfolio</p>
                <h1>Clevis Mark Nyongesa</h1>
                <p class="portfolio-title">Web Developer and Graphic Designer</p>

                <div class="contact-grid">
                    <div class="contact-item">
                        <span>Phone Number</span>
                        <strong>+254743483176</strong>
                    </div>
                    <div class="contact-item">
                        <span>Email Address</span>
                        <strong>nyongesaclevis76@gmail.com</strong>
                    </div>
                    <div class="contact-item">
                        <span>Location</span>
                        <strong>Nairobi, Kenya</strong>
                    </div>
                    <div class="contact-item">
                        <span>Website / Portfolio</span>
                        <strong><?= htmlspecialchars($websiteLink, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="contact-item">
                        <span>Professional Focus</span>
                        <strong>Responsive web systems and clean visual branding</strong>
                    </div>
                    <div class="contact-item">
                        <span>Document Type</span>
                        <strong>PDF-ready portfolio profile</strong>
                    </div>
                </div>
            </header>

            <div class="portfolio-body">
                <section class="section">
                    <div class="section-header">
                        <h2>Profile Summary</h2>
                        <p>Concise overview of strengths and focus</p>
                    </div>
                    <p class="summary">
                        <?= htmlspecialchars(implode(' ', $summary), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </section>

                <section class="section">
                    <div class="section-header">
                        <h2>Skills</h2>
                        <p>Technical and creative capabilities</p>
                    </div>
                    <div class="chips">
                        <?php foreach ($skills as $skill): ?>
                            <span class="chip"><?= htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section">
                    <div class="section-header">
                        <h2>Education</h2>
                        <p>Academic background and current study</p>
                    </div>
                    <div class="education-list">
                        <?php foreach ($education as $item): ?>
                            <article class="education-item">
                                <h3><?= htmlspecialchars($item['institution'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="meta-line"><strong>Course:</strong> <?= htmlspecialchars($item['course'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="meta-line"><strong>Year:</strong> <?= htmlspecialchars($item['year'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section">
                    <div class="section-header">
                        <h2>Projects / Experience</h2>
                        <p>Selected work and practical output</p>
                    </div>
                    <div class="project-grid">
                        <?php foreach ($projects as $index => $project): ?>
                            <article class="project-item<?= $index === 2 ? ' full' : ''; ?>">
                                <h3><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="meta-line"><?= htmlspecialchars($project['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="meta-line"><strong>Role:</strong> <?= htmlspecialchars($project['role'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <div class="project-tags">
                                    <?php foreach (explode(', ', $project['technologies']) as $technology): ?>
                                        <span><?= htmlspecialchars($technology, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section">
                    <div class="section-header">
                        <h2>Work Experience</h2>
                        <p>Current practical engagement</p>
                    </div>
                    <div class="experience-list">
                        <?php foreach ($experience as $item): ?>
                            <article class="experience-item">
                                <h3><?= htmlspecialchars($item['company'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="meta-line"><strong>Role:</strong> <?= htmlspecialchars($item['role'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="meta-line"><strong>Duration:</strong> <?= htmlspecialchars($item['duration'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="meta-line"><strong>Responsibilities:</strong> <?= htmlspecialchars($item['responsibilities'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="section">
                    <div class="section-header">
                        <h2>Certifications</h2>
                        <p>Optional professional credentials</p>
                    </div>
                    <?php if ($certifications !== []): ?>
                        <div class="chips">
                            <?php foreach ($certifications as $certification): ?>
                                <span class="chip"><?= htmlspecialchars($certification, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="muted">No certifications listed at this time.</p>
                    <?php endif; ?>
                </section>

                <section class="section">
                    <div class="section-header">
                        <h2>Contact / Footer</h2>
                        <p>Closing statement</p>
                    </div>
                    <div class="footer-note">
                        <strong>Ready to collaborate.</strong>
                        If you need a responsive website, a polished brand identity, or a modern digital presentation, I am available to help bring the idea to life. Please get in touch to discuss your project and next steps.
                    </div>
                </section>
            </div>
        </article>
    </div>
</body>
</html>
