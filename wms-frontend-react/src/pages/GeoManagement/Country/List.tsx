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
  country_code: string
  country_name: string
  country_code_3: string
  numeric_code: string
  currency_code: string
  phone_code: string
  capital: string
  creation_date:string
  created_by:string
  last_modified_date:string
  last_modified_by:string
  status: number
  [key: string]: any
}

interface Errors {
  country_code?: string
  country_name?: string
  country_code_3?: string
  numeric_code?: string
  currency_id?: string
  phone_code?: string
  capital?: string
  phone_number?: string
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
    selector: (row: RowData) => row.country_code,
    sortable: true,
  },
  {
    name: 'Name',
    selector: (row: RowData) => row.country_name,
    sortable: true,
  },
  {
    name: 'Code 3',
    selector: (row: RowData) => row.country_code_3 || '-',
    sortable: true,
  },
  {
    name: 'Numeric Code',
    selector: (row: RowData) => row.numeric_code || '-',
    sortable: true,
  },
  {
    name: 'Currency Code',
    selector: (row: RowData) => row.currency_code || '-',
    sortable: true,
  },
  {
    name: 'Phone Code',
    selector: (row: RowData) => row.phone_code || '-',
    sortable: true,
  },
  {
    name: 'Capital',
    selector: (row: RowData) => row.capital || '-',
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
  const [countryLists, setCountryLists] = useState<any>([])
  const [currencyLists, setCurrencyLists] = useState<any>([])
  const [country, setCountry] = useState<any>({})
  const [isPageLoading, setIsPageLoading] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const { showToast } = provideUtility()

  const [formData, setFormData] = useState({
    country_code: '',
    country_name: '',
    country_code_3: '',
    numeric_code: '',
    currency_id: '',
    phone_code: '',
    capital: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: 1,
  })

  const [updateFormData, setUpdateFormData] = useState({
    id: '',
    country_code: '',
    country_name: '',
    country_code_3: '',
    numeric_code: '',
    currency_id: '',
    phone_code: '',
    capital: '',
    creation_date: '',
    created_by: '',
    last_modified_date: '',
    last_modified_by: '',
    status: '',
  })

  useEffect(() => {
    setIsPageLoading(true)
    fetchCountryLists()
    fetchCurrencyLists()
  }, [])

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

  const fetchCurrencyLists = async () => {
    try {
      setIsPageLoading(true)
      const res = await http.fetchDataWithToken('currencies')
      console.log(res)
      setCurrencyLists(res.data?.data || [])
    } catch (err) {
      setIsPageLoading(false)
      console.error('Failed to fetch Currency lists:', err)
    } finally {
      setIsPageLoading(false)
    }
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
    setCountry(countryLists?.find((x: any) => x.id === row.id))
    setIsViewOpen(true)
  }

  const handleCreate = () => {
    setErrors({
        country_code: '',
        country_name: '',
        country_code_3: '',
        numeric_code: '',
        currency_id: '',
        phone_code: '',
        capital: '',
        phone_number: '',
    })
    setIsCreateOpen(true)
  }

  const handleEdit = (row: any) => {
    setIsUpdateOpen(true)
    setErrors({
      country_code: '',
      country_name: '',
      country_code_3: '',
      numeric_code: '',
      currency_id: '',
      phone_code: '',
      capital: '',
      phone_number: '',
    })
    const country_data = countryLists.find((x: any) => x.id === row.id)
    if (country_data) {
      setUpdateFormData({
        id: country_data.id,
        country_code: country_data.country_code,
        country_name: country_data.country_name,
        country_code_3: country_data.country_code_3,
        numeric_code: country_data.numeric_code,
        currency_id: country_data.currency_id,
        phone_code: country_data.phone_code,
        capital: country_data.capital,
        creation_date: country_data.creation_date,
        created_by: country_data.created_by,
        last_modified_date: country_data.last_modified_date,
        last_modified_by: country_data.last_modified_by,
        status: country_data.status,
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
      const response = await http.deleteDataWithToken(`/countries/${row.id}`)
      console.log(response)
      if (response.status == true) {
        Swal.fire({
          title: 'Deleted!',
          text: 'Country has been deleted.',
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
      const response = await http.postDataWithToken('/countries', formData)
      if (response.status === true) {
        setIsCreateOpen(false)
        showToast('', 'Create Country successful', 'top-right', 'success')
        setFormData({
          country_code: '',
          country_name: '',
          country_code_3: '',
          numeric_code: '',
          currency_id: '',
          phone_code: '',
          capital: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: 1,
        })
        fetchCountryLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.data.message, 'top-right', 'error')
        const apiErrors: Errors = err?.data.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Create Country failed!', 'top-right', 'error')
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
        `/countries/${updateFormData.id}`,
        updateFormData
      )
      if (response.status === true) {
        setIsUpdateOpen(false)
        showToast('', 'Update Country successful', 'top-right', 'success')
        setUpdateFormData({
          id: '',
          country_code: '',
          country_name: '',
          country_code_3: '',
          numeric_code: '',
          currency_id: '',
          phone_code: '',
          capital: '',
          creation_date: '',
          created_by: '',
          last_modified_date: '',
          last_modified_by: '',
          status: '',
        })
        fetchCountryLists()
      } else {
        showToast('', 'Something went wrong!.', 'top-right', 'error')
      }
    } catch (err: any) {
      if (err?.status === 422) {
        showToast('', err?.message, 'top-right', 'error')
        const apiErrors: Errors = err?.errors
        setErrors(apiErrors)
      } else {
        showToast('', 'Update Country failed!', 'top-right', 'error')
      }
      console.error(err)
    } finally {
      setIsLoading(false)
    }
  }

  const [filterText, setFilterText] = React.useState('')
  const filteredData = useMemo(() => {
    if (!filterText) return countryLists

    return countryLists.filter((item: any) =>
      Object.values(item).some(
        (val) =>
          val && val.toString().toLowerCase().includes(filterText.toLowerCase())
      )
    )
  }, [filterText, countryLists])

  return (
    <div className="space-y-10">
      <div className="flex justify-between items-center">
        <h1 className="text-xl font-semibold">Country Lists</h1>
        <Button variant="primary" size="sm" onClick={handleCreate}>
          Add Country
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-3">
        <div className="rounded-md shadow-lg border border-gray-200 bg-white p-2">
          <div className="rounded-2xl border border-gray-200 bg-white p-5 ">
            <p className="text-gray-500 text-theme-sm dark:text-gray-400">
              Total Countries
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {countryLists.length ?? 0}
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
              Asian Countries
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  4
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
              Active Countries
            </p>
            <div className="flex items-end justify-between mt-3">
              <div className="">
                <h4 className="text-2xl font-bold text-gray-800 dark:text-white/90">
                  {countryLists.filter((x:any) => x.status == 1).length}
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
                placeholder="Search Countries…"
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
              Add New Country
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Country Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.country_code}
                  onChange={handleChange('country_code')}
                  onKeyUp={() => handleRemove('country_code')}
                  error={!!errors.country_code}
                  hint={errors.country_code}
                />
              </div>
              <div>
                <Label>
                  Country Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.country_name}
                  onChange={handleChange('country_name')}
                  onKeyUp={() => handleRemove('country_name')}
                  error={!!errors.country_name}
                  hint={errors.country_name}
                />
              </div>
              <div>
                <Label>
                  Country Code 3<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={formData.country_code_3}
                  onChange={handleChange('country_code_3')}
                  onKeyUp={() => handleRemove('country_code_3')}
                  error={!!errors.country_code_3}
                  hint={errors.country_code_3}
                />
              </div>
              <div>
                <Label>
                  Numeric Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={formData.numeric_code}
                  onChange={handleChange('numeric_code')}
                  onKeyUp={() => handleRemove('numeric_code')}
                  error={!!errors.numeric_code}
                  hint={errors.numeric_code}
                />
              </div>
              <div>
                <Label>
                  Currency Code<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={currencyLists}
                  valueKey="id"
                  value={formData.currency_id}
                  getOptionLabel={(item) => `${item.currency_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency_id')
                    setFormData((prev) => ({
                      ...prev,
                      currency_id: val,
                    }))
                  }}
                  error={!!errors.currency_id}
                  hint={errors.currency_id}
                />
              </div>
              <div>
                <Label>Phone Code</Label>
                <Input
                  type="number"
                  value={formData.phone_code}
                  onChange={handleChange('phone_code')}
                  onKeyUp={() => handleRemove('phone_code')}
                  error={!!errors.phone_code}
                  hint={errors.phone_code}
                />
              </div>

              <div>
                <Label>Capital</Label>
                <Input
                  type="text"
                  value={formData.capital}
                  onChange={handleChange('capital')}
                  onKeyUp={() => handleRemove('capital')}
                  error={!!errors.capital}
                  hint={errors.capital}
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
            <h2 className="text-xl font-semibold text-gray-800">Country</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Country Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={country.country_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Country Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={country.country_name}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Country Code 3<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={country.country_code_3}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Numeric Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={country.numeric_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>
                  Currency Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={country.currency_code}
                  disabled={true}
                />
              </div>
              <div>
                <Label>Phone Code</Label>
                <Input
                  type="number"
                  value={country.phone_code}
                  disabled={true}
                />
              </div>

              <div>
                <Label>Capital</Label>
                <Input type="text" value={country.capital} disabled={true} />
              </div>
              <div>
                <Label>Status</Label>
                <ToggleSwitchInput
                  label="Enable Active"
                  defaultChecked={!!country.status}
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
            <h2 className="text-xl font-semibold text-gray-800">
              Edit Country
            </h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label>
                  Country Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.country_code}
                  onChange={handleChange('country_code')}
                  onKeyUp={() => handleRemove('country_code')}
                  error={!!errors.country_code}
                  hint={errors.country_code}
                />
              </div>
              <div>
                <Label>
                  Country Name<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.country_name}
                  onChange={handleChange('country_name')}
                  onKeyUp={() => handleRemove('country_name')}
                  error={!!errors.country_name}
                  hint={errors.country_name}
                />
              </div>
              <div>
                <Label>
                  Country Code 3<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="text"
                  value={updateFormData.country_code_3}
                  onChange={handleChange('country_code_3')}
                  onKeyUp={() => handleRemove('country_code_3')}
                  error={!!errors.country_code_3}
                  hint={errors.country_code_3}
                />
              </div>
              <div>
                <Label>
                  Numeric Code<span className="text-error-500">*</span>
                </Label>
                <Input
                  type="number"
                  value={updateFormData.numeric_code}
                  onChange={handleChange('numeric_code')}
                  onKeyUp={() => handleRemove('numeric_code')}
                  error={!!errors.numeric_code}
                  hint={errors.numeric_code}
                />
              </div>
              <div>
                <Label>
                  Currency Code<span className="text-error-500">*</span>
                </Label>
                <SingleSelectInput
                  options={currencyLists}
                  valueKey="id"
                  value={updateFormData.currency_id}
                  getOptionLabel={(item) => `${item.currency_code}`}
                  onSingleSelectChange={(val) => {
                    handleRemove('currency_id')
                    setUpdateFormData((prev) => ({
                      ...prev,
                      currency_id: val,
                    }))
                  }}
                  error={!!errors.currency_id}
                  hint={errors.currency_id}
                />
              </div>
              <div>
                <Label>Phone Code</Label>
                <Input
                  type="number"
                  value={updateFormData.phone_code}
                  onChange={handleChange('phone_code')}
                  onKeyUp={() => handleRemove('phone_code')}
                  error={!!errors.phone_code}
                  hint={errors.phone_code}
                />
              </div>

              <div>
                <Label>Capital</Label>
                <Input
                  type="text"
                  value={updateFormData.capital}
                  onChange={handleChange('capital')}
                  onKeyUp={() => handleRemove('capital')}
                  error={!!errors.capital}
                  hint={errors.capital}
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
