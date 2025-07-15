import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/PutAwayTaskSlice'
import { PutAwayTaskType } from '../../../../type/inbound/putAwayTaskType'

export const fetchPutAwayTaskLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'put-away-tasks'
        )
        console.log('asn detail lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch ASN Detail lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createPutAwayTask =
  (data: PutAwayTaskType) => async (dispatch: AppDispatch) => {
    try {
      await http.postDataWithToken('put-away-tasks', data)
      console.log('Create PutAway Task successfully!')
      dispatch(fetchPutAwayTaskLists())
      return { status: false, error: [] }
    } catch (err: any) {
      console.error('Failed to create ASN Detail', err)
      if (err?.data.status == 422) {
        return { status: false, error: err?.data }
      }
    }
  }

export const updatePutAwayTask =
  (data: PutAwayTaskType, id: any) => async (dispatch: AppDispatch) => {
    try {
      await http.putDataWithToken(`put-away-tasks/${id}`, data)
      console.log('Update PutAway Task successfully!')
      dispatch(fetchPutAwayTaskLists())
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

export const deletePutAwayTask = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`put-away-tasks/${id}`)
    console.log('Deleted PutAway Task successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete PutAway Task', err)
      return false
  }
}
