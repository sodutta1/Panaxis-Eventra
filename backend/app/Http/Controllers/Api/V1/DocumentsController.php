<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentsController extends Controller
{
 use Concerns\ScopesToCompany;
 private const TYPES=[
  'quotations'=>[\App\Models\Quotation::class,'quotation_number','Quotation'],
  'invoices'=>[\App\Models\Invoice::class,'invoice_number','Invoice'],
  'expenses'=>[\App\Models\EventExpense::class,'expense_number','Expense voucher'],
  'tickets'=>[\App\Models\Ticket::class,'ticket_number','Ticket'],
  'work-orders'=>[\App\Models\VendorAssignment::class,'id','Work order'],
  'events'=>[\App\Models\Event::class,'event_code','Event report'],
 ];
 private function record(Request $r,string $type,int $id) {
  abort_unless(isset(self::TYPES[$type]),404);[$class]=$this::TYPES[$type];
  return $this->findForCompany($class::query(),$r,$id);
 }
 public function link(Request $r,string $type,int $id){$this->record($r,$type,$id);return response()->json(['url'=>URL::temporarySignedRoute('api.v1.documents.download',now()->addMinutes(2),['type'=>$type,'id'=>$id,'user'=>$r->user()->id]),'expires_in'=>120]);}
 public function download(Request $r,string $type,int $id){
  abort_unless($r->hasValidSignature() && (int)$r->query('user')===$r->user()->id,403,'This link has expired. Request the document again.');
  $m=$this->record($r,$type,$id);$this->audit($r,$type,$id);
  return response($this->render($r,$type,$m),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$this->filename($type,$m).'"']);
 }
 public function bulk(Request $r,string $type){
  $r->validate(['ids'=>'required|array|min:1|max:100','ids.*'=>'required|integer|min:1']);
  $records=[];foreach(array_unique($r->input('ids')) as $id)$records[]=$this->record($r,$type,$id);
  $file=tempnam(sys_get_temp_dir(),'pdfs');$zip=new \ZipArchive;$zip->open($file,\ZipArchive::OVERWRITE);
  foreach($records as $m){$zip->addFromString($this->filename($type,$m),$this->render($r,$type,$m));$this->audit($r,$type,$m->id);}$zip->close();
  return response()->download($file,'Eventra-documents-'.date('Y-m-d').'.zip',['Content-Type'=>'application/zip'])->deleteFileAfterSend();
 }
 private function filename(string $type,$m):string {
  [, $field,$label]=self::TYPES[$type];$client=method_exists($m,'client')?$m->client?->name:($m->holder_name??$m->title??$m->service_title??'');
  return Str::slug($label.'-'.$m->$field.'-'.$client).'.pdf';
 }
 private function audit(Request $r,string $type,int $id){DB::table('document_downloads')->insert(['company_id'=>$this->companyId($r),'user_id'=>$r->user()->id,'document_type'=>$type,'record_id'=>$id,'action'=>'download']);}
 private function render(Request $r,string $type,$m):string {
  // Reuse the existing complete document layouts, including QR tickets and itemised invoices.
  if($type!=='expenses'){
   $controller=app(PdfController::class);$method=['quotations'=>'quotation','invoices'=>'invoice','tickets'=>'ticketPdf','work-orders'=>'workOrder','events'=>'closeout'][$type];
   return $controller->$method($r,$m->id)->getOriginalContent();
  }
  $company=\App\Models\Company::findOrFail($this->companyId($r));
  return Pdf::loadView('pdf.expense',['expense'=>$m->load(['event','vendor']),'company'=>$company])->setPaper('a4')->output();
 }
}
