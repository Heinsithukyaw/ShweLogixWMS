import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/SupplierSlice'

export const fetchSupplierLists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('business-parties')
    const supplier_lists = res?.data?.data.filter(
      (x: any) => x.party_type === 'Supplier'
    )
    dispatch(setContentSuccess(supplier_lists || []))
  } catch (err: any) {
    console.error('Failed to fetch Supplier lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
