<?php

use Illuminate\Support\Facades\Route;

// Document Management Routes
Route::group(['prefix' => 'documents', 'namespace' => 'App\Http\Controllers\Document'], function () {
    // Document Categories
    Route::get('categories', 'DocumentCategoryController@index');
    Route::post('categories', 'DocumentCategoryController@store');
    Route::get('categories/{id}', 'DocumentCategoryController@show');
    Route::put('categories/{id}', 'DocumentCategoryController@update');
    Route::delete('categories/{id}', 'DocumentCategoryController@destroy');
    
    // Documents
    Route::get('/', 'DocumentController@index');
    Route::post('/', 'DocumentController@store');
    Route::get('/{id}', 'DocumentController@show');
    Route::post('/{id}', 'DocumentController@update');
    Route::delete('/{id}', 'DocumentController@destroy');
    Route::get('/{id}/download', 'DocumentController@download');
    Route::get('/{id}/download/{versionId}', 'DocumentController@download');
    
    // Document Versions
    Route::get('/{id}/versions', 'DocumentVersionController@index');
    Route::post('/{id}/versions', 'DocumentVersionController@store');
    Route::get('/{id}/versions/{versionId}', 'DocumentVersionController@show');
    Route::put('/{id}/versions/{versionId}', 'DocumentVersionController@update');
    Route::post('/{id}/versions/{versionId}/restore', 'DocumentVersionController@restore');
    Route::get('/{id}/versions/{versionId}/download', 'DocumentVersionController@download');
    
    // Document Permissions
    Route::get('/{id}/permissions', 'DocumentPermissionController@index');
    Route::post('/{id}/permissions', 'DocumentPermissionController@store');
    Route::put('/{id}/permissions/{permissionId}', 'DocumentPermissionController@update');
    Route::delete('/{id}/permissions/{permissionId}', 'DocumentPermissionController@destroy');
    
    // Document Shares
    Route::get('/{id}/shares', 'DocumentShareController@index');
    Route::post('/{id}/shares', 'DocumentShareController@store');
    Route::put('/{id}/shares/{shareId}', 'DocumentShareController@update');
    Route::delete('/{id}/shares/{shareId}', 'DocumentShareController@destroy');
    
    // Shared Document Access
    Route::get('/shared/{token}', 'DocumentShareController@accessShared');
    Route::post('/shared/{token}', 'DocumentShareController@accessShared');
    Route::get('/shared/{token}/download', 'DocumentShareController@downloadShared');
    Route::post('/shared/{token}/download', 'DocumentShareController@downloadShared');
});

// Workflow Engine Routes
Route::group(['prefix' => 'workflows', 'namespace' => 'App\Http\Controllers\Workflow'], function () {
    // Workflow Definitions
    Route::get('definitions', 'WorkflowDefinitionController@index');
    Route::post('definitions', 'WorkflowDefinitionController@store');
    Route::get('definitions/{id}', 'WorkflowDefinitionController@show');
    Route::put('definitions/{id}', 'WorkflowDefinitionController@update');
    Route::delete('definitions/{id}', 'WorkflowDefinitionController@destroy');
    Route::post('definitions/{id}/version', 'WorkflowDefinitionController@createVersion');
    Route::get('definitions/entity-type/{entityType}', 'WorkflowDefinitionController@getByEntityType');
    
    // Workflow Steps
    Route::get('definitions/{workflowId}/steps', 'WorkflowStepController@index');
    Route::post('definitions/{workflowId}/steps', 'WorkflowStepController@store');
    Route::get('definitions/{workflowId}/steps/{stepId}', 'WorkflowStepController@show');
    Route::put('definitions/{workflowId}/steps/{stepId}', 'WorkflowStepController@update');
    Route::delete('definitions/{workflowId}/steps/{stepId}', 'WorkflowStepController@destroy');
    Route::get('definitions/{workflowId}/steps/code/{stepCode}', 'WorkflowStepController@getByStepCode');
    
    // Workflow Instances
    Route::get('instances', 'WorkflowInstanceController@index');
    Route::post('instances/start', 'WorkflowInstanceController@start');
    Route::get('instances/{id}', 'WorkflowInstanceController@show');
    Route::post('instances/{id}/transition', 'WorkflowInstanceController@transition');
    Route::post('instances/{id}/cancel', 'WorkflowInstanceController@cancel');
    Route::put('instances/{id}/data', 'WorkflowInstanceController@updateData');
    Route::get('instances/entity/{entityType}/{entityId}', 'WorkflowInstanceController@getByEntity');
    Route::get('instances/active/entity/{entityType}/{entityId}', 'WorkflowInstanceController@getActiveByEntity');
    
    // Workflow Step Instances
    Route::get('instances/{instanceId}/steps', 'WorkflowStepInstanceController@index');
    Route::get('instances/{instanceId}/steps/{stepInstanceId}', 'WorkflowStepInstanceController@show');
    Route::put('instances/{instanceId}/steps/{stepInstanceId}', 'WorkflowStepInstanceController@update');
    Route::post('instances/{instanceId}/steps/{stepInstanceId}/approval', 'WorkflowStepInstanceController@createApproval');
    Route::get('instances/{instanceId}/steps/{stepInstanceId}/approvals', 'WorkflowStepInstanceController@getApprovals');
    Route::get('instances/{instanceId}/current-step', 'WorkflowStepInstanceController@getCurrentStep');
});

// Data Lineage Routes
Route::group(['prefix' => 'data-lineage', 'namespace' => 'App\Http\Controllers\DataLineage'], function () {
    // Data Sources
    Route::get('sources', 'DataSourceController@index');
    Route::post('sources', 'DataSourceController@store');
    Route::get('sources/{id}', 'DataSourceController@show');
    Route::put('sources/{id}', 'DataSourceController@update');
    Route::delete('sources/{id}', 'DataSourceController@destroy');
    Route::post('sources/{id}/test-connection', 'DataSourceController@testConnection');
    
    // Data Entities
    Route::get('entities', 'DataEntityController@index');
    Route::post('entities', 'DataEntityController@store');
    Route::get('entities/{id}', 'DataEntityController@show');
    Route::put('entities/{id}', 'DataEntityController@update');
    Route::delete('entities/{id}', 'DataEntityController@destroy');
    Route::get('entities/{id}/fields', 'DataEntityController@getFields');
    Route::post('entities/{id}/fields', 'DataEntityController@addField');
    Route::put('entities/{id}/fields/{fieldId}', 'DataEntityController@updateField');
    Route::delete('entities/{id}/fields/{fieldId}', 'DataEntityController@removeField');
    
    // Data Flows
    Route::get('flows', 'DataFlowController@index');
    Route::post('flows', 'DataFlowController@store');
    Route::get('flows/{id}', 'DataFlowController@show');
    Route::put('flows/{id}', 'DataFlowController@update');
    Route::delete('flows/{id}', 'DataFlowController@destroy');
    Route::get('flows/{id}/field-mappings', 'DataFlowController@getFieldMappings');
    Route::post('flows/{id}/field-mappings', 'DataFlowController@addFieldMapping');
    Route::put('flows/{id}/field-mappings/{mappingId}', 'DataFlowController@updateFieldMapping');
    Route::delete('flows/{id}/field-mappings/{mappingId}', 'DataFlowController@removeFieldMapping');
    Route::get('flows/{id}/transformations', 'DataFlowController@getTransformations');
    Route::post('flows/{id}/transformations', 'DataFlowController@addTransformation');
    Route::put('flows/{id}/transformations/{transformationId}', 'DataFlowController@updateTransformation');
    Route::delete('flows/{id}/transformations/{transformationId}', 'DataFlowController@removeTransformation');
    Route::post('flows/{id}/execute', 'DataFlowController@executeFlow');
    
    // Data Flow Executions
    Route::get('executions', 'DataFlowExecutionController@index');
    Route::get('executions/{id}', 'DataFlowExecutionController@show');
    Route::post('executions/{id}/cancel', 'DataFlowExecutionController@cancel');
    Route::post('executions/{id}/retry', 'DataFlowExecutionController@retry');
    Route::get('executions/{id}/logs', 'DataFlowExecutionController@getLogs');
    Route::get('flows/{flowId}/statistics', 'DataFlowExecutionController@getFlowStatistics');
});

// Deduplication Engine Routes
Route::group(['prefix' => 'deduplication', 'namespace' => 'App\Http\Controllers\Deduplication'], function () {
    // Deduplication Rules
    Route::get('rules', 'DeduplicationRuleController@index');
    Route::post('rules', 'DeduplicationRuleController@store');
    Route::get('rules/{id}', 'DeduplicationRuleController@show');
    Route::put('rules/{id}', 'DeduplicationRuleController@update');
    Route::delete('rules/{id}', 'DeduplicationRuleController@destroy');
    Route::post('rules/{id}/execute', 'DeduplicationRuleController@executeRule');
    
    // Deduplication Executions
    Route::get('executions', 'DeduplicationExecutionController@index');
    Route::get('executions/{id}', 'DeduplicationExecutionController@show');
    Route::post('executions/{id}/cancel', 'DeduplicationExecutionController@cancel');
    Route::get('executions/{id}/matches', 'DeduplicationExecutionController@getMatches');
    Route::get('executions/{id}/statistics', 'DeduplicationExecutionController@getStatistics');
    Route::get('executions/{id}/logs', 'DeduplicationExecutionController@getLogs');
    
    // Duplicate Matches
    Route::get('matches/{id}', 'DuplicateMatchController@show');
    Route::post('matches/{id}/merge', 'DuplicateMatchController@merge');
    Route::post('matches/{id}/keep', 'DuplicateMatchController@keep');
    Route::post('matches/{id}/ignore', 'DuplicateMatchController@ignore');
    Route::post('matches/{id}/undo', 'DuplicateMatchController@undoResolution');
    Route::get('matches/group/{groupId}', 'DuplicateMatchController@getByGroup');
    Route::post('matches/group/{groupId}/resolve', 'DuplicateMatchController@resolveGroup');
    
    // Fuzzy Matching Configurations
    Route::get('fuzzy-configs', 'FuzzyMatchingConfigController@index');
    Route::post('fuzzy-configs', 'FuzzyMatchingConfigController@store');
    Route::get('fuzzy-configs/{id}', 'FuzzyMatchingConfigController@show');
    Route::put('fuzzy-configs/{id}', 'FuzzyMatchingConfigController@update');
    Route::delete('fuzzy-configs/{id}', 'FuzzyMatchingConfigController@destroy');
    Route::post('fuzzy-configs/{id}/test', 'FuzzyMatchingConfigController@testMatching');
});

// EDI/IDoc Routes
Route::group(['prefix' => 'edi', 'namespace' => 'App\Http\Controllers\EDI'], function () {
    // EDI Trading Partners
    Route::get('partners', 'EdiTradingPartnerController@index');
    Route::post('partners', 'EdiTradingPartnerController@store');
    Route::get('partners/{id}', 'EdiTradingPartnerController@show');
    Route::put('partners/{id}', 'EdiTradingPartnerController@update');
    Route::delete('partners/{id}', 'EdiTradingPartnerController@destroy');
    Route::post('partners/{id}/test-connection', 'EdiTradingPartnerController@testConnection');
    
    // EDI Document Types
    Route::get('document-types', 'EdiDocumentTypeController@index');
    Route::post('document-types', 'EdiDocumentTypeController@store');
    Route::get('document-types/{id}', 'EdiDocumentTypeController@show');
    Route::put('document-types/{id}', 'EdiDocumentTypeController@update');
    Route::delete('document-types/{id}', 'EdiDocumentTypeController@destroy');
    Route::get('document-types/standard/{standard}', 'EdiDocumentTypeController@getByStandard');
    Route::post('document-types/{id}/validate', 'EdiDocumentTypeController@validateDocument');
    
    // EDI Mappings
    Route::get('mappings', 'EdiMappingController@index');
    Route::post('mappings', 'EdiMappingController@store');
    Route::get('mappings/{id}', 'EdiMappingController@show');
    Route::put('mappings/{id}', 'EdiMappingController@update');
    Route::delete('mappings/{id}', 'EdiMappingController@destroy');
    Route::post('mappings/{id}/test', 'EdiMappingController@testMapping');
    
    // EDI Transactions
    Route::get('transactions', 'EdiTransactionController@index');
    Route::post('transactions', 'EdiTransactionController@store');
    Route::get('transactions/{id}', 'EdiTransactionController@show');
    Route::put('transactions/{id}', 'EdiTransactionController@update');
    Route::post('transactions/inbound', 'EdiTransactionController@processInbound');
    Route::post('transactions/outbound', 'EdiTransactionController@generateOutbound');
    Route::get('transactions/{id}/original-data', 'EdiTransactionController@getOriginalData');
    
    // IDoc Configurations
    Route::get('idoc/configurations', 'IdocController@indexConfigurations');
    Route::post('idoc/configurations', 'IdocController@storeConfiguration');
    Route::get('idoc/configurations/{id}', 'IdocController@showConfiguration');
    Route::put('idoc/configurations/{id}', 'IdocController@updateConfiguration');
    Route::delete('idoc/configurations/{id}', 'IdocController@destroyConfiguration');
    Route::post('idoc/configurations/{id}/test-connection', 'IdocController@testConnection');
    
    // IDoc Transactions
    Route::get('idoc/transactions', 'IdocController@indexTransactions');
    Route::post('idoc/transactions', 'IdocController@storeTransaction');
    Route::get('idoc/transactions/{id}', 'IdocController@showTransaction');
    Route::put('idoc/transactions/{id}', 'IdocController@updateTransaction');
    Route::post('idoc/inbound', 'IdocController@processInbound');
    Route::post('idoc/outbound', 'IdocController@generateOutbound');
    Route::get('idoc/transactions/{id}/original-data', 'IdocController@getOriginalData');
});

// Batch Processing Routes
Route::group(['prefix' => 'batch', 'namespace' => 'App\Http\Controllers\Batch'], function () {
    // Batch Job Definitions
    Route::get('job-definitions', 'BatchJobDefinitionController@index');
    Route::post('job-definitions', 'BatchJobDefinitionController@store');
    Route::get('job-definitions/{id}', 'BatchJobDefinitionController@show');
    Route::put('job-definitions/{id}', 'BatchJobDefinitionController@update');
    Route::delete('job-definitions/{id}', 'BatchJobDefinitionController@destroy');
    Route::post('job-definitions/{id}/run', 'BatchJobDefinitionController@runJob');
    
    // Batch Job Schedules
    Route::get('job-schedules', 'BatchJobScheduleController@index');
    Route::post('job-schedules', 'BatchJobScheduleController@store');
    Route::get('job-schedules/{id}', 'BatchJobScheduleController@show');
    Route::put('job-schedules/{id}', 'BatchJobScheduleController@update');
    Route::delete('job-schedules/{id}', 'BatchJobScheduleController@destroy');
    Route::post('job-schedules/{id}/toggle-active', 'BatchJobScheduleController@toggleActive');
    Route::post('job-schedules/{id}/run-now', 'BatchJobScheduleController@runNow');
    
    // Batch Job Instances
    Route::get('job-instances', 'BatchJobInstanceController@index');
    Route::get('job-instances/{id}', 'BatchJobInstanceController@show');
    Route::post('job-instances/{id}/cancel', 'BatchJobInstanceController@cancel');
    Route::post('job-instances/{id}/restart', 'BatchJobInstanceController@restart');
    Route::get('job-instances/{id}/chunks', 'BatchJobInstanceController@getChunks');
    Route::get('job-instances/{id}/chunks/{chunkId}/records', 'BatchJobInstanceController@getChunkRecords');
    Route::get('job-instances/{id}/error-records', 'BatchJobInstanceController@getErrorRecords');
    Route::get('job-instances/{id}/logs', 'BatchJobInstanceController@getLogs');
    
    // File Transfers
    Route::get('file-transfer/configurations', 'FileTransferController@indexConfigurations');
    Route::post('file-transfer/configurations', 'FileTransferController@storeConfiguration');
    Route::get('file-transfer/configurations/{id}', 'FileTransferController@showConfiguration');
    Route::put('file-transfer/configurations/{id}', 'FileTransferController@updateConfiguration');
    Route::delete('file-transfer/configurations/{id}', 'FileTransferController@destroyConfiguration');
    Route::post('file-transfer/configurations/{id}/test-connection', 'FileTransferController@testConnection');
    
    Route::get('file-transfer/schedules', 'FileTransferController@indexSchedules');
    Route::post('file-transfer/schedules', 'FileTransferController@storeSchedule');
    Route::get('file-transfer/schedules/{id}', 'FileTransferController@showSchedule');
    Route::put('file-transfer/schedules/{id}', 'FileTransferController@updateSchedule');
    Route::delete('file-transfer/schedules/{id}', 'FileTransferController@destroySchedule');
    
    Route::get('file-transfer/transfers', 'FileTransferController@indexTransfers');
    Route::get('file-transfer/transfers/{id}', 'FileTransferController@showTransfer');
    Route::post('file-transfer/transfers', 'FileTransferController@initiateTransfer');
    Route::post('file-transfer/transfers/{id}/cancel', 'FileTransferController@cancelTransfer');
    Route::post('file-transfer/transfers/{id}/retry', 'FileTransferController@retryTransfer');
});