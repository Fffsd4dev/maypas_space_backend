<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\SetEstateManagerFromUrl as setEstate;
use App\Http\Middleware\EnsureAdmin as Admin;
use App\Http\Middleware\SanitizeInput as Sanitize;
use App\Http\Middleware\EnsureLandlord as Landlord;
use App\Http\Middleware\EnsureTenant as Tenant;
use App\Http\Middleware\EnsureTenantAdmin as AllAdmins;


use App\Http\Controllers\Api\V1\{SystemAdminAuthController, 
    AdminRolesController, 
    AdminController, 
    UserController, 
    UserAuthController,
    PropertyController,
    EstateManagerController,
    LandlordController,
    ApartmentController,
    RentManagerController,
    RentCycleController,
    TenantController,
    UserTypeController,
    LandlordAgentController,
    AmenityController,
    ComplaintController,
    ComplaintResponseController,
    NotificationController,
    GuarantorController,
    SpecialtyController,
    TechnicianController,
    MaintenanceRequestController,
    MaintenanceLogController,
    ChargeController,
    DocumentController,
    InvoiceController,
    BrandController,
    Test,
    UnsignedDocumentController,
    DocumentSigningController,
    DocumentSigningRequestController,
    SignedDocumentController,
    StreamingController,
    SubscriptionController,
    SubscriptionCycleController,
    KycRequestController,
    LocationController,
    BranchController
};




Route::middleware('auth:sanctum')->group(function () {
        
         Route::get('/amenity/all', [AmenityController::class, 'index']);
        Route::get('/amenity/get/{id}', [AmenityController::class, 'show']);
         //get all apartments rented by a tenant
       
});

Route::prefix('system-admin')->group(function(){
    Route::post('/login', [SystemAdminAuthController::class, 'login']);
    Route::post('/test/exp', [Test::class, 'testRentExpirationJob']);

    Route::get('/apartment/categories', [ApartmentController::class, 'CategoryIndex']);

    Route::post('/confirm-user', [SystemAdminAuthController::class,'sendOtp']);
    Route::post('/verify-otp', [SystemAdminAuthController::class,'verifyOtp']);
    Route::post('/reset-password', [SystemAdminAuthController::class,'passwordReset']);
    Route::get('/test/background', [ApartmentController::class, 'testRent']);

    Route::middleware(['auth:sanctum', Admin::class])->group(function(){
        Route::post('/subscription/plan/create', [SubscriptionController::class, 'store']);
        Route::get('subscription/plan/view/all', [SubscriptionController::class, 'index']);
        Route::post('subscription/plan/update/{id}', [SubscriptionController::class, 'update']);
        Route::post('subscription/plan/delete/{id}', [SubscriptionController::class, 'destroy']);

    Route::post('/subscription/subscribe-by-admin', [SubscriptionCycleController::class, 'subscribe_by_admin']);
    Route::post('/subscription/suspend/{id}', [SubscriptionCycleController::class, 'suspend']);
    Route::post('/subscription/lift-suspension', [SubscriptionCycleController::class, 'lift_suspension_by_admin']);
    Route::post('/subscription/cancel/{id}', [SubscriptionCycleController::class, 'cancel']);
        
        Route::post('/logout', [SystemAdminAuthController::class, 'logout']);
        //System Admin Roles Crud
        Route::post('/create-role', [AdminRolesController::class,'create']);
        Route::put('/update-role/{id}', [AdminRolesController::class,'update']);
        Route::delete('/delete-role', [AdminRolesController::class,'destroy']);
        Route::get('/view-roles', [AdminRolesController::class,'viewAll']);
        Route::get('/view-role/{id}', [AdminRolesController::class,'viewOne']);

        //System Admin Crud
        Route::post('/create-admin', [AdminController::class,'create']);
        Route::put('/update-admin/{id}', [AdminController::class,'update']);
        Route::delete('/delete-admin/{id}', [AdminController::class,'destroy']);
        Route::get('/view-admins', [AdminController::class,'viewAll']);
        Route::get('/view-admin/{id}', [AdminController::class,'viewOne']);

        //Estate Manager Endpoints
        Route::post('/create-estate-manager', [EstateManagerController::class,'create']);
        Route::patch('/update-estate-manager/{id}', [EstateManagerController::class,'update']);
        Route::get('/view-estate-manager/{id}', [EstateManagerController::class,'getEstateManager']);
        Route::get('/view-estate-managers', [EstateManagerController::class,'getEstateManagers']);
        Route::delete('/delete-estate-manager/{id}', [EstateManagerController::class,'destroy']);

        //Specialist Crud
        Route::post('/specialty/create', [SpecialtyController::class,'store']);
        Route::put('/specialty/update/{id}', [SpecialtyController::class,'update']);
        Route::delete('/specialty/delete/{id}', [SpecialtyController::class,'destroy']);
        Route::get('/view-specialties', [SpecialtyController::class,'index']);
        Route::get('/view-specialty/{id}', [SpecialtyController::class,'show']);

        //Verification of Landlords and Agents
        Route::get('/view-users-for-verification', [LandlordAgentController::class,'fetchLandlordsForVerification']);
        Route::patch('/accept-verification/{id}', [LandlordAgentController::class,'verifyLandlordDocuments']);
        Route::patch('/reject-verification/{id}', [LandlordAgentController::class,'rejectLandlordDocuments']);

//for estate managers


    // Apartment Category routes
        
        Route::post('/apartment/categories', [ApartmentController::class, 'CategoryStore']);
        Route::get('/apartment/category/{id}', [ApartmentController::class, 'CategoryShow']);
        Route::put('/apartment/category/{id}', [ApartmentController::class, 'CategoryUpdate']);
        Route::delete('/apartment/category/{id}', [ApartmentController::class, 'CategoryDestroy']);

        //amenities route

        Route::post('/amenity/create', [AmenityController::class, 'store']);
        Route::put('/amenity/update/{id}', [AmenityController::class, 'update']);
        Route::delete('/amenity/delete/{id}', [AmenityController::class, 'destroy']);

    });
});

//Route to signup estate agent by the estate managers.
Route::post('/estate-manager/sign-up', [EstateManagerController::class,'signUp']);

Route::prefix('{tenant_slug}')->middleware([setEstate::class])->group(function(){
    //Routes that don't need authentication
    Route::post('/login', [UserAuthController::class, 'login']);
    Route::post('/set-password', [UserAuthController::class, 'passwordReset']);
    
    Route::post('/register', [UserAuthController::class, 'create']);
    Route::post('/verify-otp', [UserAuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [UserAuthController::class, 'resendOtp']);

    Route::post('/confirm-user', [UserAuthController::class, 'confirmUser']);
    Route::post('/reset-password', [UserAuthController::class, 'resetPasswordOtp']);
     Route::get('get/brand/data', [BrandController::class, 'getBrandData']);

    Route::prefix('landlord')->group(function(){
        //Routes that don't need authentication for normal Agents and Landlords
        Route::post('/login', [LandlordAgentController::class, 'login']);
        Route::post('/set-password', [LandlordAgentController::class, 'passwordReset']);
        
        Route::post('/register', [LandlordAgentController::class, 'create']);
        Route::post('/verify-otp', [LandlordAgentController::class, 'verifyOtp']);
        Route::post('/resend-otp', [LandlordAgentController::class, 'resendOtp']);

        Route::post('/confirm-user', [LandlordAgentController::class, 'confirmUser']);
        Route::post('/reset-password', [LandlordAgentController::class, 'resetPasswordOtp']);
    });


    //Tenant Self-create
    Route::post('/tenant/sign-up', [TenantController::class, 'selfCreate']);

    //Guarded Routes
    Route::middleware(['auth:sanctum'])->group(function(){
        Route::get('/view-own', [UserController::class, 'viewOwn']);
        Route::put('/update-profile', [UserController::class, 'update']);
        Route::patch('/deactivate-profile', [UserController::class, 'deactivate']);

        Route::post('/add-property', [PropertyController::class, 'store']);

        //User change password
        Route::post('/change-password', [UserAuthController::class, 'changePassword']);
        
        Route::post('/logout', [UserAuthController::class, 'logout']);

        //Tenant routes accessible to any logged in user
        Route::get('/tenant/view/{id}', [TenantController::class, 'show']);
        Route::post('/tenant/update/{id}', [TenantController::class, 'update']);

        Route::post('/tenant/update/personal', [TenantController::class, 'updatePersonal']);
        Route::post('/tenant/create/emergency', [TenantController::class, 'createEmergency']);
        Route::post('/tenant/create/kin', [TenantController::class, 'createKin']);
        Route::post('/tenant/create/document', [TenantController::class, 'createDocument']);
        Route::post('/tenant/create/other-documents', [TenantController::class, 'addOther']);
        //accessible to all under tenant slug;

        Route::get('/apartments/{id}', [ApartmentController::class, 'show']);
        Route::get('/tenant/apartments', [ApartmentController::class, 'getTenantApartments']);

        //Document streaming route
        Route::get('/tenant/documents/{uuid}/view',[StreamingController::class, 'view']);
        Route::get('/tenant/documents/signed/{uuid}/view',[StreamingController::class, 'viewSigned']);

        //Location routes for all logged in users
        Route::get('/location/view-all', [LocationController::class, 'index']);
        Route::get('/location/view/{uuid}', [LocationController::class, 'show']);

        //branches routes for all logged in users
        Route::get('/branch/view-all/{locationUuid}', [BranchController::class, 'index']);
        Route::get('/branch/view/{uuid}', [BranchController::class, 'show']);

        Route::middleware([Tenant::class, Sanitize::class])->group(function(){
            Route::post('/complaint/create/{apartmentUuid}', [ComplaintController::class, 'create']);
            Route::post('/complaint/update/{id}', [ComplaintController::class, 'update']);
            Route::delete('/complaint/delete/{id}', [ComplaintController::class, 'destroy']);
            Route::get('/complaint/view-all', [ComplaintController::class, 'index']);
            Route::get('/complaint/view/{id}', [ComplaintController::class, 'viewOne']);

            //Document Signing
            Route::post('/document/sign', [DocumentSigningController::class,'sign']);
            Route::get('/document/unsigned/view', [DocumentSigningRequestController::class,'tenantViewAllUnsigned']);
            Route::get('/document/unsigned/view/{uuid}', [DocumentSigningRequestController::class,'tenantViewOneUnsigned']);
            Route::get('/document/signed/view', [SignedDocumentController::class,'index']);
            Route::get('/document/signed/view/{uuid}', [SignedDocumentController::class,'show']);


            //Maintenance Crud
            Route::post('/maintenance/create/{apartmentUuid}', [MaintenanceRequestController::class, 'create']);
            Route::post('/maintenance/update/{id}', [MaintenanceRequestController::class, 'update']);
            Route::delete('/maintenance/delete/{id}', [MaintenanceRequestController::class, 'destroy']);
            Route::get('/maintenance/view-all', [MaintenanceRequestController::class, 'index']);
            Route::get('/maintenance/view/{id}', [MaintenanceRequestController::class, 'show']);

            //Guarantors
            Route::post('/guarantor/create', [GuarantorController::class, 'store']);
            Route::put('/guarantor/update/{guarantorId}', [GuarantorController::class, 'update']);
            Route::delete('/guarantor/delete/{guarantorId}', [GuarantorController::class, 'destroy']);
        });
        
        //Routes Accessible to all users in LandlordAgent table
        Route::middleware([AllAdmins::class, Sanitize::class])->group(function(){

            //Routes Accessible to only Landlords and Agents
            Route::middleware(Landlord::class)->group(function(){
                Route::post('/update-estate-manager', [UserController::class, 'completeLandlordProfile']);

                //Landlord or Agent Change password
                Route::post('/landlord/change-password', [LandlordAgentController::class, 'changePassword']);

                //Landlord route Agent Crud
                Route::post('/landlord/create', [LandlordAgentController::class, 'create']);
                Route::get('/landlord/list-all', [LandlordAgentController::class, 'index']);
                Route::get('/landlord/view-one/{uuid}', [LandlordAgentController::class, 'show']);
                Route::get('/landlord/delete/{uuid}', [LandlordAgentController::class, 'destroy']);

                //Temporary route to migrate location
                Route::get('/migrate-apartment-locations', [LandlordAgentController::class, 'migrateApartmentLocations']);

                //Upload documents for verification
                Route::post('/landlord/upload-documents', [LandlordAgentController::class, 'completeLandlordProfile']);

                Route::post('/create-landlord', [LandlordController::class, 'create']);
                Route::put('/update-landlord/{id}', [LandlordController::class, 'update']);
                Route::delete('/delete-landlord', [LandlordController::class, 'destroy']);
                Route::get('/view-landlords', [LandlordController::class, 'viewAll']);
                Route::get('/view-landlord/{id}', [LandlordController::class, 'viewOne']);

                 //Technician Crud
                Route::post('/technician/create', [TechnicianController::class,'store']);
                Route::put('/technician/update/{id}', [TechnicianController::class,'update']);
                Route::delete('/technician/delete/{id}', [TechnicianController::class,'destroy']);
                Route::get('/view-technicians', [TechnicianController::class,'index']);
                Route::get('/view-technician/{id}', [TechnicianController::class,'show']);
                Route::get('/view-specialties', [SpecialtyController::class,'index']);

                 //Landlord Document Crud
                Route::post('/document/create', [DocumentController::class,'store']);

                //New document upload
                Route::post('/document/upload', [UnsignedDocumentController::class,'store']);
                Route::post('/document/update', [UnsignedDocumentController::class,'update']);
                Route::delete('/document/delete/{uuid}', [UnsignedDocumentController::class,'destroy']);
                Route::get('/document/view-all', [UnsignedDocumentController::class,'index']);
                Route::get('/document/view/{uuid}', [UnsignedDocumentController::class,'show']);
                Route::get('/landlord/document/unsigned/view', [DocumentSigningRequestController::class,'viewAllUnsigned']);
                Route::get('/landlord/document/unsigned/view/{uuid}', [DocumentSigningRequestController::class,'viewOneUnsigned']);
                Route::get('/landlord/document/signed/view', [SignedDocumentController::class,'landlordIndex']);
                Route::get('/landlord/document/signed/view/{uuid}', [SignedDocumentController::class,'landlordShow']);


                Route::post('/document/send', [DocumentSigningRequestController::class,'store']);
                //UserType
                Route::post('/user-type/create', [UserTypeController::class, 'create']);
                Route::put('/user-type/update/{id}', [UserTypeController::class, 'update']);
                Route::delete('/user-type/delete/{id}', [UserTypeController::class, 'destroy']);
                Route::get('/user-types/view', [UserTypeController::class, 'viewAll']);
                Route::get('/user-type/view/{id}', [UserTypeController::class, 'viewOne']);

                Route::post('/apartment/create', [ApartmentController::class, 'store']);
                Route::put('/apartments/{id}', [ApartmentController::class, 'update']);
                Route::patch('/assign-apartment/{id}', [ApartmentController::class, 'assignAdminApartment']);
                Route::delete('/apartments/delete/{id}', [ApartmentController::class, 'destroy']);

                //tenant actions for landlord/agent only
                Route::post('/tenant/create', [TenantController::class, 'create']);
                Route::delete('/tenant/delete/{id}', [TenantController::class, 'destroy']);

                //notifications
                Route::get('/notification/list/unread', [NotificationController::class, 'landlordUnread']);
                Route::get('/notification/list/read', [NotificationController::class, 'landlordRead']);
                Route::get('/notification/show/{id}', [NotificationController::class, 'landlordViewOne']);

                Route::prefix('/charges/{unitUuid}')->group(function () {
                    Route::get('/', [ChargeController::class, 'index']);
                    Route::get('/{chargeId}', [ChargeController::class, 'show']);
                    Route::post('/create', [ChargeController::class, 'store']);
                    Route::put('/update/{chargeId}', [ChargeController::class, 'update']);
                    Route::delete('/delete/{chargeId}', [ChargeController::class, 'destroy']);
                });

                //for invoice
                 Route::prefix('/invoice')->group(function () {
                    Route::post('/create', [InvoiceController::class, 'createInvoice']);
                    Route::post('/get/all', [InvoiceController::class, 'getInvoices']);
                    Route::post('/get/single/{id}', [InvoiceController::class, 'getSingleInvoice']);
                     Route::post('/update/single/{id}', [InvoiceController::class, 'updateInvoiceStatus']);
                });
                Route::prefix('/brand')->group(function(){
                    Route::post('/create', [BrandController::class, 'create']);
                    Route::post('/update',[BrandController::class, 'create']);
                    Route::get('/get', [BrandController::class, 'getBrandData']);
            });
            
            
            Route::get('/apartments', [ApartmentController::class, 'index']);
            Route::post('/apartment/create', [ApartmentController::class, 'store']);
            Route::put('/apartments', [ApartmentController::class, 'updateApartment']);
            Route::put('/apartment/unit/update', [ApartmentController::class, 'updateApartmentUnit']);
            Route::delete('/apartment/unit/delete', [ApartmentController::class, 'deleteApartmentUnit']);
            Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy']);

            Route::post('/location/create', [LocationController::class, 'store']);
            Route::post('/location/update/{uuid}', [LocationController::class, 'update']);
            Route::delete('/location/delete/{uuid}', [LocationController::class, 'destroy']);

            //Branches
            Route::post('/branch/create', [BranchController::class, 'store']);
            Route::post('/branch/update/{uuid}', [BranchController::class, 'update']);
            Route::post('/branch/delete/{uuid}', [BranchController::class, 'destroy']);

            //KYC
            Route::get('/kycrequest/list-all', [KycRequestController::class, 'index']);
            Route::get('/kycrequest/view-document/{type}/{filename}', [KycRequestController::class, 'view']);
            Route::patch('/kycrequest/query/{uuid}', [KycRequestController::class, 'query']);
            Route::patch('/kycrequest/approve/{uuid}', [KycRequestController::class, 'approve']);

            //Tenant Routes
            Route::get('/tenants/view', [TenantController::class, 'index']);

              Route::get('rent/account/get/cycles/{id}', [RentManagerController::class, 'showAllCycles']);


            //Landlord Interaction with Complaint
            Route::prefix('landlord')->group(function(){
                Route::prefix('rent/account')->group(function () {
                // List all rent managers (with filters & pagination)
                Route::get('/get', [RentManagerController::class, 'index']);

                    // Create a new rent manager record
                    Route::post('/create', [RentManagerController::class, 'store']);
                    Route::post('/terminate/{id}', [RentManagerController::class, 'terminateAccount']);

                    // Update a rent manager
                    Route::put('cycle/update/{cycle_uuid}', [RentCycleController::class, 'update']);

    // Delete a rent manager and its cycles
                Route::delete('/delete/{id}', [RentManagerController::class, 'destroy']);
                    });



                //rent acount and cycles

                //Landlord Interaction with Complaint
                // Route::prefix('landlord')->group(function(){
                    Route::patch('/complaint/update/{id}', [ComplaintController::class, 'statusUpdate']);
                    Route::delete('/complaint/delete/{id}', [ComplaintController::class, 'landLordDestroy']);
                    Route::get('/complaint/view-all', [ComplaintController::class, 'landlordIndex']);
                    Route::get('/complaint/view/{id}', [ComplaintController::class, 'landlordViewOne']);

                    //Complaint reply
                    Route::post('/complaint-response/create', [ComplaintResponseController::class, 'create']);
                    Route::get('/complaint-response/list/{complaint_id}', [ComplaintResponseController::class, 'index']);
                    Route::post('/complaint-response/update/{complaint_id}/{complaint_response_id}', [ComplaintResponseController::class, 'update']);

                    //Notification
                    Route::get('/notification/list', [NotificationController::class, 'index']);
                    Route::get('/notification/list/unread', [NotificationController::class, 'unread']);
                    Route::get('/notification/show/{id}', [NotificationController::class, 'viewOne']);

                    //Maintenance Module
                    Route::patch('/maintenance/update/{id}', [MaintenanceRequestController::class, 'statusUpdate']);
                    Route::delete('/maintenance/delete/{id}', [MaintenanceRequestController::class, 'destroy']);
                    Route::get('/maintenance/view-all', [MaintenanceRequestController::class, 'index']);
                    Route::get('/maintenance/view/{id}', [MaintenanceRequestController::class, 'show']);

                    //Maintenance Log module
                    Route::post('/maintenance-log/create/{id}', [MaintenanceLogController::class, 'create']);
                    Route::delete('/maintenance-log/delete/{logId}', [MaintenanceLogController::class, 'destroy']);
                    Route::put('/maintenance-log/update/{logId}', [MaintenanceLogController::class, 'update']);
                // });
            });
            
            
            Route::get('/apartments', [ApartmentController::class, 'index']);
            

            //Tenant Routes
            Route::get('/tenants/view', [TenantController::class, 'index']);

            

            
        });

        

    });



});

        //accessible to users that are logged in;


}
);


