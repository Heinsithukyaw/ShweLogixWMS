import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ShipmentSlice'
import { ShipmentType } from '../../../../type/inbound/shipmentType'

export const fetchShipmentLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken('inbound-shipments')
        console.log('shipment lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Shipment lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createShipment = (data: ShipmentType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('inbound-shipments', data)
    console.log('Create Shipment successfully!')
    dispatch(fetchShipmentLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Shipment', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateShipment = (data: ShipmentType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`inbound-shipments/${id}`, data)
    console.log('Update Shipment successfully!')
    dispatch(fetchShipmentLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update ASN', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteShipment = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`inbound-shipments/${id}`)
    console.log('Deleted Shipment successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Shipment', err)
      return false
  }
}
