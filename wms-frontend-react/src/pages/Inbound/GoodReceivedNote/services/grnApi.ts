// services/ansApi.ts
import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/GrnSlice'
import { GrnType } from '../../../../type/inbound/grnType'

export const fetchGrnLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken('good-received-notes')
        console.log('grn lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Grn lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createGrn = (data: GrnType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('good-received-notes', data)
    console.log('Create Grn successfully!')
    dispatch(fetchGrnLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create Grn', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateGrn = (data: GrnType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`good-received-notes/${id}`, data)
    console.log('Update Grn successfully!')
    dispatch(fetchGrnLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Grn', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteGrn = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`good-received-notes/${id}`)
    console.log('Deleted GRN successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete GRN', err)
      return false
  }
}
