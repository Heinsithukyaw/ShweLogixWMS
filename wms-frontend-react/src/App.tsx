import { BrowserRouter as Router, Routes, Route } from 'react-router-dom'
import { Provider } from "react-redux"
import { store } from './store/store'
import SignIn from './pages/AuthPages/SignIn'
import SignUp from './pages/AuthPages/SignUp'
import NotFound from './pages/OtherPage/NotFound'
import UserProfiles from './pages/UserProfiles'
import Videos from './pages/UiElements/Videos'
import Images from './pages/UiElements/Images'
import Alerts from './pages/UiElements/Alerts'
import Badges from './pages/UiElements/Badges'
import Avatars from './pages/UiElements/Avatars'
import Buttons from './pages/UiElements/Buttons'
import LineChart from './pages/Charts/LineChart'
import BarChart from './pages/Charts/BarChart'
import Calendar from './pages/Calendar'
import BasicTables from './pages/Tables/BasicTables'
import FormElements from './pages/Forms/FormElements'
import Blank from './pages/Blank'
import AppLayout from './layout/AppLayout'
import { ScrollToTop } from './components/common/ScrollToTop'
import Home from './pages/Dashboard/Home'
import ProtectedRoute from './components/ProtectedRoute'
import { Toaster } from 'react-hot-toast'

// New Outbound Components
import WaveManagement from './pages/Outbound/WaveManagement'
import PickingInterface from './pages/Outbound/Picking'
import LoadPlanning from './pages/Outbound/LoadPlanning'

// Document Management and Workflow Components
import DocumentManagement from './pages/DocumentManagement'
import WorkflowEngine from './pages/WorkflowEngine'


// Master Data Section
import UnitOfMeasure from './pages/ProductManagement/UnitOfMeasure/List'
import Category from './pages/ProductManagement/Category/List'
import Brand from './pages/ProductManagement/Brand/list'
import Product from './pages/ProductManagement/Product/List'
import ProductHierarchy from './pages/ProductManagement/ProductHierarchy/List'

import Party from './pages/BusinessPartyManagement/Party/List'
import ContactPerson from './pages/BusinessPartyManagement/Contact/List'

import Warehouse from './pages/WarehouseManagement/Warehouse/List'
import Area from './pages/WarehouseManagement/Area/List'
import Zone from './pages/WarehouseManagement/Zone/List'
import Location from './pages/WarehouseManagement/Location/LocationPage'
import LocationHierarchy from './pages/WarehouseManagement/Location/LocationHierarchy/List'
import ZoneLocation from './pages/WarehouseManagement/Zone/ZoneConfiguration/ZoneLocation/ZoneLocationPage'

import MaterialHandling from './pages/EquipmentManagement/MaterialHandling/List'
import Storage from './pages/EquipmentManagement/Storage/List'
import Pallet from './pages/EquipmentManagement/Pallet/List'
import Dock from './pages/EquipmentManagement/Dock/List'

import Employee from './pages/HrManagement/Employee/List'

import OrderType from './pages/OrderTypeManagement/OrderType/List'

import Carrier from './pages/ShippingCarrierManagement/Carrier/List'

import FinancialCategory from './pages/FinancialManagement/Category/List'

import CostType from './pages/FinancialManagement/CostType/List'

import Currency from './pages/FinancialManagement/Currency/List'

import Tax from './pages/FinancialManagement/Tax/List'

import PaymentTerms from './pages/FinancialManagement/PaymentTerms/List'

import Country from './pages/GeoManagement/Country/List'

import State from './pages/GeoManagement/State/List'
import City from './pages/GeoManagement/City/List'

import Status from './pages/OperationalManagement/Status/List'
import ActivityType from './pages/OperationalManagement/ActivityType/List'

import ASN from './pages/Inbound/Asn/AsnPage'
import ASNDetail from './pages/Inbound/AsnDetail/AsnDetailPage'
import Shipment from './pages/Inbound/Shipment/ShipmentPage'
import ShipmentDetail from './pages/Inbound/ShipmentDetail/ShipmentDetailPage'
import ReceivingAppointment from './pages/Inbound/ReceivingAppointment/ReceivingAppointmentPage'
import UnloadingSession from './pages/Inbound/UnloadingSession/UnloadingSessionPage'
import QualityInspection from './pages/Inbound/QualityInspection/QualityInspectionPage'
import GoodReceivedNote from './pages/Inbound/GoodReceivedNote/GoodReceivedNotePage'
import ReceivingException from './pages/Inbound/ReceivingException/ReceivingExceptionPage'
import PutAwayTask from './pages/Inbound/PutAwayTask/PutAwayTaskPage'
import CrossDockingTask from './pages/Inbound/CrossDockingTask/CrossDockingTaskPage'
import ReceivingLaborTracking from './pages/Inbound/ReceivingLaborTracking/ReceivingLaborTrackingPage'
import ReceivingDock from './pages/Inbound/ReceivingDock/ReceivingDockPage'
import StagingLocation from './pages/Inbound/StagingLocation/StagingLocationPage'
import ReceivingEquipment from './pages/Inbound/ReceivingEquipment/ReceivingEquipmentPage'
import InboundDashboard from './pages/Inbound/Dashboard/DashboardPage'
import EventMonitoringPage from './pages/EventMonitoring/EventMonitoringPage'

export default function App() {
  return (
    <Provider store={store}>
      <Toaster position="bottom-left" />
      <Router>
        <ScrollToTop />
        <Routes>
          {/* Public Routes */}
          <Route path="/signin" element={<SignIn />} />
          <Route path="/signup" element={<SignUp />} />

          {/* Protected Routes */}
          <Route
            element={
              <ProtectedRoute>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route index path="/" element={<Home />} />

            {/* Master Data */}
            <Route
              path="/product-management/unit_of_measure"
              element={<UnitOfMeasure />}
            />
            <Route path="/product-management/category" element={<Category />} />
            <Route path="/product-management/brand" element={<Brand />} />
            <Route path="/product-management/product" element={<Product />} />
            <Route
              path="/product-management/product-hierarchy"
              element={<ProductHierarchy />}
            />

            <Route path="/business-management/party" element={<Party />} />
            <Route
              path="/business-management/contact"
              element={<ContactPerson />}
            />

            {/* Warehouse Management */}
            <Route
              path="/warehouse-management/warehouse"
              element={<Warehouse />}
            />
            <Route path="/warehouse-management/area" element={<Area />} />
            <Route path="/warehouse-management/zone" element={<Zone />} />
            <Route
              path="/warehouse-management/location"
              element={<Location />}
            />
            <Route
              path="/warehouse-management/location-hierarchy"
              element={<LocationHierarchy />}
            />
            <Route
              path="/warehouse-management/zone-location/:zoneId"
              element={<ZoneLocation />}
            />
            {/* Equipment Management */}
            <Route
              path="/equipment-management/material-handling"
              element={<MaterialHandling />}
            />
            <Route path="/equipment-management/storage" element={<Storage />} />
            <Route path="/equipment-management/pallet" element={<Pallet />} />
            <Route path="/equipment-management/dock" element={<Dock />} />

            {/* HR Management */}
            <Route path="/hr-management/employees" element={<Employee />} />

            {/* Order Type Management */}
            <Route
              path="/order-type-management/order-type"
              element={<OrderType />}
            />

            {/* Shipping Carrier Management */}
            <Route path="/shipping-management/carrier" element={<Carrier />} />

            {/* Financial Management */}
            <Route
              path="/financial-management/category"
              element={<FinancialCategory />}
            />
            <Route
              path="/financial-management/cost-type"
              element={<CostType />}
            />
            <Route
              path="/financial-management/currency"
              element={<Currency />}
            />
            <Route path="/financial-management/tax-type" element={<Tax />} />
            <Route
              path="/financial-management/payment-terms"
              element={<PaymentTerms />}
            />

            {/* Geo Management */}
            <Route path="/geo-management/country" element={<Country />} />
            <Route path="/geo-management/state" element={<State />} />
            <Route path="/geo-management/city" element={<City />} />

            {/* Operational Management */}
            <Route path="/operational-management/status" element={<Status />} />
            <Route
              path="/operational-management/activity-type"
              element={<ActivityType />}
            />

            {/* Inbound Operation */}
            {/* ASN */}
            <Route
              path="/inbound-operation/dashboard"
              element={<InboundDashboard />}
            />
            <Route path="/inbound-operation/asn" element={<ASN />} />

            <Route
              path="/inbound-operation/asn-detail"
              element={<ASNDetail />}
            />
            <Route
              path="/inbound-operation/inbound-shipment"
              element={<Shipment />}
            />
            <Route
              path="/inbound-operation/inbound-shipment-detail"
              element={<ShipmentDetail />}
            />
            <Route
              path="/inbound-operation/receiving-appointment"
              element={<ReceivingAppointment />}
            />
            <Route
              path="/inbound-operation/unloading-session"
              element={<UnloadingSession />}
            />
            <Route
              path="/inbound-operation/quality-inspection"
              element={<QualityInspection />}
            />
            <Route
              path="/inbound-operation/good-received-note"
              element={<GoodReceivedNote />}
            />
            <Route
              path="/inbound-operation/receiving-exception"
              element={<ReceivingException />}
            />
            <Route
              path="/inbound-operation/putaway-task"
              element={<PutAwayTask />}
            />
            <Route
              path="/inbound-operation/cross-docking-task"
              element={<CrossDockingTask />}
            />
            <Route
              path="/inbound-operation/receiving-labor-tracking"
              element={<ReceivingLaborTracking />}
            />
            <Route
              path="/inbound-operation/receiving-dock"
              element={<ReceivingDock />}
            />
            <Route
              path="/warehouse-management/staging-location"
              element={<StagingLocation />}
            />
            <Route
              path="/inbound-operation/receiving-equipment"
              element={<ReceivingEquipment />}
            />
            
            {/* Outbound Operations */}
            <Route
              path="/outbound-operation/wave-management"
              element={<WaveManagement />}
            />
            <Route
              path="/outbound-operation/picking"
              element={<PickingInterface />}
            />
            <Route
              path="/outbound-operation/load-planning"
              element={<LoadPlanning />}
            />
            
            {/* Document Management */}
            <Route
              path="/document-management"
              element={<DocumentManagement />}
            />
            
            {/* Workflow Management */}
            <Route
              path="/workflow-management"
              element={<WorkflowManagement />}
            />
            
            {/* Workflow Engine */}
            <Route
              path="/workflow-engine"
              element={<WorkflowEngine />}
            />
            
            {/* Event Monitoring */}
            <Route
              path="/system/event-monitoring"
              element={<EventMonitoringPage />}
            />
            
            {/* Others Page */}
            <Route path="/profile" element={<UserProfiles />} />
            <Route path="/calendar" element={<Calendar />} />
            <Route path="/blank" element={<Blank />} />

            {/* Forms */}
            <Route path="/form-elements" element={<FormElements />} />

            {/* Tables */}
            <Route path="/basic-tables" element={<BasicTables />} />

            {/* Ui Elements */}
            <Route path="/alerts" element={<Alerts />} />
            <Route path="/avatars" element={<Avatars />} />
            <Route path="/badge" element={<Badges />} />
            <Route path="/buttons" element={<Buttons />} />
            <Route path="/images" element={<Images />} />
            <Route path="/videos" element={<Videos />} />

            {/* Charts */}
            <Route path="/line-chart" element={<LineChart />} />
            <Route path="/bar-chart" element={<BarChart />} />
          </Route>

          {/* Fallback Route */}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </Router>
    </Provider>
  )
}
