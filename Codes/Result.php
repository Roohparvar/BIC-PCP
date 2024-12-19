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

        echo "<h2>Processed Protein Sequences:</h2>";
        foreach ($proteinSequences as $proteinName => $sequence) {

            // ......................................... Start part 1 molecular weight calculation
            $aminoAcidWeights = [
                'A' => 89.09,  'R' => 174.20, 'N' => 132.12, 'D' => 133.10,
                'C' => 121.16, 'E' => 147.13, 'Q' => 146.15, 'G' => 75.07,
                'H' => 155.16, 'I' => 131.17, 'L' => 131.17, 'K' => 146.19,
                'M' => 149.21, 'F' => 165.19, 'P' => 115.13, 'S' => 105.09,
                'T' => 119.12, 'W' => 204.23, 'Y' => 181.19, 'V' => 117.15
            ];

            $molecularWeight = 0.0;
            foreach (str_split($sequence) as $aminoAcid) {
                if (isset($aminoAcidWeights[$aminoAcid])) {
                    $molecularWeight += $aminoAcidWeights[$aminoAcid];
                }
            }
            echo "<h3>$proteinName (Molecular Weight: " . number_format($molecularWeight, 2) . " g/mol)</h3>";
            // ......................................... End part 1 molecular weight calculation

            // ......................................... Start part 2 protein length calculation
            $length = strlen($sequence);
            echo "<h3>Length: $length</h3>";
            // ......................................... End part 2 protein length calculation

            // ......................................... Start part 3 Amino Acid Composition
            echo "<h4>Amino Acid Composition:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Amino Acid</th><th>Count</th><th>Percentage</th></tr>";
            $aminoAcidCounts = count_chars($sequence, 1);

            foreach ($aminoAcidCounts as $ascii => $count) {
                $aminoAcid = chr($ascii);
                $percentage = ($count / $length) * 100;
                echo "<tr><td>$aminoAcid</td><td>$count</td><td>" . number_format($percentage, 2) . "%</td></tr>";
            }
            echo "</table><br>";
            // ......................................... End part 3 Amino Acid Composition

            // ......................................... Start part 4 charge calculations
            $aminoAcids = array(
                'D' => 3.8949375, 'E' => 4.2930625,
                'H' => 5.8028125, 'C' => 7.75075,
                'Y' => 9.4375625, 'K' => 10.4536875,
                'R' => 12.0601875
            );

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
            // ......................................... End part 4 charge calculations

            // ......................................... Start part 5 Count positively charged residues
            $positive_residues = array("R", "K", "H");
            $total_positives = 0;

            for ($i = 0; $i < strlen($sequence); $i++) {
                if (in_array($sequence[$i], $positive_residues)) {
                    $total_positives++;
                }
            }

            echo "<h4>Total number of positively charged residues: $total_positives</h4>";
            // ......................................... End part 5 Count positively charged residues
            
            // ......................................... Start part 6 Count negatively charged residues
            $negative_residues = array("D", "E");

            $total_negatives = 0;

            for ($i = 0; $i < strlen($sequence); $i++) {
                if (in_array($sequence[$i], $negative_residues)) {
                    $total_negatives++;
                }
            }

            echo "<h4>Total number of negatively charged residues: $total_negatives</h4>";
            // ......................................... End part 6 Count negatively charged residues
            
            // ......................................... Start part 7 Total Atomic Composition
// Define arrays for each element with the number of atoms in each amino acid
$carbon = array(3, 3, 4, 5, 9, 2, 6, 6, 6, 6, 5, 4, 5, 5, 6, 3, 4, 5, 11, 9);
$hydrogen = array(7, 7, 7, 9, 11, 5, 9, 13, 14, 13, 11, 8, 9, 10, 14, 7, 9, 11, 12, 11);
$nitrogen = array(1, 1, 1, 1, 1, 1, 3, 1, 2, 1, 1, 2, 1, 2, 4, 1, 1, 1, 2, 1);
$oxygen = array(2, 2, 4, 4, 2, 2, 2, 2, 2, 2, 2, 3, 2, 3, 2, 3, 3, 2, 2, 3);
$sulfur = array(0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0);

// Define an array of amino acids
$amino_acids = array("A", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "V", "W", "Y");

// Initialize the counters for each element
$carbon_count = 0;
$hydrogen_count = 0;
$nitrogen_count = 0;
$oxygen_count = 0;
$sulfur_count = 0;

// Loop through the protein sequence and add up the count of each element
for ($i = 0; $i < strlen($sequence); $i++) {
    $amino_acid = $sequence[$i];
    $index = array_search($amino_acid, $amino_acids);
    if ($index !== false) {
        $carbon_count += $carbon[$index];
        $hydrogen_count += $hydrogen[$index];
        $nitrogen_count += $nitrogen[$index];
        $oxygen_count += $oxygen[$index];
        $sulfur_count += $sulfur[$index];
    }
}

// Output the count of each element
echo "<h4>Total Atomic Composition:</h4>";
echo "Carbon: " . $carbon_count . "<br>";
echo "Hydrogen: " . $hydrogen_count . "<br>";
echo "Nitrogen: " . $nitrogen_count . "<br>";
echo "Oxygen: " . $oxygen_count . "<br>";
echo "Sulfur: " . $sulfur_count . "<br>";
// ......................................... End part 7 Total Atomic Composition

// ......................................... Start part 8 Extinction Coefficient
$C = substr_count($sequence, 'C');
$Y = substr_count($sequence, 'Y');
$W = substr_count($sequence, 'W');

if ($C % 2 == 0) {
    $C = $C / 2;
} else {
    $C = $C - 1;
    $C = $C / 2;
}

$extinction_coefficient = ($C * 125) + ($Y * 1490) + ($W * 5500);

if ($C == 0 && $Y == 0 && $W == 0) {
    echo "As there are no Trp, Tyr, or Cys in the region considered, your protein should not be visible by UV spectrophotometry.";
} else {
    echo "<br>The extinction coefficient of the protein sequence '$protein_sequence' is $extinction_coefficient M^-1 cm^-1.<br>";
    $Absorb = $extinction_coefficient / $molecularWeight;
    echo "The Absorb(Prot) of the protein is '$Absorb' assuming all pairs of Cys residues form cystines.";
}
// ......................................... End part 8 Extinction Coefficient

// ......................................... Start part 9 Half-Life

$half_life = array(
    'AM' => '4.4h', 
    'AY' => '>20h',
    'AE' => '>10h',
    'RM' => '1h', 
    'RY' => '2min',
    'RE' => '2min',
    'NM' => '1.4h', 
    'NY' => '3 min',
    'NE' => '>10 h',
    'DM' => '1.1h', 
    'DY' => '3 min',
    'DE' => '>10 h',
    'CM' => '1.2h', 
    'CY' => '>20 h',
    'CE' => '>10 h',
    'QM' => '0.8h', 
    'QY' => '10 min',
    'QE' => '>10 h',
    'EM' => '1h', 
    'EY' => '30 min',
    'EE' => '>10 h',
    'GM' => '30h', 
    'GY' => '>20 h',
    'GE' => '>10 h',
    'HM' => '3.5h', 
    'HY' => '10 min',
    'HE' => '>10 h',
    'IM' => '20 h', 
    'IY' => '30 min',
    'IE' => '>10 h',
    'LM' => '5.5 h', 
    'LY' => '3 min',
    'LE' => '2 min',
    'KM' => '1.3 h', 
    'KY' => '3 min',
    'KE' => '2 min',
    'MM' => '30 h', 
    'MY' => '>20 h',
    'ME' => '>10 h',
    'FM' => '1.1 h', 
    'FY' => '3 min',
    'FE' => '2 min',
    'PM' => '>20 h', 
    'PY' => '>20 h',
    'PE' => '?',
    'SM' => '1.9 h', 
    'SY' => '>20 h',
    'SE' => '>10h',
    'TM' => '7.2 h', 
    'TY' => '>20 h',
    'TE' => '>10h',
    'WM' => '2.8 h', 
    'WY' => '3 min',
    'WE' => '2 min',
    'YM' => '2.8 h', 
    'YY' => '10 min',
    'YE' => '2 min',
    'VM' => '100 h', 
    'VY' => '>20 h',
    'VE' => '>10 h'
);

$M = $sequence[0] . "M";
$Y = $sequence[0] . "Y";
$E = $sequence[0] . "E";

echo "<br><br>The estimated half-life is:<br>";
echo "'$half_life[$M]' (mammalian reticulocytes, in vitro)<br>";
echo "'$half_life[$Y]' (yeast, in vivo)<br>";
echo "'$half_life[$E]' (Escherichia coli, in vivo)<br>";
// ......................................... End part 9 Half-Life

// Start
// ...............................part 10 instability index......................................

$diwv = array(
    'AA' =>  0.62,
    'AR' => -2.53,
    'AN' =>  0.50,
    'AD' =>  3.64,
    'AC' =>  1.07,
    'AQ' =>  0.47,
    'AE' =>  3.63,
    'AG' =>  0.79,
    'AH' => -0.64,
    'AI' =>  1.80,
    'AL' =>  1.53,
    'AK' => -1.23,
    'AM' =>  1.43,
    'AF' =>  0.71,
    'AP' =>  1.23,
    'AS' =>  0.79,
    'AT' =>  0.26,
    'AW' =>  0.37,
    'AY' =>  1.47,
    'AV' =>  1.13
);

// Calculate the instability index
$L = strlen($sequence);
$ii = 0;
for ($i = 0; $i < $L-1; $i++) {
    $diwv_key = substr($seq, $i, 2);
    if (isset($diwv[$diwv_key])) {
        $ii += $diwv[$diwv_key];
    }
}
$ii *= 10 / $L;

echo "<br>Instability Index: " . $ii;
// ENd


            
            // Print separator for clarity
             echo "<h4>_______________________________________________________________________________________</h4>";
        }
    } else {
        echo "<h2>No input provided!</h2>";
    }
} else {
    echo "<h2>Invalid request method!</h2>";
}
?>
