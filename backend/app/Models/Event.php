<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Event extends Model {
    protected $guarded = [];
    protected $casts = [
        "start_date" => "datetime",
        "end_date" => "datetime",
    ];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function category() { return $this->belongsTo(EventCategory::class, "event_category_id"); }
    public function venue() { return $this->belongsTo(Venue::class); }
    public function manager() { return $this->belongsTo(User::class, "manager_id"); }
    public function days() { return $this->hasMany(EventDay::class); }
    public function schedules() { return $this->hasMany(EventSchedule::class)->orderBy("start_time"); }
    public function tasks() { return $this->hasMany(EventTask::class); }
    public function vendorAssignments() { return $this->hasMany(VendorAssignment::class); }
    public function venueBookings() { return $this->hasMany(VenueBooking::class); }
    public function budget() { return $this->hasOne(EventBudget::class); }
    public function expenses() { return $this->hasMany(EventExpense::class); }
    public function guests() { return $this->hasMany(EventGuest::class); }
    public function ticketTypes() { return $this->hasMany(TicketType::class); }
    public function tickets() { return $this->hasMany(Ticket::class); }
    public function cateringMenus() { return $this->hasMany(CateringMenu::class); }
    public function decorationThemes() { return $this->hasMany(DecorationTheme::class); }
    public function staffShifts() { return $this->hasMany(StaffShift::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function proposals() { return $this->hasMany(Proposal::class); }
    public function quotations() { return $this->hasMany(Quotation::class); }
    public function inventoryAllocations() { return $this->hasMany(InventoryAllocation::class); }
}
