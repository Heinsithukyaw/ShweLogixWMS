import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ShipmentDetailSlice'
import { ShipmentDetailType } from '../../../../type/inbound/shipmentDetailType'

export const fetchShipmentDetailLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'inbound-shipment-details'
        )
        console.log('shipment detail lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Shipment Detail lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createShipmentDetail = (data: ShipmentDetailType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('inbound-shipment-details', data)
    console.log('Create Shipment Detail successfully!')
    dispatch(fetchShipmentDetailLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Shipment Detail', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateShipmentDetail = (data: ShipmentDetailType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`inbound-shipment-details/${id}`, data)
    console.log('Update Shipment Detail successfully!')
    dispatch(fetchShipmentDetailLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Shipment Detail', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteShipmentDetail = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`inbound-shipment-details/${id}`)
    console.log('Deleted Shipment Detail successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Shipment Detail', err)
      return false
  }
}
