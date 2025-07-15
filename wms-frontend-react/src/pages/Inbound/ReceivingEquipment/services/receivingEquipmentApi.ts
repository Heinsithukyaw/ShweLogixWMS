import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ReceivingEquipmentSlice'
import { ReceivingEquipmentType } from '../../../../type/inbound/receivingEquipmentType'

export const fetchReceivingEquipmentLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'receiving-equipments'
        )
        console.log('Receiving Equipments lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Receiving Equipments lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createReceivingEquipment = (data: ReceivingEquipmentType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('receiving-equipments', data)
    console.log('Create Receiving Equipment successfully!')
    dispatch(fetchReceivingEquipmentLists()) 
    return {status:false,error:null}
  } catch (err: any) {
    console.error('Failed to create Receiving Equipment', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }else{
      return { status: false, error: 'Something went wrong!' }
    }
  }
}

export const updateReceivingEquipment = (data: ReceivingEquipmentType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`receiving-equipments/${id}`, data)
    console.log('Update Receiving Equipment successfully!')
    dispatch(fetchReceivingEquipmentLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Receiving Equipment', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteReceivingEquipment = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`receiving-equipments/${id}`)
    console.log('Deleted Receiving Equipment successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Receiving Equipment', err)
      return false
  }
}
