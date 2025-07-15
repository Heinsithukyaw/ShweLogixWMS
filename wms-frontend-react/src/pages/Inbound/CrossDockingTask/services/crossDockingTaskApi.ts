import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/CrossDockingTaskSlice'
import { CrossDockingTaskType } from '../../../../type/inbound/crossDockingTaskType'

export const fetchCrossDockingTaskLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'cross-docking-tasks'
        )
        console.log('CrossDocking Task lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch CrossDocking Task lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createCrossDockingTask = (data: CrossDockingTaskType) => async (dispatch: AppDispatch) => {
  try {
    await http.postDataWithToken('cross-docking-tasks', data)
    console.log('Create CrossDocking Task successfully!')
    dispatch(fetchCrossDockingTaskLists()) 
    return {status:false,error:[]}
  } catch (err: any) {
    console.error('Failed to create CrossDocking Task', err)
    if(err?.data.status == 422){
      return { status: false, error: err?.data }
    }
  }
}

export const updateCrossDockingTask = (data: CrossDockingTaskType,id:any) => async (dispatch: AppDispatch) => {
  try {
    await http.putDataWithToken(`cross-docking-tasks/${id}`, data)
    console.log('Update CrossDocking Task successfully!')
    dispatch(fetchCrossDockingTaskLists())
    return { status: true, error: [] }
  } catch (err: any) {
    console.error('Failed to update CrossDocking Task', err)
    if (err?.status == 422) {
      console.log(err)
      return { status: false, error: err }
    }
    return { status: false, error: err }

  }
}

export const deleteCrossDockingTask = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`cross-docking-tasks/${id}`)
    console.log('Deleted CrossDocking Task successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete CrossDocking Task', err)
      return false
  }
}
