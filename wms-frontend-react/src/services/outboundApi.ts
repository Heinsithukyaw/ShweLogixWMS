import { apiClient } from './apiClient';

// Types
export interface OrderAllocation {
  id: number;
  sales_order_id: number;
  sales_order_item_id: number;
  product_id: number;
  location_id: number;
  lot_number?: string;
  serial_number?: string;
  allocated_quantity: number;
  picked_quantity: number;
  allocation_status: 'allocated' | 'partially_picked' | 'picked' | 'cancelled';
  allocation_type: 'fifo' | 'lifo' | 'fefo' | 'manual';
  allocated_at: string;
  expires_at?: string;
  allocation_rules?: any;
  allocated_by: number;
}

export interface PickList {
  id: number;
  pick_list_number: string;
  pick_wave_id: number;
  assigned_to?: number;
  pick_type: 'single' | 'batch' | 'zone' | 'cluster' | 'wave';
  pick_method: 'discrete' | 'batch' | 'zone' | 'cluster';
  pick_status: 'pending' | 'assigned' | 'in_progress' | 'completed' | 'cancelled';
  total_picks: number;
  completed_picks: number;
  estimated_time?: number;
  actual_time?: number;
  pick_sequence?: number[];
  assigned_at?: string;
  started_at?: string;
  completed_at?: string;
  notes?: string;
  created_by: number;
}

export interface PickListItem {
  id: number;
  pick_list_id: number;
  pick_task_id: number;
  sales_order_id: number;
  sales_order_item_id: number;
  product_id: number;
  location_id: number;
  lot_number?: string;
  serial_number?: string;
  quantity_to_pick: number;
  quantity_picked: number;
  pick_sequence: number;
  pick_status: 'pending' | 'in_progress' | 'picked' | 'short_picked' | 'cancelled';
  picked_at?: string;
  picked_by?: number;
  pick_notes?: string;
}

export interface PackOrder {
  id: number;
  pack_order_number: string;
  sales_order_id: number;
  packing_station_id: number;
  assigned_to?: number;
  pack_status: 'pending' | 'assigned' | 'in_progress' | 'packed' | 'verified' | 'cancelled';
  pack_priority: 'low' | 'normal' | 'high' | 'urgent';
  total_items: number;
  packed_items: number;
  estimated_time?: number;
  actual_time?: number;
  assigned_at?: string;
  started_at?: string;
  completed_at?: string;
  packing_notes?: string;
  created_by: number;
}

export interface Shipment {
  id: number;
  shipment_number: string;
  sales_order_ids: number[];
  customer_id: number;
  shipping_carrier_id: number;
  service_level: string;
  shipment_status: 'planned' | 'ready' | 'picked_up' | 'in_transit' | 'delivered' | 'exception';
  shipment_type: 'standard' | 'express' | 'freight' | 'ltl' | 'parcel';
  tracking_number?: string;
  shipping_address: any;
  billing_address?: any;
  total_weight_kg: number;
  total_volume_cm3: number;
  total_cartons: number;
  shipping_cost?: number;
  insurance_cost?: number;
  special_services?: any;
  ship_date: string;
  expected_delivery_date?: string;
  actual_delivery_date?: string;
  shipping_notes?: string;
  created_by: number;
}

// Order Management & Allocation API
export const orderAllocationApi = {
  // Get all allocations
  getAllocations: (params?: any) => 
    apiClient.get('/outbound/orders/allocations', { params }),

  // Allocate order
  allocateOrder: (orderId: number, data: {
    allocation_type: string;
    allocation_rules?: any;
    expires_at?: string;
  }) => 
    apiClient.post(`/outbound/orders/${orderId}/allocate`, data),

  // Allocate specific item
  allocateItem: (data: {
    sales_order_item_id: number;
    location_id: number;
    quantity: number;
    allocation_type: string;
    lot_number?: string;
    serial_number?: string;
    expires_at?: string;
  }) => 
    apiClient.post('/outbound/orders/allocations/item', data),

  // Get allocation details
  getAllocation: (id: number) => 
    apiClient.get(`/outbound/orders/allocations/${id}`),

  // Update allocation
  updateAllocation: (id: number, data: any) => 
    apiClient.put(`/outbound/orders/allocations/${id}`, data),

  // Cancel allocation
  cancelAllocation: (id: number) => 
    apiClient.delete(`/outbound/orders/allocations/${id}/cancel`),

  // Get order allocation summary
  getOrderSummary: (orderId: number) => 
    apiClient.get(`/outbound/orders/${orderId}/allocation-summary`),

  // Get available inventory
  getAvailableInventory: (params: {
    product_id: number;
    quantity_needed: number;
    allocation_type: string;
  }) => 
    apiClient.get('/outbound/orders/allocations/inventory/available', { params }),

  // Bulk allocate orders
  bulkAllocate: (data: {
    order_ids: number[];
    allocation_type: string;
    allocation_rules?: any;
  }) => 
    apiClient.post('/outbound/orders/allocations/bulk-allocate', data),

  // Reallocate expired allocations
  reallocateExpired: () => 
    apiClient.post('/outbound/orders/allocations/reallocate-expired'),
};

// Order Priority API
export const orderPriorityApi = {
  // Get all priorities
  getPriorities: (params?: any) => 
    apiClient.get('/outbound/orders/priorities', { params }),

  // Set order priority
  setPriority: (orderId: number, data: {
    priority_level: string;
    priority_reason?: string;
    is_manual_override?: boolean;
  }) => 
    apiClient.post(`/outbound/orders/${orderId}/priority`, data),

  // Calculate priorities
  calculatePriorities: (data: {
    order_ids?: number[];
    recalculate_all?: boolean;
  }) => 
    apiClient.post('/outbound/orders/priorities/calculate', data),

  // Get high priority orders
  getHighPriority: () => 
    apiClient.get('/outbound/orders/priorities/high-priority'),
};

// Back Order API
export const backOrderApi = {
  // Get all backorders
  getBackOrders: (params?: any) => 
    apiClient.get('/outbound/orders/backorders', { params }),

  // Create backorder
  createBackOrder: (data: {
    sales_order_id: number;
    sales_order_item_id: number;
    product_id: number;
    backordered_quantity: number;
    backorder_reason: string;
    expected_fulfillment_date?: string;
    auto_fulfill?: boolean;
  }) => 
    apiClient.post('/outbound/orders/backorders', data),

  // Get backorder details
  getBackOrder: (id: number) => 
    apiClient.get(`/outbound/orders/backorders/${id}`),

  // Update backorder
  updateBackOrder: (id: number, data: any) => 
    apiClient.put(`/outbound/orders/backorders/${id}`, data),

  // Fulfill backorder
  fulfillBackOrder: (id: number, data: { quantity: number }) => 
    apiClient.post(`/outbound/orders/backorders/${id}/fulfill`, data),

  // Auto-fulfill backorders
  autoFulfill: () => 
    apiClient.post('/outbound/orders/backorders/auto-fulfill'),

  // Get overdue backorders
  getOverdue: () => 
    apiClient.get('/outbound/orders/backorders/overdue'),
};

// Pick Management API
export const pickListApi = {
  // Get all pick lists
  getPickLists: (params?: any) => 
    apiClient.get('/outbound/picking/lists', { params }),

  // Generate pick lists for wave
  generateForWave: (waveId: number, data: {
    pick_type: string;
    pick_method: string;
    batch_size?: number;
    zone_assignments?: any;
    optimization_rules?: any;
  }) => 
    apiClient.post(`/outbound/picking/waves/${waveId}/generate-lists`, data),

  // Get pick list details
  getPickList: (id: number) => 
    apiClient.get(`/outbound/picking/lists/${id}`),

  // Assign pick list
  assignPickList: (id: number, data: { employee_id: number }) => 
    apiClient.post(`/outbound/picking/lists/${id}/assign`, data),

  // Start pick list
  startPickList: (id: number) => 
    apiClient.post(`/outbound/picking/lists/${id}/start`),

  // Complete pick list
  completePickList: (id: number) => 
    apiClient.post(`/outbound/picking/lists/${id}/complete`),

  // Pick item
  pickItem: (listId: number, itemId: number, data: {
    quantity_picked: number;
    employee_id: number;
    confirmation_method: string;
    barcode_scanned?: string;
    notes?: string;
  }) => 
    apiClient.post(`/outbound/picking/lists/${listId}/items/${itemId}/pick`, data),

  // Create pick exception
  createException: (listId: number, itemId: number, data: {
    exception_type: string;
    description: string;
    employee_id: number;
  }) => 
    apiClient.post(`/outbound/picking/lists/${listId}/items/${itemId}/exception`, data),

  // Optimize pick sequence
  optimizeSequence: (id: number) => 
    apiClient.post(`/outbound/picking/lists/${id}/optimize-sequence`),

  // Get performance metrics
  getPerformance: (id: number) => 
    apiClient.get(`/outbound/picking/lists/${id}/performance`),

  // Bulk assign pick lists
  bulkAssign: (data: {
    pick_list_ids: number[];
    employee_id: number;
  }) => 
    apiClient.post('/outbound/picking/lists/bulk-assign', data),

  // Get pick list summary
  getSummary: (params?: any) => 
    apiClient.get('/outbound/picking/lists/summary', { params }),
};

// Packing Operations API
export const packingApi = {
  // Packing Stations
  getPackingStations: (params?: any) => 
    apiClient.get('/outbound/packing/stations', { params }),

  createPackingStation: (data: {
    station_code: string;
    station_name: string;
    warehouse_id: number;
    zone_id?: number;
    station_type: string;
    capabilities?: any;
    max_weight_kg?: number;
    equipment_list?: any;
  }) => 
    apiClient.post('/outbound/packing/stations', data),

  getPackingStation: (id: number) => 
    apiClient.get(`/outbound/packing/stations/${id}`),

  updatePackingStation: (id: number, data: any) => 
    apiClient.put(`/outbound/packing/stations/${id}`, data),

  deletePackingStation: (id: number) => 
    apiClient.delete(`/outbound/packing/stations/${id}`),

  // Pack Orders
  getPackOrders: (params?: any) => 
    apiClient.get('/outbound/packing/orders', { params }),

  createPackOrder: (data: {
    sales_order_id: number;
    packing_station_id: number;
    pack_priority?: string;
    estimated_time?: number;
  }) => 
    apiClient.post('/outbound/packing/orders', data),

  getPackOrder: (id: number) => 
    apiClient.get(`/outbound/packing/orders/${id}`),

  assignPackOrder: (id: number, data: { employee_id: number }) => 
    apiClient.post(`/outbound/packing/orders/${id}/assign`, data),

  startPackOrder: (id: number) => 
    apiClient.post(`/outbound/packing/orders/${id}/start`),

  packOrder: (id: number, data: {
    carton_type_id: number;
    items: any[];
    materials_used?: any;
    gross_weight_kg: number;
    net_weight_kg: number;
    dimensions?: any;
  }) => 
    apiClient.post(`/outbound/packing/orders/${id}/pack`, data),

  completePackOrder: (id: number) => 
    apiClient.post(`/outbound/packing/orders/${id}/complete`),

  validatePackOrder: (id: number, data: {
    validation_type: string;
    expected_value?: number;
    actual_value?: number;
  }) => 
    apiClient.post(`/outbound/packing/orders/${id}/validate`, data),

  // Carton Types
  getCartonTypes: (params?: any) => 
    apiClient.get('/outbound/packing/cartons', { params }),

  createCartonType: (data: {
    carton_code: string;
    carton_name: string;
    length_cm: number;
    width_cm: number;
    height_cm: number;
    max_weight_kg: number;
    tare_weight_kg: number;
    carton_material: string;
    cost_per_unit: number;
  }) => 
    apiClient.post('/outbound/packing/cartons', data),

  selectOptimalCarton: (data: {
    items: any[];
    total_weight: number;
    total_volume: number;
  }) => 
    apiClient.post('/outbound/packing/cartons/select-optimal', data),
};

// Shipping & Loading API
export const shippingApi = {
  // Shipments
  getShipments: (params?: any) => 
    apiClient.get('/outbound/shipping/shipments', { params }),

  createShipment: (data: {
    sales_order_ids: number[];
    customer_id: number;
    shipping_carrier_id: number;
    service_level: string;
    shipment_type: string;
    shipping_address: any;
    ship_date: string;
    special_services?: any;
  }) => 
    apiClient.post('/outbound/shipping/shipments', data),

  getShipment: (id: number) => 
    apiClient.get(`/outbound/shipping/shipments/${id}`),

  updateShipment: (id: number, data: any) => 
    apiClient.put(`/outbound/shipping/shipments/${id}`, data),

  planShipment: (id: number) => 
    apiClient.post(`/outbound/shipping/shipments/${id}/plan`),

  generateLabels: (id: number) => 
    apiClient.post(`/outbound/shipping/shipments/${id}/labels`),

  generateDocuments: (id: number, data: { document_types: string[] }) => 
    apiClient.post(`/outbound/shipping/shipments/${id}/documents`, data),

  getTracking: (id: number) => 
    apiClient.get(`/outbound/shipping/shipments/${id}/tracking`),

  // Shipping Rates
  shopRates: (data: {
    origin_zip: string;
    destination_zip: string;
    weight_kg: number;
    volume_cm3: number;
    service_types?: string[];
  }) => 
    apiClient.post('/outbound/shipping/rates/shop', data),

  compareRates: (data: {
    shipment_id: number;
    carriers?: number[];
  }) => 
    apiClient.post('/outbound/shipping/rates/compare', data),

  // Load Planning
  getLoadPlans: (params?: any) => 
    apiClient.get('/outbound/shipping/loads', { params }),

  createLoadPlan: (data: {
    shipping_carrier_id: number;
    vehicle_type: string;
    shipment_ids: number[];
    planned_departure_date: string;
    planned_departure_time: string;
  }) => 
    apiClient.post('/outbound/shipping/loads', data),

  getLoadPlan: (id: number) => 
    apiClient.get(`/outbound/shipping/loads/${id}`),

  optimizeLoad: (id: number) => 
    apiClient.post(`/outbound/shipping/loads/${id}/optimize`),

  confirmLoading: (id: number, data: {
    loaded_shipments: number[];
    actual_weight_kg?: number;
    loading_notes?: string;
  }) => 
    apiClient.post(`/outbound/shipping/loads/${id}/confirm-loading`, data),

  dispatchLoad: (id: number) => 
    apiClient.post(`/outbound/shipping/loads/${id}/dispatch`),

  // Dock Scheduling
  getDockSchedules: (params?: any) => 
    apiClient.get('/outbound/shipping/docks/schedules', { params }),

  createDockSchedule: (data: {
    loading_dock_id: number;
    load_plan_id?: number;
    shipping_carrier_id: number;
    scheduled_date: string;
    scheduled_start_time: string;
    scheduled_end_time: string;
    driver_name?: string;
    vehicle_license?: string;
    special_instructions?: string;
  }) => 
    apiClient.post('/outbound/shipping/docks/schedules', data),

  getDockAvailability: (params: {
    date: string;
    dock_id?: number;
  }) => 
    apiClient.get('/outbound/shipping/docks/availability', { params }),
};

// Mobile API for Outbound Operations
export const mobileOutboundApi = {
  // Pick Lists
  getAssignedPickLists: (employeeId: number) => 
    apiClient.get(`/mobile/outbound/pick-lists/assigned/${employeeId}`),

  scanAndPick: (listId: number, data: {
    barcode: string;
    quantity: number;
    employee_id: number;
  }) => 
    apiClient.post(`/mobile/outbound/pick-lists/${listId}/scan-item`, data),

  // Pack Orders
  getAssignedPackOrders: (employeeId: number) => 
    apiClient.get(`/mobile/outbound/pack-orders/assigned/${employeeId}`),

  scanAndPack: (orderId: number, data: {
    carton_barcode: string;
    items: any[];
    employee_id: number;
  }) => 
    apiClient.post(`/mobile/outbound/pack-orders/${orderId}/scan-carton`, data),

  // Shipping
  scanLabel: (shipmentId: number, data: {
    label_barcode: string;
    employee_id: number;
  }) => 
    apiClient.post(`/mobile/outbound/shipments/${shipmentId}/scan-label`, data),
};

// Analytics API
export const outboundAnalyticsApi = {
  getOutboundKPIs: (params?: any) => 
    apiClient.get('/outbound/analytics/kpis/outbound', { params }),

  getDailySummary: (params?: any) => 
    apiClient.get('/outbound/analytics/reports/daily-summary', { params }),

  getPickingPerformance: (params?: any) => 
    apiClient.get('/outbound/analytics/performance/picking', { params }),

  getPackingPerformance: (params?: any) => 
    apiClient.get('/outbound/analytics/performance/packing', { params }),

  getShippingPerformance: (params?: any) => 
    apiClient.get('/outbound/analytics/performance/shipping', { params }),
};