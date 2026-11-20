<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Employee;
use App\Models\SystemSetting;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\EventCategory;
use App\Models\Venue;
use App\Models\VenueBooking;
use App\Models\VendorCategory;
use App\Models\Vendor;
use App\Models\VendorAssignment;
use App\Models\Event;
use App\Models\EventDay;
use App\Models\EventSchedule;
use App\Models\EventTask;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\EventPackage;
use App\Models\Proposal;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\EventBudget;
use App\Models\EventBudgetItem;
use App\Models\ExpenseCategory;
use App\Models\EventExpense;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryAllocation;
use App\Models\CateringMenu;
use App\Models\DecorationTheme;
use App\Models\StaffShift;
use App\Models\GuestGroup;
use App\Models\EventGuest;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Company
        $company = Company::create([
            'name' => 'GrandVibe Events & Productions Pvt. Ltd.',
            'code' => 'GV-ENT',
            'email' => 'contact@grandvibe-events.com',
            'phone' => '+91 98200 12345',
            'address' => 'Floor 12, Crescenzo Tower, G-Block, Bandra Kurla Complex',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'tax_number' => '27AAACG1234F1Z5',
            'status' => 'active',
        ]);

        // 2. Branches
        $branchMumbai = Branch::create([
            'company_id' => $company->id,
            'name' => 'Mumbai Corporate HQ',
            'code' => 'BOM-01',
            'phone' => '+91 22 6677 8899',
            'email' => 'mumbai@grandvibe-events.com',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'address' => 'Bandra Kurla Complex, Bandra East, Mumbai 400051',
            'is_main_branch' => true,
            'is_active' => true,
        ]);

        $branchDelhi = Branch::create([
            'company_id' => $company->id,
            'name' => 'Delhi NCR Regional Hub',
            'code' => 'DEL-01',
            'phone' => '+91 11 4455 6677',
            'email' => 'delhi@grandvibe-events.com',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'address' => 'Statesman House, Barakhamba Road, Connaught Place, New Delhi 110001',
            'is_main_branch' => false,
            'is_active' => true,
        ]);

        $branchBlr = Branch::create([
            'company_id' => $company->id,
            'name' => 'Bangalore Tech & Innovation Hub',
            'code' => 'BLR-01',
            'phone' => '+91 80 3344 5566',
            'email' => 'blr@grandvibe-events.com',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'address' => '100 Feet Road, Indiranagar, Bengaluru 560038',
            'is_main_branch' => false,
            'is_active' => true,
        ]);

        // 3. Departments
        $deptProduction = Department::create(['company_id' => $company->id, 'name' => 'Event Production & Staging', 'code' => 'PROD']);
        $deptSales = Department::create(['company_id' => $company->id, 'name' => 'Sales & Business Development', 'code' => 'SALES']);
        $deptFinance = Department::create(['company_id' => $company->id, 'name' => 'Finance & Accounts', 'code' => 'FIN']);
        $deptCreative = Department::create(['company_id' => $company->id, 'name' => 'Creative & Decor Design', 'code' => 'DESIGN']);
        $deptOperations = Department::create(['company_id' => $company->id, 'name' => 'Guest Logistics & Hospitality', 'code' => 'HOSP']);

        // 4. Roles
        $roles = [
            ['name' => 'Super Administrator', 'slug' => 'super_admin'],
            ['name' => 'Company Admin', 'slug' => 'company_admin'],
            ['name' => 'Lead Event Manager', 'slug' => 'event_manager'],
            ['name' => 'Senior Sales Executive', 'slug' => 'sales_executive'],
            ['name' => 'Lead Accountant', 'slug' => 'accountant'],
            ['name' => 'Operations Staff', 'slug' => 'staff'],
            ['name' => 'Client Portal User', 'slug' => 'client'],
            ['name' => 'Vendor Partner', 'slug' => 'vendor'],
        ];
        foreach ($roles as $r) {
            Role::create(array_merge($r, ['company_id' => $company->id]));
        }

        // 5. Users
        $defaultPassword = Hash::make('password');

        $adminUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'Vikramaditya Singhania',
            'email' => 'admin@eventflow.test',
            'phone' => '+91 98201 99999',
            'role' => 'super_admin',
            'status' => 'active',
            'password' => $defaultPassword,
        ]);

        $managerUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'Priya Sharma',
            'email' => 'manager@eventflow.test',
            'phone' => '+91 98202 88888',
            'role' => 'event_manager',
            'status' => 'active',
            'password' => $defaultPassword,
        ]);

        $salesUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'name' => 'Rajesh Verma',
            'email' => 'sales@eventflow.test',
            'phone' => '+91 98203 77777',
            'role' => 'sales_executive',
            'status' => 'active',
            'password' => $defaultPassword,
        ]);

        $financeUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'Anita Desai',
            'email' => 'finance@eventflow.test',
            'phone' => '+91 98204 66666',
            'role' => 'accountant',
            'status' => 'active',
            'password' => $defaultPassword,
        ]);

        $clientUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'Rahul Oberoi',
            'email' => 'client@eventflow.test',
            'phone' => '+91 98205 55555',
            'role' => 'client',
            'status' => 'active',
            'password' => $defaultPassword,
        ]);

        $vendorUser = User::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'Farhan Qureshi',
            'email' => 'vendor@eventflow.test',
            'phone' => '+91 98206 44444',
            'role' => 'vendor',
            'status' => 'active',
            'password' => $defaultPassword,
        ]);

        // Employees
        Employee::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'department_id' => $deptProduction->id,
            'user_id' => $managerUser->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'email' => 'manager@eventflow.test',
            'phone' => '+91 98202 88888',
            'designation' => 'Senior Event Director',
            'salary' => 120000.00,
            'joining_date' => '2022-03-15',
            'status' => 'active',
        ]);

        Employee::create([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'department_id' => $deptSales->id,
            'user_id' => $salesUser->id,
            'employee_code' => 'EMP-002',
            'first_name' => 'Rajesh',
            'last_name' => 'Verma',
            'email' => 'sales@eventflow.test',
            'phone' => '+91 98203 77777',
            'designation' => 'Lead Client Partner',
            'salary' => 95000.00,
            'joining_date' => '2022-07-01',
            'status' => 'active',
        ]);

        // 6. Clients
        $client1 = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'user_id' => $clientUser->id,
            'client_code' => 'CL-1001',
            'name' => 'Rahul & Sanjana Oberoi',
            'company_name' => 'Oberoi Global Enterprises',
            'email' => 'rahul@oberoienterprises.com',
            'phone' => '+91 98205 55555',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'tax_number' => '27AAFCO9999M1Z8',
            'client_type' => 'wedding',
            'status' => 'active',
            'notes' => 'High-profile luxury wedding client.',
        ]);

        $client2 = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branchBlr->id,
            'client_code' => 'CL-1002',
            'name' => 'NexusTech Innovations Ltd.',
            'company_name' => 'NexusTech Innovations',
            'email' => 'events@nexustech.io',
            'phone' => '+91 80 4999 1234',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'tax_number' => '29AAACN8888F1Z2',
            'client_type' => 'corporate',
            'status' => 'active',
            'notes' => 'Annual global tech summit and conference client.',
        ]);

        $client3 = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'client_code' => 'CL-1003',
            'name' => 'Apex Auto Manufacturers Association',
            'company_name' => 'Apex Mobility Group',
            'email' => 'expo@apexmobility.org',
            'phone' => '+91 11 2345 6789',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'client_type' => 'corporate',
            'status' => 'active',
            'notes' => 'Large-scale automotive exhibition organizers.',
        ]);

        // 7. Leads
        Lead::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'assigned_to' => $salesUser->id,
            'title' => 'Winter Destination Wedding in Udaipur',
            'contact_name' => 'Kavita Chawla',
            'email' => 'kavita.c@gmail.com',
            'phone' => '+91 98111 22334',
            'source' => 'website',
            'event_type' => 'wedding',
            'event_date' => Carbon::now()->addMonths(3)->toDateString(),
            'estimated_guests' => 350,
            'estimated_budget' => 4500000.00,
            'status' => 'qualified',
            'requirements' => '3-day royal palace wedding with sangeet, mehendi, and reception dinner.',
        ]);

        Lead::create([
            'company_id' => $company->id,
            'branch_id' => $branchBlr->id,
            'assigned_to' => $salesUser->id,
            'title' => 'AI Revolution Product Keynote 2026',
            'contact_name' => 'David Lee',
            'email' => 'david.lee@synthetica.ai',
            'phone' => '+91 98450 11223',
            'source' => 'referral',
            'event_type' => 'product_launch',
            'event_date' => Carbon::now()->addMonths(2)->toDateString(),
            'estimated_guests' => 500,
            'estimated_budget' => 2000000.00,
            'status' => 'proposal_sent',
            'requirements' => 'Curved 4K LED backdrop, live stream to YouTube/LinkedIn, press lounge.',
        ]);

        // 8. Event Categories
        $catWedding = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'Weddings & Celebrations',
            'slug' => 'weddings',
            'icon' => 'fa-rings-wedding',
            'description' => 'Luxury destination weddings, sangeet nights, receptions, and engagements.'
        ]);

        $catCorporate = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'Corporate & Business',
            'slug' => 'corporate',
            'icon' => 'fa-briefcase',
            'description' => 'Annual conferences, investor summits, leadership retreats, and dealer meets.'
        ]);

        $catConcert = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'Concerts & Entertainment',
            'slug' => 'concerts',
            'icon' => 'fa-music',
            'description' => 'Live music concerts, music festivals, artist tours, and award nights.'
        ]);

        $catExhibition = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'Exhibitions & Trade Fairs',
            'slug' => 'exhibitions',
            'icon' => 'fa-store',
            'description' => 'B2B/B2C trade expos, commercial pavilions, and convention showcases.'
        ]);

        $catCollege = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'College & Cultural Festivals',
            'slug' => 'college-fests',
            'icon' => 'fa-graduation-cap',
            'description' => 'Inter-college youth festivals, technical symposiums, and battle of the bands.'
        ]);

        $catPrivate = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'Private Parties & Birthdays',
            'slug' => 'parties',
            'icon' => 'fa-cake-candles',
            'description' => 'Milestone birthdays, anniversaries, and VIP private theme parties.'
        ]);

        $catLaunch = EventCategory::create([
            'company_id' => $company->id,
            'name' => 'Product Launches',
            'slug' => 'launches',
            'icon' => 'fa-rocket',
            'description' => 'Automotive unveilings, tech gadget debuts, and brand activations.'
        ]);

        // 9. Venues
        $venue1 = Venue::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'The Imperial Grand Palace & Gardens',
            'venue_code' => 'VEN-MUM-01',
            'type' => 'banquet_hall',
            'capacity' => 1200,
            'contact_person' => 'Sunil Shetty',
            'contact_phone' => '+91 22 2654 3210',
            'contact_email' => 'banquets@imperialgrand.com',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'address' => 'Worli Sea Face, Mumbai 400018',
            'rental_cost_per_day' => 450000.00,
            'amenities' => 'Pillarless Ballroom, 2 Acre Royal Lawn, Valet Parking 400 Cars, Green Rooms, 100% DG Backup',
            'status' => 'available',
        ]);

        $venue2 = Venue::create([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'name' => 'Bharat Convention Center & Grand Arena',
            'venue_code' => 'VEN-DEL-01',
            'type' => 'convention_centre',
            'capacity' => 5000,
            'contact_person' => 'Harshvardhan Kapoor',
            'contact_phone' => '+91 11 2987 6543',
            'contact_email' => 'events@bharatconvention.org',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'address' => 'Pragati Maidan Complex, New Delhi 110002',
            'rental_cost_per_day' => 850000.00,
            'amenities' => 'Plenary Hall for 3000, 8 Breakout Rooms, Exhibition Floor 50,000 sq.ft, Broadcast Studio',
            'status' => 'available',
        ]);

        $venue3 = Venue::create([
            'company_id' => $company->id,
            'branch_id' => $branchBlr->id,
            'name' => 'Silicon View Sky Lounge & Lawn',
            'venue_code' => 'VEN-BLR-01',
            'type' => 'hotel',
            'capacity' => 600,
            'contact_person' => 'Kavya Nair',
            'contact_phone' => '+91 80 4123 9876',
            'contact_email' => 'banquets@siliconview.com',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'address' => 'Whitefield Main Road, Bengaluru 560066',
            'rental_cost_per_day' => 250000.00,
            'amenities' => 'Rooftop Infinity Deck, Acoustic Ballroom, 10Gbps Dedicated Fiber, EV Charging Stations',
            'status' => 'available',
        ]);

        $venue4 = Venue::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'name' => 'Goa Azure Oceanfront Resort',
            'venue_code' => 'VEN-GOA-01',
            'type' => 'resort',
            'capacity' => 800,
            'contact_person' => 'Carlos D Souza',
            'contact_phone' => '+91 832 277 8899',
            'contact_email' => 'weddings@goaazure.com',
            'city' => 'Candolim',
            'state' => 'Goa',
            'address' => 'Sinquerim Beach Road, Candolim, Goa 403515',
            'rental_cost_per_day' => 550000.00,
            'amenities' => 'Private White Sand Beach, Sunset Amphitheatre, 120 Luxury Villa Rooms, Open-Air Banquet',
            'status' => 'available',
        ]);

        // 10. Vendor Categories & Vendors
        $vcatAudio = VendorCategory::create(['company_id' => $company->id, 'name' => 'Audio & Sound Engineering']);
        $vcatDecor = VendorCategory::create(['company_id' => $company->id, 'name' => 'Floral, Stage & Theme Decor']);
        $vcatCatering = VendorCategory::create(['company_id' => $company->id, 'name' => 'Gourmet Catering & Mixology']);
        $vcatPhoto = VendorCategory::create(['company_id' => $company->id, 'name' => 'Cinematography & Photography']);
        $vcatArtist = VendorCategory::create(['company_id' => $company->id, 'name' => 'Celebrity & Artist Booking']);

        $vendor1 = Vendor::create([
            'company_id' => $company->id,
            'vendor_category_id' => $vcatAudio->id,
            'vendor_code' => 'VND-AV-01',
            'name' => 'SoundCraft Pro Audio & Trussing',
            'business_name' => 'SoundCraft Pro Audio Pvt Ltd',
            'email' => 'bookings@soundcraftpro.in',
            'phone' => '+91 98206 44444',
            'city' => 'Mumbai',
            'rating' => 4.90,
            'services_offered' => 'Line Array JBL VTX, Shure Axient Digital, GrandMA3 Lighting console, P3 LED Wall',
            'payment_terms' => '50% advance upon contract, 50% post-event within 7 days',
            'status' => 'active',
        ]);

        $vendor2 = Vendor::create([
            'company_id' => $company->id,
            'vendor_category_id' => $vcatDecor->id,
            'vendor_code' => 'VND-DEC-01',
            'name' => 'Floral Symphony Studios',
            'business_name' => 'Floral Symphony Luxury Decor',
            'email' => 'design@floralsymphony.in',
            'phone' => '+91 98207 33333',
            'city' => 'Mumbai',
            'rating' => 4.85,
            'services_offered' => 'Imported Dutch florals, Crystal chandeliers, Royal mandap fabrication, Thematic lighting',
            'payment_terms' => '40% advance, 40% on load-in, 20% post event',
            'status' => 'active',
        ]);

        $vendor3 = Vendor::create([
            'company_id' => $company->id,
            'vendor_category_id' => $vcatCatering->id,
            'vendor_code' => 'VND-CAT-01',
            'name' => 'Royal Saffron Gourmet Caterers',
            'business_name' => 'Royal Saffron Hospitality',
            'email' => 'cheftable@royalsaffron.com',
            'phone' => '+91 98208 22222',
            'city' => 'Mumbai',
            'rating' => 4.95,
            'services_offered' => 'Pan-Indian royal thalis, Continental live counters, Molecular gastronomy desserts, Barcraft',
            'payment_terms' => '50% advance, balance on event morning',
            'status' => 'active',
        ]);

        $vendor4 = Vendor::create([
            'company_id' => $company->id,
            'vendor_category_id' => $vcatPhoto->id,
            'vendor_code' => 'VND-MED-01',
            'name' => 'CinePixel Studios & Drone Works',
            'business_name' => 'CinePixel Cinema Works',
            'email' => 'hello@cinepixel.com',
            'phone' => '+91 98209 11111',
            'city' => 'Mumbai',
            'rating' => 4.90,
            'services_offered' => '4K RED Cinema cameras, DJI Inspire 3 Drones, Same-day video edit teaser, VR Live 360',
            'payment_terms' => '50% advance, balance on raw footage delivery',
            'status' => 'active',
        ]);

        // 11. Service Packages
        $servCat1 = ServiceCategory::create(['company_id' => $company->id, 'name' => 'Audio-Visual & Staging']);
        Service::create([
            'company_id' => $company->id,
            'service_category_id' => $servCat1->id,
            'name' => 'High-Resolution 4K P2.6 Indoor LED Screen (40x12 ft)',
            'code' => 'SRV-LED-01',
            'base_price' => 180000.00,
            'unit' => 'per_day',
            'description' => 'Ultra high definition video wall with Novastar 4K processors and rigging.'
        ]);
        Service::create([
            'company_id' => $company->id,
            'service_category_id' => $servCat1->id,
            'name' => 'Concert Grade JBL Line Array & Subwoofer Rig (10,000 Pax)',
            'code' => 'SRV-SND-01',
            'base_price' => 250000.00,
            'unit' => 'per_day',
            'description' => 'Twin 12-box line arrays, 16 dual 18-inch subwoofers, digital stage boxes and mic package.'
        ]);

        EventPackage::create([
            'company_id' => $company->id,
            'event_category_id' => $catWedding->id,
            'name' => 'The Imperial Royale Destination Wedding',
            'package_type' => 'luxury',
            'base_price' => 3500000.00,
            'guest_count_included' => 400,
            'features' => "Complete 3-Day Event Design\nRoyal Mandap with Exotic Floral Canopy\nConcert-grade Sangeet Stage & Intelligent Lighting\nDrone 4K Wedding Film & Pre-wedding shoot\nVIP Hospitality Concierge & Luggage Tagging\nBaraat Vintage Car & Dhol Ensemble",
            'description' => 'Turnkey end-to-end luxury wedding curation with dedicated celebrity event planners.',
            'is_active' => true,
        ]);

        EventPackage::create([
            'company_id' => $company->id,
            'event_category_id' => $catCorporate->id,
            'name' => 'TechSummit Enterprise 360',
            'package_type' => 'premium',
            'base_price' => 2200000.00,
            'guest_count_included' => 800,
            'features' => "Multi-track AV & Plenary Stage Setup\nSimultaneous Zoom/YouTube Hybrid Live Stream\nAutomated QR Registration & RFID Smart Badges\nVIP Speaker Greenroom Catering\nBreakout Session Audio Kits",
            'description' => 'Designed for multinational enterprise conferences, annual developer summits, and industry expos.',
            'is_active' => true,
        ]);

        // 12. Create the 7 Demo Events
        // Event 1: Wedding Event
        $eventWedding = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client1->id,
            'event_category_id' => $catWedding->id,
            'venue_id' => $venue1->id,
            'manager_id' => $managerUser->id,
            'event_code' => 'EVT-2026-WED-001',
            'title' => 'Royal Heritage Wedding of Aryan & Sanjana',
            'event_type' => 'wedding',
            'start_date' => Carbon::now()->addDays(14)->setTime(16, 0),
            'end_date' => Carbon::now()->addDays(16)->setTime(23, 0),
            'expected_guests' => 450,
            'confirmed_guests' => 420,
            'estimated_budget' => 3800000.00,
            'actual_budget' => 3500000.00,
            'contract_value' => 4200000.00,
            'amount_received' => 2500000.00,
            'status' => 'confirmed',
            'theme_concept' => 'Royal Rajasthani Palace Theme with Mogra, Rose Quartz & Brass Accents',
            'description' => 'A grand 3-day royal heritage celebration with Mehendi by the poolside, Sangeet in the Grand Ballroom, and Varmala at sunset.',
        ]);

        // Event 2: Corporate Conference
        $eventCorp = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchBlr->id,
            'client_id' => $client2->id,
            'event_category_id' => $catCorporate->id,
            'venue_id' => $venue3->id,
            'manager_id' => $managerUser->id,
            'event_code' => 'EVT-2026-COR-002',
            'title' => 'Global Tech Innovators Summit 2026',
            'event_type' => 'conference',
            'start_date' => Carbon::now()->addDays(25)->setTime(9, 0),
            'end_date' => Carbon::now()->addDays(26)->setTime(18, 0),
            'expected_guests' => 800,
            'confirmed_guests' => 740,
            'estimated_budget' => 2400000.00,
            'actual_budget' => 2200000.00,
            'contract_value' => 2750000.00,
            'amount_received' => 2750000.00,
            'status' => 'in_planning',
            'theme_concept' => 'Futuristic Cyberpunk Neon & Clean Silicon Glass',
            'description' => 'Premier technology conference featuring keynote speakers from top AI labs, venture capital pitch sessions, and product showcases.',
        ]);

        // Event 3: Birthday Party
        $eventBirthday = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client1->id,
            'event_category_id' => $catPrivate->id,
            'venue_id' => $venue1->id,
            'manager_id' => $managerUser->id,
            'event_code' => 'EVT-2026-BDY-003',
            'title' => 'Golden Jubilee 50th Birthday Celebration of Mr. Vikram Oberoi',
            'event_type' => 'birthday',
            'start_date' => Carbon::now()->addDays(8)->setTime(19, 0),
            'end_date' => Carbon::now()->addDays(8)->setTime(23, 59),
            'expected_guests' => 150,
            'confirmed_guests' => 135,
            'estimated_budget' => 900000.00,
            'actual_budget' => 850000.00,
            'contract_value' => 1050000.00,
            'amount_received' => 700000.00,
            'status' => 'confirmed',
            'theme_concept' => 'The Great Gatsby Vintage Gold & Black Tie Gala',
            'description' => 'Exclusive 50th birthday gala featuring live 7-piece jazz band, molecular cocktails, and gourmet degustation.',
        ]);

        // Event 4: Music Concert
        $eventConcert = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client3->id,
            'event_category_id' => $catConcert->id,
            'venue_id' => $venue4->id,
            'manager_id' => $managerUser->id,
            'event_code' => 'EVT-2026-CON-004',
            'title' => 'Sunwave Sunset Live Music & EDM Festival',
            'event_type' => 'concert',
            'start_date' => Carbon::now()->addDays(40)->setTime(15, 0),
            'end_date' => Carbon::now()->addDays(41)->setTime(23, 0),
            'expected_guests' => 4500,
            'confirmed_guests' => 3800,
            'estimated_budget' => 6000000.00,
            'actual_budget' => 5800000.00,
            'contract_value' => 7200000.00,
            'amount_received' => 4000000.00,
            'status' => 'confirmed',
            'theme_concept' => 'Bohemian Coastal Sunset & Psychedelic Laser Arena',
            'description' => 'Two-day beachfront electronic dance music spectacle headlined by international DJs with synchronized pyrotechnics.',
        ]);

        // Event 5: College Festival
        $eventCollege = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'client_id' => $client3->id,
            'event_category_id' => $catCollege->id,
            'venue_id' => $venue2->id,
            'manager_id' => $salesUser->id,
            'event_code' => 'EVT-2026-CLG-005',
            'title' => 'Ignite 2026 National Inter-College Youth & Cultural Fest',
            'event_type' => 'festival',
            'start_date' => Carbon::now()->addDays(32)->setTime(10, 0),
            'end_date' => Carbon::now()->addDays(34)->setTime(22, 0),
            'expected_guests' => 3000,
            'confirmed_guests' => 2600,
            'estimated_budget' => 1600000.00,
            'actual_budget' => 1500000.00,
            'contract_value' => 1850000.00,
            'amount_received' => 1850000.00,
            'status' => 'in_planning',
            'theme_concept' => 'Neon Street Beat & Youth Empowerment',
            'description' => '3-day collegiate competition covering battle of bands, fashion runway, choreography showcase, and celebrity concert night.',
        ]);

        // Event 6: Exhibition
        $eventExhibition = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'client_id' => $client3->id,
            'event_category_id' => $catExhibition->id,
            'venue_id' => $venue2->id,
            'manager_id' => $salesUser->id,
            'event_code' => 'EVT-2026-EXH-006',
            'title' => 'International Auto & Next-Gen Mobility Expo 2026',
            'event_type' => 'exhibition',
            'start_date' => Carbon::now()->addDays(50)->setTime(9, 0),
            'end_date' => Carbon::now()->addDays(53)->setTime(19, 0),
            'expected_guests' => 12000,
            'confirmed_guests' => 8500,
            'estimated_budget' => 4500000.00,
            'actual_budget' => 4200000.00,
            'contract_value' => 5500000.00,
            'amount_received' => 2000000.00,
            'status' => 'proposed',
            'theme_concept' => 'Clean Tech, Electric Future & Aerodynamic Curves',
            'description' => 'Asia-Pacific automotive exhibition with 150 manufacturer stalls, test-drive tracks, and EV charging technology summit.',
        ]);

        // Event 7: Product Launch
        $eventLaunch = Event::create([
            'company_id' => $company->id,
            'branch_id' => $branchBlr->id,
            'client_id' => $client2->id,
            'event_category_id' => $catLaunch->id,
            'venue_id' => $venue3->id,
            'manager_id' => $managerUser->id,
            'event_code' => 'EVT-2026-LCH-007',
            'title' => 'Lumina X Next-Gen Smartphone Global Keynote Launch',
            'event_type' => 'product_launch',
            'start_date' => Carbon::now()->subDays(5)->setTime(17, 0),
            'end_date' => Carbon::now()->subDays(5)->setTime(22, 0),
            'expected_guests' => 300,
            'confirmed_guests' => 295,
            'estimated_budget' => 1900000.00,
            'actual_budget' => 1800000.00,
            'contract_value' => 2200000.00,
            'amount_received' => 2200000.00,
            'status' => 'completed',
            'theme_concept' => 'Minimalist Titanium & Prismatic Light Refraction',
            'description' => 'Flawlessly executed global product launch keynote broadcast live worldwide with hands-on demo experience zones.',
        ]);

        // 13. Schedules & Run of Show for Wedding Event
        $day1 = EventDay::create(['event_id' => $eventWedding->id, 'day_number' => 1, 'date' => $eventWedding->start_date->toDateString(), 'title' => 'Mehendi & Welcome Poolside Carnival']);
        $day2 = EventDay::create(['event_id' => $eventWedding->id, 'day_number' => 2, 'date' => $eventWedding->start_date->copy()->addDay()->toDateString(), 'title' => 'Sangeet & Glitz Musical Night']);
        $day3 = EventDay::create(['event_id' => $eventWedding->id, 'day_number' => 3, 'date' => $eventWedding->start_date->copy()->addDays(2)->toDateString(), 'title' => 'The Royal Varmala, Pheras & Grand Reception']);

        EventSchedule::create([
            'event_id' => $eventWedding->id,
            'event_day_id' => $day1->id,
            'start_time' => '16:00:00',
            'end_time' => '19:30:00',
            'title' => 'Sundowner Mehendi & Bohemian Pool Carnival',
            'location_within_venue' => 'Poolside Lawns',
            'speaker_performer' => 'Folk Artists & Live Flute Maestro',
            'activity_description' => 'Traditional mehendi artists, interactive bangle stalls, sangria counters, and acoustic live set.',
            'in_charge_person' => 'Priya Sharma',
            'status' => 'scheduled',
        ]);

        EventSchedule::create([
            'event_id' => $eventWedding->id,
            'event_day_id' => $day2->id,
            'start_time' => '19:30:00',
            'end_time' => '01:00:00',
            'title' => 'The Grand Sangeet & Bollywood Symphony',
            'location_within_venue' => 'Grand Crystal Ballroom',
            'speaker_performer' => 'Celebrity Emcee + DJ Chetas Live',
            'activity_description' => 'Family choreography performances, LED visual countdown, awards ceremony, followed by 3-hour EDM-Bollywood mashup.',
            'in_charge_person' => 'Priya Sharma',
            'status' => 'scheduled',
        ]);

        EventSchedule::create([
            'event_id' => $eventWedding->id,
            'event_day_id' => $day3->id,
            'start_time' => '17:00:00',
            'end_time' => '18:30:00',
            'title' => 'Royal Baraat Procession',
            'location_within_venue' => 'Palace Main Boulevard Gate',
            'speaker_performer' => 'Brass Band & 21-Dhol Ensemble',
            'activity_description' => 'Baraat procession featuring vintage convertible Rolls-Royce, flower petal showers, and energetic family procession.',
            'in_charge_person' => 'Rajesh Verma',
            'status' => 'scheduled',
        ]);

        EventSchedule::create([
            'event_id' => $eventWedding->id,
            'event_day_id' => $day3->id,
            'start_time' => '18:30:00',
            'end_time' => '20:00:00',
            'title' => 'Sunset Varmala & Vedic Pheras',
            'location_within_venue' => 'Lakeside Mandap Arena',
            'speaker_performer' => 'Sitar & Santoor Ensemble',
            'activity_description' => 'Elevated circular hydraulic mandap with 360-degree guest seating, cold pyros during varmala exchange.',
            'in_charge_person' => 'Priya Sharma',
            'status' => 'scheduled',
        ]);

        EventSchedule::create([
            'event_id' => $eventWedding->id,
            'event_day_id' => $day3->id,
            'start_time' => '20:30:00',
            'end_time' => '23:45:00',
            'title' => 'The Imperial Banquet & Gala Dinner',
            'location_within_venue' => 'Imperial Gardens',
            'speaker_performer' => 'Live Symphony Orchestra',
            'activity_description' => '150-dish curated feast featuring royal Awadhi, Gujarati thali, and live European rotisserie counters.',
            'in_charge_person' => 'Anita Desai',
            'status' => 'scheduled',
        ]);

        // 14. Event Tasks
        $tasks = [
            ['title' => 'Finalize 3D Mandap Floral Design & Fabric Swatches', 'priority' => 'high', 'status' => 'completed', 'days' => -2],
            ['title' => 'Confirm Sangeet Sound System Frequency & Microphones', 'priority' => 'urgent', 'status' => 'in_progress', 'days' => 2],
            ['title' => 'Verify 120 Luxury Hotel Room Bookings & Welcome Hampers', 'priority' => 'urgent', 'status' => 'in_progress', 'days' => 3],
            ['title' => 'Procure Police NOC and Fire Safety License for Pyros', 'priority' => 'high', 'status' => 'completed', 'days' => -5],
            ['title' => 'Conduct Dry Run of Live 4K LED Screen Novastar Switchers', 'priority' => 'medium', 'status' => 'pending', 'days' => 5],
            ['title' => 'Dispatch Personal QR Invitation Passes via WhatsApp & Email', 'priority' => 'high', 'status' => 'completed', 'days' => -1],
            ['title' => 'Coordinate VIP Airport Pickups & Chauffeur Fleet Briefing', 'priority' => 'urgent', 'status' => 'pending', 'days' => 6],
        ];

        foreach ($tasks as $t) {
            EventTask::create([
                'event_id' => $eventWedding->id,
                'assigned_to' => $managerUser->id,
                'title' => $t['title'],
                'description' => 'Critical operational milestone for ' . $eventWedding->title,
                'due_date' => Carbon::now()->addDays($t['days'])->toDateString(),
                'priority' => $t['priority'],
                'status' => $t['status'],
            ]);
        }

        // 15. Vendor Assignments
        VendorAssignment::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'vendor_id' => $vendor1->id,
            'service_title' => 'Turnkey Audio, Intelligent Lighting & P2.6 LED Wall Rigging',
            'scope_of_work' => 'Provide full stage truss, line arrays, 40 Moving heads, GrandMA3 control, stage sound engineer.',
            'contract_amount' => 750000.00,
            'paid_amount' => 400000.00,
            'status' => 'in_progress',
        ]);

        VendorAssignment::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'vendor_id' => $vendor2->id,
            'service_title' => 'Palace Royal Mandap, Floral Ceiling Canopy & Ambient Chandeliers',
            'scope_of_work' => 'Curate 10,000 Dutch roses, mogra strings, golden lanterns, stage backdrops across 3 days.',
            'contract_amount' => 1100000.00,
            'paid_amount' => 600000.00,
            'status' => 'contracted',
        ]);

        VendorAssignment::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'vendor_id' => $vendor3->id,
            'service_title' => 'Catering & Hospitality Services for 450 Pax x 3 Days',
            'scope_of_work' => 'Full breakfast, lunch, high tea, dinner buffet, 8 live food stations, trained silver-service staff.',
            'contract_amount' => 1250000.00,
            'paid_amount' => 750000.00,
            'status' => 'contracted',
        ]);

        VendorAssignment::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'vendor_id' => $vendor4->id,
            'service_title' => 'Cinematic 4K Wedding Film, Drone Flyovers & Live Telecast',
            'scope_of_work' => '8-member crew, 2 drones, Jimmy Jib, daily social media reel edits within 4 hours.',
            'contract_amount' => 450000.00,
            'paid_amount' => 250000.00,
            'status' => 'contracted',
        ]);

        // 16. Budgets & Expenses
        $budget = EventBudget::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'total_estimated_income' => 4200000.00,
            'total_estimated_cost' => 3800000.00,
            'total_actual_cost' => 3550000.00,
            'contingency_percent' => 5.00,
            'contingency_amount' => 190000.00,
            'status' => 'active',
        ]);

        $budgetItems = [
            ['category' => 'Venue Rental & Permissions', 'item_name' => 'The Imperial Palace 3-Day Ballroom & Grounds', 'est' => 900000, 'act' => 900000],
            ['category' => 'Decor & Thematic Production', 'item_name' => 'Royal Mandap, Entrance Gate & Stage Decor', 'est' => 1150000, 'act' => 1100000],
            ['category' => 'Audio, Visual & Stage Lighting', 'item_name' => 'Line Arrays, LED Screens & Lighting Truss', 'est' => 800000, 'act' => 750000],
            ['category' => 'Catering & Beverages', 'item_name' => 'Gourmet 3-Day Banquets & Live Street Counters', 'est' => 1300000, 'act' => 1250000],
            ['category' => 'Media & Cinematography', 'item_name' => '4K RED Cameras, Drones & Post Production', 'est' => 500000, 'act' => 450000],
        ];

        foreach ($budgetItems as $bi) {
            EventBudgetItem::create([
                'event_budget_id' => $budget->id,
                'category' => $bi['category'],
                'item_name' => $bi['item_name'],
                'estimated_cost' => $bi['est'],
                'actual_cost' => $bi['act'],
            ]);
        }

        $expCat = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Vendor Advance Payments']);
        $expCatSafety = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Licensing & Government Permissions']);

        EventExpense::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'expense_category_id' => $expCat->id,
            'vendor_id' => $vendor1->id,
            'approved_by' => $financeUser->id,
            'expense_number' => 'EXP-2026-001',
            'title' => '50% Advance for Sound & Lighting Rigging',
            'amount' => 400000.00,
            'incurred_date' => Carbon::now()->subDays(7)->toDateString(),
            'payment_method' => 'bank_transfer',
            'receipt_number' => 'NEFT-SC-998822',
            'status' => 'paid',
        ]);

        EventExpense::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'expense_category_id' => $expCat->id,
            'vendor_id' => $vendor2->id,
            'approved_by' => $financeUser->id,
            'expense_number' => 'EXP-2026-002',
            'title' => 'Floral Import & Fabrication Deposit',
            'amount' => 600000.00,
            'incurred_date' => Carbon::now()->subDays(5)->toDateString(),
            'payment_method' => 'bank_transfer',
            'receipt_number' => 'RTGS-FLR-114477',
            'status' => 'paid',
        ]);

        EventExpense::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'expense_category_id' => $expCatSafety->id,
            'approved_by' => $financeUser->id,
            'expense_number' => 'EXP-2026-003',
            'title' => 'Municipal Corporation & Fire NOC Processing Fees',
            'amount' => 45000.00,
            'incurred_date' => Carbon::now()->subDays(10)->toDateString(),
            'payment_method' => 'upi',
            'receipt_number' => 'GOV-CHQ-332211',
            'status' => 'paid',
        ]);

        // 17. Inventory & Assets
        $invCatAV = InventoryCategory::create(['company_id' => $company->id, 'name' => 'Stage Equipment & Electronics']);
        $invCatFurniture = InventoryCategory::create(['company_id' => $company->id, 'name' => 'Royal Furniture & Props']);

        $item1 = InventoryItem::create([
            'company_id' => $company->id,
            'inventory_category_id' => $invCatAV->id,
            'item_code' => 'INV-LED-P2',
            'name' => 'Modular P2.6 Outdoor/Indoor 500x500mm LED Panels',
            'total_quantity' => 120,
            'available_quantity' => 40,
            'unit' => 'pcs',
            'rental_rate' => 1200.00,
            'replacement_cost' => 35000.00,
            'condition' => 'good',
        ]);

        $item2 = InventoryItem::create([
            'company_id' => $company->id,
            'inventory_category_id' => $invCatFurniture->id,
            'item_code' => 'INV-CHR-GOLD',
            'name' => 'Louis XVI Royal Gold Carved Banquet Chairs with Velvet Cushion',
            'total_quantity' => 500,
            'available_quantity' => 50,
            'unit' => 'pcs',
            'rental_rate' => 250.00,
            'replacement_cost' => 4500.00,
            'condition' => 'good',
        ]);

        InventoryAllocation::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'inventory_item_id' => $item1->id,
            'quantity_allocated' => 80,
            'checkout_time' => Carbon::now()->addDays(13)->setTime(8, 0),
            'return_time' => Carbon::now()->addDays(17)->setTime(18, 0),
            'status' => 'allocated',
        ]);

        InventoryAllocation::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'inventory_item_id' => $item2->id,
            'quantity_allocated' => 450,
            'checkout_time' => Carbon::now()->addDays(13)->setTime(10, 0),
            'return_time' => Carbon::now()->addDays(17)->setTime(14, 0),
            'status' => 'allocated',
        ]);

        // 18. Guests, Groups & RSVPs for Wedding
        $grpVIP = GuestGroup::create(['company_id' => $company->id, 'event_id' => $eventWedding->id, 'name' => 'VIP Dignitaries & Family Heads']);
        $grpBride = GuestGroup::create(['company_id' => $company->id, 'event_id' => $eventWedding->id, 'name' => 'Bride Family & Close Friends']);
        $grpGroom = GuestGroup::create(['company_id' => $company->id, 'event_id' => $eventWedding->id, 'name' => 'Groom Family & Corporate Guests']);

        $sampleGuests = [
            ['name' => 'Dr. Harish & Meera Malhotra', 'email' => 'harish.m@gmail.com', 'phone' => '+91 98210 11223', 'grp' => $grpVIP->id, 'rsvp' => 'attending', 'plus' => 2, 'table' => 'T-01 (VIP)'],
            ['name' => 'Kapil & Ananya Singhania', 'email' => 'kapil.s@singhania.com', 'phone' => '+91 98210 22334', 'grp' => $grpVIP->id, 'rsvp' => 'attending', 'plus' => 1, 'table' => 'T-02 (VIP)'],
            ['name' => 'Rohit Saxena', 'email' => 'rohit.saxena@outlook.com', 'phone' => '+91 98210 33445', 'grp' => $grpGroom->id, 'rsvp' => 'attending', 'plus' => 0, 'table' => 'T-07'],
            ['name' => 'Natasha Mehta', 'email' => 'natasha.m@fashion.in', 'phone' => '+91 98210 44556', 'grp' => $grpBride->id, 'rsvp' => 'attending', 'plus' => 1, 'table' => 'T-11'],
            ['name' => 'Vikram & Sunita Goel', 'email' => 'vgoel@goelgroup.com', 'phone' => '+91 98210 55667', 'grp' => $grpGroom->id, 'rsvp' => 'declined', 'plus' => 0, 'table' => null],
            ['name' => 'Sameer Merchant', 'email' => 'sameer@merchantco.in', 'phone' => '+91 98210 66778', 'grp' => $grpVIP->id, 'rsvp' => 'pending', 'plus' => 1, 'table' => 'T-03 (VIP)'],
        ];

        foreach ($sampleGuests as $idx => $g) {
            EventGuest::create([
                'company_id' => $company->id,
                'event_id' => $eventWedding->id,
                'guest_group_id' => $g['grp'],
                'name' => $g['name'],
                'email' => $g['email'],
                'phone' => $g['phone'],
                'invitation_code' => 'WED-GUEST-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'rsvp_status' => $g['rsvp'],
                'plus_ones' => $g['plus'],
                'table_number' => $g['table'],
                'dietary_preference' => ($idx % 2 === 0) ? 'veg' : 'non_veg',
                'checked_in' => ($idx === 0),
                'checked_in_at' => ($idx === 0) ? Carbon::now()->subHours(2) : null,
            ]);
        }

        // 19. Ticketing for Concert (Sunwave Music Festival)
        $tTypeGA = TicketType::create([
            'company_id' => $company->id,
            'event_id' => $eventConcert->id,
            'name' => 'General Admission 2-Day Season Pass',
            'price' => 2499.00,
            'total_capacity' => 3500,
            'sold_count' => 2950,
            'benefits' => 'Access to Main Arena, Food Village, and Beachside Sunset Deck',
            'is_active' => true,
        ]);

        $tTypeVIP = TicketType::create([
            'company_id' => $company->id,
            'event_id' => $eventConcert->id,
            'name' => 'VIP Backstage Elevated Lounge Pass',
            'price' => 7999.00,
            'total_capacity' => 500,
            'sold_count' => 480,
            'benefits' => 'Elevated Viewing Platform, Dedicated Fast-Track Entry, Complimentary Bar & Artist Meet and Greet',
            'is_active' => true,
        ]);

        Ticket::create([
            'company_id' => $company->id,
            'event_id' => $eventConcert->id,
            'ticket_type_id' => $tTypeVIP->id,
            'ticket_number' => 'TCK-2026-SUN-0001',
            'qr_token' => 'QR-SUNWAVE-8899-A001',
            'holder_name' => 'Armaan Malik',
            'holder_email' => 'armaan@malikstudios.com',
            'holder_phone' => '+91 98111 55667',
            'price_paid' => 7999.00,
            'payment_status' => 'paid',
            'ticket_status' => 'valid',
        ]);

        Ticket::create([
            'company_id' => $company->id,
            'event_id' => $eventConcert->id,
            'ticket_type_id' => $tTypeGA->id,
            'ticket_number' => 'TCK-2026-SUN-0002',
            'qr_token' => 'QR-SUNWAVE-8899-B002',
            'holder_name' => 'Deepak Shenoy',
            'holder_email' => 'deepak@capitalmind.in',
            'holder_phone' => '+91 98111 66778',
            'price_paid' => 2499.00,
            'payment_status' => 'paid',
            'ticket_status' => 'valid',
        ]);

        // 20. Proposals & Quotations
        $proposal = Proposal::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client1->id,
            'event_id' => $eventWedding->id,
            'proposal_number' => 'PROP-2026-0042',
            'title' => 'Proposal for Royal Heritage Wedding of Aryan & Sanjana',
            'total_amount' => 4200000.00,
            'valid_until' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'approved',
            'scope_of_work' => 'Comprehensive turnkey production including venue transformation, floral designs, stage architecture, guest management, and catering coordination.',
            'deliverables' => '3 Days of complete staging, professional sound and intelligent lighting, 4K videography, hospitality desk, luxury vintage car.',
            'terms_and_conditions' => 'Advance 50% on confirmation, 40% two weeks prior to event, 10% on completion.',
        ]);

        $quotation = Quotation::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client1->id,
            'event_id' => $eventWedding->id,
            'proposal_id' => $proposal->id,
            'quotation_number' => 'QTN-2026-0091',
            'issue_date' => Carbon::now()->subDays(15)->toDateString(),
            'valid_until' => Carbon::now()->addDays(15)->toDateString(),
            'subtotal' => 3559322.00,
            'discount' => 0.00,
            'tax_rate' => 18.00,
            'tax_amount' => 640678.00,
            'grand_total' => 4200000.00,
            'status' => 'accepted',
            'notes' => 'Includes all logistics, staging, and 3-day production.',
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'item_name' => 'Royal Heritage Turnkey Wedding Production Package',
            'item_type' => 'package',
            'description' => 'Complete 3-day decor, staging, sound, lighting, artist hospitality and coordination.',
            'quantity' => 1,
            'unit' => 'event',
            'unit_price' => 3559322.00,
            'total_price' => 3559322.00,
        ]);

        // 21. Invoices & Payments
        $inv1 = Invoice::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client1->id,
            'event_id' => $eventWedding->id,
            'invoice_number' => 'INV-2026-0101',
            'invoice_date' => Carbon::now()->subDays(12)->toDateString(),
            'due_date' => Carbon::now()->addDays(2)->toDateString(),
            'subtotal' => 2118644.00,
            'discount' => 0.00,
            'tax_rate' => 18.00,
            'tax_amount' => 381356.00,
            'grand_total' => 2500000.00,
            'paid_amount' => 2500000.00,
            'balance_due' => 0.00,
            'status' => 'paid',
            'notes' => 'First milestone booking advance payment (60%).',
        ]);

        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'Event Confirmation & Staging Advance for Royal Heritage Wedding',
            'quantity' => 1,
            'unit_price' => 2118644.00,
            'total_price' => 2118644.00,
        ]);

        Payment::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'invoice_id' => $inv1->id,
            'client_id' => $client1->id,
            'event_id' => $eventWedding->id,
            'payment_number' => 'PAY-2026-0088',
            'payment_date' => Carbon::now()->subDays(10)->toDateString(),
            'amount' => 2500000.00,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'HDFC-RTGS-9900118822',
            'status' => 'completed',
            'notes' => 'Advance received via RTGS.',
        ]);

        $inv2 = Invoice::create([
            'company_id' => $company->id,
            'branch_id' => $branchMumbai->id,
            'client_id' => $client1->id,
            'event_id' => $eventWedding->id,
            'invoice_number' => 'INV-2026-0102',
            'invoice_date' => Carbon::now()->subDays(2)->toDateString(),
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'subtotal' => 1440678.00,
            'discount' => 0.00,
            'tax_rate' => 18.00,
            'tax_amount' => 259322.00,
            'grand_total' => 1700000.00,
            'paid_amount' => 0.00,
            'balance_due' => 1700000.00,
            'status' => 'unpaid',
            'notes' => 'Second & final milestone invoice due prior to load-in.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'description' => 'Balance Settlement & Production Execution Milestone',
            'quantity' => 1,
            'unit_price' => 1440678.00,
            'total_price' => 1440678.00,
        ]);

        // Catering Menus
        CateringMenu::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'menu_name' => 'The Royal Rajwada 7-Course Banquet',
            'meal_type' => 'dinner',
            'per_plate_cost' => 2800.00,
            'expected_plates' => 450,
            'menu_items' => "Welcome Drinks: Kesariya Thandai, Blue Curacao Mocktail\nStarters: Paneer Angara, Dahi Ke Kebab, Galouti Kebabs with Sheermal\nMain Course: Dal Makhani Bukhara, Shahi Paneer Lababdar, Awadhi Dum Biryani with Burani Raita, Rogan Josh\nBreads: Zafrani Naan, Missi Roti, Laccha Paratha\nDesserts: Jalebi with Rabdi, Gulab Jamun, Saffron Matka Kulfi, Paan Station",
            'status' => 'confirmed',
        ]);

        // Decoration Themes
        DecorationTheme::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'theme_name' => 'Royal Mewar Palace & Rose Gold Splendor',
            'color_palette' => 'Royal Crimson Red, Rose Gold, Warm Amber, Antique Brass',
            'floral_requirements' => 'Red Ecuadorian Roses, Mogra garlands, White Hydrangeas, Brass Lotus urns',
            'lighting_concept' => '300 Warm Edison Filament Bulbs, Amber LED uplighting on pillars, moving beams',
            'estimated_cost' => 1100000.00,
            'status' => 'approved',
        ]);

        // Staff Shifts
        StaffShift::create([
            'company_id' => $company->id,
            'event_id' => $eventWedding->id,
            'user_id' => $managerUser->id,
            'role_title' => 'Main Stage & Varmala Director',
            'shift_date' => $eventWedding->start_date->copy()->addDays(2)->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '23:59:00',
            'attendance_status' => 'scheduled',
            'allowance' => 2500.00,
        ]);

        // System Settings
        SystemSetting::create(['company_id' => $company->id, 'key' => 'company_name', 'value' => 'GrandVibe Events & Productions']);
        SystemSetting::create(['company_id' => $company->id, 'key' => 'tax_rate_gst', 'value' => '18']);
        SystemSetting::create(['company_id' => $company->id, 'key' => 'currency_symbol', 'value' => '₹']);
        SystemSetting::create(['company_id' => $company->id, 'key' => 'default_booking_advance_percent', 'value' => '50']);
    }
}
