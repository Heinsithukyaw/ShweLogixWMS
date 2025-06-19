const Spinner = ({ size = 5 }: { size?: number }) => (
  <div
    className={`h-${size} w-${size} animate-spin rounded-full border-2 border-white border-t-transparent`}
  />
)

export default Spinner
