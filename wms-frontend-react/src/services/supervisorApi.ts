import http from '../lib/http'
import { AppDispatch } from '../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
} from '../store/features/SupervisorSlice'

export const fetchSupervisorLists = () => async (dispatch: AppDispatch) => {
  try {
    dispatch(setContentStart())
    const res = await http.fetchDataWithToken('employees')
    const supervisors = res?.data.data.filter((x:any) => x.is_supervisor == 1)
    dispatch(setContentSuccess(supervisors || []))
  } catch (err: any) {
    console.error('Failed to fetch Supervisor lists', err)
    dispatch(setContentFailure(err.message || 'Unknown error'))
  }
}
