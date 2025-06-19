// services/ansApi.ts
import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/GrnItemSlice'
import { GrnItemType } from '../../../../type/inbound/grnItemType'

export const fetchGrnItemLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken('good-received-note-items')
        console.log('grn item lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Grn Item lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createGrnItem = (data: GrnItemType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('good-received-note-items', data)
    console.log('Create Grn successfully!')
    dispatch(fetchGrnItemLists()) 
    return {status:true,error:[]}
  } catch (err: any) {
    console.error('Failed to create Grn Item', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateGrnItem = (data: GrnItemType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken(`update-good-received-note-items`, data)
    console.log('Update Grn Item successfully!')
    dispatch(fetchGrnItemLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update Grn Item', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteGrn = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`good-received-note-items/${id}`)
    console.log('Deleted GRN Item successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete GRN Item', err)
      return false
  }
}
