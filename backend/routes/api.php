<?php

declare(strict_types=1);

use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\Accounting\AccountingReportsController;
use App\Http\Controllers\Accounting\FiscalPeriodController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\SuenlaceController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AgreementLeaveTypeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\ComunicacionesController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyGroupController;
use App\Http\Controllers\Employee\EmployeeBehaviorController;
use App\Http\Controllers\Employee\EmployeeDocumentController;
use App\Http\Controllers\Employee\EmployeeMaterialController;
use App\Http\Controllers\Employee\EmployeeQualificationController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Gestoria\DownloadTokenController;
use App\Http\Controllers\Gestoria\PayslipController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\PublicDownloadController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\OrgChartController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScheduleTemplateController;
use App\Http\Controllers\Socios\EntityController;
use App\Http\Controllers\Socios\ExpenseCategoryController;
use App\Http\Controllers\Socios\ExpenseController;
use App\Http\Controllers\Socios\MemberController;
use App\Http\Controllers\Socios\MemberPaymentController;
use App\Http\Controllers\Socios\MemberTypeController;
use App\Http\Controllers\Socios\SocioToolsController;
use App\Http\Controllers\Socios\TreasuryController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperAdmin\AuditController as SuperAdminAuditController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\PlanController as SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\SuperAdmin\TenantUserController as SuperAdminTenantUserController;
use App\Http\Controllers\SuperAdmin\TlsCertificateController;
use App\Http\Controllers\TenantModuleController;
use App\Http\Controllers\WorkCalendarController;
use App\Http\Controllers\WorkCenterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Todas las rutas de negocio cuelgan de /api/v1 y pasan por el TenantMiddleware
| (alias `tenant`): el schema PostgreSQL se fija a partir del subdominio antes de
| ejecutar cualquier query.
|
*/

// Registro público de nuevos tenants (sin tenant resuelto): crea el tenant.
Route::prefix('v1')->group(function () {
    Route::get('register/check-subdomain', [RegisterController::class, 'checkSubdomain']);
    Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:10,1');
    Route::get('plans', [RegisterController::class, 'plans']);

    // Logo de marca blanca: público y resuelto por id de tenant en la ruta (lo usa <img>
    // antes de autenticar y desde cualquier origen, sin cabecera de tenant).
    Route::get('branding/{tenant}/logo', [BrandingController::class, 'logo']);
});

Route::prefix('v1')->middleware('tenant')->group(function () {

    Route::prefix('auth')->group(function () {
        // Rutas públicas. `throttle:login` limita a 5 intentos/min por IP.
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('magic-link', [AuthController::class, 'magicLink'])->middleware('throttle:login');
        Route::post('magic-link/verify', [AuthController::class, 'magicLinkVerify']);

        // Rutas autenticadas con token Sanctum.
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Branding del tenant: público (el frontend lo aplica antes de autenticar).
    Route::get('branding', [BrandingController::class, 'show']);

    // Zona pública de descarga: enlace de un solo uso (72 h), sin login.
    Route::get('download/{token}', [PublicDownloadController::class, 'show'])->middleware('throttle:60,1');

    // Fichaje por PIN: público dentro del tenant (kiosk/web/móvil sin sesión).
    Route::get('kiosk/options', [AttendanceController::class, 'kioskOptions']);
    Route::post('attendance/identify', [AttendanceController::class, 'identify'])->middleware('throttle:60,1');
    Route::post('attendance/clock', [AttendanceController::class, 'clock'])->middleware('throttle:60,1');

    // Gestión de fichajes: administradores y coordinadores de RRHH.
    Route::middleware(['auth:sanctum', 'role:admin|super-admin|rrhh-coordinator'])->group(function () {
        Route::get('attendance/daily', [AttendanceController::class, 'daily']);
        Route::post('attendance/manual', [AttendanceController::class, 'manual']);
        Route::get('attendance/{attendance}/corrections', [AttendanceController::class, 'corrections']);
        Route::put('attendance/{attendance}', [AttendanceController::class, 'correct']);
        Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy']);
    });

    // Informes (Sprint 8). Registro horario ET 34.9, diario y resumen de ausencias.
    // La gestoría también puede consultarlos (no modifica fichajes ni empleados).
    Route::middleware(['auth:sanctum', 'role:admin|super-admin|rrhh-coordinator|gestoria'])->group(function () {
        Route::post('reports/work-time-record', [ReportsController::class, 'workTimeRecord']);
        Route::post('reports/daily-attendance', [ReportsController::class, 'dailyAttendance']);
        Route::post('reports/leave-summary', [ReportsController::class, 'leaveSummary']);
    });

    // Panel de gestoría: nóminas y enlaces de descarga (admin + gestoría).
    // Sin acceso a datos sensibles (DNI/IBAN), ni a modificar empleados/fichajes/configuración.
    Route::middleware(['auth:sanctum', 'role:admin|super-admin|gestoria'])->group(function () {
        Route::get('payslips', [PayslipController::class, 'index']);
        Route::post('employees/{employee}/payslips', [PayslipController::class, 'store']);
        Route::get('payslips/{payslip}/download', [PayslipController::class, 'download']);
        Route::delete('payslips/{payslip}', [PayslipController::class, 'destroy']);

        Route::get('download-tokens', [DownloadTokenController::class, 'index']);
        Route::post('download-tokens', [DownloadTokenController::class, 'store']);

        // Exportación contable a a3asesor (suenlace.dat).
        Route::get('accounting/export/suenlace', [SuenlaceController::class, 'export']);
    });

    // Configuración de empresa: solo administradores del tenant.
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
        // Marca blanca: branding y dominio propio.
        Route::put('branding', [BrandingController::class, 'update']);
        Route::post('branding/logo', [BrandingController::class, 'uploadLogo']);

        // Grupos de empresas.
        Route::apiResource('company-groups', CompanyGroupController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['company-groups' => 'companyGroup']);

        Route::post('companies', [CompanyController::class, 'store'])->middleware('plan.limit:companies');
        Route::apiResource('companies', CompanyController::class)->except(['store']);

        // Centros de trabajo: listado/alta anidados bajo la empresa; edición/borrado planos.
        Route::get('companies/{company}/work-centers', [WorkCenterController::class, 'index']);
        Route::post('companies/{company}/work-centers', [WorkCenterController::class, 'store']);
        Route::put('work-centers/{workCenter}', [WorkCenterController::class, 'update']);
        Route::delete('work-centers/{workCenter}', [WorkCenterController::class, 'destroy']);

        Route::apiResource('milestones', MilestoneController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::apiResource('holidays', HolidayController::class)->only(['index', 'store', 'update', 'destroy']);

        // Convenios y sus tipos de ausencia/presencia (anidados para listar/crear).
        Route::apiResource('agreements', AgreementController::class);
        Route::get('agreements/{agreement}/leave-types', [AgreementLeaveTypeController::class, 'index']);
        Route::post('agreements/{agreement}/leave-types', [AgreementLeaveTypeController::class, 'store']);
        Route::put('leave-types/{leaveType}', [AgreementLeaveTypeController::class, 'update']);
        Route::delete('leave-types/{leaveType}', [AgreementLeaveTypeController::class, 'destroy']);

        // Plantillas de horario.
        Route::apiResource('schedule-templates', ScheduleTemplateController::class)
            ->parameters(['schedule-templates' => 'scheduleTemplate']);

        // Calendarios laborales y operaciones de llenado/borrado/clonado/simulación.
        Route::apiResource('calendars', WorkCalendarController::class);
        Route::post('calendars/{calendar}/fill-quick', [WorkCalendarController::class, 'fillQuick']);
        Route::post('calendars/{calendar}/fill-manual', [WorkCalendarController::class, 'fillManual']);
        Route::delete('calendars/{calendar}/clear', [WorkCalendarController::class, 'clear']);
        Route::post('calendars/{calendar}/clone', [WorkCalendarController::class, 'clone']);
        Route::get('calendars/{calendar}/simulate-vacation', [WorkCalendarController::class, 'simulateVacation']);
        Route::post('calendars/{calendar}/employees', [WorkCalendarController::class, 'assignEmployees']);

        // Empleados. Las rutas estáticas van antes del apiResource para que el
        // parámetro {employee} no capture 'template'/'invite'/'import'.
        Route::get('employees/template', [EmployeeController::class, 'template']);
        Route::get('employees/export', [EmployeeController::class, 'export']);
        Route::get('employees/contracts-expiring', [EmployeeController::class, 'expiringContracts']);
        Route::post('employees/invite', [EmployeeController::class, 'invite'])->middleware('plan.limit:employees');
        Route::post('employees/import', [EmployeeController::class, 'import'])->middleware('plan.limit:employees');
        Route::patch('employees/{employee}/activate', [EmployeeController::class, 'activate']);
        Route::patch('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate']);
        Route::get('employees/{employee}/bradford', [EmployeeController::class, 'bradford']);
        Route::post('employees', [EmployeeController::class, 'store'])->middleware('plan.limit:employees');
        Route::apiResource('employees', EmployeeController::class)->except(['store']);

        // Sub-fichas del empleado: formación, materiales, comportamiento y documentos.
        Route::get('employees/{employee}/qualifications', [EmployeeQualificationController::class, 'index']);
        Route::post('employees/{employee}/qualifications', [EmployeeQualificationController::class, 'store']);
        Route::delete('qualifications/{qualification}', [EmployeeQualificationController::class, 'destroy']);

        Route::get('employees/{employee}/materials', [EmployeeMaterialController::class, 'index']);
        Route::post('employees/{employee}/materials', [EmployeeMaterialController::class, 'store']);
        Route::put('materials/{material}', [EmployeeMaterialController::class, 'update']);
        Route::delete('materials/{material}', [EmployeeMaterialController::class, 'destroy']);

        Route::get('employees/{employee}/behavior', [EmployeeBehaviorController::class, 'index']);
        Route::post('employees/{employee}/behavior', [EmployeeBehaviorController::class, 'store']);
        Route::delete('behavior/{behaviorRecord}', [EmployeeBehaviorController::class, 'destroy']);

        Route::get('employees/{employee}/documents', [EmployeeDocumentController::class, 'index']);
        Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store']);
        Route::get('documents/{document}/download', [EmployeeDocumentController::class, 'download']);
        Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy']);
    });

    // Ausencias / presencias. Crear/listar/consultar: cualquier usuario autenticado
    // (el empleado solicita). Aprobar/rechazar/pendientes: solo gestores.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('leave-requests', [LeaveRequestController::class, 'store']);
        Route::get('leave-requests/pending', [LeaveRequestController::class, 'pending'])
            ->middleware('role:admin|super-admin|rrhh-coordinator');
        Route::get('leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
        Route::delete('leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy']);
        Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
            ->middleware('role:admin|super-admin|rrhh-coordinator');
        Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
            ->middleware('role:admin|super-admin|rrhh-coordinator');
        Route::get('employees/{employee}/vacations', [LeaveRequestController::class, 'vacations']);
    });

    // Portal del empleado (cualquier usuario autenticado opera sobre su propia ficha).
    Route::middleware('auth:sanctum')->prefix('me')->group(function () {
        Route::get('/', [MeController::class, 'profile']);
        Route::get('attendances', [MeController::class, 'attendances']);
        Route::get('schedule', [MeController::class, 'schedule']);
        Route::get('leave-types', [MeController::class, 'leaveTypes']);
        Route::get('leave-requests', [MeController::class, 'leaveRequests']);
        Route::post('leave-requests', [MeController::class, 'storeLeaveRequest']);
        Route::get('vacations', [MeController::class, 'vacations']);
        Route::get('payslips', [MeController::class, 'payslips']);
        Route::get('payslips/{payslip}/download', [MeController::class, 'downloadPayslip']);
        Route::put('profile', [MeController::class, 'updateProfile']);
        Route::post('avatar', [MeController::class, 'uploadAvatar']);
        Route::get('avatar', [MeController::class, 'avatar']);
        Route::get('labor', [MeController::class, 'laborData']);
        // Portal del socio (solo lectura).
        Route::get('member', [MeController::class, 'member']);
        Route::get('member/payments', [MeController::class, 'memberPayments']);
    });

    // Organigrama (gestores).
    Route::middleware(['auth:sanctum', 'role:admin|super-admin|rrhh-coordinator'])->group(function () {
        Route::post('org-chart/nodes', [OrgChartController::class, 'store']);
        Route::put('org-chart/nodes/{node}', [OrgChartController::class, 'update']);
        Route::delete('org-chart/nodes/{node}', [OrgChartController::class, 'destroy']);
        Route::patch('org-chart/nodes/{node}/notifications', [OrgChartController::class, 'notifications']);
        Route::get('org-chart/{workCenter}', [OrgChartController::class, 'show']);
    });

    // Módulos del tenant (Configuración). Lectura para gestores; activación solo admin.
    Route::middleware(['auth:sanctum', 'role:admin|super-admin|rrhh-coordinator'])
        ->get('tenant-modules', [TenantModuleController::class, 'index']);
    Route::middleware(['auth:sanctum', 'role:admin|super-admin|rrhh-coordinator'])
        ->get('subscription', [SubscriptionController::class, 'show']);
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])
        ->post('subscription/upgrade-request', [SubscriptionController::class, 'requestUpgrade']);
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])
        ->patch('tenant-modules/{key}', [TenantModuleController::class, 'update']);

    // Módulo Socios / Asociaciones (Sprint 9-10). Gestión por administradores del tenant.
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
        Route::post('entities', [EntityController::class, 'store'])->middleware('plan.limit:entities');
        Route::apiResource('entities', EntityController::class)->except(['store']);

        // Tipos de socio (anidados bajo entidad para listar/crear).
        Route::get('entities/{entity}/member-types', [MemberTypeController::class, 'index']);
        Route::post('entities/{entity}/member-types', [MemberTypeController::class, 'store']);
        Route::put('member-types/{memberType}', [MemberTypeController::class, 'update']);
        Route::delete('member-types/{memberType}', [MemberTypeController::class, 'destroy']);

        // Socios.
        Route::get('entities/{entity}/members', [MemberController::class, 'index']);
        Route::post('entities/{entity}/members', [MemberController::class, 'store'])->middleware('plan.limit:members');
        Route::get('members/{member}', [MemberController::class, 'show']);
        Route::put('members/{member}', [MemberController::class, 'update']);
        Route::delete('members/{member}', [MemberController::class, 'destroy']);

        // Pagos de cuota (anidados bajo socio; listado por entidad para Tesorería).
        Route::get('entities/{entity}/payments', [MemberPaymentController::class, 'byEntity']);
        Route::get('members/{member}/payments', [MemberPaymentController::class, 'index']);
        Route::post('members/{member}/payments', [MemberPaymentController::class, 'store']);
        Route::put('member-payments/{memberPayment}', [MemberPaymentController::class, 'update']);
        Route::delete('member-payments/{memberPayment}', [MemberPaymentController::class, 'destroy']);

        // Categorías de gasto.
        Route::get('entities/{entity}/expense-categories', [ExpenseCategoryController::class, 'index']);
        Route::post('entities/{entity}/expense-categories', [ExpenseCategoryController::class, 'store']);
        Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update']);
        Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy']);

        // Gastos.
        Route::get('entities/{entity}/expenses', [ExpenseController::class, 'index']);
        Route::post('entities/{entity}/expenses', [ExpenseController::class, 'store']);
        Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);

        // Tesorería del ejercicio.
        Route::get('entities/{entity}/treasury', [TreasuryController::class, 'show']);
        Route::get('entities/{entity}/treasury/{year}', [TreasuryController::class, 'show']);

        // Herramientas de socios: PDFs, Excel import/export y backup JSON.
        Route::get('member-payments/{memberPayment}/receipt', [SocioToolsController::class, 'receipt']);
        Route::get('members/{member}/card', [SocioToolsController::class, 'card']);
        Route::get('members/{member}/sheet', [SocioToolsController::class, 'sheet']);
        Route::get('entities/{entity}/members-pdf', [SocioToolsController::class, 'membersPdf']);
        Route::get('entities/members/template', [SocioToolsController::class, 'template']);
        Route::post('entities/{entity}/members/import', [SocioToolsController::class, 'import']);
        Route::get('entities/{entity}/members/export', [SocioToolsController::class, 'export']);
        Route::get('entities/{entity}/backup', [SocioToolsController::class, 'backupExport']);
        Route::post('entities/backup/import', [SocioToolsController::class, 'backupImport']);
    });

    // Comunicaciones: email masivo a socios/empleados, historial y recordatorios de cuota.
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->group(function () {
        Route::get('entities/{entity}/communications/preview-socios', [ComunicacionesController::class, 'previewSocios']);
        Route::post('entities/{entity}/communications/socios', [ComunicacionesController::class, 'sendSocios']);
        Route::get('communications/preview-empleados', [ComunicacionesController::class, 'previewEmpleados']);
        Route::post('communications/empleados', [ComunicacionesController::class, 'sendEmpleados']);
        Route::get('communications', [ComunicacionesController::class, 'history']);
        Route::get('entities/{entity}/quota-reminder', [ComunicacionesController::class, 'reminderShow']);
        Route::put('entities/{entity}/quota-reminder', [ComunicacionesController::class, 'reminderUpdate']);
    });

    // Contabilidad (módulo contabilidad; el menú se gatea por el módulo activo).
    Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('accounting')->group(function () {
        Route::apiResource('accounts', AccountController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['accounts' => 'account']);

        Route::get('journal-entries', [JournalEntryController::class, 'index']);
        Route::post('journal-entries', [JournalEntryController::class, 'store']);
        Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show']);
        Route::put('journal-entries/{journalEntry}', [JournalEntryController::class, 'update']);
        Route::delete('journal-entries/{journalEntry}', [JournalEntryController::class, 'destroy']);

        Route::get('fiscal-periods', [FiscalPeriodController::class, 'index']);
        Route::post('fiscal-periods', [FiscalPeriodController::class, 'store']);
        Route::post('fiscal-periods/{fiscalPeriod}/close', [FiscalPeriodController::class, 'close']);
        Route::post('fiscal-periods/{fiscalPeriod}/reopen', [FiscalPeriodController::class, 'reopen']);

        Route::get('balance-sheet', [AccountingReportsController::class, 'balanceSheet']);
        Route::get('income-statement', [AccountingReportsController::class, 'incomeStatement']);
        Route::get('trial-balance', [AccountingReportsController::class, 'trialBalance']);
        Route::get('ledger', [AccountingReportsController::class, 'ledger']);
    });

    // Panel super-admin (operador Datarecover). Opera sobre datos del schema public.
    Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('superadmin')->group(function () {
        Route::get('dashboard', [SuperAdminDashboardController::class, 'index']);
        Route::apiResource('plans', SuperAdminPlanController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('tenants', [SuperAdminTenantController::class, 'index']);
        Route::post('tenants', [SuperAdminTenantController::class, 'store']);
        Route::get('tenants/{tenant}', [SuperAdminTenantController::class, 'show']);
        Route::put('tenants/{tenant}', [SuperAdminTenantController::class, 'update']);
        Route::delete('tenants/{tenant}', [SuperAdminTenantController::class, 'destroy']);
        Route::post('tenants/{tenant}/impersonate', [SuperAdminTenantController::class, 'impersonate']);
        Route::get('tenants/{tenant}/override', [SuperAdminTenantController::class, 'overrideShow']);
        Route::put('tenants/{tenant}/override', [SuperAdminTenantController::class, 'overrideUpdate']);
        Route::get('tenants/{tenant}/modules', [SuperAdminTenantController::class, 'modules']);
        Route::patch('tenants/{tenant}/modules/{key}', [SuperAdminTenantController::class, 'toggleModule']);

        // Usuarios del tenant.
        Route::get('tenants/{tenant}/users', [SuperAdminTenantUserController::class, 'index']);
        Route::post('tenants/{tenant}/users/{userId}/reset-password', [SuperAdminTenantUserController::class, 'resetPassword']);
        Route::put('tenants/{tenant}/users/{userId}/role', [SuperAdminTenantUserController::class, 'changeRole']);
        Route::patch('tenants/{tenant}/users/{userId}/active', [SuperAdminTenantUserController::class, 'toggleActive']);

        // Auditoría.
        Route::get('audit', [SuperAdminAuditController::class, 'index']);

        // Certificado TLS wildcard de la plataforma.
        Route::get('tls-certificate', [TlsCertificateController::class, 'show']);
        Route::put('tls-certificate', [TlsCertificateController::class, 'update']);
    });

});
