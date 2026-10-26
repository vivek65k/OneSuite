<?php

use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\Crm\AccountController;
use App\Http\Controllers\Admin\Crm\AccountHierarchyController;
use App\Http\Controllers\Admin\Crm\AiAssistantController;
use App\Http\Controllers\Admin\Crm\DealApprovalController;
use App\Http\Controllers\Admin\Crm\DealApprovalMatrixController;
use App\Http\Controllers\Admin\Crm\AiChurnPredictionController;
use App\Http\Controllers\Admin\Crm\AiConversationSummaryController;
use App\Http\Controllers\Admin\Crm\AiLeadScoringController;
use App\Http\Controllers\Admin\Crm\AiNextBestActionController;
use App\Http\Controllers\Admin\Crm\AiSalesAssistantController;
use App\Http\Controllers\Admin\Crm\AiSalesForecastController;
use App\Http\Controllers\Admin\Crm\AiWinProbabilityController;
use App\Http\Controllers\Admin\Crm\ApprovalRuleController as CrmApprovalRuleController;
use App\Http\Controllers\Admin\Crm\AssignmentRuleController;
use App\Http\Controllers\Admin\Crm\CalendarController;
use App\Http\Controllers\Admin\Crm\CallController;
use App\Http\Controllers\Admin\Crm\CampaignController;
use App\Http\Controllers\Admin\Crm\CampaignMemberController;
use App\Http\Controllers\Admin\Crm\CampaignReportController;
use App\Http\Controllers\Admin\Crm\CaseController;
use App\Http\Controllers\Admin\Crm\CompetitorController;
use App\Http\Controllers\Admin\Crm\ComplaintController;
use App\Http\Controllers\Admin\Crm\ContactController;
use App\Http\Controllers\Admin\Crm\CrmAuditLogController;
use App\Http\Controllers\Admin\Crm\CrmCustomFieldController;
use App\Http\Controllers\Admin\Crm\CrmCustomObjectController;
use App\Http\Controllers\Admin\Crm\CrmImportExportController;
use App\Http\Controllers\Admin\Crm\CrmIntegrationController;
use App\Http\Controllers\Admin\Crm\CrmLayoutController;
use App\Http\Controllers\Admin\Crm\CrmQuotaController;
use App\Http\Controllers\Admin\Crm\CrmRoleController;
use App\Http\Controllers\Admin\Crm\CrmSettingsController;
use App\Http\Controllers\Admin\Crm\CrmTeamController;
use App\Http\Controllers\Admin\Crm\CrmTerritoryController;
use App\Http\Controllers\Admin\Crm\ContactRelationshipController;
use App\Http\Controllers\Admin\Crm\ContractController;
use App\Http\Controllers\Admin\Crm\CrmDashboardController;
use App\Http\Controllers\Admin\Crm\CsatController;
use App\Http\Controllers\Admin\Crm\Customer360Controller;
use App\Http\Controllers\Admin\Crm\CustomerFeedbackController;
use App\Http\Controllers\Admin\Crm\CustomerSegmentController;
use App\Http\Controllers\Admin\Crm\CustomReportController;
use App\Http\Controllers\Admin\Crm\CustomerTimelineController;
use App\Http\Controllers\Admin\Crm\DiscountApprovalController;
use App\Http\Controllers\Admin\Crm\DuplicateLeadController;
use App\Http\Controllers\Admin\Crm\EmailController;
use App\Http\Controllers\Admin\Crm\EmailMarketingController;
use App\Http\Controllers\Admin\Crm\EmailSequenceController;
use App\Http\Controllers\Admin\Crm\EscalationController;
use App\Http\Controllers\Admin\Crm\EscalationRuleController as CrmEscalationRuleController;
use App\Http\Controllers\Admin\Crm\ESignatureController;
use App\Http\Controllers\Admin\Crm\FollowUpAutomationController;
use App\Http\Controllers\Admin\Crm\FollowUpReminderController;
use App\Http\Controllers\Admin\Crm\InteractionHistoryController;
use App\Http\Controllers\Admin\Crm\KnowledgeBaseController;
use App\Http\Controllers\Admin\Crm\LandingPageController;
use App\Http\Controllers\Admin\Crm\LeadAssignmentController;
use App\Http\Controllers\Admin\Crm\LeadAttributionController;
use App\Http\Controllers\Admin\Crm\LeadController;
use App\Http\Controllers\Admin\Crm\LeadConversionController;
use App\Http\Controllers\Admin\Crm\LeadConversionReportController;
use App\Http\Controllers\Admin\Crm\LeadQualificationController;
use App\Http\Controllers\Admin\Crm\LeadRecycleController;
use App\Http\Controllers\Admin\Crm\LeadScoringController;
use App\Http\Controllers\Admin\Crm\LeadSourceController;
use App\Http\Controllers\Admin\Crm\MarketingAnalyticsController;
use App\Http\Controllers\Admin\Crm\MeetingController;
use App\Http\Controllers\Admin\Crm\NoteController;
use App\Http\Controllers\Admin\Crm\OpportunityController;
use App\Http\Controllers\Admin\Crm\OpportunityProductController;
use App\Http\Controllers\Admin\Crm\PipelineBoardController;
use App\Http\Controllers\Admin\Crm\PipelineReportController;
use App\Http\Controllers\Admin\Crm\PriceBookController;
use App\Http\Controllers\Admin\Crm\ProductController;
use App\Http\Controllers\Admin\Crm\ProposalController;
use App\Http\Controllers\Admin\Crm\QuotationController;
use App\Http\Controllers\Admin\Crm\RenewalManagementController;
use App\Http\Controllers\Admin\Crm\RevenueReportController;
use App\Http\Controllers\Admin\Crm\SalesForecastController;
use App\Http\Controllers\Admin\Crm\SalesOrderController;
use App\Http\Controllers\Admin\Crm\SalespersonPerformanceController;
use App\Http\Controllers\Admin\Crm\SalesPipelineController;
use App\Http\Controllers\Admin\Crm\SalesReportController;
use App\Http\Controllers\Admin\Crm\SalesStageController;
use App\Http\Controllers\Admin\Crm\ScheduledJobController;
use App\Http\Controllers\Admin\Crm\ScheduledReportController;
use App\Http\Controllers\Admin\Crm\ServiceRequestController;
use App\Http\Controllers\Admin\Crm\SlaManagementController;
use App\Http\Controllers\Admin\Crm\SmsCampaignController;
use App\Http\Controllers\Admin\Crm\TaskController;
use App\Http\Controllers\Admin\Crm\TriggerActionController;
use App\Http\Controllers\Admin\Crm\WebFormController;
use App\Http\Controllers\Admin\Crm\WinLossAnalysisController;
use App\Http\Controllers\Admin\Crm\WorkflowController as CrmWorkflowController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\Hrm\AttendanceDashboardController;
use App\Http\Controllers\Admin\Hrm\AttendanceRecordController;
use App\Http\Controllers\Admin\Hrm\AppraisalController;
use App\Http\Controllers\Admin\Hrm\AssessmentController;
use App\Http\Controllers\Admin\Hrm\AssetCategoryController;
use App\Http\Controllers\Admin\Hrm\AssetController;
use App\Http\Controllers\Admin\Hrm\AssetHistoryController;
use App\Http\Controllers\Admin\Hrm\AssetReturnController;
use App\Http\Controllers\Admin\Hrm\AttendanceApprovalController;
use App\Http\Controllers\Admin\Hrm\AttendanceReportController;
use App\Http\Controllers\Admin\Hrm\AttendanceRuleController;
use App\Http\Controllers\Admin\Hrm\AttritionReportController;
use App\Http\Controllers\Admin\Hrm\BiometricSyncController;
use App\Http\Controllers\Admin\Hrm\BonusController;
use App\Http\Controllers\Admin\Hrm\CandidateController;
use App\Http\Controllers\Admin\Hrm\CertificationController;
use App\Http\Controllers\Admin\Hrm\ClearanceController;
use App\Http\Controllers\Admin\Hrm\ComplianceReportController;
use App\Http\Controllers\Admin\Hrm\CompOffController;
use App\Http\Controllers\Admin\Hrm\CourseController;
use App\Http\Controllers\Admin\Hrm\DeductionController;
use App\Http\Controllers\Admin\Hrm\DiversityReportController;
use App\Http\Controllers\Admin\Hrm\DocumentTemplateController;
use App\Http\Controllers\Admin\Hrm\EmployeeController;
use App\Http\Controllers\Admin\Hrm\EmployeeDocumentCategoryController;
use App\Http\Controllers\Admin\Hrm\EmployeeDocumentController;
use App\Http\Controllers\Admin\Hrm\EmployeeDocumentExpiryController;
use App\Http\Controllers\Admin\Hrm\EmployeeProfileController;
use App\Http\Controllers\Admin\Hrm\EmployeeReportController;
use App\Http\Controllers\Admin\Hrm\EsiController;
use App\Http\Controllers\Admin\Hrm\ExitInterviewController;
use App\Http\Controllers\Admin\Hrm\ExpenseCategoryController;
use App\Http\Controllers\Admin\Hrm\ExpenseClaimController;
use App\Http\Controllers\Admin\Hrm\ExpenseReimbursementController;
use App\Http\Controllers\Admin\Hrm\ExpenseReportController;
use App\Http\Controllers\Admin\Hrm\Feedback360Controller;
use App\Http\Controllers\Admin\Hrm\FinalSettlementController;
use App\Http\Controllers\Admin\Hrm\GoalController;
use App\Http\Controllers\Admin\Hrm\HrmDashboardController;
use App\Http\Controllers\Admin\Hrm\HrPolicyController;
use App\Http\Controllers\Admin\Hrm\IncentiveController;
use App\Http\Controllers\Admin\Hrm\IncrementController;
use App\Http\Controllers\Admin\Hrm\InterviewController;
use App\Http\Controllers\Admin\Hrm\InterviewFeedbackController;
use App\Http\Controllers\Admin\Hrm\JobPositionController;
use App\Http\Controllers\Admin\Hrm\KpiController;
use App\Http\Controllers\Admin\Hrm\LearningProgressController;
use App\Http\Controllers\Admin\Hrm\LeaveBalanceController;
use App\Http\Controllers\Admin\Hrm\LeaveEncashmentController;
use App\Http\Controllers\Admin\Hrm\LeaveReportController;
use App\Http\Controllers\Admin\Hrm\LeaveRequestController;
use App\Http\Controllers\Admin\Hrm\LeaveTypeController;
use App\Http\Controllers\Admin\Hrm\LoanController;
use App\Http\Controllers\Admin\Hrm\LoanRequestController;
use App\Http\Controllers\Admin\Hrm\ManagerReviewController;
use App\Http\Controllers\Admin\Hrm\NoticePeriodController;
use App\Http\Controllers\Admin\Hrm\OfferLetterController;
use App\Http\Controllers\Admin\Hrm\OrgChartController;
use App\Http\Controllers\Admin\Hrm\OnboardingTaskController;
use App\Http\Controllers\Admin\Hrm\OvertimeRequestController;
use App\Http\Controllers\Admin\Hrm\OvertimeRuleController;
use App\Http\Controllers\Admin\Hrm\PayrollDashboardController;
use App\Http\Controllers\Admin\Hrm\PayrollReportController;
use App\Http\Controllers\Admin\Hrm\PayrollRunController;
use App\Http\Controllers\Admin\Hrm\PayslipController;
use App\Http\Controllers\Admin\Hrm\PerformanceReportController;
use App\Http\Controllers\Admin\Hrm\PfController;
use App\Http\Controllers\Admin\Hrm\ProbationController;
use App\Http\Controllers\Admin\Hrm\PromotionController;
use App\Http\Controllers\Admin\Hrm\PromotionRequestController;
use App\Http\Controllers\Admin\Hrm\RecruitmentApprovalController;
use App\Http\Controllers\Admin\Hrm\RecruitmentReportController;
use App\Http\Controllers\Admin\Hrm\RegularizationRequestController;
use App\Http\Controllers\Admin\Hrm\ResignationController;
use App\Http\Controllers\Admin\Hrm\ResumeBankController;
use App\Http\Controllers\Admin\Hrm\SalaryComponentController;
use App\Http\Controllers\Admin\Hrm\SalaryRevisionController;
use App\Http\Controllers\Admin\Hrm\SalaryStructureController;
use App\Http\Controllers\Admin\Hrm\SelfReviewController;
use App\Http\Controllers\Admin\Hrm\SeparationAssetReturnController;
use App\Http\Controllers\Admin\Hrm\SeparationDocumentController;
use App\Http\Controllers\Admin\Hrm\ShiftController;
use App\Http\Controllers\Admin\Hrm\ShiftRosterController;
use App\Http\Controllers\Admin\Hrm\ShiftSwapController;
use App\Http\Controllers\Admin\Hrm\TaxDeclarationController;
use App\Http\Controllers\Admin\Hrm\TaxSlabController;
use App\Http\Controllers\Admin\Hrm\TrainerController;
use App\Http\Controllers\Admin\Hrm\TransferRequestController;
use App\Http\Controllers\Admin\Hrm\TrainingEnrollmentController;
use App\Http\Controllers\Admin\Hrm\TrainingReportController;
use App\Http\Controllers\Admin\Hrm\TrainingSessionController;
use App\Http\Controllers\Admin\Hrm\TravelBookingController;
use App\Http\Controllers\Admin\Hrm\TravelExpenseController;
use App\Http\Controllers\Admin\Hrm\TravelReportController;
use App\Http\Controllers\Admin\Hrm\TravelRequestController;
use App\Http\Controllers\Admin\Hrm\TravelSettlementController;
use App\Http\Controllers\Admin\Hrm\WfhRequestController;
use App\Http\Controllers\Admin\Ess\MyAssetController;
use App\Http\Controllers\Admin\Ess\MyAttendanceController;
use App\Http\Controllers\Admin\Ess\MyDocumentController;
use App\Http\Controllers\Admin\Ess\MyExpenseController;
use App\Http\Controllers\Admin\Ess\MyLeaveController;
use App\Http\Controllers\Admin\Ess\MyPayslipController;
use App\Http\Controllers\Admin\Ess\MyPerformanceController;
use App\Http\Controllers\Admin\Ess\MyProfileController;
use App\Http\Controllers\Admin\Ess\MyRequestController;
use App\Http\Controllers\Admin\Ess\MyTrainingController;
use App\Http\Controllers\Admin\Ess\MyTravelController;
use App\Http\Controllers\Admin\Mss\TeamApprovalController;
use App\Http\Controllers\Admin\Mss\TeamAttendanceController;
use App\Http\Controllers\Admin\Mss\TeamDocumentController;
use App\Http\Controllers\Admin\Mss\TeamGoalController;
use App\Http\Controllers\Admin\Mss\TeamLeaveController;
use App\Http\Controllers\Admin\Mss\TeamPerformanceController;
use App\Http\Controllers\Admin\Mss\TeamReportController;
use App\Http\Controllers\Admin\Inventory\BarcodeController;
use App\Http\Controllers\Admin\Inventory\BatchSerialController;
use App\Http\Controllers\Admin\Inventory\AbcXyzController;
use App\Http\Controllers\Admin\Inventory\CategoryController as InventoryCategoryController;
use App\Http\Controllers\Admin\Inventory\CustomerReturnController;
use App\Http\Controllers\Admin\Inventory\CycleCountController;
use App\Http\Controllers\Admin\Inventory\DeliveryConfirmationController;
use App\Http\Controllers\Admin\Inventory\DemandForecastController;
use App\Http\Controllers\Admin\Inventory\DispatchController;
use App\Http\Controllers\Admin\Inventory\ExpectedReceiptController;
use App\Http\Controllers\Admin\Inventory\ExpiryManagementController;
use App\Http\Controllers\Admin\Inventory\GrnController;
use App\Http\Controllers\Admin\Inventory\IntegrationSettingController;
use App\Http\Controllers\Admin\Inventory\InventoryApprovalRuleController;
use App\Http\Controllers\Admin\Inventory\InventoryDashboardController;
use App\Http\Controllers\Admin\Inventory\InventoryPolicyController;
use App\Http\Controllers\Admin\Inventory\InventoryValuationController;
use App\Http\Controllers\Admin\Inventory\LocationController;
use App\Http\Controllers\Admin\Inventory\NumberingDocumentsController;
use App\Http\Controllers\Admin\Inventory\PackingController;
use App\Http\Controllers\Admin\Inventory\PickingStrategyController;
use App\Http\Controllers\Admin\Inventory\PickListController;
use App\Http\Controllers\Admin\Inventory\PlanningWorkbenchController;
use App\Http\Controllers\Admin\Inventory\ProductController as InventoryProductController;
use App\Http\Controllers\Admin\Inventory\ProductVariantController;
use App\Http\Controllers\Admin\Inventory\PutawayRuleController;
use App\Http\Controllers\Admin\Inventory\QualityInspectionController;
use App\Http\Controllers\Admin\Inventory\ReorderController;
use App\Http\Controllers\Admin\Inventory\ReplenishmentController;
use App\Http\Controllers\Admin\Inventory\ReportsAuditController;
use App\Http\Controllers\Admin\Inventory\SlowDeadStockController;
use App\Http\Controllers\Admin\Inventory\StockAdjustmentController;
use App\Http\Controllers\Admin\Inventory\StockAvailabilityController;
use App\Http\Controllers\Admin\Inventory\StockLedgerController;
use App\Http\Controllers\Admin\Inventory\StockOverviewController;
use App\Http\Controllers\Admin\Inventory\StockReservationController;
use App\Http\Controllers\Admin\Inventory\SupplierProductController;
use App\Http\Controllers\Admin\Inventory\SupplierReturnController;
use App\Http\Controllers\Admin\Inventory\UomController;
use App\Http\Controllers\Admin\Inventory\WarehouseDashboardController;
use App\Http\Controllers\Admin\Inventory\WarehouseTransferController;
use App\Http\Controllers\Admin\Sales\ContractController as SalesContractController;
use App\Http\Controllers\Admin\Sales\ActivityController as SalesActivityController;
use App\Http\Controllers\Admin\Sales\AutomationController as SalesAutomationController;
use App\Http\Controllers\Admin\Sales\FulfilmentController;
use App\Http\Controllers\Admin\Sales\InvoiceController;
use App\Http\Controllers\Admin\Sales\ReportController as SalesAppReportController;
use App\Http\Controllers\Admin\Sales\ReturnComplaintController;
use App\Http\Controllers\Admin\Sales\SettingController as SalesSettingController;
use App\Http\Controllers\Admin\Sales\TeamTargetController;
use App\Http\Controllers\Admin\Sales\PriceListController;
use App\Http\Controllers\Admin\Sales\QuotationController as SalesQuotationController;
use App\Http\Controllers\Admin\Sales\QuotationTemplateController;
use App\Http\Controllers\Admin\Sales\SalesCustomerController;
use App\Http\Controllers\Admin\Sales\SalesDashboardController;
use App\Http\Controllers\Admin\Sales\SalesOrderController as SalesSalesOrderController;
use App\Http\Controllers\Admin\Settings\ApprovalRuleController;
use App\Http\Controllers\Admin\Settings\AutomationRuleController;
use App\Http\Controllers\Admin\Settings\BranchController;
use App\Http\Controllers\Admin\Settings\DashboardController;
use App\Http\Controllers\Admin\Settings\DepartmentController;
use App\Http\Controllers\Admin\Settings\EmailTemplateController;
use App\Http\Controllers\Admin\Settings\EscalationRuleController;
use App\Http\Controllers\Admin\Settings\GeneralSettingsController;
use App\Http\Controllers\Admin\Settings\MailSetupController;
use App\Http\Controllers\Admin\Settings\HolidayController;
use App\Http\Controllers\Admin\Settings\NotificationRuleController;
use App\Http\Controllers\Admin\Settings\RoleController;
use App\Http\Controllers\Admin\Settings\SmsTemplateController;
use App\Http\Controllers\Admin\Settings\UserController;
use App\Http\Controllers\Admin\Settings\WhatsappTemplateController;
use App\Http\Controllers\Admin\Settings\WorkflowController;
use App\Http\Controllers\Admin\Settings\WorkingHourController;
use App\Support\Mail\MailProviders;
use Illuminate\Support\Facades\Route;

Route::post('switch-branch', [BranchSwitchController::class, 'update'])->name('switch-branch');

// Cross-app overview, grouped by app - super admin only (see
// NotificationController::allApps()). Every app's own bell instead
// links to its own {app}/notifications page, registered inside that
// app's own route group below so it's tagged and scoped correctly.
Route::get('notifications', [NotificationController::class, 'allApps'])->name('notifications.index');
Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

Route::group(['app' => 'settings', 'prefix' => 'settings'], function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('settings.notifications.index');

    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::resource('users', UserController::class)->except('show');

    Route::get('roles/export', [RoleController::class, 'export'])->name('roles.export');
    Route::resource('roles', RoleController::class)->except('show');

    Route::get('general-settings', [GeneralSettingsController::class, 'edit'])->name('general-settings.edit');
    Route::put('general-settings', [GeneralSettingsController::class, 'update'])->name('general-settings.update');

    Route::get('mail-setup', [MailSetupController::class, 'show'])->name('mail-setup.show');
    Route::post('mail-setup/{provider}/connect', [MailSetupController::class, 'connect'])->whereIn('provider', MailProviders::keys())->name('mail-setup.connect');
    // The redirect URI registered on each provider's OAuth app - changing this path breaks every client's sign-in.
    Route::get('mail-setup/{provider}/callback', [MailSetupController::class, 'callback'])->whereIn('provider', MailProviders::keys())->name('mail-setup.callback');
    Route::put('mail-setup/smtp', [MailSetupController::class, 'saveSmtp'])->name('mail-setup.smtp');
    Route::post('mail-setup/test', [MailSetupController::class, 'test'])->name('mail-setup.test');
    Route::delete('mail-setup', [MailSetupController::class, 'disconnect'])->name('mail-setup.disconnect');

    Route::get('branches/export', [BranchController::class, 'export'])->name('branches.export');
    Route::resource('branches', BranchController::class)->except('show');

    Route::get('departments/export', [DepartmentController::class, 'export'])->name('departments.export');
    Route::resource('departments', DepartmentController::class)->except('show');

    Route::get('working-hours', [WorkingHourController::class, 'edit'])->name('working-hours.edit');
    Route::put('working-hours', [WorkingHourController::class, 'update'])->name('working-hours.update');

    Route::get('holidays/export', [HolidayController::class, 'export'])->name('holidays.export');
    Route::resource('holidays', HolidayController::class)->except('show');

    Route::get('workflows/export', [WorkflowController::class, 'export'])->name('workflows.export');
    Route::resource('workflows', WorkflowController::class)->except('show');

    Route::get('approval-rules/export', [ApprovalRuleController::class, 'export'])->name('approval-rules.export');
    Route::resource('approval-rules', ApprovalRuleController::class)->except('show');

    Route::get('escalation-rules/export', [EscalationRuleController::class, 'export'])->name('escalation-rules.export');
    Route::resource('escalation-rules', EscalationRuleController::class)->except('show');

    Route::get('automation-rules/export', [AutomationRuleController::class, 'export'])->name('automation-rules.export');
    Route::resource('automation-rules', AutomationRuleController::class)->except('show');

    Route::get('notification-rules/export', [NotificationRuleController::class, 'export'])->name('notification-rules.export');
    Route::resource('notification-rules', NotificationRuleController::class)->except('show');

    Route::get('email-templates/export', [EmailTemplateController::class, 'export'])->name('email-templates.export');
    Route::resource('email-templates', EmailTemplateController::class)->except('show');

    Route::get('sms-templates/export', [SmsTemplateController::class, 'export'])->name('sms-templates.export');
    Route::resource('sms-templates', SmsTemplateController::class)->except('show');

    Route::get('whatsapp-templates/export', [WhatsappTemplateController::class, 'export'])->name('whatsapp-templates.export');
    Route::resource('whatsapp-templates', WhatsappTemplateController::class)->except('show');
});

Route::group(['app' => 'hrm', 'prefix' => 'hrm'], function () {

Route::get('/', [HrmDashboardController::class, 'index'])->name('hrm.dashboard');

Route::get('notifications', [NotificationController::class, 'index'])->name('hrm.notifications.index');

Route::get('employees/export', [EmployeeController::class, 'export'])->name('employees.export');
Route::get('employees/org-chart', [OrgChartController::class, 'index'])->name('employees.org-chart');
Route::post('employees/org-chart/reassign', [OrgChartController::class, 'reassign'])->name('employees.org-chart.reassign');
Route::put('employees/{employee}/manager', [OrgChartController::class, 'update'])->name('employees.manager.update');
Route::resource('employees', EmployeeController::class);

Route::prefix('employees/{employee}')->name('employees.')->group(function () {
    Route::get('timeline', [EmployeeController::class, 'timeline'])->name('timeline');
    Route::get('history', [EmployeeController::class, 'history'])->name('history');

    Route::get('documents', [EmployeeProfileController::class, 'documents'])->name('documents.index');
    Route::post('documents', [EmployeeProfileController::class, 'storeDocument'])->name('documents.store');
    Route::delete('documents/{document}', [EmployeeProfileController::class, 'destroyDocument'])->name('documents.destroy');

    Route::get('assets', [EmployeeProfileController::class, 'assets'])->name('assets.index');
    Route::post('assets', [EmployeeProfileController::class, 'storeAsset'])->name('assets.store');
    Route::delete('assets/{asset}', [EmployeeProfileController::class, 'destroyAsset'])->name('assets.destroy');

    Route::get('family-members', [EmployeeProfileController::class, 'familyMembers'])->name('family-members.index');
    Route::post('family-members', [EmployeeProfileController::class, 'storeFamilyMember'])->name('family-members.store');
    Route::delete('family-members/{familyMember}', [EmployeeProfileController::class, 'destroyFamilyMember'])->name('family-members.destroy');

    Route::get('educations', [EmployeeProfileController::class, 'educations'])->name('educations.index');
    Route::post('educations', [EmployeeProfileController::class, 'storeEducation'])->name('educations.store');
    Route::delete('educations/{education}', [EmployeeProfileController::class, 'destroyEducation'])->name('educations.destroy');

    Route::get('experiences', [EmployeeProfileController::class, 'experiences'])->name('experiences.index');
    Route::post('experiences', [EmployeeProfileController::class, 'storeExperience'])->name('experiences.store');
    Route::delete('experiences/{experience}', [EmployeeProfileController::class, 'destroyExperience'])->name('experiences.destroy');

    Route::get('skills', [EmployeeProfileController::class, 'skills'])->name('skills.index');
    Route::post('skills', [EmployeeProfileController::class, 'storeSkill'])->name('skills.store');
    Route::delete('skills/{skill}', [EmployeeProfileController::class, 'destroySkill'])->name('skills.destroy');

    Route::get('salaries', [EmployeeProfileController::class, 'salaries'])->name('salaries.index');
    Route::post('salaries', [EmployeeProfileController::class, 'storeSalary'])->name('salaries.store');
    Route::delete('salaries/{salary}', [EmployeeProfileController::class, 'destroySalary'])->name('salaries.destroy');

    Route::get('promotions', [EmployeeProfileController::class, 'promotions'])->name('promotions.index');
    Route::post('promotions', [EmployeeProfileController::class, 'storePromotion'])->name('promotions.store');
    Route::delete('promotions/{promotion}', [EmployeeProfileController::class, 'destroyPromotion'])->name('promotions.destroy');

    Route::get('transfers', [EmployeeProfileController::class, 'transfers'])->name('transfers.index');
    Route::post('transfers', [EmployeeProfileController::class, 'storeTransfer'])->name('transfers.store');
    Route::delete('transfers/{transfer}', [EmployeeProfileController::class, 'destroyTransfer'])->name('transfers.destroy');

    Route::get('onboarding', [EmployeeProfileController::class, 'onboarding'])->name('onboarding.index');
    Route::post('onboarding/tasks', [EmployeeProfileController::class, 'storeOnboardingTask'])->name('onboarding.tasks.store');
    Route::post('onboarding/probation', [EmployeeProfileController::class, 'storeProbation'])->name('onboarding.probation.store');
});

Route::get('job-positions/open', [JobPositionController::class, 'openJobs'])->name('job-positions.open');
Route::get('job-positions/export', [JobPositionController::class, 'export'])->name('job-positions.export');
Route::resource('job-positions', JobPositionController::class)->except('show');

Route::get('candidates/pipeline', [CandidateController::class, 'pipeline'])->name('candidates.pipeline');
Route::get('candidates/export', [CandidateController::class, 'export'])->name('candidates.export');
Route::patch('candidates/{candidate}/stage', [CandidateController::class, 'updateStage'])->name('candidates.stage');
Route::resource('candidates', CandidateController::class)->except('show');

Route::get('resume-bank/export', [ResumeBankController::class, 'export'])->name('resume-bank.export');
Route::resource('resume-bank', ResumeBankController::class)->except('show');

Route::get('interviews/export', [InterviewController::class, 'export'])->name('interviews.export');
Route::resource('interviews', InterviewController::class)->except('show');

Route::get('interview-feedback/export', [InterviewFeedbackController::class, 'export'])->name('interview-feedback.export');
Route::resource('interview-feedback', InterviewFeedbackController::class)->except('show');

Route::get('offer-letters/export', [OfferLetterController::class, 'export'])->name('offer-letters.export');
Route::patch('offer-letters/{offerLetter}/status', [OfferLetterController::class, 'updateStatus'])->name('offer-letters.status');
Route::resource('offer-letters', OfferLetterController::class)->except('show');

Route::get('recruitment-reports', [RecruitmentReportController::class, 'index'])->name('recruitment-reports.index');

Route::prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingTaskController::class, 'overview'])->name('overview');
    Route::get('manager-tasks', [OnboardingTaskController::class, 'managerTasks'])->name('manager-tasks');
    Route::get('hr-tasks', [OnboardingTaskController::class, 'hrTasks'])->name('hr-tasks');

    Route::post('tasks/{onboardingTask}/toggle', [OnboardingTaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('tasks/{onboardingTask}', [OnboardingTaskController::class, 'destroy'])->name('tasks.destroy');

    Route::get('{category}', [OnboardingTaskController::class, 'index'])->name('category.index');
    Route::post('{category}', [OnboardingTaskController::class, 'store'])->name('category.store');
});

Route::get('probation-tracking', [ProbationController::class, 'index'])->name('probation-records.index');
Route::get('probation-tracking/create', [ProbationController::class, 'create'])->name('probation-records.create');
Route::post('probation-tracking', [ProbationController::class, 'store'])->name('probation-records.store');
Route::get('probation-tracking/{probationRecord}/edit', [ProbationController::class, 'edit'])->name('probation-records.edit');
Route::put('probation-tracking/{probationRecord}', [ProbationController::class, 'update'])->name('probation-records.update');
Route::delete('probation-tracking/{probationRecord}', [ProbationController::class, 'destroy'])->name('probation-records.destroy');
Route::patch('probation-tracking/{probationRecord}/status', [ProbationController::class, 'updateStatus'])->name('probation-records.status');

Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [AttendanceDashboardController::class, 'index'])->name('dashboard');

    Route::get('daily', [AttendanceRecordController::class, 'daily'])->name('daily');
    Route::post('daily', [AttendanceRecordController::class, 'markDaily'])->name('daily.mark');
    Route::post('daily/bulk', [AttendanceRecordController::class, 'markBulk'])->name('daily.bulk');

    Route::get('monthly', [AttendanceRecordController::class, 'monthly'])->name('monthly');

    Route::get('punch-logs/export', [AttendanceRecordController::class, 'export'])->name('punch-logs.export');
    Route::get('punch-logs', [AttendanceRecordController::class, 'punchLogs'])->name('punch-logs');

    Route::get('geo', [AttendanceRecordController::class, 'geo'])->name('geo');

    Route::get('biometric-sync', [BiometricSyncController::class, 'index'])->name('biometric-sync');
    Route::post('biometric-sync', [BiometricSyncController::class, 'store'])->name('biometric-sync.store');

    Route::get('reports/export', [AttendanceReportController::class, 'export'])->name('reports.export');
    Route::get('reports', [AttendanceReportController::class, 'index'])->name('reports');
});

Route::get('regularization-requests', [RegularizationRequestController::class, 'index'])->name('regularization-requests.index');
Route::post('regularization-requests', [RegularizationRequestController::class, 'store'])->name('regularization-requests.store');
Route::post('regularization-requests/{regularizationRequest}/approve', [RegularizationRequestController::class, 'approve'])->name('regularization-requests.approve');
Route::post('regularization-requests/{regularizationRequest}/reject', [RegularizationRequestController::class, 'reject'])->name('regularization-requests.reject');
Route::delete('regularization-requests/{regularizationRequest}', [RegularizationRequestController::class, 'destroy'])->name('regularization-requests.destroy');

Route::get('wfh-requests', [WfhRequestController::class, 'index'])->name('wfh-requests.index');
Route::post('wfh-requests', [WfhRequestController::class, 'store'])->name('wfh-requests.store');
Route::post('wfh-requests/{wfhRequest}/approve', [WfhRequestController::class, 'approve'])->name('wfh-requests.approve');
Route::post('wfh-requests/{wfhRequest}/reject', [WfhRequestController::class, 'reject'])->name('wfh-requests.reject');
Route::delete('wfh-requests/{wfhRequest}', [WfhRequestController::class, 'destroy'])->name('wfh-requests.destroy');

Route::get('overtime-requests', [OvertimeRequestController::class, 'index'])->name('overtime-requests.index');
Route::post('overtime-requests', [OvertimeRequestController::class, 'store'])->name('overtime-requests.store');
Route::get('overtime-requests/lookup', [OvertimeRequestController::class, 'lookup'])->name('overtime-requests.lookup');
Route::post('overtime-requests/bulk-approve', [OvertimeRequestController::class, 'bulkApprove'])->name('overtime-requests.bulk-approve');
Route::post('overtime-requests/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve'])->name('overtime-requests.approve');
Route::post('overtime-requests/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject'])->name('overtime-requests.reject');
Route::delete('overtime-requests/{overtimeRequest}', [OvertimeRequestController::class, 'destroy'])->name('overtime-requests.destroy');

Route::get('leave-types/create', [LeaveTypeController::class, 'create'])->name('leave-types.create');
Route::post('leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
Route::get('leave-types/{leaveType}/edit', [LeaveTypeController::class, 'edit'])->name('leave-types.edit');
Route::put('leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
Route::delete('leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
Route::patch('leave-types/{leaveType}/toggle', [LeaveTypeController::class, 'toggle'])->name('leave-types.toggle');
Route::get('leave-types', [LeaveTypeController::class, 'index'])->name('leave-types.index');

Route::get('leave-balances', [LeaveBalanceController::class, 'index'])->name('leave-balances.index');
Route::post('leave-balances', [LeaveBalanceController::class, 'update'])->name('leave-balances.update');
Route::post('leave-balances/allocate', [LeaveBalanceController::class, 'allocate'])->name('leave-balances.allocate');

Route::prefix('leave-requests')->name('leave-requests.')->group(function () {
    Route::get('apply', [LeaveRequestController::class, 'apply'])->name('apply');
    Route::get('approval', [LeaveRequestController::class, 'approval'])->name('approval');
    Route::get('calendar', [LeaveRequestController::class, 'calendar'])->name('calendar');
    Route::post('/', [LeaveRequestController::class, 'store'])->name('store');
    Route::post('bulk-approve', [LeaveRequestController::class, 'bulkApprove'])->name('bulk-approve');
    Route::post('{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approve');
    Route::post('{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('reject');
    Route::delete('{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('destroy');
});

// Friendly short URLs (/hrm/leave/apply etc.) -> the real leave-request pages.
Route::get('leave/{page}', fn (string $page) => redirect()->route("admin.leave-requests.{$page}", request()->query()))
    ->whereIn('page', ['apply', 'approval', 'calendar']);

Route::get('comp-offs', [CompOffController::class, 'index'])->name('comp-offs.index');
Route::post('comp-offs', [CompOffController::class, 'store'])->name('comp-offs.store');
Route::post('comp-offs/{compOff}/approve', [CompOffController::class, 'approve'])->name('comp-offs.approve');
Route::post('comp-offs/{compOff}/reject', [CompOffController::class, 'reject'])->name('comp-offs.reject');
Route::delete('comp-offs/{compOff}', [CompOffController::class, 'destroy'])->name('comp-offs.destroy');

Route::get('leave-encashments', [LeaveEncashmentController::class, 'index'])->name('leave-encashments.index');
Route::post('leave-encashments', [LeaveEncashmentController::class, 'store'])->name('leave-encashments.store');
Route::post('leave-encashments/{leaveEncashment}/approve', [LeaveEncashmentController::class, 'approve'])->name('leave-encashments.approve');
Route::post('leave-encashments/{leaveEncashment}/reject', [LeaveEncashmentController::class, 'reject'])->name('leave-encashments.reject');
Route::delete('leave-encashments/{leaveEncashment}', [LeaveEncashmentController::class, 'destroy'])->name('leave-encashments.destroy');

Route::get('leave-reports/export', [LeaveReportController::class, 'export'])->name('leave-reports.export');
Route::get('leave-reports', [LeaveReportController::class, 'index'])->name('leave-reports.index');

Route::get('shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
Route::get('shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
Route::put('shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
Route::delete('shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');
Route::patch('shifts/{shift}/toggle', [ShiftController::class, 'toggle'])->name('shifts.toggle');
Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');

Route::prefix('shift-roster')->name('shift-roster.')->group(function () {
    Route::get('planning', [ShiftRosterController::class, 'planning'])->name('planning');
    Route::post('update', [ShiftRosterController::class, 'update'])->name('update');
    Route::post('bulk', [ShiftRosterController::class, 'bulk'])->name('bulk');
    Route::post('copy-week', [ShiftRosterController::class, 'copyWeek'])->name('copy-week');
    Route::get('assign', [ShiftRosterController::class, 'assign'])->name('assign');
    Route::post('assign', [ShiftRosterController::class, 'storeAssign'])->name('assign.store');
    Route::get('weekly', [ShiftRosterController::class, 'weekly'])->name('weekly');
    Route::get('night', [ShiftRosterController::class, 'nightShift'])->name('night');
    Route::get('flexible', [ShiftRosterController::class, 'flexibleShift'])->name('flexible');
    // Long-form aliases people type by hand; keep the query string.
    Route::get('night-shift', fn (\Illuminate\Http\Request $request) => redirect()->route('admin.shift-roster.night', $request->query()));    Route::get('flexible-shift', fn (\Illuminate\Http\Request $request) => redirect()->route('admin.shift-roster.flexible', $request->query()));
});

Route::get('shift-swaps', [ShiftSwapController::class, 'index'])->name('shift-swaps.index');
Route::post('shift-swaps', [ShiftSwapController::class, 'store'])->name('shift-swaps.store');
Route::post('shift-swaps/{shiftSwap}/approve', [ShiftSwapController::class, 'approve'])->name('shift-swaps.approve');
Route::post('shift-swaps/{shiftSwap}/reject', [ShiftSwapController::class, 'reject'])->name('shift-swaps.reject');
Route::delete('shift-swaps/{shiftSwap}', [ShiftSwapController::class, 'destroy'])->name('shift-swaps.destroy');

Route::get('overtime-rules/create', [OvertimeRuleController::class, 'create'])->name('overtime-rules.create');
Route::post('overtime-rules', [OvertimeRuleController::class, 'store'])->name('overtime-rules.store');
Route::get('overtime-rules/{overtimeRule}/edit', [OvertimeRuleController::class, 'edit'])->name('overtime-rules.edit');
Route::put('overtime-rules/{overtimeRule}', [OvertimeRuleController::class, 'update'])->name('overtime-rules.update');
Route::delete('overtime-rules/{overtimeRule}', [OvertimeRuleController::class, 'destroy'])->name('overtime-rules.destroy');
Route::patch('overtime-rules/{overtimeRule}/toggle', [OvertimeRuleController::class, 'toggle'])->name('overtime-rules.toggle');
Route::get('leave-policies', [\App\Http\Controllers\Admin\Hrm\LeavePolicyController::class, 'index'])->name('leave-policies.index');
Route::put('leave-policies', [\App\Http\Controllers\Admin\Hrm\LeavePolicyController::class, 'update'])->name('leave-policies.update');

Route::get('approval-levels', [\App\Http\Controllers\Admin\Hrm\ApprovalLevelController::class, 'index'])->name('approval-levels.index');
Route::put('approval-levels', [\App\Http\Controllers\Admin\Hrm\ApprovalLevelController::class, 'update'])->name('approval-levels.update');
// The holiday calendar lives under Settings; /hrm/holidays is a friendly alias.
Route::get('holidays', fn (\Illuminate\Http\Request $request) => redirect()->route('admin.holidays.index', $request->query()));
Route::get('working-hours', fn () => redirect()->route('admin.working-hours.edit'));
Route::get('overtime-rules', [OvertimeRuleController::class, 'index'])->name('overtime-rules.index');

Route::get('payroll-dashboard', [PayrollDashboardController::class, 'index'])->name('payroll-dashboard.index');

Route::get('salary-components', [SalaryComponentController::class, 'index'])->name('salary-components.index');
Route::post('salary-components', [SalaryComponentController::class, 'store'])->name('salary-components.store');
Route::delete('salary-components/{salaryComponent}', [SalaryComponentController::class, 'destroy'])->name('salary-components.destroy');
Route::put('salary-components/{salaryComponent}', [SalaryComponentController::class, 'update'])->name('salary-components.update');
Route::patch('salary-components/{salaryComponent}/toggle', [SalaryComponentController::class, 'toggle'])->name('salary-components.toggle');

Route::get('salary-structures', [SalaryStructureController::class, 'index'])->name('salary-structures.index');
Route::get('salary-structures/{employee}/edit', [SalaryStructureController::class, 'edit'])->name('salary-structures.edit');
Route::post('salary-structures/{employee}', [SalaryStructureController::class, 'update'])->name('salary-structures.update');

Route::get('salary-revisions', [SalaryRevisionController::class, 'index'])->name('salary-revisions.index');
Route::post('salary-revisions', [SalaryRevisionController::class, 'store'])->name('salary-revisions.store');
Route::delete('salary-revisions/{employeeSalary}', [SalaryRevisionController::class, 'destroy'])->name('salary-revisions.destroy');

Route::get('bonuses', [BonusController::class, 'index'])->name('bonuses.index');
Route::post('bonuses', [BonusController::class, 'store'])->name('bonuses.store');
Route::patch('bonuses/{bonus}/move', [BonusController::class, 'move'])->name('bonuses.move');
Route::delete('bonuses/{bonus}', [BonusController::class, 'destroy'])->name('bonuses.destroy');

Route::get('incentives', [IncentiveController::class, 'index'])->name('incentives.index');
Route::post('incentives', [IncentiveController::class, 'store'])->name('incentives.store');
Route::patch('incentives/{incentive}/move', [IncentiveController::class, 'move'])->name('incentives.move');
Route::delete('incentives/{incentive}', [IncentiveController::class, 'destroy'])->name('incentives.destroy');

Route::get('deductions', [DeductionController::class, 'index'])->name('deductions.index');
Route::post('deductions', [DeductionController::class, 'store'])->name('deductions.store');
Route::patch('deductions/{deduction}/move', [DeductionController::class, 'move'])->name('deductions.move');
Route::delete('deductions/{deduction}', [DeductionController::class, 'destroy'])->name('deductions.destroy');

Route::get('loans', [LoanController::class, 'index'])->name('loans.index');
Route::post('loans', [LoanController::class, 'store'])->name('loans.store');
Route::post('loans/{loan}/repay', [LoanController::class, 'repay'])->name('loans.repay');
Route::delete('loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

Route::get('tax-declarations', [TaxDeclarationController::class, 'index'])->name('tax-declarations.index');
Route::post('tax-declarations', [TaxDeclarationController::class, 'store'])->name('tax-declarations.store');
Route::delete('tax-declarations/{taxDeclaration}', [TaxDeclarationController::class, 'destroy'])->name('tax-declarations.destroy');

Route::get('pf', [PfController::class, 'index'])->name('pf.index');
Route::put('pf', [PfController::class, 'update'])->name('pf.update');
Route::get('pf/export', [PfController::class, 'export'])->name('pf.export');

Route::get('esi', [EsiController::class, 'index'])->name('esi.index');
Route::put('esi', [EsiController::class, 'update'])->name('esi.update');
Route::get('esi/export', [EsiController::class, 'export'])->name('esi.export');

Route::get('payroll-runs', [PayrollRunController::class, 'index'])->name('payroll-runs.index');
Route::post('payroll-runs', [PayrollRunController::class, 'store'])->name('payroll-runs.store');
Route::get('payroll-runs/{payrollRun}', [PayrollRunController::class, 'show'])->name('payroll-runs.show');
Route::post('payroll-runs/{payrollRun}/mark-paid', [PayrollRunController::class, 'markPaid'])->name('payroll-runs.mark-paid');

Route::get('payslips', [PayslipController::class, 'index'])->name('payslips.index');
Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');

Route::get('payroll-reports/export', [PayrollReportController::class, 'export'])->name('payroll-reports.export');
Route::get('payroll-reports', [PayrollReportController::class, 'index'])->name('payroll-reports.index');

Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
Route::put('goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
Route::delete('goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');

Route::get('kpis', [KpiController::class, 'index'])->name('kpis.index');
Route::post('kpis', [KpiController::class, 'store'])->name('kpis.store');
Route::put('kpis/{kpi}', [KpiController::class, 'update'])->name('kpis.update');
Route::delete('kpis/{kpi}', [KpiController::class, 'destroy'])->name('kpis.destroy');
Route::post('kpis/assign', [KpiController::class, 'assign'])->name('kpis.assign');
Route::put('employee-kpis/{employeeKpi}', [KpiController::class, 'updateActual'])->name('employee-kpis.update');
Route::delete('employee-kpis/{employeeKpi}', [KpiController::class, 'destroyAssignment'])->name('employee-kpis.destroy');

Route::get('appraisals', [AppraisalController::class, 'index'])->name('appraisals.index');
Route::post('appraisals', [AppraisalController::class, 'store'])->name('appraisals.store');
Route::post('appraisal-cycles', [AppraisalController::class, 'storeCycle'])->name('appraisal-cycles.store');
Route::put('appraisal-cycles/{appraisalCycle}', [AppraisalController::class, 'updateCycle'])->name('appraisal-cycles.update');
Route::delete('appraisal-cycles/{appraisalCycle}', [AppraisalController::class, 'destroyCycle'])->name('appraisal-cycles.destroy');
Route::post('appraisals/{appraisal}/complete', [AppraisalController::class, 'complete'])->name('appraisals.complete');
Route::post('appraisals/{appraisal}/reopen', [AppraisalController::class, 'reopen'])->name('appraisals.reopen');
Route::delete('appraisals/{appraisal}', [AppraisalController::class, 'destroy'])->name('appraisals.destroy');

Route::get('self-reviews', [SelfReviewController::class, 'index'])->name('self-reviews.index');
Route::put('self-reviews/{appraisal}', [SelfReviewController::class, 'update'])->name('self-reviews.update');

Route::get('manager-reviews', [ManagerReviewController::class, 'index'])->name('manager-reviews.index');
Route::put('manager-reviews/{appraisal}', [ManagerReviewController::class, 'update'])->name('manager-reviews.update');

Route::get('feedback-360', [Feedback360Controller::class, 'index'])->name('feedback-360.index');
Route::post('feedback-360', [Feedback360Controller::class, 'store'])->name('feedback-360.store');
Route::delete('feedback-360/{feedback360}', [Feedback360Controller::class, 'destroy'])->name('feedback-360.destroy');

Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index');
Route::post('promotions', [PromotionController::class, 'store'])->name('promotions.store');
Route::delete('promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');

Route::get('increments', [IncrementController::class, 'index'])->name('increments.index');
Route::post('increments', [IncrementController::class, 'store'])->name('increments.store');
Route::delete('increments/{increment}', [IncrementController::class, 'destroy'])->name('increments.destroy');

Route::get('performance-reports/export', [PerformanceReportController::class, 'export'])->name('performance-reports.export');
Route::get('performance-reports', [PerformanceReportController::class, 'index'])->name('performance-reports.index');

Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
Route::patch('courses/{course}/toggle', [CourseController::class, 'toggle'])->name('courses.toggle');
Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
// "Training programs" is what people often call the course catalogue; send that address to it.
Route::get('training-programs', fn () => redirect()->route('admin.courses.index'))->name('training-programs.index');
Route::get('courses', [CourseController::class, 'index'])->name('courses.index');

Route::get('training-sessions', [TrainingSessionController::class, 'index'])->name('training-sessions.index');
Route::post('training-sessions', [TrainingSessionController::class, 'store'])->name('training-sessions.store');
Route::put('training-sessions/{trainingSession}', [TrainingSessionController::class, 'update'])->name('training-sessions.update');
Route::delete('training-sessions/{trainingSession}', [TrainingSessionController::class, 'destroy'])->name('training-sessions.destroy');

Route::get('training-enrollments', [TrainingEnrollmentController::class, 'index'])->name('training-enrollments.index');
Route::post('training-enrollments', [TrainingEnrollmentController::class, 'store'])->name('training-enrollments.store');
Route::post('training-enrollments/sessions/{trainingSession}/complete', [TrainingEnrollmentController::class, 'completeSession'])->name('training-enrollments.complete-session');
Route::put('training-enrollments/{enrollment}', [TrainingEnrollmentController::class, 'update'])->name('training-enrollments.update');
Route::delete('training-enrollments/{enrollment}', [TrainingEnrollmentController::class, 'destroy'])->name('training-enrollments.destroy');

Route::get('certifications', [CertificationController::class, 'index'])->name('certifications.index');
Route::post('certifications', [CertificationController::class, 'store'])->name('certifications.store');
Route::get('certifications/export', [CertificationController::class, 'export'])->name('certifications.export');
Route::post('certifications/{certification}/renew', [CertificationController::class, 'renew'])->name('certifications.renew');
Route::put('certifications/{certification}', [CertificationController::class, 'update'])->name('certifications.update');
Route::delete('certifications/{certification}', [CertificationController::class, 'destroy'])->name('certifications.destroy');

Route::get('assessments', [AssessmentController::class, 'index'])->name('assessments.index');
Route::post('assessments', [AssessmentController::class, 'store'])->name('assessments.store');
Route::put('assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update');
Route::delete('assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');
Route::post('assessment-results', [AssessmentController::class, 'storeResult'])->name('assessment-results.store');
Route::delete('assessment-results/{result}', [AssessmentController::class, 'destroyResult'])->name('assessment-results.destroy');

Route::get('trainers/create', [TrainerController::class, 'create'])->name('trainers.create');
Route::post('trainers', [TrainerController::class, 'store'])->name('trainers.store');
Route::get('trainers/{trainer}/edit', [TrainerController::class, 'edit'])->name('trainers.edit');
Route::put('trainers/{trainer}', [TrainerController::class, 'update'])->name('trainers.update');
Route::delete('trainers/{trainer}', [TrainerController::class, 'destroy'])->name('trainers.destroy');
Route::get('trainers', [TrainerController::class, 'index'])->name('trainers.index');

Route::get('learning-progress/export', [LearningProgressController::class, 'export'])->name('learning-progress.export');
Route::get('learning-progress', [LearningProgressController::class, 'index'])->name('learning-progress.index');

Route::get('training-reports/export', [TrainingReportController::class, 'export'])->name('training-reports.export');
Route::get('training-reports', [TrainingReportController::class, 'index'])->name('training-reports.index');

Route::get('assets/assign', [AssetController::class, 'index'])->name('assets.index');
Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
Route::get('assets', fn () => redirect()->route('admin.assets.index'));
Route::put('assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
Route::post('assets/{asset}/transfer', [AssetController::class, 'transfer'])->name('assets.transfer');
Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

Route::get('assets/laptops', [AssetCategoryController::class, 'laptop'])->name('assets.laptops');
Route::get('assets/mobiles', [AssetCategoryController::class, 'mobile'])->name('assets.mobiles');
Route::get('assets/id-cards', [AssetCategoryController::class, 'idCard'])->name('assets.id-cards');
Route::get('assets/sim-cards', [AssetCategoryController::class, 'simCard'])->name('assets.sim-cards');
Route::get('assets/uniforms', [AssetCategoryController::class, 'uniform'])->name('assets.uniforms');

Route::get('asset-returns', [AssetReturnController::class, 'index'])->name('asset-returns.index');
Route::post('asset-returns/bulk', [AssetReturnController::class, 'bulk'])->name('asset-returns.bulk');
Route::put('asset-returns/{asset}/undo', [AssetReturnController::class, 'undo'])->name('asset-returns.undo');
Route::put('asset-returns/{asset}', [AssetReturnController::class, 'update'])->name('asset-returns.update');

Route::get('asset-history/export', [AssetHistoryController::class, 'export'])->name('asset-history.export');
Route::get('asset-history', [AssetHistoryController::class, 'index'])->name('asset-history.index');

Route::get('travel-requests', [TravelRequestController::class, 'index'])->name('travel-requests.index');
Route::post('travel-requests', [TravelRequestController::class, 'store'])->name('travel-requests.store');
Route::put('travel-requests/{travelRequest}', [TravelRequestController::class, 'update'])->name('travel-requests.update');
Route::delete('travel-requests/{travelRequest}', [TravelRequestController::class, 'destroy'])->name('travel-requests.destroy');

Route::get('travel-approval', [TravelRequestController::class, 'approval'])->name('travel-approval.index');
Route::post('travel-approval/bulk-approve', [TravelRequestController::class, 'bulkApprove'])->name('travel-approval.bulk-approve');
Route::post('travel-approval/{travelRequest}/approve', [TravelRequestController::class, 'approve'])->name('travel-approval.approve');
Route::post('travel-approval/{travelRequest}/reject', [TravelRequestController::class, 'reject'])->name('travel-approval.reject');

Route::get('travel-bookings', [TravelBookingController::class, 'index'])->name('travel-bookings.index');
Route::post('travel-bookings', [TravelBookingController::class, 'store'])->name('travel-bookings.store');
Route::put('travel-bookings/{booking}', [TravelBookingController::class, 'update'])->name('travel-bookings.update');
Route::delete('travel-bookings/{booking}', [TravelBookingController::class, 'destroy'])->name('travel-bookings.destroy');

Route::get('travel-expenses', [TravelExpenseController::class, 'index'])->name('travel-expenses.index');
Route::post('travel-expenses', [TravelExpenseController::class, 'store'])->name('travel-expenses.store');
Route::post('travel-expenses/bulk-approve', [TravelExpenseController::class, 'bulkApprove'])->name('travel-expenses.bulk-approve');
Route::post('travel-expenses/{expense}/approve', [TravelExpenseController::class, 'approve'])->name('travel-expenses.approve');
Route::post('travel-expenses/{expense}/reject', [TravelExpenseController::class, 'reject'])->name('travel-expenses.reject');
Route::delete('travel-expenses/{expense}', [TravelExpenseController::class, 'destroy'])->name('travel-expenses.destroy');

Route::get('travel-settlements', [TravelSettlementController::class, 'index'])->name('travel-settlements.index');
Route::post('travel-settlements/{travelRequest}/reopen', [TravelSettlementController::class, 'reopen'])->name('travel-settlements.reopen');
Route::post('travel-settlements/{travelRequest}', [TravelSettlementController::class, 'settle'])->name('travel-settlements.settle');

Route::get('travel-reports/export', [TravelReportController::class, 'export'])->name('travel-reports.export');
Route::get('travel-reports', [TravelReportController::class, 'index'])->name('travel-reports.index');

Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense-categories.index');
Route::get('expense-categories/create', [ExpenseCategoryController::class, 'create'])->name('expense-categories.create');
Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
Route::get('expense-categories/{expenseCategory}/edit', [ExpenseCategoryController::class, 'edit'])->name('expense-categories.edit');
Route::patch('expense-categories/{expenseCategory}/toggle', [ExpenseCategoryController::class, 'toggle'])->name('expense-categories.toggle');
Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');

Route::get('expense-claims', [ExpenseClaimController::class, 'index'])->name('expense-claims.index');
Route::post('expense-claims', [ExpenseClaimController::class, 'store'])->name('expense-claims.store');
Route::put('expense-claims/{expenseClaim}', [ExpenseClaimController::class, 'update'])->name('expense-claims.update');
Route::delete('expense-claims/{expenseClaim}', [ExpenseClaimController::class, 'destroy'])->name('expense-claims.destroy');

Route::get('expense-approval', [ExpenseClaimController::class, 'approval'])->name('expense-approval.index');
Route::post('expense-approval/bulk-approve', [ExpenseClaimController::class, 'bulkApprove'])->name('expense-approval.bulk-approve');
Route::post('expense-approval/{expenseClaim}/approve', [ExpenseClaimController::class, 'approve'])->name('expense-approval.approve');
Route::post('expense-approval/{expenseClaim}/reject', [ExpenseClaimController::class, 'reject'])->name('expense-approval.reject');

Route::get('expense-reimbursements', [ExpenseReimbursementController::class, 'index'])->name('expense-reimbursements.index');
Route::post('expense-reimbursements/bulk', [ExpenseReimbursementController::class, 'bulk'])->name('expense-reimbursements.bulk');
Route::post('expense-reimbursements/{expenseClaim}/undo', [ExpenseReimbursementController::class, 'undo'])->name('expense-reimbursements.undo');
Route::post('expense-reimbursements/{expenseClaim}', [ExpenseReimbursementController::class, 'reimburse'])->name('expense-reimbursements.reimburse');

Route::get('expense-reports/export', [ExpenseReportController::class, 'export'])->name('expense-reports.export');
Route::get('expense-reports', [ExpenseReportController::class, 'index'])->name('expense-reports.index');

Route::get('documents/offer-letters', [EmployeeDocumentCategoryController::class, 'offerLetter'])->name('documents.offer-letters');
Route::get('documents/appointment-letters', [EmployeeDocumentCategoryController::class, 'appointmentLetter'])->name('documents.appointment-letters');
Route::get('documents/contracts', [EmployeeDocumentCategoryController::class, 'contract'])->name('documents.contracts');
Route::get('documents/pan', [EmployeeDocumentCategoryController::class, 'pan'])->name('documents.pan');
Route::get('documents/aadhar', [EmployeeDocumentCategoryController::class, 'aadhar'])->name('documents.aadhar');
Route::get('documents/passport', [EmployeeDocumentCategoryController::class, 'passport'])->name('documents.passport');
Route::get('documents/visa', [EmployeeDocumentCategoryController::class, 'visa'])->name('documents.visa');
Route::get('documents/certificates', [EmployeeDocumentCategoryController::class, 'certificate'])->name('documents.certificates');
Route::get('documents/experience-letters', [EmployeeDocumentCategoryController::class, 'experienceLetter'])->name('documents.experience-letters');
Route::get('documents/salary-slips', [EmployeeDocumentCategoryController::class, 'salarySlip'])->name('documents.salary-slips');
Route::get('documents', fn () => redirect()->route('admin.documents.offer-letters'));
Route::get('documents/{document}/file', [EmployeeDocumentController::class, 'file'])->whereNumber('document')->name('documents.file');
Route::put('documents/{document}', [EmployeeDocumentController::class, 'update'])->name('documents.update');
Route::post('documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
Route::delete('documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');

Route::get('document-expiry/export', [EmployeeDocumentExpiryController::class, 'export'])->name('document-expiry.export');
Route::get('document-expiry', [EmployeeDocumentExpiryController::class, 'index'])->name('document-expiry.index');

// Employee Self Service (ESS) - every route here resolves "which employee"
// from the logged-in user's own linked Employee record, never from a picker.
Route::get('my-profile', [MyProfileController::class, 'index'])->name('my-profile.index');
Route::put('my-profile', [MyProfileController::class, 'update'])->name('my-profile.update');
Route::post('my-profile/photo', [MyProfileController::class, 'photo'])->name('my-profile.photo');
Route::delete('my-profile/photo', [MyProfileController::class, 'removePhoto'])->name('my-profile.photo.remove');

Route::get('my-attendance', [MyAttendanceController::class, 'index'])->name('my-attendance.index');

Route::get('my-leave', [MyLeaveController::class, 'index'])->name('my-leave.index');
Route::post('my-leave', [MyLeaveController::class, 'store'])->name('my-leave.store');
Route::delete('my-leave/{leaveRequest}', [MyLeaveController::class, 'cancel'])->whereNumber('leaveRequest')->name('my-leave.cancel');

Route::get('my-payslips', [MyPayslipController::class, 'index'])->name('my-payslips.index');
Route::get('my-payslips/export', [MyPayslipController::class, 'export'])->name('my-payslips.export');
Route::get('my-payslips/{payslip}', [MyPayslipController::class, 'show'])->whereNumber('payslip')->name('my-payslips.show');

Route::get('my-documents', [MyDocumentController::class, 'index'])->name('my-documents.index');
Route::post('my-documents', [MyDocumentController::class, 'store'])->name('my-documents.store');
Route::put('my-documents/{document}', [MyDocumentController::class, 'update'])->whereNumber('document')->name('my-documents.update');
Route::delete('my-documents/{document}', [MyDocumentController::class, 'destroy'])->whereNumber('document')->name('my-documents.destroy');

Route::get('my-assets', [MyAssetController::class, 'index'])->name('my-assets.index');
Route::post('my-assets/{asset}/report', [MyAssetController::class, 'report'])->whereNumber('asset')->name('my-assets.report');

Route::get('my-training', [MyTrainingController::class, 'index'])->name('my-training.index');
Route::post('my-training/{enrollment}/start', [MyTrainingController::class, 'start'])->whereNumber('enrollment')->name('my-training.start');
Route::post('my-training/{enrollment}/withdraw', [MyTrainingController::class, 'withdraw'])->whereNumber('enrollment')->name('my-training.withdraw');

Route::get('my-performance', [MyPerformanceController::class, 'index'])->name('my-performance.index');
Route::post('my-performance/{appraisal}/self-review', [MyPerformanceController::class, 'submitSelfReview'])->whereNumber('appraisal')->name('my-performance.self-review');
Route::put('my-performance/goals/{goal}', [MyPerformanceController::class, 'updateGoal'])->whereNumber('goal')->name('my-performance.goal');

Route::get('my-expenses', [MyExpenseController::class, 'index'])->name('my-expenses.index');
Route::post('my-expenses', [MyExpenseController::class, 'store'])->name('my-expenses.store');
Route::put('my-expenses/{expenseClaim}', [MyExpenseController::class, 'update'])->whereNumber('expenseClaim')->name('my-expenses.update');
Route::delete('my-expenses/{expenseClaim}', [MyExpenseController::class, 'destroy'])->whereNumber('expenseClaim')->name('my-expenses.destroy');

Route::get('my-travel', [MyTravelController::class, 'index'])->name('my-travel.index');
Route::post('my-travel', [MyTravelController::class, 'store'])->name('my-travel.store');
Route::put('my-travel/{travelRequest}', [MyTravelController::class, 'update'])->whereNumber('travelRequest')->name('my-travel.update');
Route::delete('my-travel/{travelRequest}', [MyTravelController::class, 'destroy'])->whereNumber('travelRequest')->name('my-travel.destroy');
Route::post('my-travel/{travelRequest}/expenses', [MyTravelController::class, 'storeExpense'])->whereNumber('travelRequest')->name('my-travel.expenses.store');
Route::delete('my-travel/{travelRequest}/expenses/{expense}', [MyTravelController::class, 'destroyExpense'])->whereNumber(['travelRequest', 'expense'])->name('my-travel.expenses.destroy');

Route::get('my-requests', [MyRequestController::class, 'index'])->name('my-requests.index');
Route::post('my-requests/overtime', [MyRequestController::class, 'storeOvertime'])->name('my-requests.overtime');
Route::post('my-requests/wfh', [MyRequestController::class, 'storeWfh'])->name('my-requests.wfh');
Route::post('my-requests/regularization', [MyRequestController::class, 'storeRegularization'])->name('my-requests.regularization');
Route::delete('my-requests/{type}/{id}', [MyRequestController::class, 'destroy'])->whereIn('type', ['regularization', 'wfh', 'overtime'])->whereNumber('id')->name('my-requests.destroy');

// Manager Self Service (MSS) - every route here scopes "team" to the
// logged-in user's own direct reports (Employee::directReports()), never
// to the whole company - see ResolvesCurrentEmployee::teamEmployeeIds().
Route::get('team-attendance/export', [TeamAttendanceController::class, 'export'])->name('team-attendance.export');
Route::get('team-attendance', [TeamAttendanceController::class, 'index'])->name('team-attendance.index');

Route::get('team-leave', [TeamLeaveController::class, 'index'])->name('team-leave.index');

Route::get('team-approvals', [TeamApprovalController::class, 'index'])->name('team-approvals.index');
Route::post('team-approvals/bulk-approve', [TeamApprovalController::class, 'bulkApprove'])->name('team-approvals.bulk-approve');
Route::post('team-approvals/leave/{leaveRequest}/approve', [TeamApprovalController::class, 'approveLeave'])->whereNumber('leaveRequest')->name('team-approvals.leave.approve');
Route::post('team-approvals/leave/{leaveRequest}/reject', [TeamApprovalController::class, 'rejectLeave'])->whereNumber('leaveRequest')->name('team-approvals.leave.reject');
Route::post('team-approvals/overtime/{overtimeRequest}/approve', [TeamApprovalController::class, 'approveOvertime'])->whereNumber('overtimeRequest')->name('team-approvals.overtime.approve');
Route::post('team-approvals/overtime/{overtimeRequest}/reject', [TeamApprovalController::class, 'rejectOvertime'])->whereNumber('overtimeRequest')->name('team-approvals.overtime.reject');
Route::post('team-approvals/wfh/{wfhRequest}/approve', [TeamApprovalController::class, 'approveWfh'])->whereNumber('wfhRequest')->name('team-approvals.wfh.approve');
Route::post('team-approvals/wfh/{wfhRequest}/reject', [TeamApprovalController::class, 'rejectWfh'])->whereNumber('wfhRequest')->name('team-approvals.wfh.reject');
Route::post('team-approvals/regularization/{regularizationRequest}/approve', [TeamApprovalController::class, 'approveRegularization'])->whereNumber('regularizationRequest')->name('team-approvals.regularization.approve');
Route::post('team-approvals/regularization/{regularizationRequest}/reject', [TeamApprovalController::class, 'rejectRegularization'])->whereNumber('regularizationRequest')->name('team-approvals.regularization.reject');
Route::post('team-approvals/travel/{travelRequest}/approve', [TeamApprovalController::class, 'approveTravel'])->whereNumber('travelRequest')->name('team-approvals.travel.approve');
Route::post('team-approvals/travel/{travelRequest}/reject', [TeamApprovalController::class, 'rejectTravel'])->whereNumber('travelRequest')->name('team-approvals.travel.reject');
Route::post('team-approvals/expense/{expenseClaim}/approve', [TeamApprovalController::class, 'approveExpense'])->whereNumber('expenseClaim')->name('team-approvals.expense.approve');
Route::post('team-approvals/expense/{expenseClaim}/reject', [TeamApprovalController::class, 'rejectExpense'])->whereNumber('expenseClaim')->name('team-approvals.expense.reject');

Route::get('team-performance', [TeamPerformanceController::class, 'index'])->name('team-performance.index');
Route::post('team-performance/{appraisal}/review', [TeamPerformanceController::class, 'submitManagerReview'])->whereNumber('appraisal')->name('team-performance.review');

Route::get('team-goals', [TeamGoalController::class, 'index'])->name('team-goals.index');
Route::post('team-goals', [TeamGoalController::class, 'store'])->name('team-goals.store');
Route::put('team-goals/{goal}', [TeamGoalController::class, 'update'])->whereNumber('goal')->name('team-goals.update');
Route::delete('team-goals/{goal}', [TeamGoalController::class, 'destroy'])->whereNumber('goal')->name('team-goals.destroy');

Route::get('team-documents', [TeamDocumentController::class, 'index'])->name('team-documents.index');

Route::get('team-reports/export', [TeamReportController::class, 'export'])->name('team-reports.export');
Route::get('team-reports', [TeamReportController::class, 'index'])->name('team-reports.index');

Route::get('resignations', [ResignationController::class, 'index'])->name('resignations.index');
Route::post('resignations', [ResignationController::class, 'store'])->name('resignations.store');
Route::post('resignations/{resignation}/accept', [ResignationController::class, 'accept'])->name('resignations.accept');
Route::post('resignations/{resignation}/withdraw', [ResignationController::class, 'withdraw'])->name('resignations.withdraw');
Route::post('resignations/{resignation}/complete', [ResignationController::class, 'complete'])->name('resignations.complete');
Route::put('resignations/{resignation}', [ResignationController::class, 'update'])->name('resignations.update');
Route::delete('resignations/{resignation}', [ResignationController::class, 'destroy'])->name('resignations.destroy');

Route::get('notice-period', [NoticePeriodController::class, 'index'])->name('notice-period.index');
Route::put('notice-period/{resignation}', [NoticePeriodController::class, 'updateLastWorkingDay'])->name('notice-period.update');

Route::get('clearances', [ClearanceController::class, 'index'])->name('clearances.index');
Route::post('clearances/resignation/{resignation}/clear-all', [ClearanceController::class, 'clearAll'])->name('clearances.clear-all');
Route::post('clearances/{clearance}/toggle', [ClearanceController::class, 'toggle'])->name('clearances.toggle');

Route::get('exit-interviews', [ExitInterviewController::class, 'index'])->name('exit-interviews.index');
Route::delete('exit-interviews/{resignation}', [ExitInterviewController::class, 'destroy'])->name('exit-interviews.destroy');
Route::post('exit-interviews/{resignation}', [ExitInterviewController::class, 'store'])->name('exit-interviews.store');

Route::get('separation-asset-returns', [SeparationAssetReturnController::class, 'index'])->name('separation-asset-returns.index');
Route::put('separation-asset-returns/{asset}', [SeparationAssetReturnController::class, 'update'])->name('separation-asset-returns.update');

Route::get('final-settlements', [FinalSettlementController::class, 'index'])->name('final-settlements.index');
Route::post('final-settlements/{resignation}/process', [FinalSettlementController::class, 'process'])->name('final-settlements.process');
Route::post('final-settlements/{settlement}/mark-paid', [FinalSettlementController::class, 'markPaid'])->name('final-settlements.mark-paid');

Route::get('separation-documents/experience-letter', [SeparationDocumentController::class, 'experienceLetter'])->name('separation-documents.experience-letter');
Route::get('separation-documents/relieving-letter', [SeparationDocumentController::class, 'relievingLetter'])->name('separation-documents.relieving-letter');
Route::post('separation-documents', [SeparationDocumentController::class, 'store'])->name('separation-documents.store');
Route::delete('separation-documents/{document}', [SeparationDocumentController::class, 'destroy'])->name('separation-documents.destroy');

Route::get('employee-reports/export', [EmployeeReportController::class, 'export'])->name('employee-reports.export');
Route::get('employee-reports', [EmployeeReportController::class, 'index'])->name('employee-reports.index');

Route::get('attrition-reports/export', [AttritionReportController::class, 'export'])->name('attrition-reports.export');
Route::get('attrition-reports', [AttritionReportController::class, 'index'])->name('attrition-reports.index');

Route::get('diversity-reports/export', [DiversityReportController::class, 'export'])->name('diversity-reports.export');
Route::get('diversity-reports', [DiversityReportController::class, 'index'])->name('diversity-reports.index');

Route::get('compliance-reports/export', [ComplianceReportController::class, 'export'])->name('compliance-reports.export');
Route::get('compliance-reports', [ComplianceReportController::class, 'index'])->name('compliance-reports.index');

Route::get('resignation-approval', [ResignationController::class, 'approval'])->name('resignation-approval.index');

Route::get('attendance-approval', [AttendanceApprovalController::class, 'index'])->name('attendance-approval.index');

Route::get('recruitment-approval', [RecruitmentApprovalController::class, 'index'])->name('recruitment-approval.index');
Route::post('recruitment-approval/{offerLetter}/approve', [RecruitmentApprovalController::class, 'approve'])->name('recruitment-approval.approve');
Route::post('recruitment-approval/{offerLetter}/reject', [RecruitmentApprovalController::class, 'reject'])->name('recruitment-approval.reject');

Route::get('loan-requests', [LoanRequestController::class, 'index'])->name('loan-requests.index');
Route::post('loan-requests', [LoanRequestController::class, 'store'])->name('loan-requests.store');
Route::post('loan-requests/{loanRequest}/approve', [LoanRequestController::class, 'approve'])->name('loan-requests.approve');
Route::post('loan-requests/{loanRequest}/reject', [LoanRequestController::class, 'reject'])->name('loan-requests.reject');

Route::get('promotion-requests', [PromotionRequestController::class, 'index'])->name('promotion-requests.index');
Route::post('promotion-requests', [PromotionRequestController::class, 'store'])->name('promotion-requests.store');
Route::delete('promotion-requests/{promotionRequest}', [PromotionRequestController::class, 'destroy'])->whereNumber('promotionRequest')->name('promotion-requests.destroy');
Route::post('promotion-requests/{promotionRequest}/approve', [PromotionRequestController::class, 'approve'])->whereNumber('promotionRequest')->name('promotion-requests.approve');
Route::post('promotion-requests/{promotionRequest}/reject', [PromotionRequestController::class, 'reject'])->whereNumber('promotionRequest')->name('promotion-requests.reject');

Route::get('transfer-requests', [TransferRequestController::class, 'index'])->name('transfer-requests.index');
Route::post('transfer-requests', [TransferRequestController::class, 'store'])->name('transfer-requests.store');
Route::delete('transfer-requests/{transferRequest}', [TransferRequestController::class, 'destroy'])->whereNumber('transferRequest')->name('transfer-requests.destroy');
Route::post('transfer-requests/{transferRequest}/approve', [TransferRequestController::class, 'approve'])->whereNumber('transferRequest')->name('transfer-requests.approve');
Route::post('transfer-requests/{transferRequest}/reject', [TransferRequestController::class, 'reject'])->whereNumber('transferRequest')->name('transfer-requests.reject');

Route::get('hr-policies', [HrPolicyController::class, 'index'])->name('hr-policies.index');
Route::get('hr-policies/create', [HrPolicyController::class, 'create'])->name('hr-policies.create');
Route::post('hr-policies', [HrPolicyController::class, 'store'])->name('hr-policies.store');
Route::get('hr-policies/{hrPolicy}/edit', [HrPolicyController::class, 'edit'])->name('hr-policies.edit');
Route::put('hr-policies/{hrPolicy}', [HrPolicyController::class, 'update'])->name('hr-policies.update');
Route::delete('hr-policies/{hrPolicy}', [HrPolicyController::class, 'destroy'])->name('hr-policies.destroy');

Route::get('attendance-rules', [AttendanceRuleController::class, 'index'])->name('attendance-rules.index');
Route::put('attendance-rules', [AttendanceRuleController::class, 'update'])->name('attendance-rules.update');

Route::get('tax-slabs', [TaxSlabController::class, 'index'])->name('tax-slabs.index');
Route::get('tax-slabs/create', [TaxSlabController::class, 'create'])->name('tax-slabs.create');
Route::post('tax-slabs', [TaxSlabController::class, 'store'])->name('tax-slabs.store');
Route::post('tax-slabs/fill', [TaxSlabController::class, 'fill'])->name('tax-slabs.fill');
Route::get('tax-slabs/{taxSlab}/edit', [TaxSlabController::class, 'edit'])->name('tax-slabs.edit');
Route::put('tax-slabs/{taxSlab}', [TaxSlabController::class, 'update'])->name('tax-slabs.update');
Route::delete('tax-slabs/{taxSlab}', [TaxSlabController::class, 'destroy'])->name('tax-slabs.destroy');

Route::get('document-templates', [DocumentTemplateController::class, 'index'])->name('document-templates.index');
Route::get('document-templates/create', [DocumentTemplateController::class, 'create'])->name('document-templates.create');
Route::post('document-templates', [DocumentTemplateController::class, 'store'])->name('document-templates.store');
Route::get('document-templates/{documentTemplate}/edit', [DocumentTemplateController::class, 'edit'])->name('document-templates.edit');
Route::put('document-templates/{documentTemplate}', [DocumentTemplateController::class, 'update'])->name('document-templates.update');
Route::delete('document-templates/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])->name('document-templates.destroy');

}); // app => hrm

Route::group(['app' => 'crm', 'prefix' => 'crm'], function () {

Route::get('/', [CrmDashboardController::class, 'index'])->name('crm.dashboard');

Route::get('notifications', [NotificationController::class, 'index'])->name('crm.notifications.index');

Route::get('leads/export', [LeadController::class, 'export'])->name('crm.leads.export');
Route::post('leads/import/preview', [LeadController::class, 'importPreview'])->name('crm.leads.import.preview');
Route::post('leads/import/confirm', [LeadController::class, 'importConfirm'])->name('crm.leads.import.confirm');
Route::post('leads/bulk-action', [LeadController::class, 'bulkAction'])->name('crm.leads.bulk-action');
Route::get('leads/mine', [LeadController::class, 'index'])->name('crm.leads.mine')->defaults('scope', 'mine');
Route::get('leads/unassigned', [LeadController::class, 'index'])->name('crm.leads.unassigned')->defaults('scope', 'unassigned');
Route::resource('leads', LeadController::class)->names('crm.leads');

Route::get('lead-sources', [LeadSourceController::class, 'index'])->name('crm.lead-sources.index');
Route::get('lead-sources/create', [LeadSourceController::class, 'create'])->name('crm.lead-sources.create');
Route::post('lead-sources', [LeadSourceController::class, 'store'])->name('crm.lead-sources.store');
Route::get('lead-sources/{leadSource}/edit', [LeadSourceController::class, 'edit'])->name('crm.lead-sources.edit');
Route::put('lead-sources/{leadSource}', [LeadSourceController::class, 'update'])->name('crm.lead-sources.update');
Route::patch('lead-sources/{leadSource}/toggle', [LeadSourceController::class, 'toggle'])->name('crm.lead-sources.toggle');
Route::delete('lead-sources/{leadSource}', [LeadSourceController::class, 'destroy'])->name('crm.lead-sources.destroy');

Route::get('lead-scoring', [LeadScoringController::class, 'index'])->name('crm.lead-scoring.index');
Route::put('lead-scoring/{lead}', [LeadScoringController::class, 'update'])->name('crm.lead-scoring.update');

Route::get('lead-assignment', [LeadAssignmentController::class, 'index'])->name('crm.lead-assignment.index');
Route::put('lead-assignment/{lead}', [LeadAssignmentController::class, 'update'])->name('crm.lead-assignment.update');

Route::get('lead-qualification', [LeadQualificationController::class, 'index'])->name('crm.lead-qualification.index');
Route::post('lead-qualification/{lead}/qualify', [LeadQualificationController::class, 'qualify'])->name('crm.lead-qualification.qualify');
Route::post('lead-qualification/{lead}/disqualify', [LeadQualificationController::class, 'disqualify'])->name('crm.lead-qualification.disqualify');
Route::post('lead-qualification/{lead}/bant', [LeadQualificationController::class, 'updateBant'])->name('crm.lead-qualification.bant');

Route::get('duplicate-management', [DuplicateLeadController::class, 'index'])->name('crm.duplicate-management.index');
Route::post('duplicate-management/{lead}/mark', [DuplicateLeadController::class, 'markDuplicate'])->name('crm.duplicate-management.mark');
Route::post('duplicate-management/{lead}/unmark', [DuplicateLeadController::class, 'unmarkDuplicate'])->name('crm.duplicate-management.unmark');

Route::get('lead-recycle', [LeadRecycleController::class, 'index'])->name('crm.lead-recycle.index');
Route::post('lead-recycle/{lead}/recycle', [LeadRecycleController::class, 'recycle'])->name('crm.lead-recycle.recycle');
Route::post('lead-recycle/{lead}/reactivate', [LeadRecycleController::class, 'reactivate'])->name('crm.lead-recycle.reactivate');

Route::get('lead-conversion', [LeadConversionController::class, 'index'])->name('crm.lead-conversion.index');
Route::post('lead-conversion/{lead}/convert', [LeadConversionController::class, 'convert'])->name('crm.lead-conversion.convert');

Route::get('accounts/export', [AccountController::class, 'export'])->name('crm.accounts.export');
Route::get('accounts/merge', [AccountController::class, 'mergeForm'])->name('crm.accounts.merge');
Route::post('accounts/merge', [AccountController::class, 'merge'])->name('crm.accounts.merge.confirm');
Route::post('accounts/{account}/share', [AccountController::class, 'share'])->name('crm.accounts.share');
Route::delete('accounts/{account}/share/{accountShare}', [AccountController::class, 'unshare'])->name('crm.accounts.unshare');
Route::resource('accounts', AccountController::class)->except('show')->names('crm.accounts');

Route::get('contacts/export', [ContactController::class, 'export'])->name('crm.contacts.export');
Route::resource('contacts', ContactController::class)->names('crm.contacts');

Route::get('account-hierarchy', [AccountHierarchyController::class, 'index'])->name('crm.account-hierarchy.index');

Route::get('contact-relationships', [ContactRelationshipController::class, 'index'])->name('crm.contact-relationships.index');
Route::post('contact-relationships', [ContactRelationshipController::class, 'store'])->name('crm.contact-relationships.store');
Route::delete('contact-relationships/{contactRelationship}', [ContactRelationshipController::class, 'destroy'])->name('crm.contact-relationships.destroy');

Route::get('customer-segments', [CustomerSegmentController::class, 'index'])->name('crm.customer-segments.index');
Route::get('customer-segments/create', [CustomerSegmentController::class, 'create'])->name('crm.customer-segments.create');
Route::post('customer-segments', [CustomerSegmentController::class, 'store'])->name('crm.customer-segments.store');
Route::get('customer-segments/{customerSegment}/edit', [CustomerSegmentController::class, 'edit'])->name('crm.customer-segments.edit');
Route::put('customer-segments/{customerSegment}', [CustomerSegmentController::class, 'update'])->name('crm.customer-segments.update');
Route::delete('customer-segments/{customerSegment}', [CustomerSegmentController::class, 'destroy'])->name('crm.customer-segments.destroy');

Route::get('customer-timeline', [CustomerTimelineController::class, 'index'])->name('crm.customer-timeline.index');
Route::get('customer-360', [Customer360Controller::class, 'index'])->name('crm.customer-360.index');

Route::get('opportunities/export', [OpportunityController::class, 'export'])->name('crm.opportunities.export');
Route::get('opportunities/mine', [OpportunityController::class, 'index'])->name('crm.opportunities.mine')->defaults('scope', 'mine');
Route::resource('opportunities', OpportunityController::class)->except('show')->names('crm.opportunities');

Route::get('sales-pipeline', [SalesPipelineController::class, 'index'])->name('crm.sales-pipeline.index');

Route::get('pipeline-board', [PipelineBoardController::class, 'index'])->name('crm.pipeline-board.index');
Route::post('pipeline-board/{opportunity}/move', [PipelineBoardController::class, 'moveStage'])->name('crm.pipeline-board.move');

Route::get('sales-stages', [SalesStageController::class, 'index'])->name('crm.sales-stages.index');
Route::get('sales-stages/create', [SalesStageController::class, 'create'])->name('crm.sales-stages.create');
Route::post('sales-stages', [SalesStageController::class, 'store'])->name('crm.sales-stages.store');
Route::get('sales-stages/{salesStage}/edit', [SalesStageController::class, 'edit'])->name('crm.sales-stages.edit');
Route::put('sales-stages/{salesStage}', [SalesStageController::class, 'update'])->name('crm.sales-stages.update');
Route::post('sales-stages/{salesStage}/move-up', [SalesStageController::class, 'moveUp'])->name('crm.sales-stages.move-up');
Route::post('sales-stages/{salesStage}/move-down', [SalesStageController::class, 'moveDown'])->name('crm.sales-stages.move-down');
Route::delete('sales-stages/{salesStage}', [SalesStageController::class, 'destroy'])->name('crm.sales-stages.destroy');

Route::get('opportunity-products', [OpportunityProductController::class, 'index'])->name('crm.opportunity-products.index');
Route::post('opportunity-products', [OpportunityProductController::class, 'store'])->name('crm.opportunity-products.store');
Route::delete('opportunity-products/{opportunityProduct}', [OpportunityProductController::class, 'destroy'])->name('crm.opportunity-products.destroy');

Route::resource('competitors', CompetitorController::class)->except('show')->names('crm.competitors');

Route::get('win-loss-analysis', [WinLossAnalysisController::class, 'index'])->name('crm.win-loss-analysis.index');

Route::get('sales-forecast', [SalesForecastController::class, 'index'])->name('crm.sales-forecast.index');

Route::resource('price-books', PriceBookController::class)->except('show')->names('crm.price-books');
Route::post('price-books/{priceBook}/prices', [PriceBookController::class, 'setPrice'])->name('crm.price-books.set-price');
Route::delete('price-books/{priceBook}/prices/{product}', [PriceBookController::class, 'removePrice'])->name('crm.price-books.remove-price');

Route::resource('products', ProductController::class)->except('show')->names('crm.products');

Route::resource('quotations', QuotationController::class)->except('show')->names('crm.quotations');
Route::post('quotations/{quotation}/lines', [QuotationController::class, 'addLine'])->name('crm.quotations.lines.store');
Route::delete('quotation-lines/{quotationLine}', [QuotationController::class, 'removeLine'])->name('crm.quotations.lines.destroy');
Route::post('quotations/{quotation}/send', [QuotationController::class, 'send'])->name('crm.quotations.send');
Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('crm.quotations.accept');
Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('crm.quotations.reject');

Route::get('discount-approvals', [DiscountApprovalController::class, 'index'])->name('crm.discount-approvals.index');
Route::post('discount-approvals/{discountApproval}/approve', [DiscountApprovalController::class, 'approve'])->name('crm.discount-approvals.approve');
Route::post('discount-approvals/{discountApproval}/reject', [DiscountApprovalController::class, 'reject'])->name('crm.discount-approvals.reject');

Route::resource('proposals', ProposalController::class)->except('show')->names('crm.proposals');
Route::post('proposals/{proposal}/send', [ProposalController::class, 'send'])->name('crm.proposals.send');
Route::post('proposals/{proposal}/decision', [ProposalController::class, 'markDecision'])->name('crm.proposals.decision');

Route::resource('sales-orders', SalesOrderController::class)->except('show')->names('crm.sales-orders');
Route::post('sales-orders/{salesOrder}/lines', [SalesOrderController::class, 'addLine'])->name('crm.sales-orders.lines.store');

Route::resource('contracts', ContractController::class)->except('show')->names('crm.contracts');

Route::get('esignatures', [ESignatureController::class, 'index'])->name('crm.esignatures.index');
Route::post('esignatures', [ESignatureController::class, 'store'])->name('crm.esignatures.store');
Route::post('esignatures/{esignatureRequest}/signed', [ESignatureController::class, 'markSigned'])->name('crm.esignatures.signed');
Route::post('esignatures/{esignatureRequest}/declined', [ESignatureController::class, 'markDeclined'])->name('crm.esignatures.declined');
Route::delete('esignatures/{esignatureRequest}', [ESignatureController::class, 'destroy'])->name('crm.esignatures.destroy');

Route::get('renewal-management', [RenewalManagementController::class, 'index'])->name('crm.renewal-management.index');
Route::post('renewal-management/{contract}/renew', [RenewalManagementController::class, 'renew'])->name('crm.renewal-management.renew');

Route::resource('tasks', TaskController::class)->except('show')->names('crm.tasks');
Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])->name('crm.tasks.complete');

Route::resource('calls', CallController::class)->except('show')->names('crm.calls');

Route::resource('meetings', MeetingController::class)->except('show')->names('crm.meetings');

Route::get('calendar', [CalendarController::class, 'index'])->name('crm.calendar.index');

Route::resource('emails', EmailController::class)->except('show')->names('crm.emails');

Route::get('notes', [NoteController::class, 'index'])->name('crm.notes.index');
Route::post('notes', [NoteController::class, 'store'])->name('crm.notes.store');
Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('crm.notes.destroy');

Route::get('follow-up-reminders', [FollowUpReminderController::class, 'index'])->name('crm.follow-up-reminders.index');
Route::post('follow-up-reminders/{reminder}/complete', [FollowUpReminderController::class, 'complete'])->name('crm.follow-up-reminders.complete');
Route::post('follow-up-reminders/{reminder}/snooze', [FollowUpReminderController::class, 'snooze'])->name('crm.follow-up-reminders.snooze');

Route::get('interaction-history', [InteractionHistoryController::class, 'index'])->name('crm.interaction-history.index');

Route::get('campaigns/export', [CampaignController::class, 'export'])->name('crm.campaigns.export');
Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('crm.campaigns.send');
Route::resource('campaigns', CampaignController::class)->names('crm.campaigns');

Route::get('email-marketing', [EmailMarketingController::class, 'index'])->name('crm.email-marketing.index');

Route::get('sms-campaigns', [SmsCampaignController::class, 'index'])->name('crm.sms-campaigns.index');

Route::resource('web-forms', WebFormController::class)->except('show')->names('crm.web-forms');

Route::resource('landing-pages', LandingPageController::class)->except('show')->names('crm.landing-pages');

Route::get('campaign-members', [CampaignMemberController::class, 'index'])->name('crm.campaign-members.index');
Route::post('campaign-members', [CampaignMemberController::class, 'store'])->name('crm.campaign-members.store');
Route::patch('campaign-members/{campaignMember}/status', [CampaignMemberController::class, 'updateStatus'])->name('crm.campaign-members.status');
Route::delete('campaign-members/{campaignMember}', [CampaignMemberController::class, 'destroy'])->name('crm.campaign-members.destroy');

Route::get('lead-attribution', [LeadAttributionController::class, 'index'])->name('crm.lead-attribution.index');

Route::get('marketing-analytics', [MarketingAnalyticsController::class, 'index'])->name('crm.marketing-analytics.index');

Route::resource('cases', CaseController::class)->except('show')->names('crm.cases');
Route::post('cases/{case}/escalate', [CaseController::class, 'escalate'])->name('crm.cases.escalate');

Route::get('complaints', [ComplaintController::class, 'index'])->name('crm.complaints.index');

Route::get('service-requests', [ServiceRequestController::class, 'index'])->name('crm.service-requests.index');

Route::get('sla-management', [SlaManagementController::class, 'index'])->name('crm.sla-management.index');
Route::post('sla-management', [SlaManagementController::class, 'store'])->name('crm.sla-management.store');
Route::put('sla-management/{slaPolicy}', [SlaManagementController::class, 'update'])->name('crm.sla-management.update');
Route::delete('sla-management/{slaPolicy}', [SlaManagementController::class, 'destroy'])->name('crm.sla-management.destroy');

Route::get('escalations', [EscalationController::class, 'index'])->name('crm.escalations.index');

Route::resource('knowledge-base', KnowledgeBaseController::class)->except('show')->names('crm.knowledge-base');

Route::get('customer-feedback', [CustomerFeedbackController::class, 'index'])->name('crm.customer-feedback.index');
Route::post('customer-feedback', [CustomerFeedbackController::class, 'store'])->name('crm.customer-feedback.store');
Route::delete('customer-feedback/{customerFeedback}', [CustomerFeedbackController::class, 'destroy'])->name('crm.customer-feedback.destroy');

Route::get('csat', [CsatController::class, 'index'])->name('crm.csat.index');

Route::get('sales-reports', [SalesReportController::class, 'index'])->name('crm.sales-reports.index');

Route::get('lead-conversion-report', [LeadConversionReportController::class, 'index'])->name('crm.lead-conversion-report.index');

Route::get('pipeline-reports', [PipelineReportController::class, 'index'])->name('crm.pipeline-reports.index');

Route::get('revenue-reports', [RevenueReportController::class, 'index'])->name('crm.revenue-reports.index');

Route::get('campaign-reports', [CampaignReportController::class, 'index'])->name('crm.campaign-reports.index');

Route::get('salesperson-performance', [SalespersonPerformanceController::class, 'index'])->name('crm.salesperson-performance.index');

Route::get('custom-reports/{customReport}/export', [CustomReportController::class, 'export'])->name('crm.custom-reports.export');
Route::resource('custom-reports', CustomReportController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->names('crm.custom-reports');

Route::get('scheduled-reports', [ScheduledReportController::class, 'index'])->name('crm.scheduled-reports.index');
Route::post('scheduled-reports', [ScheduledReportController::class, 'store'])->name('crm.scheduled-reports.store');
Route::patch('scheduled-reports/{scheduledReport}/toggle', [ScheduledReportController::class, 'toggle'])->name('crm.scheduled-reports.toggle');
Route::post('scheduled-reports/{scheduledReport}/run', [ScheduledReportController::class, 'runNow'])->name('crm.scheduled-reports.run');
Route::delete('scheduled-reports/{scheduledReport}', [ScheduledReportController::class, 'destroy'])->name('crm.scheduled-reports.destroy');

Route::get('workflows/failed-jobs', [CrmWorkflowController::class, 'failedJobs'])->name('crm.workflows.failed-jobs');
Route::post('workflow-executions/{workflowExecution}/retry', [CrmWorkflowController::class, 'retry'])->name('crm.workflows.retry');
Route::get('workflows/{workflow}/executions', [CrmWorkflowController::class, 'executions'])->name('crm.workflows.executions');
Route::post('workflows/{workflow}/run', [CrmWorkflowController::class, 'run'])->name('crm.workflows.run');
Route::resource('workflows', CrmWorkflowController::class)->except('show')->names('crm.workflows');
Route::patch('workflows/{workflow}/toggle', [CrmWorkflowController::class, 'toggle'])->name('crm.workflows.toggle');

foreach ([
    'assignment-rules' => AssignmentRuleController::class,
    'approval-rules' => CrmApprovalRuleController::class,
    'escalation-rules' => CrmEscalationRuleController::class,
    'follow-up-automation' => FollowUpAutomationController::class,
    'trigger-actions' => TriggerActionController::class,
] as $prefix => $controller) {
    Route::get($prefix, [$controller, 'index'])->name('crm.'.$prefix.'.index');
    Route::post($prefix, [$controller, 'store'])->name('crm.'.$prefix.'.store');
    Route::patch($prefix.'/{automationRule}/toggle', [$controller, 'toggle'])->name('crm.'.$prefix.'.toggle');
    Route::post($prefix.'/{automationRule}/run', [$controller, 'run'])->name('crm.'.$prefix.'.run');
    Route::delete($prefix.'/{automationRule}', [$controller, 'destroy'])->name('crm.'.$prefix.'.destroy');
}

Route::resource('email-sequences', EmailSequenceController::class)->except('show')->names('crm.email-sequences');
Route::patch('email-sequences/{emailSequence}/toggle', [EmailSequenceController::class, 'toggle'])->name('crm.email-sequences.toggle');
Route::post('email-sequences/{emailSequence}/enroll', [EmailSequenceController::class, 'enroll'])->name('crm.email-sequences.enroll');
Route::post('email-sequence-enrollments/{enrollment}/advance', [EmailSequenceController::class, 'advance'])->name('crm.email-sequences.enrollments.advance');
Route::post('email-sequence-enrollments/{enrollment}/stop', [EmailSequenceController::class, 'stopEnrollment'])->name('crm.email-sequences.enrollments.stop');

Route::get('scheduled-jobs', [ScheduledJobController::class, 'index'])->name('crm.scheduled-jobs.index');
Route::post('scheduled-jobs', [ScheduledJobController::class, 'store'])->name('crm.scheduled-jobs.store');
Route::patch('scheduled-jobs/{scheduledJob}/toggle', [ScheduledJobController::class, 'toggle'])->name('crm.scheduled-jobs.toggle');
Route::post('scheduled-jobs/{scheduledJob}/run', [ScheduledJobController::class, 'runNow'])->name('crm.scheduled-jobs.run');
Route::delete('scheduled-jobs/{scheduledJob}', [ScheduledJobController::class, 'destroy'])->name('crm.scheduled-jobs.destroy');

Route::get('ai-lead-scoring', [AiLeadScoringController::class, 'index'])->name('crm.ai-lead-scoring.index');
Route::post('ai-lead-scoring/recalculate', [AiLeadScoringController::class, 'recalculateAll'])->name('crm.ai-lead-scoring.recalculate');

Route::get('ai-win-probability', [AiWinProbabilityController::class, 'index'])->name('crm.ai-win-probability.index');
Route::post('ai-win-probability/recalculate', [AiWinProbabilityController::class, 'recalculateAll'])->name('crm.ai-win-probability.recalculate');

Route::get('ai-sales-forecast', [AiSalesForecastController::class, 'index'])->name('crm.ai-sales-forecast.index');

Route::get('ai-assistant', [AiAssistantController::class, 'index'])->name('crm.ai-assistant.index');
Route::post('ai-assistant/generate', [AiAssistantController::class, 'generate'])->name('crm.ai-assistant.generate');

Route::get('ai-conversation-summary', [AiConversationSummaryController::class, 'index'])->name('crm.ai-conversation-summary.index');

Route::get('ai-next-best-action', [AiNextBestActionController::class, 'index'])->name('crm.ai-next-best-action.index');

Route::get('ai-churn-prediction', [AiChurnPredictionController::class, 'index'])->name('crm.ai-churn-prediction.index');

Route::get('ai-sales-assistant', [AiSalesAssistantController::class, 'index'])->name('crm.ai-sales-assistant.index');

Route::get('settings', [CrmSettingsController::class, 'index'])->name('crm.settings.index');
Route::put('settings', [CrmSettingsController::class, 'update'])->name('crm.settings.update');

Route::get('custom-fields', [CrmCustomFieldController::class, 'index'])->name('crm.custom-fields.index');
Route::post('custom-fields', [CrmCustomFieldController::class, 'store'])->name('crm.custom-fields.store');
Route::patch('custom-fields/{customField}/toggle', [CrmCustomFieldController::class, 'toggle'])->name('crm.custom-fields.toggle');
Route::delete('custom-fields/{customField}', [CrmCustomFieldController::class, 'destroy'])->name('crm.custom-fields.destroy');

Route::resource('custom-objects', CrmCustomObjectController::class)->except('show')->names('crm.custom-objects');
Route::patch('custom-objects/{customObject}/toggle', [CrmCustomObjectController::class, 'toggle'])->name('crm.custom-objects.toggle');

Route::get('layouts', [CrmLayoutController::class, 'index'])->name('crm.layouts.index');
Route::put('layouts', [CrmLayoutController::class, 'update'])->name('crm.layouts.update');

Route::get('teams', [CrmTeamController::class, 'index'])->name('crm.teams.index');
Route::post('teams', [CrmTeamController::class, 'store'])->name('crm.teams.store');
Route::post('teams/{team}/members', [CrmTeamController::class, 'addMember'])->name('crm.teams.members.add');
Route::delete('teams/{team}/members/{employee}', [CrmTeamController::class, 'removeMember'])->name('crm.teams.members.remove');
Route::delete('teams/{team}', [CrmTeamController::class, 'destroy'])->name('crm.teams.destroy');

Route::get('territories', [CrmTerritoryController::class, 'index'])->name('crm.territories.index');
Route::post('territories', [CrmTerritoryController::class, 'store'])->name('crm.territories.store');
Route::delete('territories/{territory}', [CrmTerritoryController::class, 'destroy'])->name('crm.territories.destroy');

Route::get('quotas', [CrmQuotaController::class, 'index'])->name('crm.quotas.index');
Route::post('quotas', [CrmQuotaController::class, 'store'])->name('crm.quotas.store');
Route::delete('quotas/{quota}', [CrmQuotaController::class, 'destroy'])->name('crm.quotas.destroy');

Route::get('integrations', [CrmIntegrationController::class, 'index'])->name('crm.integrations.index');
Route::post('integrations', [CrmIntegrationController::class, 'store'])->name('crm.integrations.store');
Route::patch('integrations/{integration}/toggle', [CrmIntegrationController::class, 'toggle'])->name('crm.integrations.toggle');
Route::delete('integrations/{integration}', [CrmIntegrationController::class, 'destroy'])->name('crm.integrations.destroy');

Route::get('import-export', [CrmImportExportController::class, 'index'])->name('crm.import-export.index');
Route::get('import-export/export/{entity}', [CrmImportExportController::class, 'export'])->name('crm.import-export.export');
Route::post('import-export/import-leads', [CrmImportExportController::class, 'importLeads'])->name('crm.import-export.import-leads');

Route::get('audit-logs', [CrmAuditLogController::class, 'index'])->name('crm.audit-logs.index');

Route::get('permissions', [CrmRoleController::class, 'index'])->name('crm.permissions.index');
Route::post('permissions', [CrmRoleController::class, 'store'])->name('crm.permissions.store');
Route::post('permissions/{crmRole}/assign', [CrmRoleController::class, 'assign'])->name('crm.permissions.assign');
Route::delete('permissions/{crmRole}/unassign/{employee}', [CrmRoleController::class, 'unassign'])->name('crm.permissions.unassign');
Route::delete('permissions/{crmRole}', [CrmRoleController::class, 'destroy'])->name('crm.permissions.destroy');

Route::get('deal-approval-matrix', [DealApprovalMatrixController::class, 'index'])->name('crm.deal-approval-matrix.index');
Route::post('deal-approval-matrix', [DealApprovalMatrixController::class, 'store'])->name('crm.deal-approval-matrix.store');
Route::patch('deal-approval-matrix/{dealApprovalRule}/toggle', [DealApprovalMatrixController::class, 'toggle'])->name('crm.deal-approval-matrix.toggle');
Route::delete('deal-approval-matrix/{dealApprovalRule}', [DealApprovalMatrixController::class, 'destroy'])->name('crm.deal-approval-matrix.destroy');

Route::get('deal-approvals', [DealApprovalController::class, 'index'])->name('crm.deal-approvals.index');
Route::post('deal-approvals/{dealApproval}/approve', [DealApprovalController::class, 'approve'])->name('crm.deal-approvals.approve');
Route::post('deal-approvals/{dealApproval}/reject', [DealApprovalController::class, 'reject'])->name('crm.deal-approvals.reject');

}); // app => crm

Route::group(['app' => 'inventory', 'prefix' => 'inventory-warehouse'], function () {
    Route::get('/', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('inventory.notifications.index');

    Route::get('stock-overview', [StockOverviewController::class, 'index'])->name('inventory.stock-overview.index');
    Route::post('stock-overview/items', [StockOverviewController::class, 'storeItem'])->name('inventory.stock-overview.items.store');
    Route::post('stock-overview/adjust', [StockOverviewController::class, 'adjustStock'])->name('inventory.stock-overview.adjust');

    Route::get('warehouse-dashboard', [WarehouseDashboardController::class, 'index'])->name('inventory.warehouse-dashboard.index');
    Route::post('warehouse-dashboard', [WarehouseDashboardController::class, 'store'])->name('inventory.warehouse-dashboard.store');
    Route::patch('warehouse-dashboard/{warehouse}/toggle', [WarehouseDashboardController::class, 'toggle'])->name('inventory.warehouse-dashboard.toggle');

    Route::resource('products', InventoryProductController::class)->except('show')->names('inventory.products');

    Route::get('categories', [InventoryCategoryController::class, 'index'])->name('inventory.categories.index');
    Route::post('categories', [InventoryCategoryController::class, 'store'])->name('inventory.categories.store');
    Route::delete('categories/{category}', [InventoryCategoryController::class, 'destroy'])->name('inventory.categories.destroy');
    Route::post('brands', [InventoryCategoryController::class, 'storeBrand'])->name('inventory.categories.brands.store');
    Route::delete('brands/{brand}', [InventoryCategoryController::class, 'destroyBrand'])->name('inventory.categories.brands.destroy');

    Route::get('uoms', [UomController::class, 'index'])->name('inventory.uoms.index');
    Route::post('uoms', [UomController::class, 'store'])->name('inventory.uoms.store');
    Route::delete('uoms/{uom}', [UomController::class, 'destroy'])->name('inventory.uoms.destroy');
    Route::post('uom-conversions', [UomController::class, 'storeConversion'])->name('inventory.uoms.conversions.store');
    Route::delete('uom-conversions/{uomConversion}', [UomController::class, 'destroyConversion'])->name('inventory.uoms.conversions.destroy');

    Route::get('variants', [ProductVariantController::class, 'index'])->name('inventory.variants.index');
    Route::post('variants/attributes', [ProductVariantController::class, 'storeAttribute'])->name('inventory.variants.attributes.store');
    Route::post('variants/attributes/{variantAttribute}/values', [ProductVariantController::class, 'storeValue'])->name('inventory.variants.attributes.values.store');
    Route::post('variants/generate', [ProductVariantController::class, 'generate'])->name('inventory.variants.generate');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroyVariant'])->name('inventory.variants.destroy');

    Route::get('barcodes', [BarcodeController::class, 'index'])->name('inventory.barcodes.index');
    Route::post('barcodes', [BarcodeController::class, 'store'])->name('inventory.barcodes.store');
    Route::get('barcodes/{barcode}/label', [BarcodeController::class, 'label'])->name('inventory.barcodes.label');
    Route::post('barcodes/scan', [BarcodeController::class, 'scan'])->name('inventory.barcodes.scan');
    Route::delete('barcodes/{barcode}', [BarcodeController::class, 'destroy'])->name('inventory.barcodes.destroy');

    Route::get('batch-serial', [BatchSerialController::class, 'index'])->name('inventory.batch-serial.index');
    Route::post('batch-serial/batches', [BatchSerialController::class, 'storeBatch'])->name('inventory.batch-serial.batches.store');
    Route::delete('batch-serial/batches/{batch}', [BatchSerialController::class, 'destroyBatch'])->name('inventory.batch-serial.batches.destroy');
    Route::post('batch-serial/serials', [BatchSerialController::class, 'storeSerial'])->name('inventory.batch-serial.serials.store');
    Route::patch('batch-serial/serials/{serial}/status', [BatchSerialController::class, 'updateSerialStatus'])->name('inventory.batch-serial.serials.status');
    Route::delete('batch-serial/serials/{serial}', [BatchSerialController::class, 'destroySerial'])->name('inventory.batch-serial.serials.destroy');

    Route::get('suppliers', [SupplierProductController::class, 'index'])->name('inventory.suppliers.index');
    Route::post('suppliers', [SupplierProductController::class, 'store'])->name('inventory.suppliers.store');
    Route::delete('suppliers/{supplier}', [SupplierProductController::class, 'destroy'])->name('inventory.suppliers.destroy');
    Route::post('supplier-mappings', [SupplierProductController::class, 'storeMapping'])->name('inventory.suppliers.mappings.store');
    Route::delete('supplier-mappings/{supplierProduct}', [SupplierProductController::class, 'destroyMapping'])->name('inventory.suppliers.mappings.destroy');

    Route::put('warehouse-dashboard/{warehouse}', [WarehouseDashboardController::class, 'update'])->name('inventory.warehouse-dashboard.update');

    Route::get('locations', [LocationController::class, 'index'])->name('inventory.locations.index');
    Route::post('locations', [LocationController::class, 'store'])->name('inventory.locations.store');
    Route::patch('locations/{location}/toggle', [LocationController::class, 'toggle'])->name('inventory.locations.toggle');
    Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('inventory.locations.destroy');

    Route::get('putaway-rules', [PutawayRuleController::class, 'index'])->name('inventory.putaway-rules.index');
    Route::post('putaway-rules', [PutawayRuleController::class, 'store'])->name('inventory.putaway-rules.store');
    Route::delete('putaway-rules/{putawayRule}', [PutawayRuleController::class, 'destroy'])->name('inventory.putaway-rules.destroy');
    Route::post('putaway-tasks', [PutawayRuleController::class, 'createTask'])->name('inventory.putaway-rules.tasks.store');
    Route::post('putaway-tasks/{putawayTask}/complete', [PutawayRuleController::class, 'completeTask'])->name('inventory.putaway-rules.tasks.complete');

    Route::get('picking-strategies', [PickingStrategyController::class, 'index'])->name('inventory.picking-strategies.index');
    Route::put('picking-strategies/{warehouse}', [PickingStrategyController::class, 'update'])->name('inventory.picking-strategies.update');

    Route::get('warehouse-transfers', [WarehouseTransferController::class, 'index'])->name('inventory.warehouse-transfers.index');
    Route::post('warehouse-transfers', [WarehouseTransferController::class, 'store'])->name('inventory.warehouse-transfers.store');
    Route::post('warehouse-transfers/{warehouseTransfer}/approve', [WarehouseTransferController::class, 'approve'])->name('inventory.warehouse-transfers.approve');
    Route::post('warehouse-transfers/{warehouseTransfer}/pick', [WarehouseTransferController::class, 'pick'])->name('inventory.warehouse-transfers.pick');
    Route::post('warehouse-transfers/{warehouseTransfer}/dispatch', [WarehouseTransferController::class, 'dispatch'])->name('inventory.warehouse-transfers.dispatch');
    Route::post('warehouse-transfers/{warehouseTransfer}/receive', [WarehouseTransferController::class, 'receive'])->name('inventory.warehouse-transfers.receive');
    Route::post('warehouse-transfers/{warehouseTransfer}/cancel', [WarehouseTransferController::class, 'cancel'])->name('inventory.warehouse-transfers.cancel');

    Route::get('expected-receipts', [ExpectedReceiptController::class, 'index'])->name('inventory.expected-receipts.index');
    Route::post('expected-receipts', [ExpectedReceiptController::class, 'store'])->name('inventory.expected-receipts.store');
    Route::post('expected-receipts/{expectedReceipt}/cancel', [ExpectedReceiptController::class, 'cancel'])->name('inventory.expected-receipts.cancel');

    Route::get('grns', [GrnController::class, 'index'])->name('inventory.grns.index');
    Route::post('grns', [GrnController::class, 'store'])->name('inventory.grns.store');
    Route::post('grns/{grn}/post', [GrnController::class, 'post'])->name('inventory.grns.post');

    Route::get('quality-inspections', [QualityInspectionController::class, 'index'])->name('inventory.quality-inspections.index');
    Route::post('quality-inspections/{qualityInspection}/inspect', [QualityInspectionController::class, 'inspect'])->name('inventory.quality-inspections.inspect');

    Route::get('supplier-returns', [SupplierReturnController::class, 'index'])->name('inventory.supplier-returns.index');
    Route::post('supplier-returns', [SupplierReturnController::class, 'store'])->name('inventory.supplier-returns.store');
    Route::post('supplier-returns/{supplierReturn}/approve', [SupplierReturnController::class, 'approve'])->name('inventory.supplier-returns.approve');
    Route::post('supplier-returns/{supplierReturn}/dispatch', [SupplierReturnController::class, 'dispatch'])->name('inventory.supplier-returns.dispatch');

    Route::get('stock-reservations', [StockReservationController::class, 'index'])->name('inventory.stock-reservations.index');
    Route::post('stock-reservations', [StockReservationController::class, 'store'])->name('inventory.stock-reservations.store');
    Route::post('stock-reservations/{stockReservation}/release', [StockReservationController::class, 'release'])->name('inventory.stock-reservations.release');

    Route::get('pick-lists', [PickListController::class, 'index'])->name('inventory.pick-lists.index');
    Route::post('pick-lists', [PickListController::class, 'store'])->name('inventory.pick-lists.store');
    Route::post('pick-lists/{pickList}/assign-picker', [PickListController::class, 'assignPicker'])->name('inventory.pick-lists.assign-picker');
    Route::post('pick-list-lines/{pickListLine}/pick', [PickListController::class, 'pickLine'])->name('inventory.pick-lists.lines.pick');

    Route::get('packing', [PackingController::class, 'index'])->name('inventory.packing.index');
    Route::post('packing', [PackingController::class, 'store'])->name('inventory.packing.store');

    Route::get('dispatches', [DispatchController::class, 'index'])->name('inventory.dispatches.index');
    Route::post('dispatches', [DispatchController::class, 'store'])->name('inventory.dispatches.store');

    Route::get('delivery-confirmations', [DeliveryConfirmationController::class, 'index'])->name('inventory.delivery-confirmations.index');
    Route::post('delivery-confirmations/{dispatch}/confirm', [DeliveryConfirmationController::class, 'confirm'])->name('inventory.delivery-confirmations.confirm');

    Route::get('customer-returns', [CustomerReturnController::class, 'index'])->name('inventory.customer-returns.index');
    Route::post('customer-returns', [CustomerReturnController::class, 'store'])->name('inventory.customer-returns.store');
    Route::post('customer-returns/{customerReturn}/approve', [CustomerReturnController::class, 'approve'])->name('inventory.customer-returns.approve');
    Route::post('customer-returns/{customerReturn}/receive', [CustomerReturnController::class, 'receive'])->name('inventory.customer-returns.receive');

    Route::get('stock-ledger', [StockLedgerController::class, 'index'])->name('inventory.stock-ledger.index');

    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index'])->name('inventory.stock-adjustments.index');
    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->name('inventory.stock-adjustments.store');
    Route::post('stock-adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('inventory.stock-adjustments.approve');
    Route::post('stock-adjustments/{stockAdjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('inventory.stock-adjustments.reject');

    Route::get('replenishment', [ReplenishmentController::class, 'index'])->name('inventory.replenishment.index');
    Route::post('replenishment/rules', [ReplenishmentController::class, 'storeRule'])->name('inventory.replenishment.rules.store');
    Route::delete('replenishment/rules/{replenishmentRule}', [ReplenishmentController::class, 'destroyRule'])->name('inventory.replenishment.rules.destroy');
    Route::post('replenishment/rules/{replenishmentRule}/tasks', [ReplenishmentController::class, 'createTask'])->name('inventory.replenishment.rules.tasks.store');
    Route::post('replenishment/tasks/{replenishmentTask}/complete', [ReplenishmentController::class, 'completeTask'])->name('inventory.replenishment.tasks.complete');

    Route::get('cycle-counting', [CycleCountController::class, 'index'])->name('inventory.cycle-counting.index');
    Route::post('cycle-counting/schedules', [CycleCountController::class, 'storeSchedule'])->name('inventory.cycle-counting.schedules.store');
    Route::post('cycle-counting/sheets', [CycleCountController::class, 'storeSheet'])->name('inventory.cycle-counting.sheets.store');
    Route::post('cycle-counting/lines/{countSheetLine}/count', [CycleCountController::class, 'countLine'])->name('inventory.cycle-counting.lines.count');
    Route::post('cycle-counting/lines/{countSheetLine}/recount', [CycleCountController::class, 'recountLine'])->name('inventory.cycle-counting.lines.recount');
    Route::post('cycle-counting/sheets/{countSheet}/approve', [CycleCountController::class, 'approveSheet'])->name('inventory.cycle-counting.sheets.approve');

    Route::get('reorder', [ReorderController::class, 'index'])->name('inventory.reorder.index');
    Route::post('reorder/rules', [ReorderController::class, 'storeRule'])->name('inventory.reorder.rules.store');
    Route::delete('reorder/rules/{reorderRule}', [ReorderController::class, 'destroyRule'])->name('inventory.reorder.rules.destroy');
    Route::post('reorder/suggestions/generate', [ReorderController::class, 'generateSuggestions'])->name('inventory.reorder.suggestions.generate');
    Route::post('reorder/suggestions/{purchaseSuggestion}/approve', [ReorderController::class, 'approveSuggestion'])->name('inventory.reorder.suggestions.approve');
    Route::post('reorder/suggestions/{purchaseSuggestion}/dismiss', [ReorderController::class, 'dismissSuggestion'])->name('inventory.reorder.suggestions.dismiss');

    Route::get('demand-forecast', [DemandForecastController::class, 'index'])->name('inventory.demand-forecast.index');
    Route::post('demand-forecast/generate', [DemandForecastController::class, 'generate'])->name('inventory.demand-forecast.generate');

    Route::get('valuation', [InventoryValuationController::class, 'index'])->name('inventory.valuation.index');
    Route::post('valuation/{item}/costing-method', [InventoryValuationController::class, 'updateCostingMethod'])->name('inventory.valuation.costing-method');

    Route::get('slow-dead-stock', [SlowDeadStockController::class, 'index'])->name('inventory.slow-dead-stock.index');

    Route::get('expiry-management', [ExpiryManagementController::class, 'index'])->name('inventory.expiry-management.index');
    Route::post('expiry-management/batches/{batch}/dispose', [ExpiryManagementController::class, 'requestDisposal'])->name('inventory.expiry-management.dispose');
    Route::post('expiry-management/disposals/{batchDisposal}/approve', [ExpiryManagementController::class, 'approveDisposal'])->name('inventory.expiry-management.disposals.approve');
    Route::post('expiry-management/disposals/{batchDisposal}/reject', [ExpiryManagementController::class, 'rejectDisposal'])->name('inventory.expiry-management.disposals.reject');

    Route::get('abc-xyz', [AbcXyzController::class, 'index'])->name('inventory.abc-xyz.index');
    Route::post('abc-xyz/recalculate', [AbcXyzController::class, 'recalculate'])->name('inventory.abc-xyz.recalculate');

    Route::get('stock-availability', [StockAvailabilityController::class, 'index'])->name('inventory.stock-availability.index');

    Route::get('planning-workbench', [PlanningWorkbenchController::class, 'index'])->name('inventory.planning-workbench.index');

    Route::get('policies', [InventoryPolicyController::class, 'index'])->name('inventory.policies.index');
    Route::put('policies', [InventoryPolicyController::class, 'update'])->name('inventory.policies.update');

    Route::get('approval-rules', [InventoryApprovalRuleController::class, 'index'])->name('inventory.approval-rules.index');
    Route::post('approval-rules', [InventoryApprovalRuleController::class, 'store'])->name('inventory.approval-rules.store');
    Route::delete('approval-rules/{approvalRule}', [InventoryApprovalRuleController::class, 'destroy'])->name('inventory.approval-rules.destroy');

    Route::get('numbering-documents', [NumberingDocumentsController::class, 'index'])->name('inventory.numbering-documents.index');
    Route::post('numbering-documents/series', [NumberingDocumentsController::class, 'storeSeries'])->name('inventory.numbering-documents.series.store');
    Route::delete('numbering-documents/series/{numberingSeries}', [NumberingDocumentsController::class, 'destroySeries'])->name('inventory.numbering-documents.series.destroy');
    Route::post('numbering-documents/fields', [NumberingDocumentsController::class, 'storeField'])->name('inventory.numbering-documents.fields.store');
    Route::delete('numbering-documents/fields/{customFieldDefinition}', [NumberingDocumentsController::class, 'destroyField'])->name('inventory.numbering-documents.fields.destroy');
    Route::post('numbering-documents/import/items', [NumberingDocumentsController::class, 'importItems'])->name('inventory.numbering-documents.import.items');
    Route::post('numbering-documents/import/opening-balances', [NumberingDocumentsController::class, 'importOpeningBalances'])->name('inventory.numbering-documents.import.opening-balances');

    Route::get('integrations', [IntegrationSettingController::class, 'index'])->name('inventory.integrations.index');
    Route::post('integrations/{integrationKey}', [IntegrationSettingController::class, 'update'])->name('inventory.integrations.update');

    Route::get('reports-audit', [ReportsAuditController::class, 'index'])->name('inventory.reports-audit.index');
}); // app => inventory

Route::group(['app' => 'sales', 'prefix' => 'sales'], function () {
    Route::get('/', [SalesDashboardController::class, 'index'])->name('sales.dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('sales.notifications.index');

    Route::get('customers', [SalesCustomerController::class, 'index'])->name('sales.customers.index');
    Route::get('customers/{account}/edit', [SalesCustomerController::class, 'edit'])->name('sales.customers.edit');
    Route::put('customers/{account}', [SalesCustomerController::class, 'update'])->name('sales.customers.update');

    Route::get('pricing', [PriceListController::class, 'index'])->name('sales.pricing.index');
    Route::post('pricing/lists', [PriceListController::class, 'storeList'])->name('sales.pricing.lists.store');
    Route::delete('pricing/lists/{priceList}', [PriceListController::class, 'destroyList'])->name('sales.pricing.lists.destroy');
    Route::post('pricing/lists/{priceList}/items', [PriceListController::class, 'storeListItem'])->name('sales.pricing.lists.items.store');
    Route::delete('pricing/lists/items/{priceListItem}', [PriceListController::class, 'destroyListItem'])->name('sales.pricing.lists.items.destroy');
    Route::post('pricing/customer-prices', [PriceListController::class, 'storeCustomerPrice'])->name('sales.pricing.customer-prices.store');
    Route::delete('pricing/customer-prices/{customerPrice}', [PriceListController::class, 'destroyCustomerPrice'])->name('sales.pricing.customer-prices.destroy');

    Route::get('quotations', [SalesQuotationController::class, 'index'])->name('sales.quotations.index');
    Route::get('quotations/create', [SalesQuotationController::class, 'create'])->name('sales.quotations.create');
    Route::post('quotations', [SalesQuotationController::class, 'store'])->name('sales.quotations.store');
    Route::get('quotations/{quotation}/edit', [SalesQuotationController::class, 'edit'])->name('sales.quotations.edit');
    Route::put('quotations/{quotation}', [SalesQuotationController::class, 'update'])->name('sales.quotations.update');
    Route::delete('quotations/{quotation}', [SalesQuotationController::class, 'destroy'])->name('sales.quotations.destroy');
    Route::post('quotations/{quotation}/lines', [SalesQuotationController::class, 'addLine'])->name('sales.quotations.lines.store');
    Route::delete('quotation-lines/{quotationLine}', [SalesQuotationController::class, 'removeLine'])->name('sales.quotations.lines.destroy');
    Route::post('quotations/{quotation}/send', [SalesQuotationController::class, 'send'])->name('sales.quotations.send');
    Route::post('quotations/{quotation}/accept', [SalesQuotationController::class, 'accept'])->name('sales.quotations.accept');
    Route::post('quotations/{quotation}/reject', [SalesQuotationController::class, 'reject'])->name('sales.quotations.reject');
    Route::post('quotations/{quotation}/convert', [SalesQuotationController::class, 'convertToOrder'])->name('sales.quotations.convert');

    Route::get('quotation-templates', [QuotationTemplateController::class, 'index'])->name('sales.quotation-templates.index');
    Route::post('quotation-templates', [QuotationTemplateController::class, 'store'])->name('sales.quotation-templates.store');
    Route::delete('quotation-templates/{quotationTemplate}', [QuotationTemplateController::class, 'destroy'])->name('sales.quotation-templates.destroy');
    Route::post('quotation-templates/{quotationTemplate}/lines', [QuotationTemplateController::class, 'storeLine'])->name('sales.quotation-templates.lines.store');
    Route::delete('quotation-template-lines/{quotationTemplateLine}', [QuotationTemplateController::class, 'destroyLine'])->name('sales.quotation-templates.lines.destroy');

    Route::get('orders', [SalesSalesOrderController::class, 'index'])->name('sales.orders.index');
    Route::get('orders/create', [SalesSalesOrderController::class, 'create'])->name('sales.orders.create');
    Route::post('orders', [SalesSalesOrderController::class, 'store'])->name('sales.orders.store');
    Route::get('orders/{salesOrder}/edit', [SalesSalesOrderController::class, 'edit'])->name('sales.orders.edit');
    Route::put('orders/{salesOrder}', [SalesSalesOrderController::class, 'update'])->name('sales.orders.update');
    Route::delete('orders/{salesOrder}', [SalesSalesOrderController::class, 'destroy'])->name('sales.orders.destroy');
    Route::post('orders/{salesOrder}/lines', [SalesSalesOrderController::class, 'addLine'])->name('sales.orders.lines.store');
    Route::post('order-lines/{salesOrderLine}/fulfill', [SalesSalesOrderController::class, 'fulfillLine'])->name('sales.orders.lines.fulfill');
    Route::post('orders/{salesOrder}/confirm', [SalesSalesOrderController::class, 'confirm'])->name('sales.orders.confirm');
    Route::post('orders/{salesOrder}/close', [SalesSalesOrderController::class, 'close'])->name('sales.orders.close');
    Route::post('orders/{salesOrder}/cancel', [SalesSalesOrderController::class, 'cancel'])->name('sales.orders.cancel');

    Route::get('fulfilment', [FulfilmentController::class, 'index'])->name('sales.fulfilment.index');

    Route::get('contracts', [SalesContractController::class, 'index'])->name('sales.contracts.index');
    Route::get('contracts/create', [SalesContractController::class, 'create'])->name('sales.contracts.create');
    Route::post('contracts', [SalesContractController::class, 'store'])->name('sales.contracts.store');
    Route::get('contracts/{contract}/edit', [SalesContractController::class, 'edit'])->name('sales.contracts.edit');
    Route::put('contracts/{contract}', [SalesContractController::class, 'update'])->name('sales.contracts.update');
    Route::delete('contracts/{contract}', [SalesContractController::class, 'destroy'])->name('sales.contracts.destroy');
    Route::post('contracts/{contract}/renew', [SalesContractController::class, 'renew'])->name('sales.contracts.renew');
    Route::post('contracts/{contract}/generate-order', [SalesContractController::class, 'generateOrder'])->name('sales.contracts.generate-order');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('sales.invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->name('sales.invoices.store');
    Route::post('invoices/{invoiceRequest}/invoiced', [InvoiceController::class, 'markInvoiced'])->name('sales.invoices.invoiced');
    Route::post('invoices/{invoiceRequest}/payment', [InvoiceController::class, 'recordPayment'])->name('sales.invoices.payment');
    Route::post('invoices/{invoiceRequest}/cancel', [InvoiceController::class, 'cancel'])->name('sales.invoices.cancel');

    Route::get('activities', [SalesActivityController::class, 'index'])->name('sales.activities.index');
    Route::post('activities', [SalesActivityController::class, 'store'])->name('sales.activities.store');
    Route::post('activities/{activity}/complete', [SalesActivityController::class, 'complete'])->name('sales.activities.complete');
    Route::delete('activities/{activity}', [SalesActivityController::class, 'destroy'])->name('sales.activities.destroy');

    Route::get('team-targets', [TeamTargetController::class, 'index'])->name('sales.team-targets.index');
    Route::post('team-targets/teams', [TeamTargetController::class, 'storeTeam'])->name('sales.team-targets.teams.store');
    Route::delete('team-targets/teams/{team}', [TeamTargetController::class, 'destroyTeam'])->name('sales.team-targets.teams.destroy');
    Route::post('team-targets/teams/{team}/members', [TeamTargetController::class, 'addMember'])->name('sales.team-targets.teams.members.add');
    Route::delete('team-targets/teams/{team}/members/{employee}', [TeamTargetController::class, 'removeMember'])->name('sales.team-targets.teams.members.remove');
    Route::post('team-targets/territories', [TeamTargetController::class, 'storeTerritory'])->name('sales.team-targets.territories.store');
    Route::delete('team-targets/territories/{territory}', [TeamTargetController::class, 'destroyTerritory'])->name('sales.team-targets.territories.destroy');
    Route::post('team-targets/quotas', [TeamTargetController::class, 'storeQuota'])->name('sales.team-targets.quotas.store');
    Route::delete('team-targets/quotas/{quota}', [TeamTargetController::class, 'destroyQuota'])->name('sales.team-targets.quotas.destroy');

    Route::get('returns-complaints', [ReturnComplaintController::class, 'index'])->name('sales.returns-complaints.index');
    Route::post('returns-complaints/returns', [ReturnComplaintController::class, 'storeReturn'])->name('sales.returns-complaints.returns.store');
    Route::post('returns-complaints/returns/{customerReturn}/replacement', [ReturnComplaintController::class, 'createReplacement'])->name('sales.returns-complaints.returns.replacement');
    Route::post('returns-complaints/complaints', [ReturnComplaintController::class, 'storeComplaint'])->name('sales.returns-complaints.complaints.store');

    Route::get('reports', [SalesAppReportController::class, 'index'])->name('sales.reports.index');

    Route::get('automation', [SalesAutomationController::class, 'index'])->name('sales.automation.index');
    Route::post('automation', [SalesAutomationController::class, 'store'])->name('sales.automation.store');
    Route::post('automation/{automationRule}/toggle', [SalesAutomationController::class, 'toggle'])->name('sales.automation.toggle');
    Route::post('automation/{automationRule}/run', [SalesAutomationController::class, 'run'])->name('sales.automation.run');
    Route::delete('automation/{automationRule}', [SalesAutomationController::class, 'destroy'])->name('sales.automation.destroy');

    Route::get('settings', [SalesSettingController::class, 'index'])->name('sales.settings.index');
    Route::put('settings', [SalesSettingController::class, 'update'])->name('sales.settings.update');
}); // app => sales
