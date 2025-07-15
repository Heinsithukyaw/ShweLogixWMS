# ShweLogixWMS Outbound Operations API Documentation

## Overview

The Outbound Operations API provides comprehensive functionality for managing the complete order fulfillment process from order priority management to final shipment dispatch. This API supports advanced features including order consolidation, multi-strategy picking, intelligent packing, and route optimization.

## Base URL
```
https://api.shwelogixwms.com/api/outbound
```

## Authentication
All API endpoints require authentication using Bearer tokens:
```
Authorization: Bearer {your-api-token}
```

---

## 📋 Order Priority Management

### Create Order Priority
**POST** `/order-priorities`

Creates a new order priority assignment with dynamic scoring.

#### Request Body
```json
{
  "sales_order_id": 123,
  "warehouse_id": 1,
  "priority_level": "high",
  "priority_reason": "VIP Customer - Rush Order",
  "requested_ship_date": "2024-01-20",
  "customer_priority": "expedited",
  "business_impact": "High value customer retention",
  "special_instructions": "Handle with priority",
  "auto_calculate_score": true
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "id": 456,
    "sales_order_id": 123,
    "priority_level": "high",
    "priority_score": 85,
    "priority_reason": "VIP Customer - Rush Order",
    "effective_date": "2024-01-15T10:30:00Z",
    "expires_at": "2024-01-20T23:59:59Z",
    "status": "active"
  },
  "message": "Order priority created successfully"
}
```

### Bulk Update Priorities
**POST** `/order-priorities/bulk-update`

Updates multiple order priorities simultaneously.

#### Request Body
```json
{
  "updates": [
    {
      "sales_order_id": 123,
      "priority_level": "urgent",
      "priority_reason": "Customer escalation"
    },
    {
      "sales_order_id": 124,
      "priority_level": "medium",
      "priority_reason": "Standard processing"
    }
  ],
  "apply_immediately": true
}
```

---

## 🔄 Order Consolidation

### Create Consolidation
**POST** `/order-consolidations`

Creates an order consolidation with multiple strategies.

#### Request Body
```json
{
  "warehouse_id": 1,
  "consolidation_type": "customer",
  "consolidation_criteria": {
    "customer_id": 789,
    "ship_to_postal_code": "12345",
    "carrier_id": 2
  },
  "orders": [123, 124, 125],
  "priority_level": "high",
  "consolidation_window_hours": 24,
  "max_orders": 10,
  "max_weight": 50.0,
  "max_volume": 100.0,
  "notes": "Customer consolidation for efficiency"
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "id": 789,
    "consolidation_number": "CONS-2024-000123",
    "consolidation_type": "customer",
    "total_orders": 3,
    "total_weight": 15.5,
    "total_volume": 45.2,
    "estimated_savings": 25.50,
    "consolidation_score": 92,
    "status": "active",
    "expires_at": "2024-01-16T10:30:00Z"
  },
  "message": "Order consolidation created successfully"
}
```

### Get Consolidation Opportunities
**GET** `/order-consolidations/opportunities`

Identifies potential consolidation opportunities.

#### Query Parameters
- `warehouse_id` (required): Warehouse ID
- `consolidation_type`: customer, location, carrier, route
- `min_savings`: Minimum savings threshold
- `max_age_hours`: Maximum order age for consolidation

---

## 📦 Batch Pick Management

### Create Batch Pick
**POST** `/batch-picks`

Creates a new batch pick with optimization.

#### Request Body
```json
{
  "warehouse_id": 1,
  "pick_type": "multi_order",
  "batch_strategy": "priority",
  "max_orders": 5,
  "max_items": 50,
  "max_weight": 25.0,
  "assigned_picker_id": 10,
  "auto_assign": true,
  "optimization_criteria": {
    "minimize_travel": true,
    "group_by_location": true,
    "consider_item_weight": true
  }
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "id": 456,
    "batch_number": "BP-2024-000123",
    "pick_type": "multi_order",
    "total_orders": 4,
    "total_items": 28,
    "total_locations": 15,
    "estimated_pick_time": 45,
    "assigned_picker_id": 10,
    "status": "created",
    "optimization_score": 88
  },
  "message": "Batch pick created successfully"
}
```

### Start Batch Pick
**POST** `/batch-picks/{id}/start`

Starts the batch picking process.

### Complete Batch Pick
**POST** `/batch-picks/{id}/complete`

Completes the batch pick with results.

#### Request Body
```json
{
  "completion_notes": "Batch pick completed successfully",
  "actual_pick_time": 42,
  "items_picked": 28,
  "items_short": 0,
  "quality_issues": []
}
```

---

## 🎯 Zone Pick Management

### Create Zone Pick
**POST** `/zone-picks`

Creates a zone-based pick operation.

#### Request Body
```json
{
  "warehouse_id": 1,
  "zone_id": 5,
  "pick_strategy": "distance_optimized",
  "orders": [123, 124],
  "max_pickers": 2,
  "auto_assign_pickers": true,
  "pick_sequence_optimization": "shortest_path"
}
```

### Assign Pickers
**POST** `/zone-picks/{id}/assign-pickers`

Assigns pickers to zone pick operations.

#### Request Body
```json
{
  "assignments": [
    {
      "picker_id": 10,
      "assignment_type": "primary",
      "assigned_locations": ["A1-B1-L1", "A1-B2-L1"]
    },
    {
      "picker_id": 11,
      "assignment_type": "secondary",
      "assigned_locations": ["A2-B1-L1", "A2-B2-L1"]
    }
  ]
}
```

---

## 🔗 Cluster Pick Management

### Create Cluster Pick
**POST** `/cluster-picks`

Creates a cluster pick with advanced optimization.

#### Request Body
```json
{
  "warehouse_id": 1,
  "cluster_strategy": "location_based",
  "orders": [123, 124, 125],
  "max_cluster_size": 5,
  "assigned_picker_id": 10,
  "auto_optimize": true,
  "optimization_parameters": {
    "distance_weight": 0.4,
    "volume_weight": 0.3,
    "priority_weight": 0.3
  }
}
```

### Optimize Cluster Pick
**POST** `/cluster-picks/{id}/optimize`

Optimizes the cluster pick route and sequence.

#### Request Body
```json
{
  "optimization_type": "mixed",
  "constraints": {
    "max_travel_distance": 500,
    "respect_pick_priorities": true,
    "consider_item_fragility": true
  },
  "force_reoptimize": true
}
```

---

## 📋 Pack Order Management

### Create Pack Order
**POST** `/pack-orders`

Creates a new pack order for shipment preparation.

#### Request Body
```json
{
  "sales_order_id": 123,
  "packing_station_id": 5,
  "packer_id": 10,
  "carton_type_id": 15,
  "pack_method": "multi_item",
  "priority_level": "high",
  "special_instructions": "Handle fragile items carefully",
  "pack_deadline": "2024-01-15T16:00:00Z",
  "requires_gift_wrap": false,
  "requires_signature": true,
  "items": [
    {
      "sales_order_item_id": 456,
      "quantity_to_pack": 2,
      "special_handling": "fragile"
    }
  ]
}
```

### Start Packing
**POST** `/pack-orders/{id}/start-packing`

Initiates the packing process.

### Complete Packing
**POST** `/pack-orders/{id}/complete-packing`

Completes the packing with final measurements and details.

#### Request Body
```json
{
  "actual_weight": 2.5,
  "actual_dimensions": {
    "length": 30,
    "width": 20,
    "height": 15
  },
  "carton_used": 15,
  "packing_materials": ["bubble_wrap", "packing_peanuts"],
  "quality_notes": "Packed securely",
  "completion_notes": "Ready for shipment",
  "items": [
    {
      "item_id": 789,
      "quantity_packed": 2,
      "condition": "good",
      "notes": "Packed with extra protection"
    }
  ]
}
```

---

## 🚚 Shipment Management

### Create Shipment
**POST** `/shipments`

Creates a new shipment for order delivery.

#### Request Body
```json
{
  "warehouse_id": 1,
  "carrier_id": 3,
  "shipment_type": "standard",
  "service_level": "ground",
  "ship_date": "2024-01-16",
  "ship_from_address": {
    "street": "123 Warehouse St",
    "city": "Warehouse City",
    "state": "WS",
    "postal_code": "12345",
    "country": "US"
  },
  "ship_to_address": {
    "street": "456 Customer Ave",
    "city": "Customer City",
    "state": "CS",
    "postal_code": "67890",
    "country": "US"
  },
  "orders": [123, 124],
  "package_type": "box",
  "dimensions": {
    "length": 30,
    "width": 20,
    "height": 15
  },
  "weight": 2.5,
  "declared_value": 100.00,
  "insurance_required": true,
  "signature_required": true
}
```

### Ship Shipment
**POST** `/shipments/{id}/ship`

Dispatches the shipment and generates tracking.

#### Request Body
```json
{
  "actual_ship_date": "2024-01-16T14:30:00Z",
  "shipped_by": 10,
  "shipping_notes": "Shipment dispatched on schedule",
  "generate_label": true,
  "send_notifications": true
}
```

---

## 📦 Carton Type Management

### Get Carton Recommendations
**POST** `/carton-types/recommend`

Gets optimal carton recommendations based on requirements.

#### Request Body
```json
{
  "weight": 2.5,
  "dimensions": {
    "length": 25,
    "width": 15,
    "height": 10
  },
  "is_fragile": false,
  "is_hazmat": false,
  "is_food_grade": false,
  "max_cost": 5.00,
  "preferred_material": "cardboard",
  "limit": 5
}
```

#### Response
```json
{
  "success": true,
  "data": [
    {
      "carton_type": {
        "id": 15,
        "carton_code": "BOX-001",
        "name": "Standard Box",
        "internal_dimensions": {
          "length": 30,
          "width": 20,
          "height": 15
        },
        "max_weight": 10,
        "cost_per_unit": 2.50
      },
      "fit_score": 95,
      "cost_efficiency": 88,
      "volume_efficiency": 92,
      "overall_score": 91.5,
      "waste_space": 2500
    }
  ],
  "message": "Carton recommendations generated successfully"
}
```

---

## 💰 Shipping Rate Management

### Calculate Shipping Rates
**POST** `/shipping-rates/calculate`

Calculates shipping rates for given parameters.

#### Request Body
```json
{
  "carrier_id": 3,
  "service_type": "standard",
  "origin_zone_id": 1,
  "destination_zone_id": 5,
  "weight": 3.5,
  "dimensions": {
    "length": 30,
    "width": 20,
    "height": 15
  },
  "declared_value": 100.00,
  "is_residential": false,
  "requires_signature": true,
  "distance": 250.5,
  "calculation_date": "2024-01-15"
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "calculation_parameters": {
      "weight": 3.5,
      "dimensions": {
        "length": 30,
        "width": 20,
        "height": 15
      }
    },
    "calculation_date": "2024-01-15T10:30:00Z",
    "rates": [
      {
        "carrier": {
          "id": 3,
          "name": "Express Carrier",
          "code": "EXP"
        },
        "service_type": "standard",
        "rate_structure": "weight_based",
        "base_rate": 7.00,
        "surcharges": {
          "fuel_surcharge": 0.70,
          "signature_surcharge": 3.00
        },
        "total_rate": 10.70,
        "currency": "USD",
        "calculation_details": {
          "calculation_method": "Weight-based calculation",
          "weight_used": 3.5,
          "chargeable_weight": 3.5
        }
      }
    ]
  },
  "message": "Shipping rates calculated successfully"
}
```

---

## 🚛 Load Plan Management

### Create Load Plan
**POST** `/load-plans`

Creates a new load plan for vehicle dispatch.

#### Request Body
```json
{
  "warehouse_id": 1,
  "vehicle_id": 5,
  "driver_id": 10,
  "load_type": "delivery",
  "planned_departure_time": "2024-01-16T08:00:00Z",
  "planned_return_time": "2024-01-16T18:00:00Z",
  "route_optimization": "distance",
  "shipments": [123, 124, 125],
  "max_weight_override": 1200,
  "special_instructions": "Handle with care",
  "requires_signature": true,
  "temperature_controlled": false,
  "priority_level": "high",
  "auto_optimize": true
}
```

### Optimize Load Plan
**POST** `/load-plans/{id}/optimize`

Optimizes the load plan route and delivery sequence.

#### Request Body
```json
{
  "optimization_type": "distance",
  "constraints": {
    "max_route_duration": 480,
    "mandatory_breaks": true,
    "avoid_rush_hours": true
  },
  "force_reoptimize": true
}
```

### Dispatch Load Plan
**POST** `/load-plans/{id}/dispatch`

Dispatches the load plan for delivery.

#### Request Body
```json
{
  "actual_departure_time": "2024-01-16T08:15:00Z",
  "dispatch_notes": "Load dispatched on schedule",
  "fuel_level": 95.0,
  "odometer_reading": 125000,
  "pre_trip_inspection_completed": true
}
```

---

## 📊 Analytics Endpoints

### Order Priority Analytics
**GET** `/order-priorities/analytics`

Returns comprehensive order priority analytics.

#### Query Parameters
- `warehouse_id`: Filter by warehouse
- `date_from`: Start date for analytics
- `date_to`: End date for analytics
- `priority_level`: Filter by priority level

#### Response
```json
{
  "success": true,
  "data": {
    "total_priorities": 1250,
    "by_level": {
      "urgent": 125,
      "high": 350,
      "medium": 600,
      "low": 175
    },
    "average_score": 65.5,
    "score_distribution": {
      "90-100": 85,
      "80-89": 245,
      "70-79": 420,
      "60-69": 350,
      "below_60": 150
    },
    "fulfillment_performance": {
      "on_time_rate": 94.5,
      "average_fulfillment_time": 2.3
    }
  }
}
```

### Batch Pick Analytics
**GET** `/batch-picks/analytics`

Returns batch picking performance analytics.

### Shipment Analytics
**GET** `/shipments/analytics`

Returns comprehensive shipment analytics.

### Load Plan Analytics
**GET** `/load-plans/analytics`

Returns load planning and dispatch analytics.

---

## 🔍 Common Query Parameters

Most list endpoints support these common parameters:

- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)
- `sort_by`: Field to sort by
- `sort_direction`: asc or desc
- `search`: Search term
- `warehouse_id`: Filter by warehouse
- `status`: Filter by status
- `date_from`: Start date filter
- `date_to`: End date filter

## 📝 Response Format

All API responses follow this standard format:

```json
{
  "success": boolean,
  "data": object|array,
  "message": string,
  "meta": {
    "current_page": number,
    "per_page": number,
    "total": number,
    "last_page": number
  },
  "errors": object (only on validation errors)
}
```

## ⚠️ Error Handling

### HTTP Status Codes
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Internal Server Error

### Error Response Format
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## 🚀 Rate Limiting

API requests are limited to:
- 1000 requests per hour for authenticated users
- 100 requests per hour for unauthenticated requests

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1642694400
```

## 📚 Additional Resources

- [Authentication Guide](./authentication.md)
- [Webhook Documentation](./webhooks.md)
- [SDK Documentation](./sdks.md)
- [Postman Collection](./postman-collection.json)

---

*Last updated: January 15, 2024*
*API Version: 2.0*