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
'WW' => 1.0,
'WC' => 1.0,
'WM' => 24.68,
'WH' => 24.68,
'WY' => 1.0,
'WF' => 1,
'WQ' => 1,
'WN' => 13.34,
'WI' => 1,
'WR' => 1,
'WD' => 1,
'WP' => 1,
'WT' => -14.03,
'WK' => 1.0,
'WE' => 1.0,
'WV' => -7.49,
'WS' => 1.0,
'WG' => -9.37,
'WA' => -14.03,
'WL' => 13.34,
'CW' => 24.68,
'CC' => 1.0,
'CM' => 33.6,
'CH' => 33.6,
'CY' => 1.0,
'CF' => 1,
'CQ' => -6.54,
'CN' => 1,
'CI' => 1,
'CR' => 1,
'CD' => 20.26,
'CP' => 20.26,
'CT' => 33.6,
'CK' => 1.0,
'CE' => 1.0,
'CV' => -6.54,
'CS' => 1.0,
'CG' => 1.0,
'CA' => 1,
'CL' => 20.26,
'MW' => 1.0,
'MC' => 1.0,
'MM' => -1.88,
'MH' => 58.28,
'MY' => 24.68,
'MF' => 1,
'MQ' => -6.54,
'MN' => 1,
'MI' => 1,
'MR' => -6.54,
'MD' => 1,
'MP' => 44.94,
'MT' => -1.88,
'MK' => 1.0,
'ME' => 1.0,
'MV' => 1.0,
'MS' => 44.94,
'MG' => 1.0,
'MA' => 13.34,
'ML' => 1.0,
'HW' => -1.88,
'HC' => 1.0,
'HM' => 1.0,
'HH' => 1.0,
'HY' => 44.94,
'HF' => -9.37,
'HQ' => 1,
'HN' => 24.68,
'HI' => 44.94,
'HR' => 1,
'HD' => 1,
'HP' => -1.88,
'HT' => -6.54,
'HK' => 24.68,
'HE' => 1.0,
'HV' => 1.0,
'HS' => 1.0,
'HG' => -9.37,
'HA' => 1,
'HL' => 1.0,
'YW' => -9.37,
'YC' => 1.0,
'YM' => 44.94,
'YH' => 13.34,
'YY' => 13.34,
'YF' => 1,
'YQ' => 1,
'YN' => 1,
'YI' => 1,
'YR' => -15.91,
'YD' => 24.68,
'YP' => 13.34,
'YT' => -7.49,
'YK' => 1.0,
'YE' => 6.54,
'YV' => 1.0,
'YS' => 1.0,
'YG' => -7.49,
'YA' => 24.68,
'YL' => 1.0,
'FW' => 1.0,
'FC' => 1.0,
'FM' => 1.0,
'FH' => 1.0,
'FY' => 33.6,
'FF' => 1,
'FQ' => 1,
'FN' => 1,
'FI' => 1,
'FR' => 1,
'FD' => 13.34,
'FP' => 20.26,
'FT' => 1.0,
'FK' => -14.03,
'FE' => 1.0,
'FV' => 1.0,
'FS' => 1.0,
'FG' => 1.0,
'FA' => 1,
'FL' => 1.0,
'QW' => 1.0,
'QC' => -6.54,
'QM' => 1.0,
'QH' => 1.0,
'QY' => -6.54,
'QF' => -6.54,
'QQ' => 20.26,
'QN' => 1,
'QI' => 1,
'QR' => 1,
'QD' => 20.26,
'QP' => 20.26,
'QT' => 1.0,
'QK' => 1.0,
'QE' => 20.26,
'QV' => -6.54,
'QS' => 44.94,
'QG' => 1.0,
'QA' => 1,
'QL' => 1.0,
'NW' => -9.37,
'NC' => -1.88,
'NM' => 1.0,
'NH' => 1.0,
'NY' => 1.0,
'NF' => -14.03,
'NQ' => -6.54,
'NN' => 1,
'NI' => 44.94,
'NR' => 1,
'ND' => 1,
'NP' => -1.88,
'NT' => -7.49,
'NK' => 24.68,
'NE' => 1.0,
'NV' => 1.0,
'NS' => 1.0,
'NG' => -14.03,
'NA' => 1,
'NL' => 1.0,
'IW' => 1.0,
'IC' => 1.0,
'IM' => 1.0,
'IH' => 13.34,
'IY' => 1.0,
'IF' => 1.0,
'IQ' => 1.0,
'IN' => 1.0,
'II' => 1.0,
'IR' => 1.0,
'ID' => 1.0,
'IP' => - 1.88,
'IT' => 1.0,
'IK' => -7.49,
'IE' => 44.94,
'IV' => -7.49,
'IS' => 1.0,
'IG' => 1.0,
'IA' => 1.0,
'IL' => 20.26,
'RW' => 58.28,
'RC' => 1.0,
'RM' => 1.0,
'RH' => 20.26,
'RY' => -6.54,
'RF' => 1,
'RQ' => 20.26,
'RN' => 13.34,
'RI' => 1,
'RR' => 58.28,
'RD' => 1,
'RP' => 20.26,
'RT' => 1.0,
'RK' => 1.0,
'RE' => 1.0,
'RV' => 1.0,
'RS' => 44.94,
'RG' => -7.49,
'RA' => 1,
'RL' => 1.0,
'DW' => 1.0,
'DC' => 1.0,
'DM' => 1.0,
'DH' => 1.0,
'DY' => 1.0,
'DF' => -6.54,
'DQ' => 1,
'DN' => 1,
'DI' => 1,
'DR' => -6.54,
'DD' => 1,
'DP' => 1,
'DT' => -14.03,
'DK' => -7.49,
'DE' => 1.0,
'DV' => 1.0,
'DS' => 20.26,
'DG' => 1.0,
'DA' => 1,
'DL' => 1.0,
'PW' => -1.88,
'PC' => -6.54,
'PM' => -6.54,
'PH' => 1.0,
'PY' => 1.0,
'PF' => 20.26,
'PQ' => 20.26,
'PN' => 1,
'PI' => 1,
'PR' => -6.54,
'PD' => -6.54,
'PP' => 20.26,
'PT' => 1.0,
'PK' => 1.0,
'PE' => 18.38,
'PV' => 20.26,
'PS' => 20.26,
'PG' => 1.0,
'PA' => 20.26,
'PL' => 1.0,
'TW' => -14.03,
'TC' => 1.0,
'TM' => 1.0,
'TH' => 1.0,
'TY' => 1.0,
'TF' => 13.34,
'TQ' => -6.54,
'TN' => -14.03,
'TI' => 1,
'TR' => 1,
'TD' => 1,
'TP' => 1,
'TT' => 1.0,
'TK' => 1.0,
'TE' => 20.26,
'TV' => 1.0,
'TS' => 1.0,
'TG' => -7.49,
'TA' => 1,
'TL' => 1.0,
'KW' => 1.0,
'KC' => 1.0,
'KM' => 33.6,
'KH' => 1.0,
'KY' => 1.0,
'KF' => 1,
'KQ' => 24.68,
'KN' => 1,
'KI' => -7.49,
'KR' => 33.6,
'KD' => 1,
'KP' => -6.54,
'KT' => 1.0,
'KK' => 1.0,
'KE' => 1.0,
'KV' => -7.49,
'KS' => 1.0,
'KG' => -7.49,
'KA' => 1,
'KL' => -7.49,
'EW' => -14.03,
'EC' => 44.94,
'EM' => 1.0,
'EH' => -6.54,
'EY' => 1.0,
'EF' => 1,
'EQ' => 20.26,
'EN' => 1,
'EI' => 20.26,
'ER' => 1,
'ED' => 20.26,
'EP' => 20.26,
'ET' => 1.0,
'EK' => 1.0,
'EE' => 33.6,
'EV' => 1.0,
'ES' => 20.26,
'EG' => 1.0,
'EA' => 1,
'EL' => 1.0,
'VW' => 1.0,
'VC' => 1.0,
'VM' => 1.0,
'VH' => 1.0,
'VY' => -6.54,
'VF' => 1,
'VQ' => 1,
'VN' => 1,
'VI' => 1,
'VR' => 1,
'VD' => -14.03,
'VP' => 20.26,
'VT' => -7.49,
'VK' => -1.88,
'VE' => 1.0,
'VV' => 1.0,
'VS' => 1.0,
'VG' => -7.49,
'VA' => 1,
'VL' => 1.0,
'SW' => 1.0,
'SC' => 33.6,
'SM' => 1.0,
'SH' => 1.0,
'SY' => 1.0,
'SF' => 1,
'SQ' => 20.26,
'SN' => 1,
'SI' => 1,
'SR' => 20.26,
'SD' => 1,
'SP' => 44.94,
'ST' => 1.0,
'SK' => 1.0,
'SE' => 20.26,
'SV' => 1.0,
'SS' => 20.26,
'SG' => 1.0,
'SA' => 1,
'SL' => 1.0,
'GW' => 13.34,
'GC' => 1.0,
'GM' => 1.0,
'GH' => 1.0,
'GY' => -7.49,
'GF' => 1,
'GQ' => 1,
'GN' => -7.49,
'GI' => -7.49,
'GR' => 1,
'GD' => 1,
'GP' => 1,
'GT' => -7.49,
'GK' => -7.49,
'GE' => -6.54,
'GV' => 1.0,
'GS' => 1.0,
'GG' => 13.34,
'GA' => -7.49,
'GL' => 1.0,
'AW' => 1.0,
'AC' => 44.94,
'AM' => 1.0,
'AH' => -7.49,
'AY' => 1.0,
'AF' => 1,
'AQ' => 1,
'AN' => 1,
'AI' => 1,
'AR' => 1,
'AD' => -7.49,
'AP' => 20.26,
'AT' => 1.0,
'AK' => 1.0,
'AE' => 1.0,
'AV' => 1.0,
'AS' => 1.0,
'AG' => 1.0,
'AA' => 1,
'AL' => 1.0,
'LW' => 24.68,
'LC' => 1.0,
'LM' => 1.0,
'LH' => 1.0,
'LY' => 1.0,
'LF' => 1,
'LQ' => 33.6,
'LN' => 1,
'LI' => 1,
'LR' => 20.26,
'LD' => 1,
'LP' => 20.26,
'LT' => 1.0,
'LK' => -7.49,
'LE' => 1.0,
'LV' => 1.0,
'LS' => 1.0,
'LG' => 1.0,
'LA' => 1,
'LL' => 1.0
);

// Calculate the instability index
$L = strlen($sequence);
$ii = 0;
for ($i = 0; $i < $L-1; $i++) {
    $diwv_key = substr($sequence, $i, 2);
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
