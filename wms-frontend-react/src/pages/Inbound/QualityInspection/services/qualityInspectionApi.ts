import http from '../../../../lib/http'
import { AppDispatch } from '../../../../store/store'
import {
  setContentStart,
  setContentSuccess,
  setContentFailure,
  deleteContentData
} from '../../../../store/features/inbound/QualityInspectionSlice'
import { QualityInspectionType } from '../../../../type/inbound/qualityInspectionType'

export const fetchQualityInspectionLists = () => async (dispatch: AppDispatch) => {
    try {
        dispatch(setContentStart())
        const res = await http.fetchDataWithToken(
          'quality-inspections'
        )
        console.log('Quality Inspection lists')
        console.log(res)
        dispatch(setContentSuccess(res.data?.data || []))
    } catch (err:any) {
        console.error('Failed to fetch Quality Inspection lists', err)
        dispatch(setContentFailure(err.message || 'Unknown error'))
    } 
}

export const createQualityInspection =
  (data: QualityInspectionType) => async (dispatch: AppDispatch) => {
    try {
      await http.postDataWithToken('quality-inspections', data)
      console.log('Create Quality Inspection successfully!')
      dispatch(fetchQualityInspectionLists())
      return { status: false, error: [] }
    } catch (err: any) {
      console.error('Failed to create Quality Inspection', err)
      if (err?.data.status == 422) {
        return { status: false, error: err?.data }
      }
    }
  }

export const updateQualityInspection =
  (data: QualityInspectionType, id: any) => async (dispatch: AppDispatch) => {
    try {
      await http.postDataWithToken(`quality-inspections/${id}`, data)
      console.log('Update Quality Inspection successfully!')
      dispatch(fetchQualityInspectionLists())
      return { status: true, error: [] }
    } catch (err: any) {
      console.error('Failed to update Quality Inspection', err)
      if (err?.status == 422) {
        console.log(err)
        return { status: false, error: err }
      }
      return { status: false, error: err }
    }
  }

export const deleteQualityInspection = (id: any) => async (dispatch: AppDispatch) => {
  try {
    await http.deleteDataWithToken(`quality-inspections/${id}`)
    console.log('Deleted Quality Inspection successfully!')
    dispatch(deleteContentData(id))
    return true
  } catch (err: any) {
    console.error('Failed to delete Quality Inspection', err)
    return false
  }
}
