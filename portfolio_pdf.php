<?php

require_once __DIR__ . '/includes/receipt.php';

$websiteLink = clevis_receipt_public_base_url();
$fileName = 'Clevis_Mark_Nyongesa_Portfolio.pdf';

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
    ['institution' => 'Bukhalalire Primary School', 'course' => 'Primary Education', 'year' => '2017 to 2019'],
    ['institution' => 'Mundika High School', 'course' => 'Secondary Education / KCSE', 'year' => '2020 to 2023'],
    ['institution' => 'Maseno University', 'course' => 'Undergraduate Degree Program', 'year' => '2024 to date'],
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

function clevis_portfolio_pdf_header(string $title, string $subtitle, string $websiteLink): string
{
    $stream = '';
    $stream .= clevis_pdf_rect(24, 24, 547, 794, [0.985, 0.989, 0.994], [0.86, 0.88, 0.91], 1.0);
    $stream .= clevis_pdf_rect(24, 24, 547, 126, [0.09, 0.23, 0.39], [0.09, 0.23, 0.39], 1.0);
    $stream .= clevis_pdf_circle(74, 78, 26, [0.98, 0.99, 1.0], [0.98, 0.99, 1.0], 1.0);
    $stream .= clevis_pdf_text(57, 70, 'CM', 'F4', 18, 0.09, 0.23, 0.39);
    $stream .= clevis_pdf_text(116, 60, $title, 'F4', 28, 1.0, 1.0, 1.0);
    $stream .= clevis_pdf_text(116, 92, $subtitle, 'F1', 13, 0.85, 0.91, 0.97);
    $stream .= clevis_pdf_text(390, 60, 'Website / Portfolio', 'F1', 10, 0.82, 0.89, 0.97);
    $stream .= clevis_pdf_text(390, 78, $websiteLink, 'F4', 11, 1.0, 1.0, 1.0);

    return $stream;
}

function clevis_portfolio_pdf_section_title(string $title, string $note, float $top): string
{
    $stream = '';
    $stream .= clevis_pdf_text(40, $top, $title, 'F4', 15, 0.09, 0.23, 0.39);
    $stream .= clevis_pdf_text(40, $top + 16, $note, 'F1', 9.5, 0.44, 0.49, 0.57);
    $stream .= clevis_pdf_line(40, $top + 24, 555, $top + 24, [0.83, 0.86, 0.90], 0.8);

    return $stream;
}

function clevis_portfolio_pdf_labeled_line(string $label, string $value, float $top, float $x = 40): string
{
    return clevis_pdf_text($x, $top, $label . ': ' . $value, 'F1', 11.5, 0.18, 0.20, 0.24);
}

function clevis_portfolio_pdf_bullets(array $items, float $top, float $x = 40, int $limit = 86, float $lineGap = 14.0): string
{
    $stream = '';
    $cursor = $top;

    foreach ($items as $item) {
        $lines = clevis_pdf_wrap_text((string)$item, $limit);
        $bulletLines = [];

        foreach ($lines as $index => $line) {
            $bulletLines[] = ($index === 0 ? '• ' : '  ') . $line;
        }

        $stream .= clevis_pdf_multiline_text($x, $cursor, $bulletLines, 'F1', 11.2, 13.0, 0.18, 0.20, 0.24);
        $cursor += (count($bulletLines) * 13.0) + 4.0;
    }

    return $stream;
}

function clevis_portfolio_pdf_card(string $title, string $body, string $meta, string $technologies, float $top, float $height): string
{
    $stream = '';
    $stream .= clevis_pdf_rect(40, $top, 515, $height, [1.0, 1.0, 1.0], [0.85, 0.88, 0.91], 1.0);
    $stream .= clevis_pdf_text(56, $top + 20, $title, 'F4', 14, 0.09, 0.23, 0.39);

    $bodyLines = clevis_pdf_wrap_text($body, 74);
    $stream .= clevis_pdf_multiline_text(56, $top + 40, $bodyLines, 'F1', 10.8, 13.0, 0.22, 0.25, 0.30);

    $metaTop = $top + 40 + (count($bodyLines) * 13.0) + 10.0;
    $stream .= clevis_pdf_text(56, $metaTop, 'Role: ' . $meta, 'F1', 10.6, 0.32, 0.36, 0.42);
    $stream .= clevis_pdf_text(56, $metaTop + 16, 'Technologies: ' . $technologies, 'F1', 10.6, 0.32, 0.36, 0.42);

    return $stream;
}

$pageOne = '';
$pageOne .= clevis_portfolio_pdf_header('Clevis Mark Nyongesa', 'Web Developer and Graphic Designer', $websiteLink);

$pageOne .= clevis_portfolio_pdf_section_title('Profile Summary', 'Concise professional overview', 168);
$summaryLines = clevis_pdf_wrap_text(implode(' ', $summary), 94);
$pageOne .= clevis_pdf_multiline_text(40, 200, $summaryLines, 'F1', 11.4, 14.5, 0.18, 0.20, 0.24);

$pageOne .= clevis_portfolio_pdf_section_title('Skills', 'Core technical and creative capabilities', 290);
$skillsTop = 324;
$leftSkills = array_slice($skills, 0, 6);
$rightSkills = array_slice($skills, 6);
$pageOne .= clevis_portfolio_pdf_bullets($leftSkills, $skillsTop, 46, 42, 14.0);
$pageOne .= clevis_portfolio_pdf_bullets($rightSkills, $skillsTop, 298, 42, 14.0);

$pageOne .= clevis_portfolio_pdf_section_title('Education', 'Academic background and study progress', 430);
$eduTop = 464;
foreach ($education as $item) {
    $pageOne .= clevis_pdf_rect(40, $eduTop, 515, 42, [0.99, 0.99, 0.99], [0.88, 0.90, 0.93], 0.9);
    $pageOne .= clevis_pdf_text(54, $eduTop + 14, $item['institution'], 'F4', 12.5, 0.09, 0.23, 0.39);
    $pageOne .= clevis_pdf_text(54, $eduTop + 28, $item['course'] . ' | ' . $item['year'], 'F1', 10.3, 0.31, 0.35, 0.41);
    $eduTop += 52;
}

$pageTwo = '';
$pageTwo .= clevis_portfolio_pdf_header('Clevis Mark Nyongesa', 'Web Developer and Graphic Designer', $websiteLink);

$pageTwo .= clevis_portfolio_pdf_section_title('Projects / Experience', 'Selected work and practical output', 168);
$projectTop = 202;
foreach ($projects as $index => $project) {
    $height = $index === 2 ? 126 : 136;
    $pageTwo .= clevis_portfolio_pdf_card(
        $project['title'],
        $project['description'],
        $project['role'],
        $project['technologies'],
        $projectTop,
        $height
    );
    $projectTop += $height + 14;
}

$pageTwo .= clevis_portfolio_pdf_section_title('Work Experience', 'Current practical engagement', 640);
$expTop = 674;
foreach ($experience as $item) {
    $pageTwo .= clevis_pdf_rect(40, $expTop, 515, 92, [1.0, 1.0, 1.0], [0.85, 0.88, 0.91], 1.0);
    $pageTwo .= clevis_pdf_text(56, $expTop + 18, $item['company'], 'F4', 13.5, 0.09, 0.23, 0.39);
    $pageTwo .= clevis_pdf_text(56, $expTop + 36, 'Role: ' . $item['role'], 'F1', 10.6, 0.22, 0.25, 0.30);
    $pageTwo .= clevis_pdf_text(56, $expTop + 52, 'Duration: ' . $item['duration'], 'F1', 10.6, 0.22, 0.25, 0.30);
    $responsibilityLines = clevis_pdf_wrap_text($item['responsibilities'], 72);
    $pageTwo .= clevis_pdf_multiline_text(56, $expTop + 68, $responsibilityLines, 'F1', 10.6, 12.5, 0.22, 0.25, 0.30);
}

$pageTwo .= clevis_pdf_text(40, 780, 'Certifications', 'F4', 15, 0.09, 0.23, 0.39);
if ($certifications !== []) {
    $pageTwo .= clevis_portfolio_pdf_bullets($certifications, 804, 46, 88, 12.5);
} else {
    $pageTwo .= clevis_pdf_text(40, 806, 'No certifications listed at this time.', 'F1', 11.2, 0.22, 0.25, 0.30);
}

$pageTwo .= clevis_pdf_rect(40, 800, 515, 22, [0.92, 0.95, 0.98], [0.86, 0.89, 0.92], 0.8);
$pageTwo .= clevis_pdf_text(54, 814, 'Ready to collaborate on responsive websites, visual branding, and practical digital solutions.', 'F1', 10.2, 0.14, 0.18, 0.22);

$pdf = clevis_pdf_stream([$pageOne, $pageTwo]);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
