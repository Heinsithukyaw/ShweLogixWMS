// services/ansApi.ts
import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/AsnSlice'
import { AsnType } from '../../../../type/inbound/asnType'

export const fetchAsnLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken('advanced-shipping-notices')
        console.log('asn lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch ASN lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createAsn = (data: AsnType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('advanced-shipping-notices', data)
    console.log('Create ASN successfully!')
    dispatch(fetchAsnLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create ASN', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateAsn = (data: AsnType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`advanced-shipping-notices/${id}`, data)
    console.log('Update ASN successfully!')
    dispatch(fetchAsnLists())
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

export const deleteAsn = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`advanced-shipping-notices/${id}`)
    console.log('Deleted ASN successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete ASN', err)
      return false
  }
}
