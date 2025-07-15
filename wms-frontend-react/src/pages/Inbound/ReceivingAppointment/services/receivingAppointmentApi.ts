import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ReceivingAppointmentSlice'
import { ReceivingAppointmentType } from '../../../../type/inbound/receivingAppointmentType'

export const fetchReceivingAppointmentLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'receiving-appointments'
        )
        console.log('receiving appointment lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Receiving Appointment lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createReceivingAppointment = (data: ReceivingAppointmentType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('receiving-appointments', data)
    console.log('Create Receiving Appointment successfully!')
    dispatch(fetchReceivingAppointmentLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Receiving Appointment', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateReceivingAppointment = (data: ReceivingAppointmentType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`receiving-appointments/${id}`, data)
    console.log('Update Receiving Appointment successfully!')
    dispatch(fetchReceivingAppointmentLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Receiving Appointment', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteReceivingAppointment =
  (id: any) => async (dispatch: AppDispatch) => {
    try {
      await http.deleteDataWithToken(`receiving-appointments/${id}`)
      console.log('Deleted ASN Detail successfully!')
      dispatch(deleteContentData(id))
      return true
    } catch (err: any) {
      console.error('Failed to delete ASN Detail', err)
      return false
    }
  }
