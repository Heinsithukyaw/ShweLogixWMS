import { useEffect } from 'react'

export const useScannerTrigger = (callback: () => void) => {
  let buffer = ''
  let lastTime = Date.now()

  useEffect(() => {
    const onKeyPress = (e: KeyboardEvent) => {
      const now = Date.now()
      if (now - lastTime > 50) buffer = '' // reset on delay
      lastTime = now

      buffer += e.key
      if (buffer.length > 5 && e.key === 'Enter') {
        callback() // barcode scanned
        buffer = ''
      }
    }

    window.addEventListener('keypress', onKeyPress)
    return () => window.removeEventListener('keypress', onKeyPress)
  }, [callback])
}
