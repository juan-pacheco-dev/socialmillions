<?php
// includes/xlsx_writer.php
// Escritor de XLSX Mínimo para PHP
// Genera archivos .xlsx válidos usando ZipArchive estándar

class XlsxWriter
{
    public static function output($filename, $rows, $headers = [])
    {
        $temp_file = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');

        // 4. xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');

        // 5. xl/styles.xml (Estilos básicos con encabezados en negrita)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>');

        // 6. xl/worksheets/sheet1.xml
        $xml_sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $rowIndex = 1;

        // Agrego Encabezados
        if (!empty($headers)) {
            $xml_sheet .= '<row r="' . $rowIndex . '">';
            $colIndex = 0;
            foreach ($headers as $header) {
                $cellRef = self::num2alpha($colIndex) . $rowIndex;
                $xml_sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="1"><is><t>' . self::xml_escape($header) . '</t></is></c>';
                $colIndex++;
            }
            $xml_sheet .= '</row>';
            $rowIndex++;
        }

        // Agrego Datos
        foreach ($rows as $row) {
            $xml_sheet .= '<row r="' . $rowIndex . '">';
            $colIndex = 0;
            foreach ($row as $value) {
                $cellRef = self::num2alpha($colIndex) . $rowIndex;

                // Manejo de nulos
                $value = ($value === null) ? '' : $value;

                // Detecto número vs cadena
                if (is_numeric($value) && strlen($value) < 15 && substr($value, 0, 1) != '0') {
                    $xml_sheet .= '<c r="' . $cellRef . '"><v>' . $value . '</v></c>';
                } else {
                    $xml_sheet .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . self::xml_escape($value) . '</t></is></c>';
                }
                $colIndex++;
            }
            $xml_sheet .= '</row>';
            $rowIndex++;
        }

        $xml_sheet .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml_sheet);

        $zip->close();

        // Encabezado de Salida
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($temp_file));
        header('Cache-Control: max-age=0');

        readfile($temp_file);
        unlink($temp_file);
        exit();
    }

    private static function num2alpha($n)
    {
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n % 26 + 0x41) . $r;
        return $r;
    }

    private static function xml_escape($str)
    {
        return htmlspecialchars((string) $str, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
?>