import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/ProductSlice'

export const fetchProductLists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('products')
    
    dispatch(setContentSuccess(res?.data?.data || []))
  } catch (err: any) {
    console.error('Failed to fetch Product lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
