// import EcommerceMetrics from "../../components/ecommerce/EcommerceMetrics";
// import MonthlySalesChart from "../../components/ecommerce/MonthlySalesChart";
// import StatisticsChart from "../../components/ecommerce/StatisticsChart";
// import MonthlyTarget from "../../components/ecommerce/MonthlyTarget";
// import RecentOrders from "../../components/ecommerce/RecentOrders";
// import DemographicCard from "../../components/ecommerce/DemographicCard";
import React,{useEffect} from 'react'
import PageMeta from "../../components/common/PageMeta";
import { useAppDispatch } from '../../store/hook'
import { fetchSupplierLists } from '../../services/supplierApi'
import { fetchCarrierLists } from '../../services/shippingCarrierApi'
import { fetchProductLists } from '../../services/productApi'
import { fetchLocationLists } from '../../services/locationApi'
import { fetchPalletLists } from '../../services/palletApi'
import { fetchUomLists } from '../../services/uomApi'
import { fetchDockLists } from '../../services/dockApi'
import { fetchMHELists } from '../../services/materialHandlingEqApi'
import { fetchSupervisorLists } from '../../services/supervisorApi'
import { fetchEmployeeLists } from '../../services/empApi'
import { fetchZoneLists } from '../../services/zoneApi'
import { fetchWarehouseLists } from '../../services/warehouseApi'
import { fetchAreaLists } from '../../services/areaApi'
import { fetchMainLocationLists } from '../../pages/WarehouseManagement/Location/services/locationApi'

export default function Home() {

      const dispatch = useAppDispatch()
  
  useEffect(() => {
      dispatch(fetchSupplierLists())
      dispatch(fetchCarrierLists())
      dispatch(fetchProductLists())
      dispatch(fetchLocationLists())
      dispatch(fetchPalletLists())
      dispatch(fetchUomLists())
      dispatch(fetchDockLists())
      dispatch(fetchMHELists())
      dispatch(fetchSupervisorLists())
      dispatch(fetchEmployeeLists())
      dispatch(fetchZoneLists())
      dispatch(fetchWarehouseLists())
      dispatch(fetchAreaLists())
      dispatch(fetchMainLocationLists())
  }, [dispatch])

  return (
    <>
      <PageMeta
        title="WMS"
        description="Warehouse Management System"
      />
      <div className="text-slate-500">
        Dashboard Page
      </div>
      <div className="grid grid-cols-12 gap-4 md:gap-6">
        <div className="col-span-12 space-y-6 xl:col-span-7">
          {/* <EcommerceMetrics />

          <MonthlySalesChart /> */}
        </div>

        <div className="col-span-12 xl:col-span-5">
          {/* <MonthlyTarget /> */}
        </div>

        <div className="col-span-12">
          {/* <StatisticsChart /> */}
        </div>

        <div className="col-span-12 xl:col-span-5">
          {/* <DemographicCard /> */}
        </div>

        <div className="col-span-12 xl:col-span-7">
          {/* <RecentOrders /> */}
        </div>
      </div>
    </>
  );
}
