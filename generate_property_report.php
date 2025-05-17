<?php
include 'auth_session.php';
include 'db_connection.php';

header('Content-Type: application/json');

// Ensure admin role
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$category = $_GET['category'] ?? '';
$valid_categories = ['listed', 'reserved', 'booked'];
if (!in_array($category, $valid_categories)) {
    echo json_encode(['error' => 'Invalid category']);
    exit;
}

// Fetch properties based on category
$title = '';
$query = '';
if ($category === 'listed') {
    $title = 'Listed Properties Report';
    $query = "SELECT p.property_id, p.title, p.location, p.status, u.full_name as owner_name 
              FROM properties p JOIN users u ON p.owner_id = u.user_id 
              WHERE p.status IN ('available', 'pending') 
              ORDER BY p.created_at DESC";
} elseif ($category === 'reserved') {
    $title = 'Reserved Properties Report';
    $query = "SELECT p.property_id, p.title, p.location, p.status, u.full_name as owner_name, b.booking_id, b.created_at as booking_date 
              FROM properties p 
              JOIN users u ON p.owner_id = u.user_id 
              JOIN bookings b ON p.property_id = b.property_id 
              WHERE p.status = 'reserved' AND b.status = 'pending' 
              ORDER BY b.created_at DESC";
} elseif ($category === 'booked') {
    $title = 'Booked Properties Report';
    $query = "SELECT p.property_id, p.title, p.location, p.status, u.full_name as owner_name, l.lease_id, l.start_date 
              FROM properties p 
              JOIN users u ON p.owner_id = u.user_id 
              JOIN leases l ON p.property_id = l.property_id 
              WHERE p.status = 'rented' AND l.status = 'active' 
              ORDER BY l.start_date DESC";
}

$stmt = $conn->prepare($query);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Generate LaTeX content
$latex_content = <<<EOD
\documentclass[a4paper,12pt]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage{lmodern}
\usepackage{geometry}
\geometry{margin=1in}
\usepackage{booktabs}
\usepackage{longtable}
\usepackage{pdflscape}
\usepackage{hyperref}
\hypersetup{colorlinks=true,linkcolor=blue,citecolor=blue,filecolor=blue,urlcolor=blue}
\usepackage{fancyhdr}
\pagestyle{fancy}
\fancyhf{}
\fancyhead[L]{\textbf{NEXUS Property Management}}
\fancyhead[R]{\today}
\fancyfoot[C]{\thepage}
\renewcommand{\headrulewidth}{0.4pt}
\renewcommand{\footrulewidth}{0.4pt}
\title{$title}
\author{NEXUS Admin}
\date{\today}

\begin{document}

\maketitle
\begin{abstract}
This report provides a detailed overview of $category properties managed by NEXUS as of \today. It includes property details such as title, location, status, and owner information.
\end{abstract}

\section*{Property Details}
\begin{longtable}{p{1cm} p{4cm} p{4cm} p{2cm} p{3cm}}
\toprule
\textbf{ID} & \textbf{Title} & \textbf{Location} & \textbf{Status} & \textbf{Owner} \\
\midrule
\endhead
EOD;

foreach ($properties as $property) {
    $id = htmlspecialchars($property['property_id']);
    $title = htmlspecialchars($property['title']);
    $location = htmlspecialchars($property['location']);
    $status = htmlspecialchars($property['status']);
    $owner = htmlspecialchars($property['owner_name']);
    $latex_content .= "\\hline\n$id & $title & $location & $status & $owner \\\\\n";
}

$latex_content .= <<<EOD
\bottomrule
\end{longtable}

\end{document}
EOD;

// Save LaTeX file
$filename = "report_$category_" . date('YmdHis') . ".tex";
file_put_contents($filename, $latex_content);

// Compile LaTeX to PDF
$command = "latexmk -pdf -interaction=nonstopmode " . escapeshellarg($filename);
exec($command . " 2>&1", $output, $return_var);

if ($return_var !== 0) {
    echo json_encode(['error' => 'Failed to generate PDF']);
    exit;
}

// Serve PDF
$pdf_file = str_replace('.tex', '.pdf', $filename);
if (file_exists($pdf_file)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($pdf_file) . '"');
    readfile($pdf_file);
    
    // Clean up
    unlink($pdf_file);
    unlink($filename);
    foreach (glob(str_replace('.tex', '.*', $filename)) as $aux_file) {
        if (file_exists($aux_file)) unlink($aux_file);
    }
    exit;
} else {
    echo json_encode(['error' => 'PDF not found']);
}
?>