<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = '';

    if (!empty($_POST['sequence'])) {
        $input = $_POST['sequence'];
    } elseif (!empty($_FILES['file']['name'])) {
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


        $aminoAcidWeights = [
            'A' => 89.09,  'R' => 174.20, 'N' => 132.12, 'D' => 133.10,
            'C' => 121.15, 'E' => 147.13, 'Q' => 146.15, 'G' => 75.07,
            'H' => 155.16, 'I' => 131.17, 'L' => 131.17, 'K' => 146.19,
            'M' => 149.21, 'F' => 165.19, 'P' => 115.13, 'S' => 105.09,
            'T' => 119.12, 'W' => 204.23, 'Y' => 181.19, 'V' => 117.15
        ];

        $aminoAcids = array(
            'D' => 3.8949375, 'E' => 4.2930625,
            'H' => 5.8028125, 'C' => 7.75075,
            'Y' => 9.4375625, 'K' => 10.4536875,
            'R' => 12.0601875
        );

        echo "<h2>Processed Protein Sequences:</h2>";
        foreach ($proteinSequences as $proteinName => $sequence) {
            $length = strlen($sequence);


            $molecularWeight = 0.0;
            foreach (str_split($sequence) as $aminoAcid) {
                if (isset($aminoAcidWeights[$aminoAcid])) {
                    $molecularWeight += $aminoAcidWeights[$aminoAcid];
                }
            }

            echo "<h3>$proteinName (Length: $length, Molecular Weight: " . number_format($molecularWeight, 2) . " g/mol)</h3>";
            echo "<pre>$sequence</pre>";


            $aminoAcidCounts = count_chars($sequence, 1);

            echo "<h4>Amino Acid Composition:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Amino Acid</th><th>Count</th><th>Percentage</th></tr>";

            foreach ($aminoAcidCounts as $ascii => $count) {
                $aminoAcid = chr($ascii);
                $percentage = ($count / $length) * 100;
                echo "<tr><td>$aminoAcid</td><td>$count</td><td>" . number_format($percentage, 2) . "%</td></tr>";
            }

            echo "</table><br>";


            $charge = array();


            for ($pH = 0; $pH <= 14; $pH++) {
                $charge[$pH] = 0;

                for ($i = 0; $i < strlen($sequence); $i++) {
                    $aminoAcid = $sequence[$i];
                    if (isset($aminoAcids[$aminoAcid])) {
                        if ($aminoAcid == "D" || $aminoAcid == "E") {
                            $pK = $aminoAcids[$aminoAcid];
                            $charge[$pH] += (1 / (1 + pow(10, $pK - $pH)));
                        } else {
                            $pK = $aminoAcids[$aminoAcid];
                            $charge[$pH] += (1 / (1 + pow(10, $pH - $pK)));
                        }
                    }
                }
            }

            $minDiff = 999999;
            $pI = 0;
            for ($pH = 0; $pH <= 14; $pH++) {
                if (abs($charge[$pH]) < $minDiff) {
                    $minDiff = abs($charge[$pH]);
                    $pI = $pH;
                }
            }

            echo "<h4>Theoretical pI of $proteinName: " . number_format($pI, 2) . "</h4><br>";
        }
    } else {
        echo "<h2>No input provided!</h2>";
    }
} else {
    echo "<h2>Invalid request method!</h2>";
}
?>
