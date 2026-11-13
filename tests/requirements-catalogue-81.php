<?php
/**
 * Central Master Plan V12.1 File 19 identifier-inventory regression.
 * Checks only catalogue traceability; never asserts runtime/staging acceptance.
 */
$root = dirname(__DIR__).'/19-unified-notifications/docs/';
$sources = array(
    'functional-and-central' => $root.'REQUIREMENTS-TRACEABILITY.md',
    'advanced' => $root.'ADVANCED-ATTENTION-OS-3.0.0.md',
);
$seen = array();
$failures = array();
$tests = 0;
function catalog_check($condition,$label){global $tests,$failures;++$tests;if(!$condition){$failures[]=$label;}}
foreach($sources as $name=>$file){
    $text = file_get_contents($file);
    if(false===$text){$failures[]='unreadable '.$name;continue;}
    preg_match_all('/^\|\s*(F19-(?:FR|NFR|CEN|AF)-\d{2,3})\s*\|/m',$text,$matches);
    foreach($matches[1] as $id){$seen[$id]=($seen[$id]??0)+1;}
}
$expected=array_merge(
    array_map(static fn($n)=>'F19-FR-'.str_pad((string)$n,3,'0',STR_PAD_LEFT),range(1,21)),
    array_map(static fn($n)=>'F19-NFR-'.str_pad((string)$n,3,'0',STR_PAD_LEFT),range(1,10)),
    array('F19-CEN-01','F19-CEN-02'),
    array_map(static fn($n)=>'F19-AF-'.str_pad((string)$n,3,'0',STR_PAD_LEFT),range(1,48))
);
foreach($expected as $id){
    catalog_check(isset($seen[$id]),'V12.1 File 19 ID absent: '.$id);
}
catalog_check(81===count($seen),'File 19 must have exactly 81 distinct traceability IDs');
catalog_check(0===count(array_filter($seen,static fn($count)=>$count!==1)),'each File 19 requirement ID must occur once in catalogue tables');
$unknown=array_diff(array_keys($seen),$expected);
catalog_check(!$unknown,'no unknown File 19 IDs are substituted for V12.1 IDs: '.implode(',',$unknown));
if($failures){fwrite(STDERR,"FAIL: ".count($failures)." of ".$tests." catalogue checks:\n - ".implode("\n - ",$failures)."\n");exit(1);}
echo "PASS: ".$tests." File 19 V12.1 catalogue-identity checks (81 unique requirements; NOT runtime acceptance)\n";
