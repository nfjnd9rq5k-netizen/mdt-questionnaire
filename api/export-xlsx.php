<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Accès refusé');
}

$jsonData = $_POST['data'] ?? '';
$filename = $_POST['filename'] ?? 'export';
$studyId = $_POST['studyId'] ?? 'Export';

if (empty($jsonData)) {
    http_response_code(400);
    die('Données manquantes');
}

$data = json_decode($jsonData, true);
if (!$data || !isset($data['headers']) || !isset($data['rows'])) {
    http_response_code(400);
    die('Format de données invalide');
}

$headers = $data['headers'];
$rows = $data['rows'];

class SimpleXLSXWriter {
    private $tempDir;
    private $sheetName;
    private $headers;
    private $rows;
    private $strings = [];
    private $stringIndex = [];
    
    public function __construct($sheetName, $headers, $rows) {
        $this->tempDir = sys_get_temp_dir() . '/xlsx_' . uniqid();
        $this->sheetName = preg_replace('/[^a-zA-Z0-9_]/', '_', substr($sheetName, 0, 31));
        $this->headers = $headers;
        $this->rows = $rows;
        
        mkdir($this->tempDir, 0777, true);
        mkdir($this->tempDir . '/_rels', 0777, true);
        mkdir($this->tempDir . '/xl', 0777, true);
        mkdir($this->tempDir . '/xl/_rels', 0777, true);
        mkdir($this->tempDir . '/xl/worksheets', 0777, true);
        
        $this->buildStringTable();
    }
    
    private function buildStringTable() {
        foreach ($this->headers as $h) {
            $this->addString((string)$h);
        }
        foreach ($this->rows as $row) {
            foreach ($row as $cell) {
                $this->addString((string)($cell ?? ''));
            }
        }
    }
    
    private function addString($str) {
        if (!isset($this->stringIndex[$str])) {
            $this->stringIndex[$str] = count($this->strings);
            $this->strings[] = $str;
        }
    }
    
    private function getStringIndex($str) {
        return $this->stringIndex[(string)$str] ?? 0;
    }
    
    public function output($filename) {
        $this->createContentTypes();
        $this->createRels();
        $this->createWorkbook();
        $this->createWorkbookRels();
        $this->createStyles();
        $this->createSharedStrings();
        $this->createWorksheet();
        
        $zipFile = $this->tempDir . '/' . $filename . '.xlsx';
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            die('Erreur création ZIP');
        }
        
        $this->addToZip($zip, '[Content_Types].xml');
        $this->addToZip($zip, '_rels/.rels');
        $this->addToZip($zip, 'xl/workbook.xml');
        $this->addToZip($zip, 'xl/_rels/workbook.xml.rels');
        $this->addToZip($zip, 'xl/styles.xml');
        $this->addToZip($zip, 'xl/sharedStrings.xml');
        $this->addToZip($zip, 'xl/worksheets/sheet1.xml');
        
        $zip->close();
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Content-Length: ' . filesize($zipFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        readfile($zipFile);
        
        $this->cleanup();
    }
    
    private function addToZip($zip, $file) {
        $zip->addFile($this->tempDir . '/' . $file, $file);
    }
    
    private function cleanup() {
        $files = [
            '/xl/worksheets/sheet1.xml',
            '/xl/sharedStrings.xml', 
            '/xl/styles.xml',
            '/xl/_rels/workbook.xml.rels',
            '/xl/workbook.xml',
            '/_rels/.rels',
            '/[Content_Types].xml'
        ];
        foreach ($files as $f) {
            @unlink($this->tempDir . $f);
        }
        @rmdir($this->tempDir . '/xl/worksheets');
        @rmdir($this->tempDir . '/xl/_rels');
        @rmdir($this->tempDir . '/xl');
        @rmdir($this->tempDir . '/_rels');
        
        foreach (glob($this->tempDir . '/*.xlsx') as $f) {
            @unlink($f);
        }
        @rmdir($this->tempDir);
    }
    
    private function createContentTypes() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
        $xml .= '</Types>';
        file_put_contents($this->tempDir . '/[Content_Types].xml', $xml);
    }
    
    private function createRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $xml .= '</Relationships>';
        file_put_contents($this->tempDir . '/_rels/.rels', $xml);
    }
    
    private function createWorkbook() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheets>';
        $xml .= '<sheet name="' . htmlspecialchars($this->sheetName) . '" sheetId="1" r:id="rId1"/>';
        $xml .= '</sheets>';
        $xml .= '</workbook>';
        file_put_contents($this->tempDir . '/xl/workbook.xml', $xml);
    }
    
    private function createWorkbookRels() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
        $xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $xml .= '</Relationships>';
        file_put_contents($this->tempDir . '/xl/_rels/workbook.xml.rels', $xml);
    }
    
    private function createStyles() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<fonts count="2">';
        $xml .= '<font><sz val="11"/><name val="Calibri"/></font>';
        $xml .= '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Verdana"/></font>';
        $xml .= '</fonts>';
        $xml .= '<fills count="4">';
        $xml .= '<fill><patternFill patternType="none"/></fill>';
        $xml .= '<fill><patternFill patternType="gray125"/></fill>';
        $xml .= '<fill><patternFill patternType="solid"><fgColor rgb="FF0F243E"/><bgColor indexed="64"/></patternFill></fill>';
        $xml .= '<fill><patternFill patternType="solid"><fgColor rgb="FFF5F5F5"/><bgColor indexed="64"/></patternFill></fill>';
        $xml .= '</fills>';
        $xml .= '<borders count="2">';
        $xml .= '<border><left/><right/><top/><bottom/><diagonal/></border>';
        $xml .= '<border>';
        $xml .= '<left style="thin"><color rgb="FFCCCCCC"/></left>';
        $xml .= '<right style="thin"><color rgb="FFCCCCCC"/></right>';
        $xml .= '<top style="thin"><color rgb="FFCCCCCC"/></top>';
        $xml .= '<bottom style="thin"><color rgb="FFCCCCCC"/></bottom>';
        $xml .= '<diagonal/>';
        $xml .= '</border>';
        $xml .= '</borders>';
        $xml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $xml .= '<cellXfs count="4">';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';
        $xml .= '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>';
        $xml .= '</cellXfs>';
        $xml .= '</styleSheet>';
        file_put_contents($this->tempDir . '/xl/styles.xml', $xml);
    }
    
    private function createSharedStrings() {
        $count = count($this->strings);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">';
        
        foreach ($this->strings as $str) {
            $xml .= '<si><t>' . htmlspecialchars($str) . '</t></si>';
        }
        
        $xml .= '</sst>';
        file_put_contents($this->tempDir . '/xl/sharedStrings.xml', $xml);
    }
    
    private function createWorksheet() {
        $numCols = count($this->headers);
        $numRows = count($this->rows) + 1;
        $lastCol = $this->colLetter($numCols);
        
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="A1:' . $lastCol . $numRows . '"/>';
        $xml .= '<cols>';
        
        for ($i = 1; $i <= $numCols; $i++) {
            $xml .= '<col min="' . $i . '" max="' . $i . '" width="18" customWidth="1"/>';
        }
        
        $xml .= '</cols><sheetData>';
        
        $xml .= '<row r="1" spans="1:' . $numCols . '">';
        foreach ($this->headers as $i => $h) {
            $col = $this->colLetter($i + 1);
            $idx = $this->getStringIndex($h);
            $xml .= '<c r="' . $col . '1" s="1" t="s"><v>' . $idx . '</v></c>';
        }
        $xml .= '</row>';
        
        foreach ($this->rows as $rowIdx => $row) {
            $r = $rowIdx + 2;
            $style = ($rowIdx % 2 == 0) ? '2' : '3';
            
            $xml .= '<row r="' . $r . '" spans="1:' . $numCols . '">';
            foreach ($row as $i => $cell) {
                $col = $this->colLetter($i + 1);
                $idx = $this->getStringIndex((string)($cell ?? ''));
                $xml .= '<c r="' . $col . $r . '" s="' . $style . '" t="s"><v>' . $idx . '</v></c>';
            }
            $xml .= '</row>';
        }
        
        $xml .= '</sheetData></worksheet>';
        file_put_contents($this->tempDir . '/xl/worksheets/sheet1.xml', $xml);
    }
    
    private function colLetter($n) {
        $l = '';
        while ($n > 0) {
            $n--;
            $l = chr(65 + ($n % 26)) . $l;
            $n = intval($n / 26);
        }
        return $l;
    }
}

$xlsx = new SimpleXLSXWriter($studyId, $headers, $rows);
$xlsx->output($filename);
