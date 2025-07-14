import axios from 'axios';
import {
  PackingStation,
  CartonType,
  PackOrder,
  PackedCarton,
  Shipment,
  LoadPlan,
  QualityCheckpoint,
  OutboundQualityCheck,
  ApiResponse,
  PaginatedResponse,
  CreatePackingStationForm,
  CreatePackOrderForm,
  CreateShipmentForm
} from '../../type/outbound';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Axios instance with default config
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Add auth token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Packing Operations API
export const packingService = {
  // Packing Stations
  getPackingStations: async (): Promise<ApiResponse<PackingStation[]>> => {
    const response = await api.get('/outbound/packing/stations');
    return response.data;
  },

  createPackingStation: async (data: CreatePackingStationForm): Promise<ApiResponse<PackingStation>> => {
    const response = await api.post('/outbound/packing/stations', data);
    return response.data;
  },

  getPackingStation: async (id: number): Promise<ApiResponse<PackingStation>> => {
    const response = await api.get(`/outbound/packing/stations/${id}`);
    return response.data;
  },

  updatePackingStation: async (id: number, data: Partial<CreatePackingStationForm>): Promise<ApiResponse<PackingStation>> => {
    const response = await api.put(`/outbound/packing/stations/${id}`, data);
    return response.data;
  },

  // Carton Types
  getCartonTypes: async (): Promise<ApiResponse<CartonType[]>> => {
    const response = await api.get('/outbound/packing/cartons');
    return response.data;
  },

  createCartonType: async (data: any): Promise<ApiResponse<CartonType>> => {
    const response = await api.post('/outbound/packing/cartons', data);
    return response.data;
  },

  getCartonRecommendation: async (data: any): Promise<ApiResponse<CartonType>> => {
    const response = await api.post('/outbound/packing/cartons/recommend', data);
    return response.data;
  },

  // Pack Orders
  getPendingPackOrders: async (): Promise<ApiResponse<PackOrder[]>> => {
    const response = await api.get('/outbound/packing/orders/pending');
    return response.data;
  },

  createPackOrder: async (data: CreatePackOrderForm): Promise<ApiResponse<PackOrder>> => {
    const response = await api.post('/outbound/packing/orders', data);
    return response.data;
  },

  startPacking: async (id: number): Promise<ApiResponse<PackOrder>> => {
    const response = await api.post(`/outbound/packing/orders/${id}/start`);
    return response.data;
  },

  // Packed Cartons
  createPackedCarton: async (data: any): Promise<ApiResponse<PackedCarton>> => {
    const response = await api.post('/outbound/packing/cartons', data);
    return response.data;
  },

  validatePackedCarton: async (id: number): Promise<ApiResponse<PackedCarton>> => {
    const response = await api.post(`/outbound/packing/cartons/${id}/validate`);
    return response.data;
  },

  qualityCheckCarton: async (id: number): Promise<ApiResponse<any>> => {
    const response = await api.post(`/outbound/packing/cartons/${id}/quality-check`);
    return response.data;
  },

  // Multi-Carton Shipments
  createMultiCartonShipment: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/packing/multi-carton', data);
    return response.data;
  },

  // Packing Materials
  getPackingMaterials: async (): Promise<ApiResponse<any[]>> => {
    const response = await api.get('/outbound/packing/materials');
    return response.data;
  },

  updatePackingMaterialInventory: async (id: number, data: any): Promise<ApiResponse<any>> => {
    const response = await api.put(`/outbound/packing/materials/${id}/inventory`, data);
    return response.data;
  },
};

// Shipping Operations API
export const shippingService = {
  // Shipments
  getShipments: async (): Promise<ApiResponse<Shipment[]>> => {
    const response = await api.get('/outbound/shipping');
    return response.data;
  },

  createShipment: async (data: CreateShipmentForm): Promise<ApiResponse<Shipment>> => {
    const response = await api.post('/outbound/shipping', data);
    return response.data;
  },

  getShipment: async (id: number): Promise<ApiResponse<Shipment>> => {
    const response = await api.get(`/outbound/shipping/${id}`);
    return response.data;
  },

  updateShipment: async (id: number, data: Partial<CreateShipmentForm>): Promise<ApiResponse<Shipment>> => {
    const response = await api.put(`/outbound/shipping/${id}`, data);
    return response.data;
  },

  // Shipping Rates
  getShippingRates: async (): Promise<ApiResponse<any[]>> => {
    const response = await api.get('/outbound/shipping/rates/get');
    return response.data;
  },

  performRateShopping: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/rates/shop', data);
    return response.data;
  },

  // Labels and Documents
  generateShippingLabel: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/labels/generate', data);
    return response.data;
  },

  generateShippingDocument: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/documents/generate', data);
    return response.data;
  },

  // Load Plans
  createLoadPlan: async (data: any): Promise<ApiResponse<LoadPlan>> => {
    const response = await api.post('/outbound/shipping/load-plans', data);
    return response.data;
  },

  // Dock Operations
  getLoadingDocks: async (): Promise<ApiResponse<any[]>> => {
    const response = await api.get('/outbound/shipping/docks/loading');
    return response.data;
  },

  scheduleDockAppointment: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/docks/schedule', data);
    return response.data;
  },

  confirmLoading: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/loading/confirm', data);
    return response.data;
  },

  recordDeliveryConfirmation: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/delivery/confirm', data);
    return response.data;
  },

  // Manifests
  createShippingManifest: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/shipping/manifests', data);
    return response.data;
  },

  closeShippingManifest: async (id: number): Promise<ApiResponse<any>> => {
    const response = await api.post(`/outbound/shipping/manifests/${id}/close`);
    return response.data;
  },

  transmitShippingManifest: async (id: number): Promise<ApiResponse<any>> => {
    const response = await api.post(`/outbound/shipping/manifests/${id}/transmit`);
    return response.data;
  },
};

// Quality Control API
export const qualityControlService = {
  getQualityCheckpoints: async (): Promise<ApiResponse<QualityCheckpoint[]>> => {
    const response = await api.get('/outbound/quality-control/checkpoints');
    return response.data;
  },

  createQualityCheckpoint: async (data: any): Promise<ApiResponse<QualityCheckpoint>> => {
    const response = await api.post('/outbound/quality-control/checkpoints', data);
    return response.data;
  },

  performQualityCheck: async (data: any): Promise<ApiResponse<OutboundQualityCheck>> => {
    const response = await api.post('/outbound/quality-control/checks', data);
    return response.data;
  },

  verifyWeight: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/quality-control/weight/verify', data);
    return response.data;
  },

  verifyDimensions: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/quality-control/dimensions/verify', data);
    return response.data;
  },

  performDamageInspection: async (data: any): Promise<ApiResponse<any>> => {
    const response = await api.post('/outbound/quality-control/damage/inspect', data);
    return response.data;
  },

  getQualityExceptions: async (): Promise<ApiResponse<any[]>> => {
    const response = await api.get('/outbound/quality-control/exceptions');
    return response.data;
  },

  resolveQualityException: async (id: number, data: any): Promise<ApiResponse<any>> => {
    const response = await api.post(`/outbound/quality-control/exceptions/${id}/resolve`, data);
    return response.data;
  },

  getQualityMetrics: async (): Promise<ApiResponse<any>> => {
    const response = await api.get('/outbound/quality-control/metrics');
    return response.data;
  },
};

export default {
  packingService,
  shippingService,
  qualityControlService,
};