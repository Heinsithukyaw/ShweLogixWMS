import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/EmployeeSlice'

export const fetchEmployeeLists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('employees')
    
    dispatch(setContentSuccess(res?.data?.data || []))
  } catch (err: any) {
    console.error('Failed to fetch Employee lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
