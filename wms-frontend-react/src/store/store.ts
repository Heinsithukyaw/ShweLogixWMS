import { configureStore, combineReducers } from '@reduxjs/toolkit'
import { setupListeners } from '@reduxjs/toolkit/query/react'
import { persistReducer, persistStore } from 'redux-persist'
import storage from 'redux-persist/lib/storage'
import { useDispatch } from 'react-redux'
import AuthReducer from './features/AuthSlice'
import AsnReducer from './features/inbound/AsnSlice'
import SupplierReducer from './features/SupplierSlice'
import ProductReducer from './features/ProductSlice'
import ShippingCarrierReducer from './features/ShippingCarrierSlice'
import AsnDetailReducer from './features/inbound/AsnDetailSlice'
import LocationReducer from './features/LocationSlice'
import PalletReducer from './features/PalletSlice'
import UomReducer from './features/UomSlice'
import ShipmentReducer from './features/inbound/ShipmentSlice'
import ShipmentDetailReducer from './features/inbound/ShipmentDetailSlice'
import DockReducer from './features/DockSlice'
import ReceivingAppointmentReducer from './features/inbound/ReceivingAppointmentSlice'
import UnloadingSessionReducer from './features/inbound/UnloadingSessionSlice'
import MaterialHandlingEqReducer from './features/MaterialHandlingEqSlice'
import SupervisorReducer from './features/SupervisorSlice'
import QualityInspectionReducer from './features/inbound/QualityInspectionSlice'
import GrnReducer from './features/inbound/GrnSlice'
import GrnItemReducer from './features/inbound/GrnItemSlice'
import ReceivingExceptionReducer from './features/inbound/ReceivingExceptionSlice'
import PutAwayTaskReducer from './features/inbound/PutAwayTaskSlice'
import EmployeeReducer from './features/EmployeeSlice'
import CrossDockingTaskReducer from './features/inbound/CrossDockingTaskSlice'
import ReceivingLaborTrackingReducer from './features/inbound/ReceivingLaborTrackingSlice'
import ReceivingDockReducer from './features/inbound/ReceivingDockSlice'
import ZoneReducer from './features/ZoneSlice'
import StagingLocationReducer from './features/inbound/StagingLocationSlice'
import ReceivingEquipmentReducer from './features/inbound/ReceivingEquipmentSlice'
import WarehouseReducer from './features/WarehouseSlice'
import AreaReducer from './features/AreaSlice'
import MLocationReducer from './features/masterData/LocationSlice'

const rootReducer = combineReducers({
  auth: AuthReducer,
  asn: AsnReducer,
  supplier: SupplierReducer,
  product: ProductReducer,
  carrier: ShippingCarrierReducer,
  asnDetail: AsnDetailReducer,
  location: LocationReducer,
  pallet: PalletReducer,
  uom: UomReducer,
  shipment: ShipmentReducer,
  shipmentDetail: ShipmentDetailReducer,
  dock: DockReducer,
  receivingAppointment: ReceivingAppointmentReducer,
  unloadingSession: UnloadingSessionReducer,
  materialHandlingEq: MaterialHandlingEqReducer,
  supervisor: SupervisorReducer,
  qualityInspection: QualityInspectionReducer,
  grn: GrnReducer,
  grnItem: GrnItemReducer,
  receivingException: ReceivingExceptionReducer,
  putAwayTask: PutAwayTaskReducer,
  employee: EmployeeReducer,
  crossDockingTask: CrossDockingTaskReducer,
  receivingLaborTracking: ReceivingLaborTrackingReducer,
  receivingDock: ReceivingDockReducer,
  zone:ZoneReducer,
  stagingLocation:StagingLocationReducer,
  receivingEquipment:ReceivingEquipmentReducer,
  warehouse:WarehouseReducer,
  area:AreaReducer,
  mLocation:MLocationReducer
})

const persistConfig = {
  key: 'root',
  storage,
  whitelist: ['auth','asn','supplier','carrier','asnDetail','product','location','pallet','uom','shipment','shipmentDetail','receivingAppointment','dock','unloadingSession','materialHandlingEq','supervisor','qualityInspection','grn','grnItem','receivingException','putAwayTask','employee','crossDockingTask','receivingLaborTracking','receivingDock','zone','stagingLocation','receivingEquipment','warehouse','area','mLocation']
}

const persistedReducer = persistReducer(persistConfig, rootReducer)

export const store = configureStore({
  reducer: persistedReducer,
  devTools: process.env.NODE_ENV !== 'production',
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['persist/PERSIST'],
        ignoredActionPaths: ['meta.arg', 'payload.register'],
        ignoredPaths: ['auth.someNonSerializableField'],
      },
    }),
})

export const persistor = persistStore(store)

setupListeners(store.dispatch)

export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch
export const useAppDispatch = useDispatch.withTypes<AppDispatch>()
