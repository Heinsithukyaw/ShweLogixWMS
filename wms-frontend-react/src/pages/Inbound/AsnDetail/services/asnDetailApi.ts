import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/AsnDetailSlice'
import { AsnDetailType } from '../../../../type/inbound/asnDetailType'

export const fetchAsnDetailLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'advanced-shipping-notice-detail'
        )
        console.log('asn detail lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch ASN Detail lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createAsnDetail = (data: AsnDetailType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('advanced-shipping-notice-detail', data)
    console.log('Create ASN Detail successfully!')
    dispatch(fetchAsnDetailLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create ASN Detail', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateAsnDetail = (data: AsnDetailType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`advanced-shipping-notice-detail/${id}`, data)
    console.log('Update ASN Detail successfully!')
    dispatch(fetchAsnDetailLists())
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

export const deleteAsnDetail = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`advanced-shipping-notice-detail/${id}`)
    console.log('Deleted ASN Detail successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete ASN Detail', err)
      return false
  }
}
