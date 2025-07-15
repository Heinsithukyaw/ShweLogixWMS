import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/LocationSlice'

export const fetchLocationLists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('locations')
    
    dispatch(setContentSuccess(res?.data?.data || []))
  } catch (err: any) {
    console.error('Failed to fetch Location lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
