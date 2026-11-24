<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class QuickFindController extends Controller
{
 use Concerns\ScopesToCompany;
 public function __invoke(Request $request) {
  $term=mb_substr((string)$request->input('search'),0,100);if(mb_strlen($term)<2)return response()->json(['data'=>[]]);
  $items=[];
  foreach ([['leads',\App\Models\Lead::class,'title',['title','contact_name','phone','email']],['clients',\App\Models\Client::class,'name',['name','client_code','phone','email']],['events',\App\Models\Event::class,'title',['title','event_code']],['vendors',\App\Models\Vendor::class,'name',['name','vendor_code','phone','email']],['quotations',\App\Models\Quotation::class,'quotation_number',['quotation_number']],['invoices',\App\Models\Invoice::class,'invoice_number',['invoice_number']],['tickets',\App\Models\Ticket::class,'ticket_number',['ticket_number','holder_name','holder_phone','holder_email']]] as [$module,$model,$label,$fields]) {
   $records=$this->scopeCompany($model::query(),$request)->where(function($q)use($fields,$term){foreach($fields as $f)$q->orWhere($f,'like','%'.str_replace(['%','_'],['\\%','\\_'],$term).'%');})->limit(5)->get();
   foreach($records as $r)$items[]=['label'=>$r->$label,'module'=>ucfirst($module),'url'=>'/'.$module.'?reference='.$r->id];
  }
  return response()->json(['data'=>$items]);
 }
}
