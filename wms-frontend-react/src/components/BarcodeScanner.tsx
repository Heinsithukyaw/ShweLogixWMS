import React, { useState, useRef, useEffect } from 'react'
import { BrowserMultiFormatReader, IScannerControls } from '@zxing/browser'
// import Spinner from '../components/ui/loading/spinner'

const BarcodeScanner: React.FC = () => {
  const [showCamera, setShowCamera] = useState(false)
  const [result, setResult] = useState('')
  const [error, setError] = useState('')
  const [scanError, setScanError] = useState('')
  const videoRef = useRef<HTMLVideoElement>(null)
  const controlsRef = useRef<IScannerControls | null>(null)
  const timeoutRef = useRef<NodeJS.Timeout | null>(null)

  const startScanTimeout = () => {
    // Set a 10-second timeout to show error if no barcode is found
    timeoutRef.current = setTimeout(() => {
      setError('No barcode detected within 30 seconds.')
      setScanError('')
      setShowCamera(false)
    }, 30000)
  }

  useEffect(() => {
    setScanError('')
    if (!showCamera) return

    const reader = new BrowserMultiFormatReader()
    console.log('reader is work')
    reader
      .decodeFromVideoDevice(
        undefined,
        videoRef.current!,
        (res, err, controls) => {
          if (res) {
            setResult(res.getText())
            console.log('Camera Scanned:', res.getText())

            setError('')

            clearTimeout(timeoutRef.current!)
            controls.stop()
            setShowCamera(false)
          } else if (err) {
            console.error('Scan error:', err.message)
            setScanError('Barcode not found!')
          }
        }
      )
      .then((controls) => {
        controlsRef.current = controls
        startScanTimeout() 
      })
      .catch((err) => {
        setError('Error accessing camera.')
        console.error(err)
      })

    return () => {
      clearTimeout(timeoutRef.current!)
      controlsRef.current?.stop()
    }
  }, [showCamera])

  return (
    <div>
      {scanError && (
        <>
          <p style={{ color: 'red' }}>{scanError}</p>
        </>
      )}

      <h3>Scanned Barcode Result: {result}</h3>

      {error && <p style={{ color: 'red' }}>{error}</p>}

      {!showCamera && (
        <button
          onClick={() => {
            setError('')
            setShowCamera(true)
          }}
          className="border border-gray-600 rounded-lg p-2"
        >
          Scan with Camera
        </button>
      )}

      {showCamera && <video ref={videoRef} style={{ width: '100%' }} />}
    </div>
  )
}

export default BarcodeScanner
