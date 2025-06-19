import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ReceivingLaborTrackingSlice'
import { ReceivingLaborTrackingType } from '../../../../type/inbound/receivingLaborTrackingType'

export const fetchReceivingLaborTrackingLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'receiving-labor-trackings'
        )
        console.log('Receiving Labor Tracking lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Receiving Labor Tracking lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createReceivingLaborTracking = (data: ReceivingLaborTrackingType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('receiving-labor-trackings', data)
    console.log('Create Receiving Labor Tracking successfully!')
    dispatch(fetchReceivingLaborTrackingLists()) 
    return {status:false,error:null}
  } catch (err: any) {
    console.error('Failed to create Receiving LaborTracking', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }else{
      return { status: false, error: 'Something went wrong!' }
    }
  }
}

export const updateReceivingLaborTracking = (data: ReceivingLaborTrackingType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`receiving-labor-trackings/${id}`, data)
    console.log('Update Receiving Labor Tracking successfully!')
    dispatch(fetchReceivingLaborTrackingLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Receiving LaborTracking', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteReceivingLaborTracking = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`receiving-labor-trackings/${id}`)
    console.log('Deleted Receiving Labor Tracking successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Receiving Labor Tracking', err)
      return false
  }
}
