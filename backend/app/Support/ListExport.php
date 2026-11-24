<?php
namespace App\Support;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListExport
{
    public static function response(Builder $query, Request $request)
    {
        // Export visible business columns only. Never emit passwords, tokens or storage paths.
        $columns = array_values(array_filter(\Illuminate\Support\Facades\Schema::getColumnListing($query->getModel()->getTable()),fn($c)=>!in_array($c,['company_id','password','remember_token','qr_token','receipt_path','bill_data'],true)));
        $rows = [array_map(fn($c)=>ucwords(str_replace('_',' ',$c)),$columns)];
        foreach ($query->cursor() as $model) {
            $rows[] = array_map(function($c) use($model) {
                $v = $model->getRawOriginal($c) ?? '';
                $v = is_scalar($v) ? (string)$v : '';
                return preg_match('/^[=+@\-\t\r]/',$v) ? "'".$v : $v;
            },$columns);
            abort_if(count($rows)>50001,422,'Narrow the filters to export fewer than 50,000 records.');
        }
        $name = str_replace('_','-',$query->getModel()->getTable()).'-'.date('Y-m-d');
        if ($request->input('format') === 'csv') return response()->streamDownload(function() use($rows) {
            $f=fopen('php://output','w'); fwrite($f,"\xEF\xBB\xBF"); foreach($rows as $row) fputcsv($f,$row,',','"',''); fclose($f);
        },$name.'.csv',['Content-Type'=>'text/csv']);
        $file=tempnam(sys_get_temp_dir(),'xlsx'); $zip=new \ZipArchive; $zip->open($file,\ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml','<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Records" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $xml='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach($rows as $i=>$row) { $xml.='<row r="'.($i+1).'">'; foreach($row as $v) $xml.='<c t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8').'</t></is></c>'; $xml.='</row>'; }
        $zip->addFromString('xl/worksheets/sheet1.xml',$xml.'</sheetData></worksheet>'); $zip->close();
        return response()->download($file,$name.'.xlsx',['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
    }
}
