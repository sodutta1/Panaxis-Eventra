<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('event_expenses',function(Blueprint $t){$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->decimal('gst_amount',12,2)->default(0);$t->string('campaign')->nullable();$t->string('category_name')->nullable();$t->string('bill_name')->nullable();$t->longText('bill_data')->nullable();$t->text('reversal_reason')->nullable();});
  Schema::create('document_downloads',function(Blueprint $t){$t->id();$t->foreignId('company_id')->constrained();$t->foreignId('user_id')->constrained();$t->string('document_type');$t->unsignedBigInteger('record_id');$t->string('action')->default('download');$t->timestamp('created_at')->useCurrent();});
 }
 public function down():void{Schema::dropIfExists('document_downloads');Schema::table('event_expenses',function(Blueprint $t){$t->dropConstrainedForeignId('created_by');$t->dropColumn(['gst_amount','campaign','category_name','bill_name','bill_data','reversal_reason']);});}
};
