export interface PickWave {
  id: number;
  wave_number: string;
  wave_type: 'standard' | 'priority' | 'express' | 'batch' | 'zone';
  wave_status: 'planned' | 'released' | 'in_progress' | 'completed' | 'cancelled';
  total_orders: number;
  total_lines: number;
  total_units: number;
  assigned_to?: number[];
  planned_start_time?: string;
  planned_completion_time?: string;
  actual_start_time?: string;
  actual_completion_time?: string;
  created_at: string;
  created_by: number;
}

export interface PickWaveOrder {
  id: number;
  wave_id: number;
  sales_order_id: number;
  sales_order_number: string;
  customer_name: string;
  order_priority: 'low' | 'normal' | 'high' | 'urgent';
  order_type: string;
  total_lines: number;
  total_units: number;
  ship_by_date: string;
}

export interface PickWaveItem {
  id: number;
  wave_id: number;
  sales_order_id: number;
  sales_order_item_id: number;
  product_id: number;
  product_name: string;
  product_sku: string;
  quantity_ordered: number;
  quantity_allocated: number;
  quantity_picked: number;
  location_id?: number;
  location_code?: string;
  zone_id?: number;
  zone_name?: string;
  pick_status: 'pending' | 'allocated' | 'picked' | 'short_picked' | 'exception';
}

export interface PickTask {
  id: number;
  task_number: string;
  pick_list_id: number;
  employee_id?: number;
  employee_name?: string;
  task_type: 'single' | 'batch' | 'zone' | 'cluster';
  task_status: 'pending' | 'assigned' | 'in_progress' | 'completed' | 'cancelled';
  priority: 'low' | 'normal' | 'high' | 'urgent';
  total_items: number;
  completed_items: number;
  estimated_time_minutes?: number;
  actual_time_minutes?: number;
  assigned_at?: string;
  started_at?: string;
  completed_at?: string;
  created_at: string;
}

export interface PickTaskItem {
  id: number;
  task_id: number;
  sales_order_id: number;
  sales_order_number: string;
  product_id: number;
  product_name: string;
  product_sku: string;
  location_id: number;
  location_code: string;
  zone_id: number;
  zone_name: string;
  quantity_to_pick: number;
  quantity_picked: number;
  sequence_number: number;
  status: 'pending' | 'picked' | 'short_picked' | 'exception';
  lot_number?: string;
  serial_number?: string;
  expiration_date?: string;
  picked_at?: string;
  picked_by?: number;
  exception_reason?: string;
}

export interface PickException {
  id: number;
  task_id: number;
  task_item_id: number;
  exception_type: 'inventory_shortage' | 'location_mismatch' | 'product_damage' | 'barcode_issue' | 'other';
  description: string;
  reported_by: number;
  reported_at: string;
  resolution_status: 'pending' | 'resolved' | 'escalated';
  resolved_by?: number;
  resolved_at?: string;
  resolution_notes?: string;
}

export interface PickPerformanceMetrics {
  employee_id: number;
  employee_name: string;
  total_picks: number;
  completed_picks: number;
  pick_accuracy: number;
  picks_per_hour: number;
  average_pick_time: number;
  exceptions_count: number;
  exception_rate: number;
  time_period: string;
}

export interface WaveCreationParams {
  wave_type: 'standard' | 'priority' | 'express' | 'batch' | 'zone';
  order_ids?: number[];
  order_criteria?: {
    ship_by_date_start?: string;
    ship_by_date_end?: string;
    order_types?: string[];
    customer_ids?: number[];
    priority_levels?: string[];
    product_categories?: string[];
  };
  wave_criteria?: {
    max_orders?: number;
    max_lines?: number;
    max_units?: number;
    max_volume?: number;
    max_weight?: number;
  };
  assignment?: {
    auto_assign?: boolean;
    employee_ids?: number[];
  };
  scheduling?: {
    planned_start_time?: string;
    planned_completion_time?: string;
  };
}