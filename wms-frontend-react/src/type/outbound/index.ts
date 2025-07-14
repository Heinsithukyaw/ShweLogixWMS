// Outbound Operations TypeScript Interfaces

export interface PackingStation {
  id: number;
  station_code: string;
  station_name: string;
  warehouse_id: number;
  zone_id?: number;
  station_type: 'standard' | 'express' | 'fragile' | 'oversized' | 'multi_order';
  station_status: 'active' | 'inactive' | 'maintenance';
  capabilities?: Record<string, any>;
  max_weight_kg?: number;
  equipment_list?: Record<string, any>;
  assigned_to?: number;
  is_automated: boolean;
  warehouse?: any;
  zone?: any;
  employee?: any;
  created_at: string;
  updated_at: string;
}

export interface CartonType {
  id: number;
  carton_code: string;
  carton_name: string;
  length_cm: number;
  width_cm: number;
  height_cm: number;
  max_weight_kg: number;
  tare_weight_kg: number;
  volume_cm3: number;
  carton_material: 'cardboard' | 'plastic' | 'wood' | 'metal';
  cost_per_unit: number;
  usage_rules?: Record<string, any>;
  supplier?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
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
  salesOrder?: any;
  packingStation?: PackingStation;
  employee?: any;
  created_at: string;
  updated_at: string;
}

export interface PackedCarton {
  id: number;
  carton_number: string;
  pack_order_id: number;
  sales_order_id: number;
  carton_type_id: number;
  packing_station_id: number;
  packed_by: number;
  carton_sequence: number;
  gross_weight_kg: number;
  net_weight_kg: number;
  actual_length_cm?: number;
  actual_width_cm?: number;
  actual_height_cm?: number;
  packed_items: Record<string, any>;
  materials_used?: Record<string, any>;
  carton_status: 'packed' | 'verified' | 'shipped' | 'damaged';
  packed_at: string;
  verified_by?: number;
  verified_at?: string;
  packing_notes?: string;
  packOrder?: PackOrder;
  cartonType?: CartonType;
  packingStation?: PackingStation;
  packer?: any;
  verifier?: any;
  created_at: string;
  updated_at: string;
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
  shipping_address: Record<string, any>;
  billing_address?: Record<string, any>;
  total_weight_kg: number;
  total_volume_cm3: number;
  total_cartons: number;
  shipping_cost?: number;
  insurance_cost?: number;
  special_services?: Record<string, any>;
  ship_date: string;
  expected_delivery_date?: string;
  actual_delivery_date?: string;
  shipping_notes?: string;
  created_by: number;
  carrier?: any;
  customer?: any;
  created_at: string;
  updated_at: string;
}

export interface LoadPlan {
  id: number;
  load_plan_number: string;
  shipping_carrier_id: number;
  vehicle_type: string;
  vehicle_id?: string;
  shipment_ids: number[];
  load_status: 'planned' | 'loading' | 'loaded' | 'dispatched' | 'delivered';
  total_weight_kg: number;
  total_volume_cm3: number;
  vehicle_capacity_weight_kg: number;
  vehicle_capacity_volume_cm3: number;
  utilization_weight_pct: number;
  utilization_volume_pct: number;
  loading_sequence?: Record<string, any>;
  planned_departure_date: string;
  planned_departure_time: string;
  actual_departure_time?: string;
  loading_notes?: string;
  created_by: number;
  carrier?: any;
  shipments?: Shipment[];
  created_at: string;
  updated_at: string;
}

export interface QualityCheckpoint {
  id: number;
  checkpoint_code: string;
  checkpoint_name: string;
  warehouse_id: number;
  zone_id?: number;
  checkpoint_type: 'packing' | 'shipping' | 'final' | 'custom';
  checkpoint_location: string;
  is_mandatory: boolean;
  quality_criteria: Record<string, any>;
  checkpoint_sequence: number;
  is_active: boolean;
  created_by: number;
  warehouse?: any;
  zone?: any;
  created_at: string;
  updated_at: string;
}

export interface OutboundQualityCheck {
  id: number;
  check_number: string;
  checkpoint_id: number;
  sales_order_id?: number;
  shipment_id?: number;
  packed_carton_id?: number;
  inspector_id: number;
  check_results: Record<string, any>;
  overall_result: 'passed' | 'failed' | 'conditional';
  quality_score?: number;
  inspection_notes?: string;
  corrective_actions?: string;
  requires_reinspection: boolean;
  inspected_at: string;
  checkpoint?: QualityCheckpoint;
  inspector?: any;
  created_at: string;
  updated_at: string;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}

// Form Types
export interface CreatePackingStationForm {
  station_name: string;
  warehouse_id: number;
  zone_id?: number;
  station_type: 'standard' | 'express' | 'fragile' | 'oversized' | 'multi_order';
  station_status: 'active' | 'inactive' | 'maintenance';
  capabilities?: Record<string, any>;
  max_weight_kg?: number;
  equipment_list?: Record<string, any>;
  assigned_to?: number;
  is_automated?: boolean;
}

export interface CreatePackOrderForm {
  sales_order_id: number;
  packing_station_id: number;
  assigned_to?: number;
  pack_priority: 'low' | 'normal' | 'high' | 'urgent';
  total_items: number;
  estimated_time?: number;
  packing_notes?: string;
}

export interface CreateShipmentForm {
  sales_order_ids: number[];
  customer_id: number;
  shipping_carrier_id: number;
  service_level: string;
  shipment_type: 'standard' | 'express' | 'freight' | 'ltl' | 'parcel';
  tracking_number?: string;
  shipping_address: Record<string, any>;
  billing_address?: Record<string, any>;
  total_weight_kg: number;
  total_volume_cm3: number;
  total_cartons: number;
  shipping_cost?: number;
  insurance_cost?: number;
  special_services?: Record<string, any>;
  ship_date: string;
  expected_delivery_date?: string;
  shipping_notes?: string;
}