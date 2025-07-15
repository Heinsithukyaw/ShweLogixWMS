import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/MaterialHandlingEqSlice'

export const fetchMHELists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('material-handling-eqs')
    
    dispatch(setContentSuccess(res?.data?.data || []))
  } catch (err: any) {
    console.error('Failed to fetch MHE lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
