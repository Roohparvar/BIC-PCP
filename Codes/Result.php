<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = '';

    if (!empty($_POST['sequence'])) {
        $input = $_POST['sequence'];
    }
    elseif (!empty($_FILES['file']['name'])) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $input = file_get_contents($fileTmpPath);
    }

    if (!empty($input)) {
        $lines = explode("\n", $input);

        $currentProtein = null;
        $proteinSequences = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (strpos($line, '>') === 0) {
                $currentProtein = substr($line, 1);
                $proteinSequences[$currentProtein] = '';
            } else {
                if ($currentProtein) {
                    $proteinSequences[$currentProtein] .= $line;
                }
            }
        }

        echo "<h2>Processed Protein Sequences:</h2>";
        foreach ($proteinSequences as $proteinName => $sequence) {
            echo "<h3>$proteinName</h3>";
            echo "<pre>$sequence</pre>";
        }
    } else {
        echo "<h2>No input provided!</h2>";
    }
} else {
    echo "<h2>Invalid request method!</h2>";
}
?>
