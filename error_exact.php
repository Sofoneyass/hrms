<?php
// Enable all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

function showExactError($error, $query = null) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[1] ?? $backtrace[0];
    
    echo "<div style='background:#fff0f0; border:1px solid red; padding:15px; font-family:monospace;'>";
    
    // Error Origin
    echo "<h3 style='margin-top:0; color:#d00;'>";
    echo "💥 <u>" . basename($caller['file']) . "</u> on line <u>{$caller['line']}</u>";
    echo "</h3>";
    
    // Error Details
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error->getMessage()) . "</p>";
    
    // Query (if provided)
    if ($query) {
        echo "<p><strong>Query:</strong> <code>" . htmlspecialchars($query) . "</code></p>";
    }
    
    // Code Snippet
    echo "<p><strong>Code Context:</strong></p>";
    $lines = file($caller['file']);
    $start = max(0, $caller['line'] - 3);
    $snippet = array_slice($lines, $start, 5);
    echo "<pre style='background:#f8f8f8; padding:10px;'>";
    foreach ($snippet as $i => $line) {
        $lineNum = $start + $i + 1;
        $highlight = ($lineNum == $caller['line']) ? "background:#ffeb3b;" : "";
        echo "<span style='{$highlight}'>Line {$lineNum}: " . htmlspecialchars($line) . "</span>";
    }
    echo "</pre>";
    
    echo "</div>";
}

// Usage Example:
try {
    require 'db_connection.php';
    $query = "SELECT * FROM non_existent_table";
    $result = $conn->query($query);
    if (!$result) throw new Exception($conn->error);
} catch (Exception $e) {
    showExactError($e, $query);
    die();
}
?>