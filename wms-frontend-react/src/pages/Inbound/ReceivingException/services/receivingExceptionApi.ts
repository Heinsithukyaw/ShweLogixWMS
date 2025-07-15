import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/ReceivingExceptionSlice'
import { ReceivingExceptionType } from '../../../../type/inbound/receivingExceptionType'

export const fetchReceivingExceptionLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'receiving-exceptions'
        )
        console.log('Receiving Exception lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Receiving Exception lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createReceivingException = (data: ReceivingExceptionType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('receiving-exceptions', data)
    console.log('Create Receiving Exception successfully!')
    dispatch(fetchReceivingExceptionLists()) 
    return {status:false,error:null}
  } catch (err: any) {
    console.error('Failed to create Receiving Exception', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }else{
      return { status: false, error: 'Something went wrong!' }
    }
  }
}

export const updateReceivingException = (data: ReceivingExceptionType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`receiving-exceptions/${id}`, data)
    console.log('Update Receiving Exception successfully!')
    dispatch(fetchReceivingExceptionLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Receiving Exception', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteReceivingException = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`receiving-exceptions/${id}`)
    console.log('Deleted Receiving Exception successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Receiving Exception', err)
      return false
  }
}
