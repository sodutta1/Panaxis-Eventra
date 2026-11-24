<?php
namespace Tests\Feature\Api\V1;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Lead;
class UiContractTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->seed();}
 private function auth(string $email='admin@eventflow.test'):array{$this->app['auth']->forgetGuards();return ['Authorization'=>'Bearer '.User::where('email',$email)->firstOrFail()->createToken('ui')->plainTextToken];}
 public function test_all_primary_lists_default_to_25_and_support_csv_and_real_xlsx():void{
  $auth=$this->auth();foreach(['leads','clients','venues','vendors','events','quotations','invoices','expenses','guests','tickets','admin/users'] as $route){
   $this->getJson('/api/v1/'.$route,$auth)->assertOk()->assertJsonPath('meta.per_page',25);
   $this->get('/api/v1/'.$route.'?format=csv',$auth)->assertOk()->assertHeader('content-type','text/csv; charset=utf-8');
   $r=$this->get('/api/v1/'.$route.'?format=xlsx',$auth)->assertOk();$this->assertStringContainsString('spreadsheetml',$r->headers->get('content-type'));
  }
 }
 public function test_filters_numeric_sort_and_pagination_are_server_side():void{
  $auth=$this->auth();$company=User::where('email','admin@eventflow.test')->value('company_id');
  for($i=0;$i<30;$i++)Lead::create(['company_id'=>$company,'title'=>'Kolkata cost review '.$i,'contact_name'=>'Ananya Sen','source'=>'referral','estimated_budget'=>1000+$i,'status'=>'new']);
  $r=$this->getJson('/api/v1/leads?name=Kolkata%20cost&sort=estimated_budget&direction=desc&per_page=10&page=2',$auth)->assertOk()->assertJsonCount(10,'data')->assertJsonPath('meta.total',30)->assertJsonPath('data.0.title','Kolkata cost review 19');
  $id=$r->json('data.0.id');$this->getJson('/api/v1/leads?reference='.$id,$auth)->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',$id);
  $this->getJson('/api/v1/leads?search=Ananya',$auth)->assertOk()->assertJsonPath('meta.total',30);
 }
 public function test_signed_downloads_reject_tampering_and_audit_actual_download():void{
  $auth=$this->auth();foreach(['quotations'=>\App\Models\Quotation::class,'invoices'=>\App\Models\Invoice::class,'expenses'=>\App\Models\EventExpense::class,'tickets'=>\App\Models\Ticket::class,'work-orders'=>\App\Models\VendorAssignment::class,'events'=>\App\Models\Event::class] as $type=>$class){
   $id=$class::where('company_id',User::where('email','admin@eventflow.test')->value('company_id'))->value('id');$this->assertNotNull($id,$type);
   $r=$this->getJson("/api/v1/documents/{$type}/{$id}/link",$auth)->assertOk()->assertJsonPath('expires_in',120);
   $url=$r->json('url');$this->get($url,$auth)->assertOk()->assertHeader('content-type','application/pdf');
   $this->assertDatabaseHas('document_downloads',['document_type'=>$type,'record_id'=>$id]);
   $this->get($url.'x',$auth)->assertForbidden();
  }
 }
 public function test_portal_scope_and_quick_find_deny_vendor_and_foreign_client():void{
  $vendor=$this->auth('vendor@eventflow.test');$this->getJson('/api/v1/quick-find?search=Oberoi',$vendor)->assertForbidden();$this->getJson('/api/v1/documents/invoices/1/link',$vendor)->assertForbidden();
  $client=$this->auth('client@eventflow.test');$this->getJson('/api/v1/portal/client/records?section=events',$client)->assertOk();$clientId=\App\Models\Client::where('user_id',User::where('email','client@eventflow.test')->value('id'))->value('id');$invoiceId=\App\Models\Invoice::where('client_id',$clientId)->value('id');$this->getJson('/api/v1/documents/invoices/'.$invoiceId.'/link',$client)->assertOk();
  $foreign=\App\Models\Invoice::where('client_id','!=',$clientId)->first();if($foreign)$this->getJson('/api/v1/documents/invoices/'.$foreign->id.'/link',$client)->assertNotFound();
 }
 public function test_expense_maker_cannot_self_approve_or_bypass_by_update():void{
  $auth=$this->auth();$id=$this->postJson('/api/v1/expenses',['title'=>'Park Street venue bill','amount'=>12000,'incurred_date'=>'2026-11-24','payment_method'=>'upi','gst_amount'=>2160,'campaign'=>'Winter launch','category_name'=>'Venue'],$auth)->assertCreated()->assertJsonPath('data.status','pending')->json('data.id');
  $this->postJson("/api/v1/expenses/{$id}/approve",[],$auth)->assertUnprocessable();
  $this->patchJson("/api/v1/expenses/{$id}",['status'=>'approved'],$auth)->assertOk()->assertJsonPath('data.status','pending');
  $finance=$this->auth('finance@eventflow.test');$this->postJson("/api/v1/expenses/{$id}/approve",[],$finance)->assertOk()->assertJsonPath('data.status','approved');
  $auth=$this->auth();$this->patchJson("/api/v1/expenses/{$id}",['amount'=>1],$auth)->assertUnprocessable();
  $this->deleteJson("/api/v1/expenses/{$id}",[],$auth)->assertUnprocessable();
 }
}
