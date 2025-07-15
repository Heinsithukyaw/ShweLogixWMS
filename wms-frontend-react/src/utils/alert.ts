import Swal from 'sweetalert2'

export const showSuccess = (title: string, text?: string) => {
  Swal.fire({
    icon: 'success',
    title,
    text,
    // confirmButtonColor: '#22c55e', 
    showConfirmButton: false,
    timer: 1500,
  })
}

export const showError = (title: string, text?: string) => {
  Swal.fire({
    icon: 'error',
    title,
    text,
    confirmButtonColor: '#ef4444', // red
  })
}

export const showConfirm = async (
  title: string,
  text?: string
): Promise<boolean> => {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes',
  })
  return result.isConfirmed
}
