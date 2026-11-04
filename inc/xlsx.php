<?php
function xl_col(int $i): string { $s=''; $i++; while($i>0){ $m=($i-1)%26; $s=chr(65+$m).$s; $i=intdiv($i-1,26); } return $s; }
function xl_esc($s): string {
  $s=preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u','',(string)$s);
  return htmlspecialchars((string)$s,ENT_XML1|ENT_QUOTES,'UTF-8');
}
function xlsx_output(string $filename, array $header, array $rows): void {
  if(!class_exists('ZipArchive')){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
    $o=fopen('php://output','w'); fwrite($o,"\xEF\xBB\xBF"); fputcsv($o,$header);
    foreach($rows as $r) fputcsv($o,$r);
    exit;
  }
  $n=count($header);
  $x='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
  $sheet=$x.'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView rightToLeft="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="'.$n.'" width="22" customWidth="1"/></cols><sheetData>';
  $sheet.='<row r="1">';
  foreach($header as $i=>$h) $sheet.='<c r="'.xl_col($i).'1" t="inlineStr" s="1"><is><t>'.xl_esc($h).'</t></is></c>';
  $sheet.='</row>';
  $r=2;
  foreach($rows as $row){
    $sheet.='<row r="'.$r.'">';
    foreach(array_values($row) as $i=>$v){
      if($v===null || $v==='') continue;
      $ref=xl_col($i).$r;
      if(is_int($v)) $sheet.='<c r="'.$ref.'"><v>'.$v.'</v></c>';
      else $sheet.='<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.xl_esc($v).'</t></is></c>';
    }
    $sheet.='</row>'; $r++;
  }
  $sheet.='</sheetData></worksheet>';
  $ns='http://schemas.openxmlformats.org/';
  $tmp=tempnam(sys_get_temp_dir(),'xl');
  $z=new ZipArchive();
  $z->open($tmp,ZipArchive::OVERWRITE);
  $z->addFromString('[Content_Types].xml',$x.'<Types xmlns="'.$ns.'package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
  $z->addFromString('_rels/.rels',$x.'<Relationships xmlns="'.$ns.'package/2006/relationships"><Relationship Id="rId1" Type="'.$ns.'officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
  $z->addFromString('xl/workbook.xml',$x.'<workbook xmlns="'.$ns.'spreadsheetml/2006/main" xmlns:r="'.$ns.'officeDocument/2006/relationships"><sheets><sheet name="Kila" sheetId="1" r:id="rId1"/></sheets></workbook>');
  $z->addFromString('xl/_rels/workbook.xml.rels',$x.'<Relationships xmlns="'.$ns.'package/2006/relationships"><Relationship Id="rId1" Type="'.$ns.'officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="'.$ns.'officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
  $z->addFromString('xl/styles.xml',$x.'<styleSheet xmlns="'.$ns.'spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
  $z->addFromString('xl/worksheets/sheet1.xml',$sheet);
  $z->close();
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="'.$filename.'.xlsx"');
  header('Content-Length: '.filesize($tmp));
  readfile($tmp);
  @unlink($tmp);
  exit;
}
