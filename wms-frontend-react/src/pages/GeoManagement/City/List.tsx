import React, { useState, useEffect, useMemo } from 'react'
import http from '../../../lib/http'
import provideUtility from '../../../utils/toast'
import Spinner from '../../../components/ui/loading/spinner'
import AdvancedDataTable from '../../../components/ui/dataTable'
import Button from '../../../components/ui/button/Button'
import BaseModal from '../../../components/ui/modal'
import Label from '../../../components/form/Label'
import Input from '../../../components/form/input/InputField'
import SingleSelectInput from '../../../components/form/form-elements/SelectInputs'
import ToggleSwitchInput from '../../../components/form/form-elements/ToggleSwitch'
import { TableColumn } from 'react-data-table-component'
import { showConfirm } from '../../../utils/alert'
import Swal from 'sweetalert2'

interface RowData {
  city_code: string
  city_name: string
  country_code: string
  state_code: string
  postal_code: string
  latitude: string
  longitude: string
  creation_date: string
  created_by: string
  last_modified_date: string
  last_modified_by: string
  status: number
  [key: string]: any
}

interface Errors {
  city_code?: string
  city_name?: string
  country_id?: string
  state_id?: string
}

const columns: TableColumn<RowData>[] = [
  {
    name: 'No.',
    selector: (_row: any, index: any) => index + 1,
    width: '70px',
    sortable: false,
  },
  {
    name: 'Code',
    selector: (row: RowData) => row.city_code,
    sortable: true,
  },
  {
    name: 'Country Code',
    selector: (row: RowData) => row.country_name,
    sortable: true,
  },
  {
    name: 'State Code',
    selector: (row: RowData) => row.state_code || '-',
    sortable: true,
  },
  {
    name: 'Postal Code',
    selector: (row: RowData) => row.postal_code || '-',
    sortable: true,
  },
  {
    name: 'Latitude',
    selector: (row: RowData) => row.latitude || '-',
    sortable: true,
  },
  {
    name: 'Longtitude',
    selector: (row: RowData) => row.longitude || '-',
    sortable: true,
  },
  {
    name: 'Creation Date',
    selector: (row: RowData) => row.creation_date || '-',
    sortable: true,
  },
  {
    name: 'Created By',
    selector: (row: RowData) => row.created_by || '-',
    sortable: true,
  },
  {
    name: 'Last Modified Date',
    selector: (row: RowData) => row.last_modified_date || '-',
    sortable: true,
  },
  {
    name: 'Last Modified By',
    selector: (row: RowData) => row.last_modified_by || '-',
    sortable: true,
  },
  {
    name: 'Status',
    cell: (row: RowData) => (
      <span
        className={`px-2 py-1 text-xs font-semibold rounded-full ${
          row.status === 1
            ? 'bg-green-100 text-green-700'
            : 'bg-red-100 text-red-700'
        }`}
      >
        {row.status === 1 ? 'Active' : 'Inactive'}
      </span>
    ),
    sortable: true,
  },
]

const List: React.FC = () => {
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isViewOpen, setIsViewOpen] = useState(false)
  const [isUpdateOpen, setIsUpdateOpen] = useState(false)
  const [errors, setErrors] = useState<Errors>({})
  const [cityLists, setCityLists] = useState<any>([])
  const [allStateLists, setAllStateLists] = useState<any>([])
  const [stateLists, setStateLists] = useState<any>([])
  const [countryLists, setCountryLists] = useState<any>([])
  const [city, setCity] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const [formData, setFormData] = useState({
    city_code: '',
    city_name: '',
    country_id: '',
    state_id: '',
    postal_code: '',
    latitude: '',
    longitude: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    city_code: '',
    city_name: '',
    country_id: '',
    state_id: '',
    postal_code: '',
    latitude: '',
    longitude: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchCityLists()
    fetchStateLists()
    fetchCountryLists()
  }, [])

  const fetchCityLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('cities')
      console.log(res)
      setCityLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch City lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchStateLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('states')
      console.log(res)
      setAllStateLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch State lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const fetchCountryLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('countries')
      console.log(res)
      setCountryLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Country lists:', err)
    } finally {
      setIsPageLoading(false)
    }
  }

  const handleGetState = (val: any) => {
    const states = allStateLists.filter((x: any) => x.country_id == val)
    setStateLists(states)
  }

  const handleRemove = (field: string) => {
    setErrors((prev) => ({
      ...prev,
      [field]: null,
    }))
  }

  const handleToggle = (checked: boolean) => {
    const is_active = checked ? 1 : 0
    if (isCreateOpen) {
      setFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    } else {
      setUpdateFormData((prev: any) => ({
        ...prev,
        status: is_active,
      }))
    }
  }

  const handleCloseModal = () => {
    setIsCreateOpen(false)
    setIsViewOpen(false)
    setIsUpdateOpen(false)
  }

  const handleView = (row: any) => {
    setCity(cityLists?.find((x: any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
      city_code: '',
      city_name: '',
      country_id: '',
      state_id: '',
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      city_code: '',
      city_name: '',
      country_id: '',
      state_id: '',
    })
    const city_data = cityLists.find((x: any) => x.id === row.id)
    if (city_data) {
      setUpdateFormData({
        id: city_data.id,
        city_code: city_data.city_code,
        city_name: city_data.city_name,
        country_id: city_data.country_id,
        state_id: city_data.state_id,
        postal_code: city_data.postal_code,
        latitude: city_data.latitude,
        longitude: city_data.longitude,
        creation_date: city_data.creation_date,
        created_by: city_data.created_by,
        last_modified_date: city_data.last_modified_date,
        last_modified_by: city_data.last_modified_by,
        status: city_data.status,
      })
    }
  }

  const handleDelete = async (row: any) => {
    const confirmed = await showConfirm(
      'Are you sure?',
      'This action cannot be undone.'
    )

    if (!confirmed) return

    try {
      const response = await http.deleteDataWithToken(`/cities/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'City has been deleted.',
          icon: 'success',
        })
        fetchCountryLists()
      } else {
        Swal.fire({
          title: 'Error!',
          text: 'Failed to delete item.',
          icon: 'error',
        })
      }
    } catch (error: any) {
      Swal.fire({
        title: 'Error!',
        text: error.message || 'Failed to delete item.',
        icon: 'error',
      })
    }
  }

  const handleChange =
    (field: string) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
      const value = e.target.value
      if (isCreateOpen) {
        setFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
      } else {
        setUpdateFormData((prev) => ({
          ...prev,
          [field]: value,
        }))
      }
    }

  const handleSubmit = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.postDataWithToken('/cities', formData)
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create City successful', 'top-right', 'success')
        setFormData({
          city_code: '',
          city_name: '',
          country_id: '',
          state_id: '',
          postal_code: '',
          latitude: '',
          longitude: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: 1,
        })
        fetchCityLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create City failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const handleUpdate = async () => {
    setIsLoading(true)
    setErrors({})
    try {
      const response = await http.putDataWithToken(
        `/cities/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update City successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          city_code: '',
          city_name: '',
          country_id: '',
          state_id: '',
          postal_code: '',
          latitude: '',
          longitude: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: '',
        })
        fetchCityLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update City failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return cityLists

    return cityLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, cityLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">City Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add City
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Cities
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {cityLists.length ?? 0}
                </h4>
              </div>
              <div className="flex items-center gap-1">
                <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-success-600">
                  <span className="text-md">Active</span>
                </span>
                {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
              </div>
            </div>
          </div>
        </div>

        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Yangon States
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  0
                </h4>
              </div>
              <div className="flex items-center gap-1">
                <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-success-600">
                  <span className="text-xs">
                    <button className="rounded-2xl bg-blue-700 text-white p-2">
                      View All
                    </button>
                  </span>
                </span>
                {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
              </div>
            </div>
          </div>
        </div>

        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Mandalay State
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  0
                </h4>
              </div>
              <div className="flex items-center gap-1">
                <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-yellow-600">
                  <span className="text-md">Alert</span>
                </span>
                {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
              </div>
            </div>
          </div>
        </div>

        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Other States
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  0
                </h4>
              </div>
              <div className="flex items-center gap-1">
                <span className="inline-flex items-center px-2.5 py-0.5 justify-center gap-1 rounded-full font-medium text-sm bg-success-50 text-yellow-600">
                  <span className="text-md">Alert</span>
                </span>
                {/* <span className="text-gray-500 text-theme-xs ">
                    Vs last month
                  </span> */}
              </div>
            </div>
          </div>
        </div>
      </div>

      {isPageLoading ? (
        <div className="flex justify-center items-center space-x-2">
          <div className="w-5 h-5 border-2 border-t-blue-500 border-blue-200 rounded-full animate-spin"></div>
          <span className="text-sm text-gray-500">Loading...</span>
        </div>
      ) : (
        <AdvancedDataTable
          data={filteredData || []}
          columns={columns}
          onView={handleView}
          onEdit={handleEdit}
          onDelete={handleDelete}
          subHeader
          subHeaderComponent={
            <div className="w-full flex items-center justify-between px-0 py-2 bg-muted">
              <Input
                type="text"
                placeholder="Search Cities…"
                value={filterText}
                onChange={(e) => setFilterText(e.target.value)}
                className="w-full max-w-sm"
              />
            </div>
          }
        />
      )}

      {isCreateOpen && (
        <BaseModal
          isOpen={isCreateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Add New City
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  City Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.city_code}
                  onChange={handleChange('city_code')}
                  onKeyUp={() => handleRemove('city_code')}
                  error={!!errors.city_code}
                  hint={errors.city_code}
                />
              </div>
              <div>
                <Label>
                  City Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.city_name}
                  onChange={handleChange('city_name')}
                  onKeyUp={() => handleRemove('city_name')}
                  error={!!errors.city_name}
                  hint={errors.city_name}
                />
              </div>
              <div>
                <Label>
                  Country<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={countryLists}
                  valueKey="id"
                  value={formData.country_id}
                  getOptionLabel={(item) => `${item.country_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('country_id')
                    handleGetState(val)
                    setFormData((prev) => ({
                      ...prev,
                      country_id: val,
                    }))
                  }}
                  error={!!errors.country_id}
                  hint={errors.country_id}
                />
              </div>
              <div>
                <Label>
                  State<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={stateLists}
                  valueKey="id"
                  value={formData.state_id}
                  getOptionLabel={(item) => `${item.state_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('state_id')
                    setFormData((prev) => ({
                      ...prev,
                      state_id: val,
                    }))
                  }}
                  error={!!errors.state_id}
                  hint={errors.state_id}
                />
              </div>
              <div>
                <Label>Postal Code</Label>
                <Input
                  type="number"
                  value={formData.postal_code}
                  onChange={handleChange('postal_code')}
                  onKeyUp={() => handleRemove('postal_code')}
                />
              </div>
              <div>
                <Label>Latitude</Label>
                <Input
                  type="text"
                  value={formData.latitude}
                  onChange={handleChange('latitude')}
                  onKeyUp={() => handleRemove('latitude')}
                />
              </div>
              <div>
                <Label>Longitude</Label>
                <Input
                  type="text"
                  value={formData.longitude}
                  onChange={handleChange('longitude')}
                  onKeyUp={() => handleRemove('longitude')}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!formData.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleSubmit}
              >
                Confirm
              </Button>
            </div>
          </div>
        </BaseModal>
      )}

      {isViewOpen && (
        <BaseModal
          isOpen={isViewOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">
              Add New City
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  City Code<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={city.city_code} disabled={true} />
              </div>
              <div>
                <Label>
                  City Name<span className="text-error-500">*</span>
                </Label>
                <Input type="text" value={city.city_name} disabled={true} />
              </div>
              <div>
                <Label>
                  Country<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={city.country_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  State<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={city.state_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Postal Code</Label>
                <Input
                  type="number"
                  value={city.postal_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Latitude</Label>
                <Input type="text" value={city.latitude} disabled={true} />
              </div>
              <div>
                <Label>Longitude</Label>
                <Input type="text" value={city.longitude} disabled={true} />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!city.status}
                  onToggleChange={handleToggle}
                  disabled={true}
                />
              </div>
            </div>
          </div>
        </BaseModal>
      )}

      {isUpdateOpen && (
        <BaseModal
          isOpen={isUpdateOpen}
          onClose={handleCloseModal}
          isFullscreen={false}
        >
          <div className="space-y-6">
            <h2 className="text-xl font-semibold text-gray-800">Edit City</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  City Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.city_code}
                  onChange={handleChange('city_code')}
                  onKeyUp={() => handleRemove('city_code')}
                  error={!!errors.city_code}
                  hint={errors.city_code}
                />
              </div>
              <div>
                <Label>
                  City Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.city_name}
                  onChange={handleChange('city_name')}
                  onKeyUp={() => handleRemove('city_name')}
                  error={!!errors.city_name}
                  hint={errors.city_name}
                />
              </div>
              <div>
                <Label>
                  Country<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={countryLists}
                  valueKey="id"
                  value={updateFormData.country_id}
                  getOptionLabel={(item) => `${item.country_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('country_id')
                    handleGetState(val)
                    setUpdateFormData((prev) => ({
                      ...prev,
                      country_id: val,
                    }))
                  }}
                  error={!!errors.country_id}
                  hint={errors.country_id}
                />
              </div>
              <div>
                <Label>
                  State<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={stateLists}
                  valueKey="id"
                  value={updateFormData.state_id}
                  getOptionLabel={(item) => `${item.state_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('state_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      state_id: val,
                    }))
                  }}
                  error={!!errors.state_id}
                  hint={errors.state_id}
                />
              </div>
              <div>
                <Label>Postal Code</Label>
                <Input
                  type="number"
                  value={updateFormData.postal_code}
                  onChange={handleChange('postal_code')}
                  onKeyUp={() => handleRemove('postal_code')}
                />
              </div>
              <div>
                <Label>Latitude</Label>
                <Input
                  type="text"
                  value={updateFormData.latitude}
                  onChange={handleChange('latitude')}
                  onKeyUp={() => handleRemove('latitude')}
                />
              </div>
              <div>
                <Label>Longitude</Label>
                <Input
                  type="text"
                  value={updateFormData.longitude}
                  onChange={handleChange('longitude')}
                  onKeyUp={() => handleRemove('longitude')}
                />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!updateFormData.status}
                  onToggleChange={handleToggle}
                />
              </div>
            </div>
            <div className="flex justify-end gap-2">
              <Button variant="secondary" onClick={handleCloseModal}>
                Cancel
              </Button>
              <Button
                variant="primary"
                startIcon={isLoading && <Spinner size={4} />}
                onClick={handleUpdate}
              >
                Update
              </Button>
            </div>
          </div>
        </BaseModal>
      )}
    </div>
  )
}

export default List
