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
  abort_unless(in_array($r->user()->role,['super_admin','company_admin','admin','event_manager','sales_executive','accountant','staff','client']),403);
  abort_unless(isset(self::TYPES[$type]),404);[$class]=$this::TYPES[$type];
  $record=$this->findForCompany($class::query(),$r,$id);
  if($r->user()->role==='client'){
   abort_unless(in_array($type,['quotations','invoices','events']),403);
   abort_unless(\App\Models\Client::where('id',$record->client_id)->where('user_id',$r->user()->id)->where('company_id',$this->companyId($r))->exists(),404);
  }
  return $record;
 }
 public function show(Request $r,string $type,int $id){
  $m=$this->record($r,$type,$id);
  $data=collect($m->getAttributes())->reject(fn($v,$k)=>in_array($k,['id','company_id','qr_token','bill_data','receipt_path','created_at','updated_at'])||str_ends_with($k,'_id'))->all();
  foreach(['client'=>'Client','event'=>'Event','vendor'=>'Vendor'] as $relation=>$label)if(method_exists($m,$relation)){$related=$m->$relation;$data[$label]=$related?($related->name??$related->title):'Not set';}
  if($type==='quotations'||$type==='invoices')$data['line_items']=$m->items->map(fn($i)=>['description'=>$i->item_name??$i->description??'Item','quantity'=>$i->quantity,'rate'=>$i->unit_price,'amount'=>$i->total??$i->line_total])->all();
  return response()->json(['data'=>$data]);
 }
 public function link(Request $r,string $type,int $id){$this->record($r,$type,$id);return response()->json(['url'=>URL::temporarySignedRoute('api.v1.documents.download',now()->addMinutes(2),['type'=>$type,'id'=>$id,'user'=>$r->user()->id],absolute:false),'expires_in'=>120]);}
 public function download(Request $r,string $type,int $id){
  abort_unless($r->hasValidSignature(false) && (int)$r->query('user')===$r->user()->id,403,'This link has expired. Request the document again.');
  $m=$this->record($r,$type,$id);$bytes=$this->render($r,$type,$m);$this->audit($r,$type,$id);
  return response($bytes,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$this->filename($type,$m).'"']);
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
