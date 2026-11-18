import React from 'react'
import { createRoot } from 'react-dom/client'
import CustomerServicePage from '../../../../miniprogram/src/plugins/customer-service'
createRoot(document.getElementById('app')!).render(<CustomerServicePage />)
