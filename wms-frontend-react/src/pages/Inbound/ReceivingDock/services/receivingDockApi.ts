import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ReceivingDockSlice'
import { ReceivingDockType } from '../../../../type/inbound/receivingDockType'

export const fetchReceivingDockLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'receiving-docks'
        )
        console.log('Receiving Dock lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Receiving Dock lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createReceivingDock = (data: ReceivingDockType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('receiving-docks', data)
    console.log('Create Receiving Dock successfully!')
    dispatch(fetchReceivingDockLists()) 
    return {status:false,error:null}
  } catch (err: any) {
    console.error('Failed to create Receiving Dock', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }else{
      return { status: false, error: 'Something went wrong!' }
    }
  }
}

export const updateReceivingDock = (data: ReceivingDockType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`receiving-docks/${id}`, data)
    console.log('Update Receiving Dock successfully!')
    dispatch(fetchReceivingDockLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Receiving Dock', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteReceivingDock = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`receiving-docks/${id}`)
    console.log('Deleted Receiving Dock successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Receiving Dock', err)
      return false
  }
}
